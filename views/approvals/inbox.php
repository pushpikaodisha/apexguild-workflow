<?php
/**
 * Executive Approval Inbox View
 */
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h2 class="fw-bold mb-1" style="letter-spacing: -0.02em;">
            <?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?> Authorization Inbox
        </h2>
        <p class="text-muted small mb-0">Review pending requisitions requiring your corporate authorization tier.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/requests" class="btn btn-outline-secondary">
            <i class="bi bi-folder2-open"></i> All Requisitions
        </a>
    </div>
</div>

<!-- Executive Summary Metrics -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="p-3 bg-white rounded-3 border d-flex align-items-center gap-3 shadow-sm">
            <div class="brand-icon icon-amber" style="width:48px;height:48px;font-size:1.5rem;">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div>
                <div class="text-muted small fw-semibold text-uppercase">Awaiting Your Action</div>
                <div class="fs-4 fw-bold text-dark"><?= count($requests) ?> Requisitions</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="p-3 bg-white rounded-3 border d-flex align-items-center gap-3 shadow-sm">
            <div class="brand-icon icon-rose" style="width:48px;height:48px;font-size:1.5rem;">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div>
                <div class="text-muted small fw-semibold text-uppercase">High / Urgent Priority</div>
                <div class="fs-4 fw-bold text-dark"><?= $urgentCount ?> Requisitions</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="p-3 bg-white rounded-3 border d-flex align-items-center gap-3 shadow-sm">
            <div class="brand-icon icon-emerald" style="width:48px;height:48px;font-size:1.5rem;">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div>
                <div class="text-muted small fw-semibold text-uppercase">Financial Commitment</div>
                <div class="fs-4 fw-bold text-dark">$<?= number_format($totalAmount, 2) ?> USD</div>
            </div>
        </div>
    </div>
</div>

<!-- Authority Policy Callout -->
<?php if ($role === 'CFO'): ?>
    <div class="alert alert-warning border-warning d-flex align-items-center gap-2 mb-4 p-3 shadow-sm">
        <i class="bi bi-shield-check fs-4 text-warning"></i>
        <div>
            <strong>CFO Fiscal Governance Active:</strong> You hold executive authorization authority for major expenditures exceeding <strong>$5,000.00 USD</strong>. Requisitions at or below this threshold are automatically routed past the CFO tier directly to procurement fulfillment.
        </div>
    </div>
<?php elseif ($role === 'Manager'): ?>
    <div class="alert alert-info border-info d-flex align-items-center gap-2 mb-4 p-3 shadow-sm">
        <i class="bi bi-info-circle fs-4 text-info"></i>
        <div>
            <strong>Operations Manager Tier Active:</strong> You are the primary reviewer for departmental requisitions, evaluating operational necessity, team availability, and budget allocation before escalating.
        </div>
    </div>
<?php endif; ?>

<!-- Pending Approval Items Table -->
<div class="enterprise-card">
    <div class="card-header-custom">
        <h3 class="card-title-custom">
            <i class="bi bi-check2-square text-primary"></i>
            <span>Action Required: Requisitions Queue</span>
        </h3>
        <span class="badge bg-primary-subtle text-primary fw-semibold"><?= count($requests) ?> Pending</span>
    </div>

    <div class="table-responsive">
        <table class="enterprise-table">
            <thead>
                <tr>
                    <th>Tracking Code</th>
                    <th>Requisition & Workflow</th>
                    <th>Requester</th>
                    <th>Value / Key Data</th>
                    <th>Priority</th>
                    <th>SLA Countdown</th>
                    <th class="text-end">Authorizations</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-check-circle-fill text-success fs-2 d-block mb-2"></i>
                            Your approval queue is completely clear! No actions currently pending.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($requests as $req): ?>
                        <tr>
                            <td>
                                <a href="/requests/show?id=<?= $req['id'] ?>" class="fw-bold font-monospace text-primary text-decoration-none">
                                    <?= htmlspecialchars($req['tracking_code'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </td>
                            <td>
                                <div class="fw-semibold text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($req['title'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($req['title'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div class="small text-muted d-flex align-items-center gap-1">
                                    <i class="bi <?= htmlspecialchars($req['workflow_icon'] ?? 'bi-diagram-3', ENT_QUOTES, 'UTF-8') ?>"></i>
                                    <span><?= htmlspecialchars($req['workflow_title'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="small fw-semibold text-dark"><?= htmlspecialchars($req['requester_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="small text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars($req['requester_dept'], ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td>
                                <?php if (isset($req['form_data']['amount'])): ?>
                                    <strong class="text-success fs-6">$<?= number_format((float)$req['form_data']['amount'], 2) ?></strong>
                                    <?php if ((float)$req['form_data']['amount'] > 5000): ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis small d-block" style="font-size: 0.65rem;">&gt; $5k CFO Threshold</span>
                                    <?php endif; ?>
                                <?php elseif (isset($req['form_data']['days_count'])): ?>
                                    <span class="badge bg-info-subtle text-info fw-semibold"><?= htmlspecialchars($req['form_data']['days_count'], ENT_QUOTES, 'UTF-8') ?> Days Leave</span>
                                <?php elseif (isset($req['form_data']['clearance_level'])): ?>
                                    <span class="badge bg-purple-subtle text-purple fw-semibold"><?= htmlspecialchars($req['form_data']['clearance_level'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">&mdash;</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="priority-badge priority-<?= strtolower($req['priority']) ?>">
                                    <?= htmlspecialchars($req['priority'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($req['is_sla_breached']): ?>
                                    <span class="badge bg-danger text-white">
                                        <i class="bi bi-exclamation-circle me-1"></i><?= $req['sla_display'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-clock-history text-primary me-1"></i><?= $req['sla_display'] ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-success" onclick="openApprovalModal(<?= $req['id'] ?>, 'APPROVE', '<?= htmlspecialchars(addslashes($req['title']), ENT_QUOTES, 'UTF-8') ?>')">
                                        <i class="bi bi-check-lg"></i> Sign & Authorize
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="openApprovalModal(<?= $req['id'] ?>, 'REJECT', '<?= htmlspecialchars(addslashes($req['title']), ENT_QUOTES, 'UTF-8') ?>')">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                    <a href="/requests/show?id=<?= $req['id'] ?>" class="btn btn-sm btn-outline-secondary" title="View Full Lifecycle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
