<?php
declare(strict_types=1);

namespace ApexGuild\Controllers;

use ApexGuild\Core\Database;
use ApexGuild\Core\Session;
use ApexGuild\Core\Router;
use ApexGuild\Domain\StateMachine;
use ApexGuild\Domain\FormCompiler;

/**
 * Requisition Lifecycle Controller
 */
class RequestController
{
    private Database $db;
    private StateMachine $stateMachine;
    private FormCompiler $formCompiler;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->stateMachine = new StateMachine($this->db);
        $this->formCompiler = new FormCompiler();
    }

    /**
     * All Enterprise Requests (filterable)
     */
    public function index(): void
    {
        $status     = $_GET['status'] ?? '';
        $workflowId = (int)($_GET['workflow_id'] ?? 0);
        $priority   = $_GET['priority'] ?? '';
        $query      = trim($_GET['q'] ?? '');

        $sql = "
            SELECT r.*, w.title as workflow_title, w.icon as workflow_icon, w.category as workflow_category, u.name as requester_name, u.email as requester_email
            FROM requests r
            JOIN workflows w ON r.workflow_id = w.id
            JOIN users u ON r.user_id = u.id
            WHERE 1=1
        ";
        $params = [];

        if ($status !== '') {
            $sql .= " AND r.status = ?";
            $params[] = $status;
        }
        if ($workflowId > 0) {
            $sql .= " AND r.workflow_id = ?";
            $params[] = $workflowId;
        }
        if ($priority !== '') {
            $sql .= " AND r.priority = ?";
            $params[] = $priority;
        }
        if ($query !== '') {
            $sql .= " AND (r.tracking_code LIKE ? OR r.title LIKE ? OR u.name LIKE ?)";
            $params[] = "%{$query}%";
            $params[] = "%{$query}%";
            $params[] = "%{$query}%";
        }

        $sql .= " ORDER BY r.created_at DESC";

        $requests = $this->db->fetchAll($sql, $params);
        $workflows = $this->db->fetchAll("SELECT id, title FROM workflows WHERE is_active = 1 ORDER BY title ASC");

        Router::render('requests/index', [
            'title'       => 'Enterprise Requisition Registry - ApexGuild Enterprise',
            'requests'    => $requests,
            'workflows'   => $workflows,
            'filterStatus'=> $status,
            'filterWf'    => $workflowId,
            'filterPri'   => $priority,
            'searchQuery' => $query,
            'flashes'     => Session::getFlashes()
        ]);
    }

    /**
     * My Submitted Requests
     */
    public function my(): void
    {
        $userId = Session::id();
        $requests = $this->db->fetchAll("
            SELECT r.*, w.title as workflow_title, w.icon as workflow_icon, w.category as workflow_category, u.name as requester_name
            FROM requests r
            JOIN workflows w ON r.workflow_id = w.id
            JOIN users u ON r.user_id = u.id
            WHERE r.user_id = ?
            ORDER BY r.created_at DESC
        ", [$userId]);

        Router::render('requests/index', [
            'title'        => 'My Requisitions - ApexGuild Enterprise',
            'requests'     => $requests,
            'workflows'    => $this->db->fetchAll("SELECT id, title FROM workflows ORDER BY title ASC"),
            'filterStatus' => '',
            'filterWf'     => 0,
            'filterPri'    => '',
            'searchQuery'  => '',
            'isMyOnly'     => true,
            'flashes'      => Session::getFlashes()
        ]);
    }

    /**
     * Show New Request Creation Form
     */
    public function create(): void
    {
        $workflowId = (int)($_GET['workflow_id'] ?? 0);
        $workflow = null;

        if ($workflowId > 0) {
            $workflow = $this->db->fetch("SELECT * FROM workflows WHERE id = ? AND is_active = 1", [$workflowId]);
        }

        if (!$workflow) {
            // Select first active workflow or prompt choice
            $workflow = $this->db->fetch("SELECT * FROM workflows WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
        }

        if (!$workflow) {
            Session::flash('warning', 'No active workflows available. Please contact your system administrator.');
            Router::redirect('/workflows');
            return;
        }

        $allWorkflows = $this->db->fetchAll("SELECT id, title, category, icon FROM workflows WHERE is_active = 1 ORDER BY title ASC");
        $fields = json_decode($workflow['schema_json'], true) ?: [];
        $steps = json_decode($workflow['steps_json'], true) ?: [];

        $formHtml = $this->formCompiler->renderFormHtml($fields, [], []);

        Router::render('requests/create', [
            'title'        => "New Requisition: {$workflow['title']} - ApexGuild Enterprise",
            'workflow'     => $workflow,
            'allWorkflows' => $allWorkflows,
            'formHtml'     => $formHtml,
            'steps'        => $steps,
            'csrfToken'    => Session::csrfToken(),
            'flashes'      => Session::getFlashes()
        ]);
    }

    /**
     * Store Requisition (Draft or Submit)
     */
    public function store(): void
    {
        $csrf = $_POST['_csrf'] ?? '';
        if (!Session::validateCsrf($csrf)) {
            Session::flash('danger', 'Form verification token expired. Please re-submit.');
            Router::redirect('/requests/create');
            return;
        }

        $workflowId = (int)($_POST['workflow_id'] ?? 0);
        $workflow = $this->db->fetch("SELECT * FROM workflows WHERE id = ? AND is_active = 1", [$workflowId]);
        if (!$workflow) {
            Session::flash('danger', 'Designated workflow template was not found.');
            Router::redirect('/workflows');
            return;
        }

        $title      = trim($_POST['title'] ?? '');
        $priority   = $_POST['priority'] ?? 'Medium';
        $submissionMode = $_POST['submit_action'] ?? 'submit_now'; // 'save_draft' or 'submit_now'
        $postData   = $_POST['data'] ?? [];
        $filesData  = $_FILES ?? [];

        if (empty($title)) {
            $title = $workflow['title'] . ' Requisition (' . date('M d, Y') . ')';
        }

        $fields = json_decode($workflow['schema_json'], true) ?: [];

        // Validate form fields
        $validation = $this->formCompiler->validate($fields, $postData, $filesData);

        if (!$validation['valid'] && $submissionMode === 'submit_now') {
            // Re-render form with validation errors and entered values
            $allWorkflows = $this->db->fetchAll("SELECT id, title, category, icon FROM workflows WHERE is_active = 1 ORDER BY title ASC");
            $formHtml = $this->formCompiler->renderFormHtml($fields, $postData, $validation['errors']);

            Session::flash('danger', 'Please review the highlighted validation errors before submitting.');
            Router::render('requests/create', [
                'title'        => "New Requisition: {$workflow['title']} - ApexGuild Enterprise",
                'workflow'     => $workflow,
                'allWorkflows' => $allWorkflows,
                'formHtml'     => $formHtml,
                'steps'        => json_decode($workflow['steps_json'], true) ?: [],
                'titleVal'     => $title,
                'priorityVal'  => $priority,
                'csrfToken'    => Session::csrfToken(),
                'flashes'      => Session::getFlashes()
            ]);
            return;
        }

        // Generate enterprise tracking code
        $year = date('Y');
        $randomSeq = str_pad((string)random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
        $trackingCode = "APX-{$year}-{$randomSeq}";

        $userId = Session::id();
        $jsonData = json_encode($validation['sanitized'], JSON_PRETTY_PRINT);
        $timestamp = date('Y-m-d H:i:s');

        if ($submissionMode === 'save_draft') {
            // Insert as Draft
            $this->db->execute("
                INSERT INTO requests (
                    tracking_code, workflow_id, user_id, title, priority,
                    current_step, current_step_name, assigned_role, status,
                    data_json, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, 1, 'Initial Draft', 'Requester', 'Draft', ?, ?, ?)
            ", [$trackingCode, $workflowId, $userId, $title, $priority, $jsonData, $timestamp, $timestamp]);

            $newId = $this->db->lastInsertId();

            // Audit record for Draft
            $sig = $this->stateMachine->generateDigitalSignature($newId, $userId, 'CREATED', 'Draft saved', $timestamp);
            $this->db->execute("
                INSERT INTO audit_logs (request_id, user_id, action, step_name, from_status, to_status, comment, signature_hash, timestamp)
                VALUES (?, ?, 'CREATED', 'Draft Creation', 'Draft', 'Draft', 'Requisition draft initialized by requester.', ?, ?)
            ", [$newId, $userId, $sig, $timestamp]);

            Session::flash('info', "Draft requisition [{$trackingCode}] saved successfully.");
            Router::redirect("/requests/show?id={$newId}");
            return;
        }

        // Submission Mode: Insert and Immediately Transition via State Machine!
        $this->db->execute("
            INSERT INTO requests (
                tracking_code, workflow_id, user_id, title, priority,
                current_step, current_step_name, assigned_role, status,
                data_json, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, 1, 'Initial Draft', 'Requester', 'Draft', ?, ?, ?)
        ", [$trackingCode, $workflowId, $userId, $title, $priority, $jsonData, $timestamp, $timestamp]);

        $newId = $this->db->lastInsertId();

        // Perform Submit Transition
        $user = Session::user();
        $this->stateMachine->transition(
            $newId,
            $user,
            StateMachine::ACTION_SUBMIT,
            'Requisition formally submitted into enterprise approval pipeline.'
        );

        Session::flash('success', "Requisition [{$trackingCode}] successfully submitted and dispatched to initial approval tier!");
        Router::redirect("/requests/show?id={$newId}");
    }

    /**
     * Show Detailed Requisition Lifecycle & Verification
     */
    public function show(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $request = $this->db->fetch("
            SELECT r.*, w.title as workflow_title, w.code as workflow_code, w.description as workflow_description,
                   w.category as workflow_category, w.icon as workflow_icon, w.schema_json, w.steps_json,
                   u.name as requester_name, u.email as requester_email, u.department as requester_dept, u.role as requester_role
            FROM requests r
            JOIN workflows w ON r.workflow_id = w.id
            JOIN users u ON r.user_id = u.id
            WHERE r.id = ?
        ", [$id]);

        if (!$request) {
            Session::flash('danger', 'The requested requisition was not found.');
            Router::redirect('/requests');
            return;
        }

        $fields = json_decode($request['schema_json'], true) ?: [];
        $steps = json_decode($request['steps_json'], true) ?: [];
        $data = json_decode($request['data_json'], true) ?: [];

        // Format fields for visual inspection
        $formattedFields = $this->formCompiler->formatReadonlyFields($fields, $data);

        // Fetch Audit Logs with User Details
        $auditLogs = $this->db->fetchAll("
            SELECT a.*, u.name as actor_name, u.role as actor_role, u.department as actor_dept, u.avatar as actor_avatar
            FROM audit_logs a
            JOIN users u ON a.user_id = u.id
            WHERE a.request_id = ?
            ORDER BY a.timestamp ASC, a.id ASC
        ", [$id]);

        // Verify digital signatures for audit logs
        foreach ($auditLogs as &$log) {
            $log['is_verified'] = $this->stateMachine->verifyDigitalSignature(
                (int)$log['request_id'],
                (int)$log['user_id'],
                $log['action'],
                $log['comment'] ?? '',
                $log['timestamp'],
                $log['signature_hash'] ?? ''
            );
        }
        unset($log);

        $currentUser = Session::user();

        // Check if current user can approve/reject
        $canApproveCheck = $this->stateMachine->canPerformAction($request, $request, $currentUser, StateMachine::ACTION_APPROVE);
        $canCancelCheck  = $this->stateMachine->canPerformAction($request, $request, $currentUser, StateMachine::ACTION_CANCEL);
        $canSubmitCheck  = $this->stateMachine->canPerformAction($request, $request, $currentUser, StateMachine::ACTION_SUBMIT);

        Router::render('requests/show', [
            'title'           => "{$request['tracking_code']} - {$request['title']}",
            'request'         => $request,
            'steps'           => $steps,
            'formattedFields' => $formattedFields,
            'auditLogs'       => $auditLogs,
            'currentUser'     => $currentUser,
            'canApprove'      => $canApproveCheck['allowed'],
            'approveReason'   => $canApproveCheck['reason'],
            'canCancel'       => $canCancelCheck['allowed'],
            'canSubmit'       => $canSubmitCheck['allowed'],
            'csrfToken'       => Session::csrfToken(),
            'flashes'         => Session::getFlashes()
        ]);
    }

    /**
     * Submit an existing Draft
     */
    public function submitDraft(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $csrf = $_POST['_csrf'] ?? '';

        if (!Session::validateCsrf($csrf)) {
            Session::flash('danger', 'Security validation expired.');
            Router::redirect("/requests/show?id={$id}");
            return;
        }

        try {
            $this->stateMachine->transition(
                $id,
                Session::user(),
                StateMachine::ACTION_SUBMIT,
                'Requisition draft submitted by owner.'
            );
            Session::flash('success', 'Requisition submitted for approval.');
        } catch (\Throwable $e) {
            Session::flash('danger', 'Submission failed: ' . $e->getMessage());
        }

        Router::redirect("/requests/show?id={$id}");
    }

    /**
     * Cancel Request
     */
    public function cancel(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $csrf = $_POST['_csrf'] ?? '';
        $reason = trim($_POST['cancel_reason'] ?? 'Cancelled by requester');

        if (!Session::validateCsrf($csrf)) {
            Session::flash('danger', 'Security validation expired.');
            Router::redirect("/requests/show?id={$id}");
            return;
        }

        try {
            $this->stateMachine->transition(
                $id,
                Session::user(),
                StateMachine::ACTION_CANCEL,
                $reason
            );
            Session::flash('info', 'Requisition was cancelled.');
        } catch (\Throwable $e) {
            Session::flash('danger', 'Cancellation failed: ' . $e->getMessage());
        }

        Router::redirect("/requests/show?id={$id}");
    }
}
