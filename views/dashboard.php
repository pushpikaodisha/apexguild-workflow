<?php
/**
 * Executive Operations Center Dashboard
 */
?>

<!-- Role Welcome Banner -->
<div class="enterprise-card p-4 mb-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #ffffff; border: none;">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-primary text-uppercase px-2 py-1" style="letter-spacing: 0.05em; font-size: 0.72rem;">Active Session</span>
                <span class="text-white-50 small">&bull;</span>
                <span class="text-white-50 small">RBAC Authorized Environment</span>
            </div>
            <h2 class="fw-bold mb-1 text-white" style="letter-spacing: -0.02em;">
                Welcome, <?= htmlspecialchars($user['name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?>
            </h2>
            <p class="text-white-50 mb-0" style="max-width: 650px;">
                Logged in as <strong class="text-info"><?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?></strong> (<?= htmlspecialchars($user['department'] ?? 'Corporate Operations', ENT_QUOTES, 'UTF-8') ?>).
                <?php if ($role === 'CFO'): ?>
                    <span class="text-warning d-block mt-1"><i class="bi bi-shield-exclamation me-1"></i> Executive Authority: Fiscal requisitions exceeding <strong>$5,000.00</strong> require your cryptographic sign-off.</span>
                <?php elseif ($role === 'Manager'): ?>
                    <span class="text-light d-block mt-1"><i class="bi bi-check2-circle me-1"></i> Operations Authority: Tier-1 review and workload validation for departmental requests.</span>
                <?php elseif ($role === 'Admin'): ?>
                    <span class="text-light d-block mt-1"><i class="bi bi-sliders me-1"></i> System Authority: Full governance over workflow schema pipelines, step configurations, and override signing.</span>
                <?php else: ?>
                    <span class="text-light d-block mt-1"><i class="bi bi-pencil-square me-1"></i> Requester Access: Initialize and track purchase orders, leave requests, and infrastructure access.</span>
                <?php endif; ?>
            </p>
        </div>
        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
            <div class="d-flex gap-2 justify-content-lg-end flex-wrap">
                <a href="/requests/create" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> New Requisition
                </a>
                <?php if ($role !== 'Requester'): ?>
                <a href="/approvals" class="btn btn-outline-light">
                    <i class="bi bi-inbox-fill"></i> Approval Inbox
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Executive Metric Cards -->
<div class="row g-3 mb-4">
    <!-- Metric 1: Pending Approvals for Role -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="metric-card">
            <div class="metric-header">
                <span class="metric-label"><?= ($role === 'Requester') ? 'My Active Requests' : 'Pending Approvals' ?></span>
                <div class="metric-icon-box icon-amber">
                    <i class="bi bi-hourglass-split"></i>
                </div>
            </div>
            <div class="metric-value"><?= ($role === 'Requester') ? $myRequestsCount : $myPendingApprovals ?></div>
            <div class="metric-subtext">
                <span class="text-warning fw-semibold"><i class="bi bi-clock-history"></i> Active In-Flight</span>
                <span>requiring action</span>
            </div>
        </div>
    </div>

    <!-- Metric 2: Total Requisitions -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="metric-card">
            <div class="metric-header">
                <span class="metric-label">Total Requisitions</span>
                <div class="metric-icon-box icon-blue">
                    <i class="bi bi-layers-fill"></i>
                </div>
            </div>
            <div class="metric-value"><?= $totalRequests ?></div>
            <div class="metric-subtext">
                <span class="text-primary fw-semibold"><i class="bi bi-graph-up-arrow"></i> System Registry</span>
                <span>across 3 workflows</span>
            </div>
        </div>
    </div>

    <!-- Metric 3: Fully Approved -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="metric-card">
            <div class="metric-header">
                <span class="metric-label">Approved & Executed</span>
                <div class="metric-icon-box icon-emerald">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
            </div>
            <div class="metric-value"><?= $approvedCount ?></div>
            <div class="metric-subtext">
                <span class="text-success fw-semibold"><i class="bi bi-shield-check"></i> <?= $approvalRate ?>%</span>
                <span>lifecycle completion</span>
            </div>
        </div>
    </div>

    <!-- Metric 4: SLA Compliance -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="metric-card">
            <div class="metric-header">
                <span class="metric-label">SLA Compliance</span>
                <div class="metric-icon-box icon-purple">
                    <i class="bi bi-award-fill"></i>
                </div>
            </div>
            <div class="metric-value"><?= $slaComplianceRate ?>%</div>
            <div class="metric-subtext">
                <span class="text-purple fw-semibold"><i class="bi bi-lightning-charge"></i> Multi-tier benchmark</span>
                <span>&lt; 24h turnaround</span>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Pending Action Queue & Quick Workflows -->
    <div class="col-12 col-xl-8">
        <!-- Requisition Action Queue -->
        <div class="enterprise-card mb-4">
            <div class="card-header-custom">
                <h3 class="card-title-custom">
                    <i class="bi bi-list-task text-primary"></i>
                    <span><?= ($role === 'Requester') ? 'My Active Requisition Queue' : 'Priority Queue & Pending Approvals' ?></span>
                </h3>
                <a href="<?= ($role === 'Requester') ? '/requests/my' : '/approvals' ?>" class="btn btn-outline-secondary btn-sm">
                    View All <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="table-responsive">
                <table class="enterprise-table">
                    <thead>
                        <tr>
                            <th>Tracking Code</th>
                            <th>Requisition Title</th>
                            <th>Priority</th>
                            <th>Current Tier</th>
                            <th>Assigned</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pendingQueue)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="bi bi-check-circle fs-3 text-success d-block mb-2"></i>
                                    No pending items in your queue. All requisitions are cleared!
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pendingQueue as $req): ?>
                                <tr>
                                    <td>
                                        <a href="/requests/show?id=<?= $req['id'] ?>" class="fw-bold text-dark font-monospace">
                                            <?= htmlspecialchars($req['tracking_code'], ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-truncate" style="max-width: 240px;" title="<?= htmlspecialchars($req['title'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($req['title'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div class="small text-muted d-flex align-items-center gap-1">
                                            <i class="bi <?= htmlspecialchars($req['workflow_icon'] ?? 'bi-diagram-3', ENT_QUOTES, 'UTF-8') ?>"></i>
                                            <?= htmlspecialchars($req['workflow_title'] ?? 'Workflow', ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="priority-badge priority-<?= strtolower($req['priority']) ?>">
                                            <?= htmlspecialchars($req['priority'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold text-dark">
                                            Step <?= (int)$req['current_step'] ?>: <?= htmlspecialchars($req['current_step_name'] ?? 'In Review', ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($req['assigned_role'])): ?>
                                            <span class="badge bg-light text-dark border">
                                                <i class="bi bi-person-badge me-1"></i><?= htmlspecialchars($req['assigned_role'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success-subtle text-success">Cleared</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($req['status'] === 'In_Review' && ($req['assigned_role'] === $role || $role === 'Admin')): ?>
                                            <button type="button" class="btn btn-sm btn-primary" onclick="openApprovalModal(<?= $req['id'] ?>, 'APPROVE', '<?= htmlspecialchars(addslashes($req['title']), ENT_QUOTES, 'UTF-8') ?>')">
                                                <i class="bi bi-pen"></i> Sign & Approve
                                            </button>
                                        <?php else: ?>
                                            <a href="/requests/show?id=<?= $req['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                Details <i class="bi bi-chevron-right"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Launch Workflow Templates -->
        <div class="enterprise-card">
            <div class="card-header-custom">
                <h3 class="card-title-custom">
                    <i class="bi bi-lightning-charge-fill text-warning"></i>
                    <span>Launch Workflow Requisition</span>
                </h3>
                <a href="/workflows" class="btn btn-outline-secondary btn-sm">Catalog <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="card-body-custom">
                <div class="row g-3">
                    <?php foreach ($activeWorkflows as $wf): ?>
                        <div class="col-12 col-md-6">
                            <div class="p-3 border rounded-3 h-100 d-flex flex-column justify-content-between bg-light-subtle hover-border-primary transition">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <div class="brand-icon" style="width: 32px; height: 32px; font-size: 1rem;">
                                            <i class="bi <?= htmlspecialchars($wf['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($wf['title'], ENT_QUOTES, 'UTF-8') ?></h6>
                                            <span class="badge bg-secondary-subtle text-secondary small" style="font-size: 0.68rem;"><?= htmlspecialchars($wf['category'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                    </div>
                                    <p class="small text-muted mb-3">
                                        <?= htmlspecialchars($wf['description'], ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                </div>
                                <a href="/requests/create?workflow_id=<?= $wf['id'] ?>" class="btn btn-sm btn-outline-primary w-100">
                                    <i class="bi bi-pencil-square"></i> Initialize Requisition
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Cryptographic Audit Stream & System State -->
    <div class="col-12 col-xl-4">
        <!-- Cryptographic Audit Stream -->
        <div class="enterprise-card mb-4">
            <div class="card-header-custom">
                <h3 class="card-title-custom">
                    <i class="bi bi-shield-lock-fill text-success"></i>
                    <span>Cryptographic Audit Log</span>
                </h3>
                <span class="badge bg-success-subtle text-success small">SHA-256 Verified</span>
            </div>
            <div class="card-body-custom p-3">
                <div class="audit-timeline">
                    <?php if (empty($recentAuditStream)): ?>
                        <div class="text-center text-muted py-3 small">No audit entries logged yet.</div>
                    <?php else: ?>
                        <?php foreach ($recentAuditStream as $audit): ?>
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="badge bg-light text-dark border font-monospace small" style="font-size: 0.72rem;">
                                        <?= htmlspecialchars($audit['action'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <span class="small text-muted" style="font-size: 0.72rem;">
                                        <?= date('M d, H:i', strtotime($audit['timestamp'])) ?>
                                    </span>
                                </div>
                                <div class="fw-semibold text-dark small">
                                    <a href="/requests/show?id=<?= $audit['request_id'] ?>" class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($audit['tracking_code'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                    &bull; <?= htmlspecialchars($audit['user_name'], ENT_QUOTES, 'UTF-8') ?>
                                    <span class="text-muted fw-normal">(<?= htmlspecialchars($audit['user_role'], ENT_QUOTES, 'UTF-8') ?>)</span>
                                </div>
                                <?php if (!empty($audit['comment'])): ?>
                                    <div class="small text-muted mt-1 fst-italic" style="font-size: 0.8rem;">
                                        &ldquo;<?= htmlspecialchars(substr($audit['comment'], 0, 90) . (strlen($audit['comment']) > 90 ? '...' : ''), ENT_QUOTES, 'UTF-8') ?>&rdquo;
                                    </div>
                                <?php endif; ?>
                                <div class="mt-1 d-flex align-items-center gap-1">
                                    <i class="bi bi-check2-circle text-success small"></i>
                                    <span class="font-monospace text-muted" style="font-size: 0.65rem;">
                                        <?= substr($audit['signature_hash'] ?? '', 0, 24) ?>...
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- RBAC Governance Matrix Widget -->
        <div class="enterprise-card">
            <div class="card-header-custom">
                <h3 class="card-title-custom">
                    <i class="bi bi-diagram-3 text-info"></i>
                    <span>RBAC State Machine Rules</span>
                </h3>
            </div>
            <div class="card-body-custom p-3 small">
                <div class="mb-2 pb-2 border-bottom">
                    <strong class="d-block text-dark">Requester Role:</strong>
                    <span class="text-muted">Drafts and initiates workflows; views own requisitions.</span>
                </div>
                <div class="mb-2 pb-2 border-bottom">
                    <strong class="d-block text-dark">Operations Manager:</strong>
                    <span class="text-muted">Tier-1 operational review, team schedule & budget alignment.</span>
                </div>
                <div class="mb-2 pb-2 border-bottom">
                    <strong class="d-block text-dark">Chief Financial Officer (CFO):</strong>
                    <span class="text-muted">Mandatory fiscal clearance for expenditures exceeding <strong>$5,000.00</strong>.</span>
                </div>
                <div>
                    <strong class="d-block text-dark">System Administrator:</strong>
                    <span class="text-muted">Executive overrides, SecOps clearances, ERP integration, and visual workflow building.</span>
                </div>
            </div>
        </div>
    </div>
</div>
