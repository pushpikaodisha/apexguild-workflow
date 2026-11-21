<?php
declare(strict_types=1);

/**
 * ApexGuild Enterprise Workflow & RBAC State Machine
 * Enterprise Front Controller
 */

// 1. Static file pass-through for PHP built-in web server
if (php_sapi_name() === 'cli-server') {
    $requestedFile = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($requestedFile)) {
        return false;
    }
}

// 2. Custom Native PSR-4 Autoloader (Zero Composer Required)
spl_autoload_register(function (string $class) {
    $prefix = 'ApexGuild\\';
    $baseDir = dirname(__DIR__) . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

use ApexGuild\Core\Database;
use ApexGuild\Core\Session;
use ApexGuild\Core\Router;
use ApexGuild\Middleware\AuthMiddleware;
use ApexGuild\Middleware\RbacMiddleware;
use ApexGuild\Controllers\AuthController;
use ApexGuild\Controllers\DashboardController;
use ApexGuild\Controllers\WorkflowController;
use ApexGuild\Controllers\RequestController;
use ApexGuild\Controllers\ApprovalController;

// 3. Initialize Database & Session
Session::start();
Database::getInstance(); // Guarantees SQLite DB & seed existence

// If no user is logged in, optionally pre-seed session with Admin demo if requested or let login handle
$router = Router::getInstance();

// 4. Define Routes

// Root Redirection
$router->get('/', function () {
    if (Session::isLoggedIn()) {
        Router::redirect('/dashboard');
    } else {
        Router::redirect('/login');
    }
});

// Authentication & Demo Switcher
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/auth/switch-role', [AuthController::class, 'switchRole']);
$router->get('/auth/switch-role', [AuthController::class, 'switchRole']);
$router->get('/logout', [AuthController::class, 'logout']);

// Executive Dashboard
$router->get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);

// Workflows & Visual Builder
$router->get('/workflows', [WorkflowController::class, 'index'], [AuthMiddleware::class]);
$router->get('/workflows/create', [WorkflowController::class, 'create'], [RbacMiddleware::allow('Admin')]);
$router->post('/workflows/create', [WorkflowController::class, 'store'], [RbacMiddleware::allow('Admin')]);
$router->post('/workflows/toggle', [WorkflowController::class, 'toggle'], [RbacMiddleware::allow('Admin')]);

// Requisitions Lifecycle
$router->get('/requests', [RequestController::class, 'index'], [AuthMiddleware::class]);
$router->get('/requests/my', [RequestController::class, 'my'], [AuthMiddleware::class]);
$router->get('/requests/create', [RequestController::class, 'create'], [AuthMiddleware::class]);
$router->post('/requests/store', [RequestController::class, 'store'], [AuthMiddleware::class]);
$router->get('/requests/show', [RequestController::class, 'show'], [AuthMiddleware::class]);
$router->post('/requests/submit', [RequestController::class, 'submitDraft'], [AuthMiddleware::class]);
$router->post('/requests/cancel', [RequestController::class, 'cancel'], [AuthMiddleware::class]);

// Approvals & Signatures
$router->get('/approvals', [ApprovalController::class, 'inbox'], [RbacMiddleware::allow('Admin', 'Manager', 'CFO')]);
$router->post('/approvals/action', [ApprovalController::class, 'process'], [RbacMiddleware::allow('Admin', 'Manager', 'CFO')]);

// 5. Dispatch Request
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
