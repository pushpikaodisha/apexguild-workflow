<?php
declare(strict_types=1);

/**
 * ApexGuild Enterprise Workflow & RBAC State Machine
 * Database Seeder
 */

$config = require __DIR__ . '/../config/database.php';
$dbPath = $config['db']['path'];
$secretKey = $config['app']['secret_key'];

// Ensure directory exists
$dir = dirname($dbPath);
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

// Connect to SQLite
$pdo = new PDO('sqlite:' . $dbPath, null, null, $config['db']['options']);
foreach ($config['db']['pragmas'] as $pragma) {
    $pdo->exec($pragma);
}

// Run schema
$schema = file_get_contents(__DIR__ . '/schema.sql');
$pdo->exec($schema);

// 1. Seed Users
$users = [
    [
        'name'          => 'Eleanor Vance',
        'email'         => 'admin@apexguild.com',
        'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
        'role'          => 'Admin',
        'department'    => 'Governance & Security',
        'avatar'        => 'avatar-admin.png'
    ],
    [
        'name'          => 'Marcus Thorne',
        'email'         => 'manager@apexguild.com',
        'password_hash' => password_hash('manager123', PASSWORD_DEFAULT),
        'role'          => 'Manager',
        'department'    => 'Engineering Operations',
        'avatar'        => 'avatar-manager.png'
    ],
    [
        'name'          => 'Sophia Sterling',
        'email'         => 'cfo@apexguild.com',
        'password_hash' => password_hash('cfo123', PASSWORD_DEFAULT),
        'role'          => 'CFO',
        'department'    => 'Treasury & Corporate Finance',
        'avatar'        => 'avatar-cfo.png'
    ],
    [
        'name'          => 'Alex Chen',
        'email'         => 'requester@apexguild.com',
        'password_hash' => password_hash('requester123', PASSWORD_DEFAULT),
        'role'          => 'Requester',
        'department'    => 'Product Innovation',
        'avatar'        => 'avatar-requester.png'
    ]
];

