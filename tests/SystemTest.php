<?php
declare(strict_types=1);

/**
 * ApexGuild Enterprise Workflow & RBAC State Machine
 * Automated Verification & Integration Suite
 */

// Native PSR-4 Autoloader
spl_autoload_register(function (string $class) {
    $prefix = 'ApexGuild\\';
    $baseDir = dirname(__DIR__) . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $file = $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
    if (file_exists($file)) require_once $file;
});

use ApexGuild\Core\Database;
use ApexGuild\Core\Session;
use ApexGuild\Domain\StateMachine;
use ApexGuild\Domain\FormCompiler;

echo "========================================================\n";
echo "   APEXGUILD ENTERPRISE VERIFICATION SUITE\n";
echo "========================================================\n\n";

$db = Database::getInstance();
$stateMachine = new StateMachine($db);
$formCompiler = new FormCompiler();

$testsPassed = 0;
$testsTotal = 0;

function assertTest(bool $condition, string $description): void {
    global $testsPassed, $testsTotal;
    $testsTotal++;
    if ($condition) {
        $testsPassed++;
        echo " [PASS] {$description}\n";
    } else {
        echo " [FAIL] {$description}\n";
    }
}

// 1. Verify Users & Roles
$users = $db->fetchAll("SELECT * FROM users ORDER BY id ASC");
assertTest(count($users) === 4, "Users table contains exactly 4 seeded enterprise roles (Admin, Manager, CFO, Requester)");

$requester = $db->fetch("SELECT * FROM users WHERE role = 'Requester'");
$manager   = $db->fetch("SELECT * FROM users WHERE role = 'Manager'");
$cfo       = $db->fetch("SELECT * FROM users WHERE role = 'CFO'");
$admin     = $db->fetch("SELECT * FROM users WHERE role = 'Admin'");

assertTest(password_verify('admin123', $admin['password_hash']), "Admin password hash verifies correctly");
assertTest(password_verify('cfo123', $cfo['password_hash']), "CFO password hash verifies correctly");
assertTest(password_verify('manager123', $manager['password_hash']), "Manager password hash verifies correctly");
assertTest(password_verify('requester123', $requester['password_hash']), "Requester password hash verifies correctly");

// 2. Verify Workflows
$workflows = $db->fetchAll("SELECT * FROM workflows");
assertTest(count($workflows) >= 3, "At least 3 enterprise workflows pre-seeded (PO, Leave, IT Access)");

$poWorkflow = $db->fetch("SELECT * FROM workflows WHERE code = 'PURCHASE_ORDER'");
assertTest(!empty($poWorkflow), "Purchase Order Requisition workflow template loaded");

$poSteps = json_decode($poWorkflow['steps_json'], true);
assertTest(count($poSteps) === 3, "Purchase Order contains 3 sequential approval tiers");
assertTest($poSteps[1]['approver_role'] === 'CFO' && $poSteps[1]['condition'] === 'amount_gt_5000', "Purchase Order Tier 2 enforces CFO role and amount > 5000 threshold condition");

