<?php
declare(strict_types=1);

namespace ApexGuild\Domain;

use ApexGuild\Core\Database;
use RuntimeException;
use InvalidArgumentException;

/**
 * Deterministic Finite State Machine driving multi-tier status transitions,
 * dynamic step evaluation, condition routing, and cryptographic audit hashing.
 */
class StateMachine
{
    public const STATUS_DRAFT      = 'Draft';
    public const STATUS_IN_REVIEW  = 'In_Review';
    public const STATUS_APPROVED   = 'Approved';
    public const STATUS_REJECTED   = 'Rejected';
    public const STATUS_CANCELLED  = 'Cancelled';

    public const ACTION_SUBMIT  = 'SUBMIT';
    public const ACTION_APPROVE = 'APPROVE';
    public const ACTION_REJECT  = 'REJECT';
    public const ACTION_CANCEL  = 'CANCEL';

    private Database $db;
    private array $config;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
        $this->config = $this->db->getConfig();
    }

    /**
     * Check if a given user can perform an action on a request
     *
     * @return array{allowed: bool, reason: ?string}
     */
    public function canPerformAction(array $request, array $workflow, array $user, string $action): array
    {
        $status = $request['status'];
        $userRole = $user['role'];
        $userId = (int)$user['id'];
        $requesterId = (int)$request['user_id'];
        $assignedRole = $request['assigned_role'];

        // Terminal statuses cannot transition further
        if (in_array($status, [self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_CANCELLED], true)) {
            return ['allowed' => false, 'reason' => "Request has already reached terminal status [{$status}]."];
        }

        switch ($action) {
            case self::ACTION_SUBMIT:
                if ($status !== self::STATUS_DRAFT) {
                    return ['allowed' => false, 'reason' => 'Only draft requests can be submitted.'];
                }
                if ($userId !== $requesterId && $userRole !== 'Admin') {
                    return ['allowed' => false, 'reason' => 'Only the requisition owner or an Administrator may submit this draft.'];
                }
                return ['allowed' => true, 'reason' => null];

            case self::ACTION_APPROVE:
            case self::ACTION_REJECT:
                if ($status !== self::STATUS_IN_REVIEW) {
                    return ['allowed' => false, 'reason' => 'Only requests currently [In_Review] can be approved or rejected.'];
                }

                // Admins have executive override capability
                if ($userRole === 'Admin') {
                    return ['allowed' => true, 'reason' => null];
                }

                // Check assigned role match
                if ($userRole !== $assignedRole) {
                    return [
                        'allowed' => false,
                        'reason' => "Access Denied: Current workflow tier is assigned to [{$assignedRole}]. Your active role is [{$userRole}]."
                    ];
                }

                // Specific CFO Rule: Check if financial threshold is strictly met
                if ($assignedRole === 'CFO' && $userRole === 'CFO') {
                    $formData = json_decode($request['data_json'], true) ?: [];
                    $amount = (float)($formData['amount'] ?? 0);
                    $threshold = (float)($this->config['app']['cfo_threshold'] ?? 5000.00);
                    if ($amount > 0 && $amount <= $threshold) {
                        return [
                            'allowed' => false,
                            'reason' => "Requisition value (\${$amount}) does not exceed the CFO threshold (\${$threshold}). Standard manager tier applies."
                        ];
                    }
                }

                return ['allowed' => true, 'reason' => null];

            case self::ACTION_CANCEL:
                if (!in_array($status, [self::STATUS_DRAFT, self::STATUS_IN_REVIEW], true)) {
                    return ['allowed' => false, 'reason' => 'Only draft or in-review requests may be cancelled.'];
                }
                if ($userId !== $requesterId && $userRole !== 'Admin') {
                    return ['allowed' => false, 'reason' => 'Only the requisition owner or an Administrator may cancel this request.'];
                }
                return ['allowed' => true, 'reason' => null];

            default:
                return ['allowed' => false, 'reason' => "Unknown state machine action [{$action}]."];
        }
    }

    /**
     * Perform State Transition
     */
    public function transition(
        int $requestId,
        array $user,
        string $action,
        string $comment = '',
        ?string $signatureCanvas = null,
        ?string $ipAddress = null
    ): array {
        return $this->db->transaction(function (Database $db) use ($requestId, $user, $action, $comment, $signatureCanvas, $ipAddress) {
            // Fetch request and workflow
            $request = $db->fetch("SELECT * FROM requests WHERE id = ?", [$requestId]);
            if (!$request) {
                throw new InvalidArgumentException("Requisition #{$requestId} not found.");
            }

            $workflow = $db->fetch("SELECT * FROM workflows WHERE id = ?", [(int)$request['workflow_id']]);
            if (!$workflow) {
                throw new InvalidArgumentException("Associated workflow not found.");
            }

            // Verify permission
            $check = $this->canPerformAction($request, $workflow, $user, $action);
            if (!$check['allowed']) {
                throw new RuntimeException($check['reason'] ?? 'Transition unauthorized.');
            }

            $formData = json_decode($request['data_json'], true) ?: [];
            $steps = json_decode($workflow['steps_json'], true) ?: [];
            $currentStepNum = (int)$request['current_step'];
            $fromStatus = $request['status'];
            $timestamp = date('Y-m-d H:i:s');
            $ip = $ipAddress ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

            $toStatus = $fromStatus;
            $newStepNum = $currentStepNum;
            $newStepName = $request['current_step_name'];
            $newAssignedRole = $request['assigned_role'];
            $completedAt = null;
            $submittedAt = $request['submitted_at'];

            switch ($action) {
                case self::ACTION_SUBMIT:
                    $toStatus = self::STATUS_IN_REVIEW;
                    $submittedAt = $timestamp;
                    
                    // Find first applicable step
                    $firstStep = $this->findNextApplicableStep($steps, 0, $formData);
                    if ($firstStep) {
                        $newStepNum = (int)$firstStep['step_number'];
                        $newStepName = $firstStep['step_name'];
                        $newAssignedRole = $firstStep['approver_role'];
                    } else {
                        // Auto-approved if no steps
                        $toStatus = self::STATUS_APPROVED;
                        $newStepName = 'Completed';
                        $newAssignedRole = null;
                        $completedAt = $timestamp;
                    }
                    break;

                case self::ACTION_APPROVE:
                    // Find next applicable step after currentStepNum
                    $nextStep = $this->findNextApplicableStep($steps, $currentStepNum, $formData);
                    if ($nextStep) {
                        $toStatus = self::STATUS_IN_REVIEW;
                        $newStepNum = (int)$nextStep['step_number'];
                        $newStepName = $nextStep['step_name'];
                        $newAssignedRole = $nextStep['approver_role'];
                    } else {
                        // All steps cleared -> Fully Approved!
                        $toStatus = self::STATUS_APPROVED;
                        $newStepName = 'Completed';
                        $newAssignedRole = null;
                        $completedAt = $timestamp;
                    }
                    break;

                case self::ACTION_REJECT:
                    if (trim($comment) === '') {
                        throw new InvalidArgumentException("A documented audit justification is mandatory when rejecting a requisition.");
                    }
                    $toStatus = self::STATUS_REJECTED;
                    $newStepName = "Rejected at Step {$currentStepNum} ({$request['current_step_name']})";
                    $newAssignedRole = null;
                    $completedAt = $timestamp;
                    break;

                case self::ACTION_CANCEL:
                    $toStatus = self::STATUS_CANCELLED;
                    $newStepName = 'Cancelled by User';
                    $newAssignedRole = null;
                    $completedAt = $timestamp;
                    break;
            }

            // Generate Cryptographic Digital Signature
            $sigHash = $this->generateDigitalSignature(
                $requestId,
                (int)$user['id'],
                $action,
                $comment,
                $timestamp
            );

            // Update Request
            $db->execute("
                UPDATE requests SET
                    status = ?,
                    current_step = ?,
                    current_step_name = ?,
                    assigned_role = ?,
                    submitted_at = COALESCE(submitted_at, ?),
                    completed_at = ?,
                    updated_at = ?
                WHERE id = ?
            ", [
                $toStatus,
                $newStepNum,
                $newStepName,
                $newAssignedRole,
                $submittedAt,
                $completedAt,
                $timestamp,
                $requestId
            ]);

            // Insert Immutable Audit Log Record
            $db->execute("
                INSERT INTO audit_logs (
                    request_id, user_id, action, step_name, from_status, to_status,
                    comment, signature_hash, ip_address, timestamp
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ", [
                $requestId,
                (int)$user['id'],
                $action,
                $request['current_step_name'],
                $fromStatus,
                $toStatus,
                $comment,
                $sigHash,
                $ip,
                $timestamp
            ]);

            return [
                'success'        => true,
                'request_id'     => $requestId,
                'from_status'    => $fromStatus,
                'to_status'      => $toStatus,
                'current_step'   => $newStepNum,
                'assigned_role'  => $newAssignedRole,
                'signature_hash' => $sigHash,
                'timestamp'      => $timestamp
            ];
        });
    }

    /**
     * Evaluates steps sequentially and returns the first matching step whose condition passes
     */
    public function findNextApplicableStep(array $steps, int $afterStepNumber, array $formData): ?array
    {
        // Sort steps by step_number ascending
        usort($steps, fn($a, $b) => ((int)$a['step_number']) <=> ((int)$b['step_number']));

        foreach ($steps as $step) {
            $num = (int)$step['step_number'];
            if ($num <= $afterStepNumber) {
                continue;
            }

            if ($this->evaluateStepCondition($step, $formData)) {
                return $step;
            }
        }

        return null;
    }

    /**
     * Dynamic condition evaluator
     */
    public function evaluateStepCondition(array $step, array $formData): bool
    {
        $condition = $step['condition'] ?? 'always';

        if ($condition === 'always' || $condition === '' || $condition === null) {
            return true;
        }

        // Amount > $5,000 condition (CFO Tier Rule)
        if ($condition === 'amount_gt_5000') {
            $amount = (float)($formData['amount'] ?? 0);
            $threshold = (float)($this->config['app']['cfo_threshold'] ?? 5000.00);
            return $amount > $threshold;
        }

        // Amount <= $5,000 condition
        if ($condition === 'amount_lte_5000') {
            $amount = (float)($formData['amount'] ?? 0);
            $threshold = (float)($this->config['app']['cfo_threshold'] ?? 5000.00);
            return $amount <= $threshold;
        }

        return true;
    }

    /**
     * Cryptographic digital signature hash calculation
     */
    public function generateDigitalSignature(int $requestId, int $userId, string $action, string $comment, string $timestamp): string
    {
        $secret = $this->config['app']['secret_key'] ?? 'apexguild-default-secret';
        $fingerprint = "ID:{$requestId}|U:{$userId}|ACT:{$action}|TS:{$timestamp}|NOTE:" . hash('sha256', $comment);
        return hash_hmac('sha256', $fingerprint, $secret);
    }

    /**
     * Verify authenticity of an audit digital signature
     */
    public function verifyDigitalSignature(int $requestId, int $userId, string $action, string $comment, string $timestamp, string $storedHash): bool
    {
        $expected = $this->generateDigitalSignature($requestId, $userId, $action, $comment, $timestamp);
        return hash_equals($expected, $storedHash);
    }
}
