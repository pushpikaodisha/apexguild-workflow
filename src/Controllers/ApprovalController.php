<?php
declare(strict_types=1);

namespace ApexGuild\Controllers;

use ApexGuild\Core\Database;
use ApexGuild\Core\Session;
use ApexGuild\Core\Router;
use ApexGuild\Domain\StateMachine;

/**
 * Enterprise Approval Workflow & Cryptographic Sign-Off Controller
 */
class ApprovalController
{
    private Database $db;
    private StateMachine $stateMachine;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->stateMachine = new StateMachine($this->db);
    }

    /**
     * Pending Approval Inbox for Active Logged-in Role
     */
    public function inbox(): void
    {
        $role = Session::role();
        $userId = Session::id();

        if ($role === 'Requester') {
            Session::flash('info', 'Requesters do not hold approval authority. Viewing your submitted requisitions.');
            Router::redirect('/requests/my');
            return;
        }

        // Fetch requests needing approval
        if ($role === 'Admin') {
            // Admins can see all in-review items
            $requests = $this->db->fetchAll("
                SELECT r.*, w.title as workflow_title, w.icon as workflow_icon, w.category as workflow_category,
                       w.steps_json, u.name as requester_name, u.email as requester_email, u.department as requester_dept
                FROM requests r
                JOIN workflows w ON r.workflow_id = w.id
                JOIN users u ON r.user_id = u.id
                WHERE r.status = 'In_Review'
                ORDER BY CASE r.priority WHEN 'Urgent' THEN 1 WHEN 'High' THEN 2 WHEN 'Medium' THEN 3 ELSE 4 END, r.submitted_at ASC
            ");
        } else {
            // Role-targeted inbox (e.g. Manager or CFO)
            $requests = $this->db->fetchAll("
                SELECT r.*, w.title as workflow_title, w.icon as workflow_icon, w.category as workflow_category,
                       w.steps_json, u.name as requester_name, u.email as requester_email, u.department as requester_dept
                FROM requests r
                JOIN workflows w ON r.workflow_id = w.id
                JOIN users u ON r.user_id = u.id
                WHERE r.status = 'In_Review' AND r.assigned_role = ?
                ORDER BY CASE r.priority WHEN 'Urgent' THEN 1 WHEN 'High' THEN 2 WHEN 'Medium' THEN 3 ELSE 4 END, r.submitted_at ASC
            ", [$role]);
        }

        // Compute SLA urgency & decoded fields
        $urgentCount = 0;
        $totalAmount = 0.0;

        foreach ($requests as &$req) {
            $data = json_decode($req['data_json'], true) ?: [];
            $req['form_data'] = $data;

            if (isset($data['amount'])) {
                $totalAmount += (float)$data['amount'];
            }

            if ($req['priority'] === 'Urgent' || $req['priority'] === 'High') {
                $urgentCount++;
            }

            // Calculate SLA hours remaining
            $steps = json_decode($req['steps_json'], true) ?: [];
            $slaHours = 24; // default
            foreach ($steps as $s) {
                if ((int)$s['step_number'] === (int)$req['current_step']) {
                    $slaHours = (int)($s['sla_hours'] ?? 24);
                    break;
                }
            }

            $submittedTs = strtotime($req['submitted_at'] ?? $req['created_at']);
            $deadlineTs = $submittedTs + ($slaHours * 3600);
            $secondsLeft = $deadlineTs - time();

            $req['sla_hours_total'] = $slaHours;
            $req['sla_seconds_left'] = $secondsLeft;
            $req['is_sla_breached'] = $secondsLeft < 0;
            $req['sla_display'] = $secondsLeft > 0
                ? sprintf('%dh %02dm remaining', floor($secondsLeft / 3600), floor(($secondsLeft % 3600) / 60))
                : sprintf('Overdue by %dh', abs(floor($secondsLeft / 3600)));
        }
        unset($req);

        Router::render('approvals/inbox', [
            'title'        => 'Executive Approval Inbox - ApexGuild Enterprise',
            'requests'     => $requests,
            'role'         => $role,
            'urgentCount'  => $urgentCount,
            'totalAmount'  => $totalAmount,
            'csrfToken'    => Session::csrfToken(),
            'flashes'      => Session::getFlashes()
        ]);
    }

    /**
     * Process Approval or Rejection Decision with Digital Signature
     */
    public function process(): void
    {
        $csrf = $_POST['_csrf'] ?? '';
        if (!Session::validateCsrf($csrf)) {
            if ($this->isAjax()) {
                Router::json(['error' => 'Security token invalid'], 400);
            }
            Session::flash('danger', 'Security token invalid.');
            Router::redirect('/approvals');
            return;
        }

        $requestId = (int)($_POST['request_id'] ?? 0);
        $decision  = strtoupper(trim($_POST['decision'] ?? '')); // APPROVE or REJECT
        $comment   = trim($_POST['comment'] ?? '');
        $signature = trim($_POST['signature_data'] ?? '');
        $user      = Session::user();

        if (!in_array($decision, [StateMachine::ACTION_APPROVE, StateMachine::ACTION_REJECT], true)) {
            if ($this->isAjax()) {
                Router::json(['error' => 'Invalid decision action'], 400);
            }
            Session::flash('danger', 'Invalid decision action.');
            Router::redirect('/approvals');
            return;
        }

        if ($decision === StateMachine::ACTION_REJECT && empty($comment)) {
            if ($this->isAjax()) {
                Router::json(['error' => 'An audit rationale is required when rejecting a requisition'], 422);
            }
            Session::flash('danger', 'Please provide a documented audit rationale for rejection.');
            Router::redirect("/requests/show?id={$requestId}");
            return;
        }

        if (empty($comment)) {
            $comment = ($decision === StateMachine::ACTION_APPROVE)
                ? "Executive sign-off certified by {$user['name']} ({$user['role']})."
                : "Requisition declined by {$user['name']} ({$user['role']}).";
        }

        try {
            $result = $this->stateMachine->transition(
                $requestId,
                $user,
                $decision,
                $comment,
                $signature
            );

            $msg = ($decision === StateMachine::ACTION_APPROVE)
                ? "Requisition successfully authorized! Digital seal [{$result['signature_hash']}] generated."
                : "Requisition has been declined and archived.";

            if ($this->isAjax()) {
                Router::json([
                    'success' => true,
                    'message' => $msg,
                    'result'  => $result
                ]);
            }

            Session::flash('success', $msg);
            Router::redirect("/requests/show?id={$requestId}");
        } catch (\Throwable $e) {
            if ($this->isAjax()) {
                Router::json(['error' => $e->getMessage()], 422);
            }
            Session::flash('danger', 'Approval processing error: ' . $e->getMessage());
            Router::redirect("/requests/show?id={$requestId}");
        }
    }

    private function isAjax(): bool
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || str_starts_with($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }
}
