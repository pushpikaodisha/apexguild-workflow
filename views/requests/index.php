<?php
/**
 * Enterprise Requisition Registry View
 */
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h2 class="fw-bold mb-1" style="letter-spacing: -0.02em;">
            <?= !empty($isMyOnly) ? 'My Requisitions' : 'Enterprise Requisition Registry' ?>
        </h2>
        <p class="text-muted small mb-0">
            <?= !empty($isMyOnly) ? 'Track the status and digital approval lifecycle of your submitted workflows.' : 'Audit and monitor all enterprise requisitions across operational, fiscal, and security pipelines.' ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="/requests/create" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> New Requisition
        </a>
    </div>
</div>

<!-- Filters Bar -->
<div class="enterprise-card p-3 mb-4 bg-light-subtle">
    <form action="<?= !empty($isMyOnly) ? '/requests/my' : '/requests' ?>" method="GET" class="row g-2 align-items-center">
        <div class="col-12 col-md-4">
            <div class="input-group">
                <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" name="q" class="form-control enterprise-input border-start-0" placeholder="Search tracking code, title, requester..." value="<?= htmlspecialchars($searchQuery ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <div class="col-6 col-md-3">
            <select name="status" class="form-select enterprise-input">
                <option value="">All Statuses</option>
                <option value="Draft" <?= ($filterStatus ?? '') === 'Draft' ? 'selected' : '' ?>>Draft</option>
                <option value="In_Review" <?= ($filterStatus ?? '') === 'In_Review' ? 'selected' : '' ?>>In Review</option>
                <option value="Approved" <?= ($filterStatus ?? '') === 'Approved' ? 'selected' : '' ?>>Approved</option>
                <option value="Rejected" <?= ($filterStatus ?? '') === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                <option value="Cancelled" <?= ($filterStatus ?? '') === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>

        <div class="col-6 col-md-3">
            <select name="workflow_id" class="form-select enterprise-input">
                <option value="0">All Workflows</option>
                <?php foreach ($workflows as $w): ?>
                    <option value="<?= $w['id'] ?>" <?= ((int)($filterWf ?? 0) === (int)$w['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($w['title'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-filter"></i> Filter
            </button>
            <a href="<?= !empty($isMyOnly) ? '/requests/my' : '/requests' ?>" class="btn btn-outline-secondary" title="Reset Filters">
                <i class="bi bi-arrow-clockwise"></i>
            </a>
        </div>
    </form>
</div>

<!-- Requisitions Table -->
<div class="enterprise-card">
    <div class="table-responsive">
        <table class="enterprise-table">
            <thead>
                <tr>
                    <th>Tracking Code</th>
                    <th>Workflow</th>
                    <th>Requisition Title</th>
                    <th>Requester</th>
                    <th>Priority</th>
                    <th>Current Stage</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 text-secondary d-block mb-2"></i>
                            No requisitions match the current criteria.
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
                                <div class="d-flex align-items-center gap-1 small text-dark fw-semibold">
                                    <i class="bi <?= htmlspecialchars($req['workflow_icon'] ?? 'bi-diagram-3', ENT_QUOTES, 'UTF-8') ?> text-primary"></i>
                                    <span><?= htmlspecialchars($req['workflow_title'] ?? 'Workflow', ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold text-truncate" style="max-width: 220px;" title="<?= htmlspecialchars($req['title'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($req['title'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </td>
                            <td>
                                <span class="small text-secondary"><?= htmlspecialchars($req['requester_name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td>
                                <span class="priority-badge priority-<?= strtolower($req['priority']) ?>">
                                    <?= htmlspecialchars($req['priority'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td>
                                <div class="small text-dark fw-semibold">
                                    <?= htmlspecialchars($req['current_step_name'] ?? 'Stage', ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <?php if (!empty($req['assigned_role'])): ?>
                                    <span class="badge bg-light text-muted border" style="font-size: 0.68rem;">
                                        Role: <?= htmlspecialchars($req['assigned_role'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge badge-<?= strtolower(str_replace('_', '-', $req['status'])) ?>">
                                    <?= str_replace('_', ' ', htmlspecialchars($req['status'], ENT_QUOTES, 'UTF-8')) ?>
                                </span>
                            </td>
                            <td>
                                <span class="small text-muted"><?= date('M d, Y', strtotime($req['created_at'])) ?></span>
                            </td>
                            <td class="text-end">
                                <a href="/requests/show?id=<?= $req['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                    Inspect <i class="bi bi-chevron-right"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