// 3. Test State Machine Transition: High Value PO Requisition ($12,000)
$testHighPO = [
    'tracking_code'     => 'TEST-PO-HIGH-01',
    'workflow_id'       => $poWorkflow['id'],
    'user_id'           => $requester['id'],
    'title'             => 'Test High-Cap GPU Cluster Purchase ($12,000)',
    'priority'          => 'Urgent',
    'current_step'      => 1,
    'current_step_name' => 'Initial Draft',
    'assigned_role'     => 'Requester',
    'status'            => 'Draft',
    'data_json'         => json_encode(['amount' => '12000.00', 'item_description' => 'Test GPU Cluster'])
];
$db->execute("
    INSERT INTO requests (tracking_code, workflow_id, user_id, title, priority, current_step, current_step_name, assigned_role, status, data_json, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
", array_values($testHighPO));
$highId = $db->lastInsertId();

// Submit Draft
$subRes = $stateMachine->transition($highId, $requester, StateMachine::ACTION_SUBMIT, 'Submitted test draft');
$rHigh = $db->fetch("SELECT * FROM requests WHERE id = ?", [$highId]);
assertTest($rHigh['status'] === 'In_Review' && (int)$rHigh['current_step'] === 1 && $rHigh['assigned_role'] === 'Manager', "Requisition submitted: transitioned to In_Review at Step 1 assigned to Manager");

// Unauthorized attempt: CFO cannot approve Step 1 (Assigned to Manager)
$canCfoApprove = $stateMachine->canPerformAction($rHigh, $poWorkflow, $cfo, StateMachine::ACTION_APPROVE);
assertTest(!$canCfoApprove['allowed'], "RBAC Guard: CFO is blocked from approving Step 1 (Assigned to Manager)");

// Authorized approval: Manager approves Step 1
$appRes1 = $stateMachine->transition($highId, $manager, StateMachine::ACTION_APPROVE, 'Manager technical approval');
$rHigh = $db->fetch("SELECT * FROM requests WHERE id = ?", [$highId]);
assertTest($rHigh['status'] === 'In_Review' && (int)$rHigh['current_step'] === 2 && $rHigh['assigned_role'] === 'CFO', "State Machine Step 1 cleared: High amount ($12,000 > $5,000) triggers Step 2 assigned to CFO");

// CFO approves Step 2
$appRes2 = $stateMachine->transition($highId, $cfo, StateMachine::ACTION_APPROVE, 'CFO treasury clearance');
$rHigh = $db->fetch("SELECT * FROM requests WHERE id = ?", [$highId]);
assertTest($rHigh['status'] === 'In_Review' && (int)$rHigh['current_step'] === 3 && $rHigh['assigned_role'] === 'Admin', "State Machine Step 2 cleared: Advanced to Step 3 assigned to Admin");

// Admin final sign-off
$appRes3 = $stateMachine->transition($highId, $admin, StateMachine::ACTION_APPROVE, 'Admin ERP finalization');
$rHigh = $db->fetch("SELECT * FROM requests WHERE id = ?", [$highId]);
assertTest($rHigh['status'] === 'Approved' && $rHigh['assigned_role'] === null, "State Machine Step 3 cleared: Requisition reached terminal status [Approved]");


// 4. Test State Machine Transition: Low Value PO Requisition ($2,500) -> CFO Step MUST BE BYPASSED!
$testLowPO = [
    'tracking_code'     => 'TEST-PO-LOW-02',
    'workflow_id'       => $poWorkflow['id'],
    'user_id'           => $requester['id'],
    'title'             => 'Test Low-Cap Software Seats ($2,500)',
    'priority'          => 'Low',
    'current_step'      => 1,
    'current_step_name' => 'Initial Draft',
    'assigned_role'     => 'Requester',
    'status'            => 'Draft',
    'data_json'         => json_encode(['amount' => '2500.00', 'item_description' => 'Test SaaS Seats'])
];
$db->execute("
    INSERT INTO requests (tracking_code, workflow_id, user_id, title, priority, current_step, current_step_name, assigned_role, status, data_json, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
", array_values($testLowPO));
$lowId = $db->lastInsertId();

$stateMachine->transition($lowId, $requester, StateMachine::ACTION_SUBMIT, 'Submit low-value PO');
$rLow = $db->fetch("SELECT * FROM requests WHERE id = ?", [$lowId]);

// Manager approves Step 1 -> Should directly bypass Step 2 (CFO) and land on Step 3 (Admin)!
$stateMachine->transition($lowId, $manager, StateMachine::ACTION_APPROVE, 'Manager approval for low-value PO');
$rLow = $db->fetch("SELECT * FROM requests WHERE id = ?", [$lowId]);
assertTest($rLow['status'] === 'In_Review' && (int)$rLow['current_step'] === 3 && $rLow['assigned_role'] === 'Admin', "Dynamic Threshold Gate: Amount ($2,500 <= $5,000) bypassed CFO tier directly to Step 3 (Admin)");


// 5. Test Cryptographic Digital Signature Verification
$auditEntries = $db->fetchAll("SELECT * FROM audit_logs WHERE request_id = ?", [$highId]);
assertTest(count($auditEntries) >= 4, "Audit log trail created with entries for SUBMITTED and 3 APPROVALS");

$allSignaturesValid = true;
foreach ($auditEntries as $log) {
    $valid = $stateMachine->verifyDigitalSignature(
        (int)$log['request_id'],
        (int)$log['user_id'],
        $log['action'],
        $log['comment'] ?? '',
        $log['timestamp'],
        $log['signature_hash'] ?? ''
    );
    if (!$valid) {
        $allSignaturesValid = false;
        break;
    }
}
assertTest($allSignaturesValid, "All generated audit log digital signatures verified with HMAC-SHA256 integrity check");


// 6. Test FormCompiler Dynamic Rendering & Validation
$schemaFields = json_decode($poWorkflow['schema_json'], true);
$htmlOutput = $formCompiler->renderFormHtml($schemaFields);
assertTest(str_contains($htmlOutput, 'field_item_description') && str_contains($htmlOutput, 'field_amount'), "FormCompiler successfully generated dynamic input HTML controls");

$testValidInput = [
    'item_description'       => 'Production Server Dell PowerEdge R750',
    'vendor_name'            => 'Dell Direct Technologies',
    'category'               => 'Hardware & Compute Infrastructure',
    'amount'                 => '8500.00',
    'delivery_date'          => '2026-12-15',
    'business_justification' => 'Infrastructure replacement for failing node 4.'
];
$validationResult = $formCompiler->validate($schemaFields, $testValidInput);
assertTest($validationResult['valid'] === true && empty($validationResult['errors']), "FormCompiler validation passes for well-formed requisition payload");

$testInvalidInput = [
    'item_description' => '', // Required field missing
    'amount'           => 'not-a-number' // Non-numeric
];
$invalidResult = $formCompiler->validate($schemaFields, $testInvalidInput);
assertTest($invalidResult['valid'] === false && isset($invalidResult['errors']['item_description']) && isset($invalidResult['errors']['amount']), "FormCompiler correctly catches required missing attributes and numeric type constraints");


// Clean up test rows
$db->execute("DELETE FROM requests WHERE id IN (?, ?)", [$highId, $lowId]);

echo "\n--------------------------------------------------------\n";
echo "RESULT: {$testsPassed} / {$testsTotal} Integration Tests Passed!\n";
echo "========================================================\n";

if ($testsPassed === $testsTotal) {
    exit(0);
} else {
    exit(1);
}