$userStmt = $pdo->prepare("
    INSERT INTO users (name, email, password_hash, role, department, avatar)
    VALUES (:name, :email, :password_hash, :role, :department, :avatar)
    ON CONFLICT(email) DO UPDATE SET
        name = excluded.name,
        password_hash = excluded.password_hash,
        role = excluded.role,
        department = excluded.department,
        avatar = excluded.avatar
");

foreach ($users as $u) {
    $userStmt->execute($u);
}

// Fetch user IDs
$userMap = [];
$res = $pdo->query("SELECT id, email, role FROM users")->fetchAll();
foreach ($res as $row) {
    $userMap[$row['role']] = (int)$row['id'];
    $userMap[$row['email']] = (int)$row['id'];
}

// 2. Seed Workflows
$workflows = [
    [
        'title'       => 'Enterprise Purchase Order Requisition',
        'code'        => 'PURCHASE_ORDER',
        'description' => 'Multi-tier financial requisition with dynamic thresholding. Expenditures > $5,000 automatically require executive CFO authorization.',
        'category'    => 'Finance & Procurement',
        'icon'        => 'bi-cart-check',
        'schema_json' => json_encode([
            [
                'name'        => 'item_description',
                'label'       => 'Item / Service Specification',
                'type'        => 'text',
                'placeholder' => 'e.g. Dedicated Enterprise GPU Compute Cluster 8x H100',
                'required'    => true,
                'col'         => 12
            ],
            [
                'name'        => 'vendor_name',
                'label'       => 'Vendor / Contractor Name',
                'type'        => 'text',
                'placeholder' => 'e.g. NVIDIA Supermicro Corporation',
                'required'    => true,
                'col'         => 6
            ],
            [
                'name'        => 'category',
                'label'       => 'Procurement Category',
                'type'        => 'select',
                'options'     => [
                    'Hardware & Compute Infrastructure',
                    'Cloud Hosting & Enterprise SaaS',
                    'Professional Consulting & Legal',
                    'Facilities & Physical Logistics'
                ],
                'required'    => true,
                'col'         => 6
            ],
            [
                'name'        => 'amount',
                'label'       => 'Requisition Amount ($ USD)',
                'type'        => 'amount',
                'placeholder' => '0.00',
                'required'    => true,
                'min'         => 1.00,
                'step'        => '0.01',
                'hint'        => 'Amounts exceeding $5,000.00 trigger mandatory CFO audit tier.',
                'col'         => 6
            ],
            [
                'name'        => 'delivery_date',
                'label'       => 'Target Delivery Date',
                'type'        => 'date',
                'required'    => true,
                'col'         => 6
            ],
            [
                'name'        => 'business_justification',
                'label'       => 'Business Need & Projected ROI',
                'type'        => 'textarea',
                'placeholder' => 'Detail how this purchase directly fulfills current strategic deliverables and cost-center allocations.',
                'required'    => true,
                'rows'        => 3,
                'col'         => 12
            ],
            [
                'name'        => 'attachment',
                'label'       => 'Vendor Quotation / Pro-forma Specification',
                'type'        => 'file',
                'accept'      => '.pdf,.png,.jpg,.jpeg,.doc,.docx',
                'required'    => false,
                'col'         => 12
            ]
        ], JSON_PRETTY_PRINT),
        'steps_json'  => json_encode([
            [
                'step_number' => 1,
                'step_key'    => 'manager_operational_review',
                'step_name'   => 'Departmental Operational Review',
                'approver_role' => 'Manager',
                'condition'   => 'always',
                'sla_hours'   => 24,
                'description' => 'Operational validation of necessity, budget allocation, and technical fit.'
            ],
            [
                'step_number' => 2,
                'step_key'    => 'cfo_executive_signoff',
                'step_name'   => 'CFO Executive Capital Sign-off',
                'approver_role' => 'CFO',
                'condition'   => 'amount_gt_5000',
                'sla_hours'   => 48,
                'description' => 'Fiscal liquidity check and capital committee authorization for expenses > $5,000.'
            ],
            [
                'step_number' => 3,
                'step_key'    => 'admin_procurement_execution',
                'step_name'   => 'Procurement Execution & ERP Binding',
                'approver_role' => 'Admin',
                'condition'   => 'always',
                'sla_hours'   => 12,
                'description' => 'Vendor dispatch, ERP ledger recording, and purchase order contract binding.'
            ]
        ], JSON_PRETTY_PRINT),
        'is_active'   => 1
    ],
    [
        'title'       => 'Leave & Absence Authorization',
        'code'        => 'LEAVE_REQ',
        'description' => 'Standard employee leave request with automatic calendar conflict evaluation, manager sign-off, and HR ledger registry.',
        'category'    => 'Human Capital',
        'icon'        => 'bi-calendar-heart',
        'schema_json' => json_encode([
            [
                'name'        => 'leave_type',
                'label'       => 'Absence Classification',
                'type'        => 'select',
                'options'     => [
                    'Annual Paid Vacation',
                    'Medical / Health Recuperation',
                    'Parental & Family Leave',
                    'Bereavement / Emergency',
                    'Unpaid Educational Sabbatical'
                ],
                'required'    => true,
                'col'         => 6
            ],
            [
                'name'        => 'days_count',
                'label'       => 'Total Working Days',
                'type'        => 'number',
                'placeholder' => 'e.g. 5',
                'min'         => 0.5,
                'step'        => '0.5',
                'required'    => true,
                'col'         => 6
            ],
            [
                'name'        => 'start_date',
                'label'       => 'Departure Date',
                'type'        => 'date',
                'required'    => true,
                'col'         => 6
            ],
            [
                'name'        => 'end_date',
                'label'       => 'Return to Office Date',
                'type'        => 'date',
                'required'    => true,
                'col'         => 6
            ],
            [
                'name'        => 'handover_contact',
                'label'       => 'Primary Task Handover Delegate',
                'type'        => 'text',
                'placeholder' => 'e.g. Jordan Miller (Senior Platform Engineer)',
                'required'    => true,
                'col'         => 12
            ],
            [
                'name'        => 'notes',
                'label'       => 'Operational Coverage Details',
                'type'        => 'textarea',
                'placeholder' => 'Briefly describe on-call coverage or customer-facing handoffs during this absence.',
                'required'    => false,
                'rows'        => 3,
                'col'         => 12
            ]
        ], JSON_PRETTY_PRINT),
        'steps_json'  => json_encode([
            [
                'step_number' => 1,
                'step_key'    => 'manager_roster_approval',
                'step_name'   => 'Team Roster & Workload Approval',
                'approver_role' => 'Manager',
                'condition'   => 'always',
                'sla_hours'   => 24,
                'description' => 'Ensures sprint capacity and team on-call requirements are fulfilled.'
            ],
            [
                'step_number' => 2,
                'step_key'    => 'admin_hr_registry',
                'step_name'   => 'People Ops Statutory Registry',
                'approver_role' => 'Admin',
                'condition'   => 'always',
                'sla_hours'   => 24,
                'description' => 'Verification against statutory accruals and formal HRMS synchronization.'
            ]
        ], JSON_PRETTY_PRINT),
        'is_active'   => 1
    ],
    [
        'title'       => 'Zero-Trust Cloud & Infrastructure Clearance',
        'code'        => 'IT_ACCESS',
        'description' => 'Granular identity access management workflow with manager need-to-know verification and SecOps security credentialing.',
        'category'    => 'Security & Compliance',
        'icon'        => 'bi-shield-check',
        'schema_json' => json_encode([
            [
                'name'        => 'target_system',
                'label'       => 'Protected Asset / Cloud Environment',
                'type'        => 'select',
                'options'     => [
                    'Production AWS EKS Kubernetes Cluster',
                    'Primary Encrypted PostgreSQL Data Lake',
                    'Corporate Identity Vault (HashiCorp / Okta)',
                    'Edge Cloudflare Network Gateway & WAF',
                    'Internal Customer PII Warehouse'
                ],
                'required'    => true,
                'col'         => 6
            ],
            [
                'name'        => 'clearance_level',
                'label'       => 'Requested IAM Privilege Tier',
                'type'        => 'select',
                'options'     => [
                    'Read-Only Auditor / Telemetry Access',
                    'Staging Operator (Non-PII Read/Write)',
                    'Production Release Engineer',
                    'Zero-Trust Break-Glass Superuser'
                ],
                'required'    => true,
                'col'         => 6
            ],
            [
                'name'        => 'validity_days',
                'label'       => 'Clearance Time-to-Live (Days)',
                'type'        => 'number',
                'placeholder' => '14',
                'min'         => 1,
                'max'         => 90,
                'required'    => true,
                'col'         => 6
            ],
            [
                'name'        => 'security_ack',
                'label'       => 'Hardware 2FA & FIDO2 Token Enrolled',
                'type'        => 'select',
                'options'     => [
                    'Verified - YubiKey / WebAuthn Active',
                    'Exception Required (Requires SecOps Special Review)'
                ],
                'required'    => true,
                'col'         => 6
            ],
            [
                'name'        => 'business_justification',
                'label'       => 'Incident or Feature Ticket Justification',
                'type'        => 'textarea',
                'placeholder' => 'List related ticket (e.g. SEC-4912) and explicit reason why lower-privilege credentials cannot suffice.',
                'required'    => true,
                'rows'        => 3,
                'col'         => 12
            ]
        ], JSON_PRETTY_PRINT),
        'steps_json'  => json_encode([
            [
                'step_number' => 1,
                'step_key'    => 'manager_need_to_know',
                'step_name'   => 'Departmental Need-to-Know Validation',
                'approver_role' => 'Manager',
                'condition'   => 'always',
                'sla_hours'   => 12,
                'description' => 'Affirmation that the requester has business need for elevated access.'
            ],
            [
                'step_number' => 2,
                'step_key'    => 'admin_secops_clearance',
                'step_name'   => 'SecOps Zero-Trust Provisioning',
                'approver_role' => 'Admin',
                'condition'   => 'always',
                'sla_hours'   => 8,
                'description' => 'Certificate generation, ephemeral bastion lease creation, and SIEM auditing.'
            ]
        ], JSON_PRETTY_PRINT),
        'is_active'   => 1
    ]
];

$wfStmt = $pdo->prepare("
    INSERT INTO workflows (title, code, description, category, icon, schema_json, steps_json, is_active)
    VALUES (:title, :code, :description, :category, :icon, :schema_json, :steps_json, :is_active)
    ON CONFLICT(code) DO UPDATE SET
        title = excluded.title,
        description = excluded.description,
        category = excluded.category,
        icon = excluded.icon,
        schema_json = excluded.schema_json,
        steps_json = excluded.steps_json,
        is_active = excluded.is_active
");

foreach ($workflows as $wf) {
    $wfStmt->execute($wf);
}

// Fetch workflow IDs
$workflowMap = [];
$res = $pdo->query("SELECT id, code FROM workflows")->fetchAll();
foreach ($res as $row) {
    $workflowMap[$row['code']] = (int)$row['id'];
}

// 3. Clear existing demo requests & seed fresh realistic requests
$pdo->exec("DELETE FROM audit_logs");
$pdo->exec("DELETE FROM requests");

// Helper for digital signature
function generateSignature(int $requestId, int $userId, string $action, string $secretKey, string $timestamp): string {
    $payload = "REQ#{$requestId}|USER#{$userId}|ACTION#{$action}|TS#{$timestamp}";
    return hash_hmac('sha256', $payload, $secretKey);
}

// Sample Requests
$requesterId = $userMap['Requester'];
$managerId   = $userMap['Manager'];
$cfoId       = $userMap['CFO'];
$adminId     = $userMap['Admin'];

// Request 1: High Value PO Requisition ($18,450) -> In_Review at Step 2 (Assigned to CFO!)
$poId = $workflowMap['PURCHASE_ORDER'];
$poReq1 = [
    'tracking_code'     => 'APX-2026-0042',
    'workflow_id'       => $poId,
    'user_id'           => $requesterId,
    'title'             => 'NVIDIA DGX Station A100 AI Workstation Requisition',
    'priority'          => 'Urgent',
    'current_step'      => 2,
    'current_step_name' => 'CFO Executive Capital Sign-off',
    'assigned_role'     => 'CFO',
    'status'            => 'In_Review',
    'data_json'         => json_encode([
        'item_description'       => 'NVIDIA DGX Station A100 (4x 80GB GPUs) for On-Premise LLM Fine-Tuning & In-House Inference',
        'vendor_name'            => 'NVIDIA Enterprise Direct / Lambda Labs Inc.',
        'category'               => 'Hardware & Compute Infrastructure',
        'amount'                 => '18450.00',
        'delivery_date'          => date('Y-m-d', strtotime('+14 days')),
        'business_justification' => 'Essential hardware for Q3 on-premise private AI training. Amortized over 36 months, reducing cloud training costs by 68%.',
        'attachment'             => 'nvidia-quote-nv-84912.pdf'
    ], JSON_PRETTY_PRINT),
    'submitted_at'      => date('Y-m-d H:i:s', strtotime('-18 hours')),
    'completed_at'      => null
];

$reqInsert = $pdo->prepare("
    INSERT INTO requests (tracking_code, workflow_id, user_id, title, priority, current_step, current_step_name, assigned_role, status, data_json, submitted_at, completed_at, created_at)
    VALUES (:tracking_code, :workflow_id, :user_id, :title, :priority, :current_step, :current_step_name, :assigned_role, :status, :data_json, :submitted_at, :completed_at, :submitted_at)
");
$reqInsert->execute($poReq1);
$r1Id = (int)$pdo->lastInsertId();

// Audit trail for Request 1
$t1 = date('Y-m-d H:i:s', strtotime('-18 hours'));
$sig1 = generateSignature($r1Id, $requesterId, 'SUBMITTED', $secretKey, $t1);
$pdo->prepare("
    INSERT INTO audit_logs (request_id, user_id, action, step_name, from_status, to_status, comment, signature_hash, timestamp)
    VALUES (?, ?, 'SUBMITTED', 'Initial Submission', 'Draft', 'In_Review', 'Requisition submitted for departmental operational review.', ?, ?)
")->execute([$r1Id, $requesterId, $sig1, $t1]);

$t2 = date('Y-m-d H:i:s', strtotime('-14 hours'));
$sig2 = generateSignature($r1Id, $managerId, 'APPROVED', $secretKey, $t2);
$pdo->prepare("
    INSERT INTO audit_logs (request_id, user_id, action, step_name, from_status, to_status, comment, signature_hash, timestamp)
    VALUES (?, ?, 'APPROVED', 'Departmental Operational Review', 'In_Review', 'In_Review', 'Technical specs reviewed and approved. Expense exceeds $5,000 threshold, automatically escalating to CFO for executive treasury sign-off.', ?, ?)
")->execute([$r1Id, $managerId, $sig2, $t2]);


// Request 2: Leave Request -> In_Review at Step 1 (Assigned to Manager!)
$leaveId = $workflowMap['LEAVE_REQ'];
$leaveReq = [
    'tracking_code'     => 'APX-2026-0043',
    'workflow_id'       => $leaveId,
    'user_id'           => $requesterId,
    'title'             => 'Annual Technical Renewal & Rest Leave (5 Days)',
    'priority'          => 'Medium',
    'current_step'      => 1,
    'current_step_name' => 'Team Roster & Workload Approval',
    'assigned_role'     => 'Manager',
    'status'            => 'In_Review',
    'data_json'         => json_encode([
        'leave_type'        => 'Annual Paid Vacation',
        'days_count'        => '5',
        'start_date'        => date('Y-m-d', strtotime('+7 days')),
        'end_date'          => date('Y-m-d', strtotime('+12 days')),
        'handover_contact'  => 'Jordan Miller (Lead Site Reliability Engineer)',
        'notes'             => 'All primary sprint deliverables are completed. Jordan is briefed on production rotation.'
    ], JSON_PRETTY_PRINT),
    'submitted_at'      => date('Y-m-d H:i:s', strtotime('-4 hours')),
    'completed_at'      => null
];
$reqInsert->execute($leaveReq);
$r2Id = (int)$pdo->lastInsertId();

$t3 = date('Y-m-d H:i:s', strtotime('-4 hours'));
$sig3 = generateSignature($r2Id, $requesterId, 'SUBMITTED', $secretKey, $t3);
$pdo->prepare("
    INSERT INTO audit_logs (request_id, user_id, action, step_name, from_status, to_status, comment, signature_hash, timestamp)
    VALUES (?, ?, 'SUBMITTED', 'Team Roster & Workload Approval', 'Draft', 'In_Review', 'Leave requisition submitted. Awaiting manager approval.', ?, ?)
")->execute([$r2Id, $requesterId, $sig3, $t3]);


// Request 3: Zero-Trust IAM Access -> In_Review at Step 2 (Assigned to Admin!)
$secId = $workflowMap['IT_ACCESS'];
$secReq = [
    'tracking_code'     => 'APX-2026-0044',
    'workflow_id'       => $secId,
    'user_id'           => $requesterId,
    'title'             => 'Production AWS EKS Cluster Read-Write Telemetry Access',
    'priority'          => 'High',
    'current_step'      => 2,
    'current_step_name' => 'SecOps Zero-Trust Provisioning',
    'assigned_role'     => 'Admin',
    'status'            => 'In_Review',
    'data_json'         => json_encode([
        'target_system'          => 'Production AWS EKS Kubernetes Cluster',
        'clearance_level'        => 'Production Release Engineer',
        'validity_days'          => '30',
        'security_ack'           => 'Verified - YubiKey / WebAuthn Active',
        'business_justification' => 'Required for deploying and observing the new microservices architecture rollout for Enterprise Client Alpha.'
    ], JSON_PRETTY_PRINT),
    'submitted_at'      => date('Y-m-d H:i:s', strtotime('-10 hours')),
    'completed_at'      => null
];
$reqInsert->execute($secReq);
$r3Id = (int)$pdo->lastInsertId();

$t4 = date('Y-m-d H:i:s', strtotime('-10 hours'));
$sig4 = generateSignature($r3Id, $requesterId, 'SUBMITTED', $secretKey, $t4);
$pdo->prepare("
    INSERT INTO audit_logs (request_id, user_id, action, step_name, from_status, to_status, comment, signature_hash, timestamp)
    VALUES (?, ?, 'SUBMITTED', 'Need-to-Know Validation', 'Draft', 'In_Review', 'IAM request initiated by platform staff.', ?, ?)
")->execute([$r3Id, $requesterId, $sig4, $t4]);

$t5 = date('Y-m-d H:i:s', strtotime('-6 hours'));
$sig5 = generateSignature($r3Id, $managerId, 'APPROVED', $secretKey, $t5);
$pdo->prepare("
    INSERT INTO audit_logs (request_id, user_id, action, step_name, from_status, to_status, comment, signature_hash, timestamp)
    VALUES (?, ?, 'APPROVED', 'Departmental Need-to-Know Validation', 'In_Review', 'In_Review', 'Verified operational need for deployment window. Forwarded to SecOps for key rotation and bastion binding.', ?, ?)
")->execute([$r3Id, $managerId, $sig5, $t5]);


// Request 4: Low-Value PO Requisition ($1,250.00) -> Completely APPROVED!
// Notice: Amount is <= $5,000, so Step 2 (CFO) was evaluated as skipped by State Machine!
$poReq2 = [
    'tracking_code'     => 'APX-2026-0039',
    'workflow_id'       => $poId,
    'user_id'           => $requesterId,
    'title'             => 'Figma Enterprise Organization 12-Month Seat Expansion',
    'priority'          => 'Low',
    'current_step'      => 3,
    'current_step_name' => 'Completed',
    'assigned_role'     => null,
    'status'            => 'Approved',
    'data_json'         => json_encode([
        'item_description'       => '5 Additional Enterprise UI/UX Design Seats for Product Innovation Squad',
        'vendor_name'            => 'Figma Inc.',
        'category'               => 'Cloud Hosting & Enterprise SaaS',
        'amount'                 => '1250.00',
        'delivery_date'          => date('Y-m-d', strtotime('-2 days')),
        'business_justification' => 'Onboarding new contract design partners for mobile component system redesign.',
        'attachment'             => 'figma-quote-renewal.pdf'
    ], JSON_PRETTY_PRINT),
    'submitted_at'      => date('Y-m-d H:i:s', strtotime('-3 days')),
    'completed_at'      => date('Y-m-d H:i:s', strtotime('-1 day'))
];
$reqInsert->execute($poReq2);
$r4Id = (int)$pdo->lastInsertId();

$t6 = date('Y-m-d H:i:s', strtotime('-3 days'));
$sig6 = generateSignature($r4Id, $requesterId, 'SUBMITTED', $secretKey, $t6);
$pdo->prepare("
    INSERT INTO audit_logs (request_id, user_id, action, step_name, from_status, to_status, comment, signature_hash, timestamp)
    VALUES (?, ?, 'SUBMITTED', 'Departmental Operational Review', 'Draft', 'In_Review', 'Requisition created for design tool licensing.', ?, ?)
")->execute([$r4Id, $requesterId, $sig6, $t6]);

$t7 = date('Y-m-d H:i:s', strtotime('-2 days'));
$sig7 = generateSignature($r4Id, $managerId, 'APPROVED', $secretKey, $t7);
$pdo->prepare("
    INSERT INTO audit_logs (request_id, user_id, action, step_name, from_status, to_status, comment, signature_hash, timestamp)
    VALUES (?, ?, 'APPROVED', 'Departmental Operational Review', 'In_Review', 'In_Review', 'Approved by Operations Manager. Amount ($1,250.00) is below $5,000 threshold: CFO step automatically bypassed to Step 3.', ?, ?)
")->execute([$r4Id, $managerId, $sig7, $t7]);

$t8 = date('Y-m-d H:i:s', strtotime('-1 day'));
$sig8 = generateSignature($r4Id, $adminId, 'APPROVED', $secretKey, $t8);
$pdo->prepare("
    INSERT INTO audit_logs (request_id, user_id, action, step_name, from_status, to_status, comment, signature_hash, timestamp)
    VALUES (?, ?, 'APPROVED', 'Procurement Execution & ERP Binding', 'In_Review', 'Approved', 'License keys issued and synced into NetSuite ERP. Fully executed.', ?, ?)
")->execute([$r4Id, $adminId, $sig8, $t8]);


// Request 5: Draft Requisition -> Draft status
$poDraft = [
    'tracking_code'     => 'APX-2026-0045',
    'workflow_id'       => $poId,
    'user_id'           => $requesterId,
    'title'             => 'Ergonomic Standing Desks & Ultra-Wide Monitor Upgrades',
    'priority'          => 'Medium',
    'current_step'      => 1,
    'current_step_name' => 'Initial Draft',
    'assigned_role'     => 'Requester',
    'status'            => 'Draft',
    'data_json'         => json_encode([
        'item_description'       => '3x Herman Miller Motia Adjustable Standing Desks & Dell 40-inch Curved Displays',
        'vendor_name'            => 'Herman Miller Workspaces Ltd',
        'category'               => 'Facilities & Physical Logistics',
        'amount'                 => '4890.00',
        'delivery_date'          => date('Y-m-d', strtotime('+20 days')),
        'business_justification' => 'Health and ergonomic workplace improvement for frontend engineering team.',
        'attachment'             => ''
    ], JSON_PRETTY_PRINT),
    'submitted_at'      => null,
    'completed_at'      => null
];
$reqInsert->execute($poDraft);
$r5Id = (int)$pdo->lastInsertId();

$t9 = date('Y-m-d H:i:s', strtotime('-2 hours'));
$sig9 = generateSignature($r5Id, $requesterId, 'CREATED', $secretKey, $t9);
$pdo->prepare("
    INSERT INTO audit_logs (request_id, user_id, action, step_name, from_status, to_status, comment, signature_hash, timestamp)
    VALUES (?, ?, 'CREATED', 'Draft Creation', 'Draft', 'Draft', 'Requisition draft saved by requester.', ?, ?)
")->execute([$r5Id, $requesterId, $sig9, $t9]);

echo "[ApexGuild Seeder] Database seeded successfully at: {$dbPath}\n";
echo "  - 4 Enterprise Users (Admin, Manager, CFO, Requester)\n";
echo "  - 3 Core Workflows (Purchase Order, Leave Request, IT Access)\n";
echo "  - 5 Sample Requests across Draft, In_Review (Manager, CFO, Admin tiers), and Approved\n";
