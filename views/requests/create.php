<?php
/**
 * Dynamic Requisition Submission View
 */
?>

<div class="mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h2 class="fw-bold mb-1" style="letter-spacing: -0.02em;">Submit Enterprise Requisition</h2>
            <p class="text-muted small mb-0">Complete dynamic requisition specifications to initiate the multi-tier approval state machine.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="/requests" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Registry
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Dynamic Requisition Form -->
    <div class="col-12 col-lg-8">
        <div class="enterprise-card">
            <div class="card-header-custom bg-light-subtle d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <div class="brand-icon" style="width: 38px; height: 38px;">
                        <i class="bi <?= htmlspecialchars($workflow['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0 text-dark" style="font-size: 1.1rem;"><?= htmlspecialchars($workflow['title'], ENT_QUOTES, 'UTF-8') ?></h4>
                        <span class="badge bg-secondary-subtle text-secondary small"><?= htmlspecialchars($workflow['category'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>

                <!-- Template Selector Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        Switch Template
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                        <?php foreach ($allWorkflows as $w): ?>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 <?= $w['id'] === $workflow['id'] ? 'active' : '' ?>" href="/requests/create?workflow_id=<?= $w['id'] ?>">
                                    <i class="bi <?= htmlspecialchars($w['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                    <span><?= htmlspecialchars($w['title'], ENT_QUOTES, 'UTF-8') ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="card-body-custom p-4">
                <form action="/requests/store" method="POST" enctype="multipart/form-data" id="dynamicRequestForm">
                    <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">
                    <input type="hidden" name="workflow_id" value="<?= $workflow['id'] ?>">

                    <!-- Standard Requisition Metadata -->
                    <div class="row g-3 mb-4 pb-3 border-bottom">
                        <div class="col-12 col-md-8">
                            <label for="reqTitle" class="form-label small fw-semibold text-secondary text-uppercase tracking-wider">
                                Requisition Summary / Subject <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="reqTitle" name="title" class="form-control enterprise-input" 
                                   value="<?= htmlspecialchars($titleVal ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                   placeholder="e.g. Q3 Dedicated Inference Hardware Procurement" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="reqPriority" class="form-label small fw-semibold text-secondary text-uppercase tracking-wider">
                                Priority Level <span class="text-danger">*</span>
                            </label>
                            <select id="reqPriority" name="priority" class="form-select enterprise-input">
                                <option value="Low" <?= ($priorityVal ?? '') === 'Low' ? 'selected' : '' ?>>Low (Standard Batch)</option>
                                <option value="Medium" <?= ($priorityVal ?? 'Medium') === 'Medium' ? 'selected' : '' ?>>Medium (Normal Priority)</option>
                                <option value="High" <?= ($priorityVal ?? '') === 'High' ? 'selected' : '' ?>>High (Time Sensitive)</option>
                                <option value="Urgent" <?= ($priorityVal ?? '') === 'Urgent' ? 'selected' : '' ?>>Urgent (Executive Expedited)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Dynamic Fields Compiled from JSON Schema -->
                    <div class="mb-4">
                        <h5 class="fw-bold mb-3 text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-ui-checks-grid text-primary"></i>
                            <span>Requisition Attributes</span>
                        </h5>
                        <?= $formHtml ?>
                    </div>

                    <!-- Submission Action Buttons -->
                    <div class="d-flex align-items-center justify-content-between pt-3 border-top flex-wrap gap-2">
                        <button type="submit" name="submit_action" value="save_draft" class="btn btn-outline-secondary">
                            <i class="bi bi-save"></i> Save as Draft
                        </button>
                        <div class="d-flex gap-2">
                            <a href="/dashboard" class="btn btn-link text-muted text-decoration-none">Cancel</a>
                            <button type="submit" name="submit_action" value="submit_now" class="btn btn-primary px-4 fw-semibold">
                                <i class="bi bi-send-check"></i> Submit for Tier-1 Approval
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Pipeline Lifecycle & RBAC Guidance -->
    <div class="col-12 col-lg-4">
        <!-- Approval Pipeline Preview -->
        <div class="enterprise-card mb-4">
            <div class="card-header-custom">
                <h3 class="card-title-custom">
                    <i class="bi bi-signpost-split text-info"></i>
                    <span>Sequential Approval Pipeline</span>
                </h3>
            </div>
            <div class="card-body-custom p-3">
                <p class="text-muted small mb-3">
                    Upon submission, this requisition will progress through the following deterministic authorization tiers:
                </p>

                <div class="list-group list-group-flush border rounded-3 overflow-hidden">
                    <?php foreach ($steps as $idx => $step): ?>
                        <div class="list-group-item p-3 bg-light-subtle">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="badge bg-primary rounded-pill small">Tier <?= $step['step_number'] ?? ($idx + 1) ?></span>
                                <span class="badge bg-white text-dark border small fw-semibold">
                                    Role: <?= htmlspecialchars($step['approver_role'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                            <div class="fw-semibold text-dark small mt-1">
                                <?= htmlspecialchars($step['step_name'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <?php if (!empty($step['description'])): ?>
                                <div class="text-muted small" style="font-size: 0.75rem;">
                                    <?= htmlspecialchars($step['description'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($step['condition']) && $step['condition'] === 'amount_gt_5000'): ?>
                                <div class="text-warning small mt-1 fw-semibold" style="font-size: 0.72rem;">
                                    <i class="bi bi-exclamation-triangle"></i> Mandatory for items &gt; $5,000 USD
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Security & Audit Guarantee -->
        <div class="enterprise-card">
            <div class="card-body-custom p-3 text-center">
                <div class="brand-icon mx-auto mb-2" style="background:#ecfdf5; color:#059669; width:48px; height:48px; font-size:1.5rem;">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h6 class="fw-bold mb-1 text-dark">Cryptographic Integrity Sealed</h6>
                <p class="text-muted small mb-0">
                    All form payload attributes are hashed upon submission. Every subsequent approval action generates a SHA-256 HMAC digital seal permanently bound to the requester's corporate identity.
                </p>
            </div>
        </div>
    </div>
</div>
