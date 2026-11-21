<?php
declare(strict_types=1);

namespace ApexGuild\Controllers;

use ApexGuild\Core\Database;
use ApexGuild\Core\Session;
use ApexGuild\Core\Router;

/**
 * Workflow Pipeline Management & Visual Builder Controller
 */
class WorkflowController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Display all available enterprise workflow templates & pipeline steps
     */
    public function index(): void
    {
        $workflows = $this->db->fetchAll("SELECT * FROM workflows ORDER BY is_active DESC, title ASC");

        // Augment with stats & decoded steps
        foreach ($workflows as &$wf) {
            $wf['steps'] = json_decode($wf['steps_json'], true) ?: [];
            $wf['fields'] = json_decode($wf['schema_json'], true) ?: [];

            $stats = $this->db->fetch("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'In_Review' THEN 1 ELSE 0 END) as in_review,
                    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved
                FROM requests WHERE workflow_id = ?
            ", [(int)$wf['id']]);

            $wf['stats'] = [
                'total'     => (int)($stats['total'] ?? 0),
                'in_review' => (int)($stats['in_review'] ?? 0),
                'approved'  => (int)($stats['approved'] ?? 0),
            ];
        }
        unset($wf);

        Router::render('workflows/index', [
            'title'     => 'Workflow Catalog & Pipelines - ApexGuild Enterprise',
            'workflows' => $workflows,
            'userRole'  => Session::role(),
            'flashes'   => Session::getFlashes()
        ]);
    }

    /**
     * Visual Workflow Builder Interface (Admin Only)
     */
    public function create(): void
    {
        if (Session::role() !== 'Admin') {
            Session::flash('danger', 'Only System Administrators can configure new workflow templates.');
            Router::redirect('/workflows');
            return;
        }

        Router::render('workflows/create', [
            'title'     => 'Visual Workflow & Step Builder - ApexGuild Enterprise',
            'csrfToken' => Session::csrfToken(),
            'flashes'   => Session::getFlashes()
        ]);
    }

    /**
     * Store Newly Configured Enterprise Workflow (Admin Only)
     */
    public function store(): void
    {
        if (Session::role() !== 'Admin') {
            Session::flash('danger', 'Unauthorized workflow creation.');
            Router::redirect('/workflows');
            return;
        }

        $csrf = $_POST['_csrf'] ?? '';
        if (!Session::validateCsrf($csrf)) {
            Session::flash('danger', 'Security validation expired.');
            Router::redirect('/workflows/create');
            return;
        }

        $title       = trim($_POST['title'] ?? '');
        $code        = strtoupper(trim(preg_replace('/[^A-Za-z0-9_]/', '', $_POST['code'] ?? '')));
        $description = trim($_POST['description'] ?? '');
        $category    = trim($_POST['category'] ?? 'Operations');
        $icon        = trim($_POST['icon'] ?? 'bi-diagram-3');
        $schemaJson  = trim($_POST['schema_json'] ?? '[]');
        $stepsJson   = trim($_POST['steps_json'] ?? '[]');

        if (empty($title) || empty($code) || empty($description)) {
            Session::flash('danger', 'Title, unique code, and description are mandatory.');
            Router::redirect('/workflows/create');
            return;
        }

        // Validate JSON structures
        $decodedSchema = json_decode($schemaJson, true);
        if ($decodedSchema === null) {
            Session::flash('danger', 'Form Schema JSON syntax is invalid.');
            Router::redirect('/workflows/create');
            return;
        }

        $decodedSteps = json_decode($stepsJson, true);
        if ($decodedSteps === null || empty($decodedSteps)) {
            Session::flash('danger', 'Steps JSON must contain at least one valid approval step.');
            Router::redirect('/workflows/create');
            return;
        }

        try {
            $this->db->execute("
                INSERT INTO workflows (title, code, description, category, icon, schema_json, steps_json, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ", [
                $title,
                $code,
                $description,
                $category,
                $icon,
                json_encode($decodedSchema, JSON_PRETTY_PRINT),
                json_encode($decodedSteps, JSON_PRETTY_PRINT)
            ]);

            Session::flash('success', "Workflow template [{$title}] successfully constructed and published to enterprise registry.");
            Router::redirect('/workflows');
        } catch (\Throwable $e) {
            Session::flash('danger', 'Workflow saving failed: ' . $e->getMessage());
            Router::redirect('/workflows/create');
        }
    }

    /**
     * Toggle Active State of Workflow (Admin Only)
     */
    public function toggle(): void
    {
        if (Session::role() !== 'Admin') {
            Session::flash('danger', 'Action unauthorized.');
            Router::redirect('/workflows');
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $wf = $this->db->fetch("SELECT * FROM workflows WHERE id = ?", [$id]);
        if ($wf) {
            $newState = $wf['is_active'] ? 0 : 1;
            $this->db->execute("UPDATE workflows SET is_active = ? WHERE id = ?", [$newState, $id]);
            Session::flash('info', "Workflow [{$wf['title']}] state changed to: " . ($newState ? 'Active' : 'Archived'));
        }

        Router::redirect('/workflows');
    }
}
