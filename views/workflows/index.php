<?php
/**
 * Workflow Catalog & Pipelines View
 */
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h2 class="fw-bold mb-1" style="letter-spacing: -0.02em;">Workflow Templates & Pipelines</h2>
        <p class="text-muted small mb-0">Browse configured enterprise approval pipelines, step definitions, and dynamic schemas.</p>
    </div>
    <div class="d-flex gap-2">
        <?php if ($userRole === 'Admin'): ?>
            <a href="/workflows/create" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Build New Workflow Pipeline
            </a>
        <?php endif; ?>
        <a href="/requests/create" class="btn btn-outline-secondary">
            <i class="bi bi-pencil-square"></i> Submit Requisition
        </a>
    </div>
</div>

<div class="row g-4">
    <?php foreach ($workflows as $wf): ?>
        <div class="col-12 col-lg-6 col-xl-4">
            <div class="enterprise-card h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="card-header-custom bg-light-subtle d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <div class="brand-icon" style="width: 36px; height: 36px; font-size: 1.15rem;">
                                <i class="bi <?= htmlspecialchars($wf['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0 text-dark" style="font-size: 1rem;"><?= htmlspecialchars($wf['title'], ENT_QUOTES, 'UTF-8') ?></h5>
                                <span class="badge bg-secondary-subtle text-secondary small" style="font-size: 0.68rem;"><?= htmlspecialchars($wf['category'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                        <?php if ($wf['is_active']): ?>
                            <span class="badge bg-success-subtle text-success small">Active</span>
                        <?php else: ?>
                            <span class="badge bg-danger-subtle text-danger small">Archived</span>
                        <?php endif; ?>
                    </div>

                    <div class="card-body-custom">
                        <p class="text-muted small mb-3">
                            <?= htmlspecialchars($wf['description'], ENT_QUOTES, 'UTF-8') ?>
                        </p>

                        <!-- Step Pipeline Preview -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small fw-bold text-uppercase text-secondary tracking-wider" style="font-size: 0.72rem;">Approval Tier Pipeline</span>
                                <span class="badge bg-light text-muted border small"><?= count($wf['steps']) ?> Steps</span>
                            </div>
                            <div class="list-group list-group-flush border rounded-3 overflow-hidden">
                                <?php foreach ($wf['steps'] as $idx => $step): ?>
                                    <div class="list-group-item px-3 py-2 d-flex align-items-center justify-content-between bg-light-subtle">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-primary text-white rounded-circle" style="width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.7rem;">
                                                <?= $step['step_number'] ?? ($idx + 1) ?>
                                            </span>
                                            <div>
                                                <div class="small fw-semibold text-dark"><?= htmlspecialchars($step['step_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                                <?php if (!empty($step['condition']) && $step['condition'] !== 'always'): ?>
                                                    <div class="text-warning small" style="font-size: 0.7rem;">
                                                        <i class="bi bi-funnel"></i> Condition: <?= htmlspecialchars($step['condition'], ENT_QUOTES, 'UTF-8') ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-white text-dark border small fw-semibold">
                                                <?= htmlspecialchars($step['approver_role'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                            <div class="text-muted small" style="font-size: 0.68rem;"><?= (int)($step['sla_hours'] ?? 24) ?>h SLA</div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Dynamic Fields Summary -->
                        <div class="d-flex justify-content-between align-items-center text-muted small p-2 bg-light rounded-2 border">
                            <span><i class="bi bi-ui-checks me-1"></i> Form Fields:</span>
                            <span class="fw-semibold text-dark"><?= count($wf['fields']) ?> dynamic attributes</span>
                        </div>
                    </div>
                </div>

                <div class="p-3 border-top bg-light-subtle d-flex gap-2">
                    <a href="/requests/create?workflow_id=<?= $wf['id'] ?>" class="btn btn-primary btn-sm flex-grow-1">
                        <i class="bi bi-play-circle"></i> Launch Requisition
                    </a>
                    <?php if ($userRole === 'Admin'): ?>
                        <form action="/workflows/toggle" method="POST" class="m-0">
                            <input type="hidden" name="id" value="<?= $wf['id'] ?>">
                            <button type="submit" class="btn btn-outline-secondary btn-sm" title="Toggle Active Status">
                                <i class="bi bi-power"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
