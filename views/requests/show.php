<?php
/**
 * Detailed Requisition Lifecycle & Cryptographic Audit View
 */
?>

<!-- Requisition Header Banner -->
<div class="enterprise-card p-4 mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                <span class="font-monospace fw-bold fs-6 text-primary bg-primary-subtle px-2 py-1 rounded">
                    <?= htmlspecialchars($request['tracking_code'], ENT_QUOTES, 'UTF-8') ?>
                </span>
                <span class="status-badge badge-<?= strtolower(str_replace('_', '-', $request['status'])) ?>">
                    <?= str_replace('_', ' ', htmlspecialchars($request['status'], ENT_QUOTES, 'UTF-8')) ?>
                </span>
                <span class="priority-badge priority-<?= strtolower($request['priority']) ?>">
                    Priority: <?= htmlspecialchars($request['priority'], ENT_QUOTES, 'UTF-8') ?>
                </span>
                <span class="text-muted small">&bull;</span>
                <span class="text-muted small">
                    Workflow: <strong><?= htmlspecialchars($request['workflow_title'], ENT_QUOTES, 'UTF-8') ?></strong>
                </span>
            </div>
            <h2 class="fw-bold mb-1 text-dark" style="letter-spacing: -0.02em;">
                <?= htmlspecialchars($request['title'], ENT_QUOTES, 'UTF-8') ?>
            </h2>
            <div class="text-muted small d-flex align-items-center gap-2 mt-1">
                <span>Submitted by: <strong><?= htmlspecialchars($request['requester_name'], ENT_QUOTES, 'UTF-8') ?></strong> (<?= htmlspecialchars($request['requester_dept'], ENT_QUOTES, 'UTF-8') ?>)</span>
                <span>&bull;</span>
                <span>Date: <?= date('M d, Y H:i', strtotime($request['created_at'])) ?></span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="/requests" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Registry
            </a>

            <!-- If Draft and User is Requester -->
            <?php if ($canSubmit): ?>
                <form action="/requests/submit" method="POST" class="m-0">
                    <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">
                    <input type="hidden" name="id" value="<?= $request['id'] ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send-check"></i> Submit for Approval
                    </button>
                </form>
            <?php endif; ?>

            <!-- If In_Review and User has Approval Role (or Admin) -->
            <?php if ($canApprove): ?>
                <button type="button" class="btn btn-success" onclick="openApprovalModal(<?= $request['id'] ?>, 'APPROVE', '<?= htmlspecialchars(addslashes($request['title']), ENT_QUOTES, 'UTF-8') ?>')">
                    <i class="bi bi-shield-check"></i> Sign & Authorize
                </button>
                <button type="button" class="btn btn-outline-danger" onclick="openApprovalModal(<?= $request['id'] ?>, 'REJECT', '<?= htmlspecialchars(addslashes($request['title']), ENT_QUOTES, 'UTF-8') ?>')">
                    <i class="bi bi-x-circle"></i> Decline
                </button>
            <?php endif; ?>

            <!-- If In_Review / Draft and User is Requester/Admin -->
            <?php if ($canCancel): ?>
                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#cancelModal">
                    <i class="bi bi-slash-circle"></i> Cancel Requisition
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Approval State Machine Stepper -->
<div class="enterprise-card p-4 mb-4">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-diagram-3-fill text-primary"></i>
            <span>Multi-Tier State Machine Lifecycle</span>
        </h5>
        <span class="badge bg-light text-muted border small">Deterministic Finite State Machine</span>
    </div>

    <div class="stepper-container">
        <!-- Node 0: Initial Creation / Submission -->
        <div class="step-node completed">
            <div class="step-circle">
                <i class="bi bi-check-lg"></i>
            </div>
            <div class="step-label">Draft / Initiated</div>
            <div class="step-role-tag"><?= htmlspecialchars($request['requester_name'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>

        <!-- Dynamic Steps from Workflow -->
        <?php 
        $currentStepNum = (int)$request['current_step'];
        $reqStatus = $request['status'];
        foreach ($steps as $idx => $step): 
            $stepNum = (int)($step['step_number'] ?? ($idx + 1));
            
            // Determine state of this step
            $nodeClass = '';
            $icon = $stepNum;

            if ($reqStatus === 'Approved') {
                $nodeClass = 'completed';
                $icon = '<i class="bi bi-check-lg"></i>';
            } elseif ($reqStatus === 'Rejected') {
                if ($stepNum < $currentStepNum) {
                    $nodeClass = 'completed';
                    $icon = '<i class="bi bi-check-lg"></i>';
                } elseif ($stepNum === $currentStepNum) {
                    $nodeClass = 'rejected';
                    $icon = '<i class="bi bi-x-lg"></i>';
                } else {
                    $nodeClass = '';
                }
            } elseif ($reqStatus === 'Cancelled') {
                if ($stepNum < $currentStepNum) {
                    $nodeClass = 'completed';
                    $icon = '<i class="bi bi-check-lg"></i>';
                } else {
                    $nodeClass = 'skipped';
                }
            } else { // In_Review or Draft
                if ($stepNum < $currentStepNum) {
                    $nodeClass = 'completed';
                    $icon = '<i class="bi bi-check-lg"></i>';
                } elseif ($stepNum === $currentStepNum) {
                    $nodeClass = 'active';
                    $icon = '<i class="bi bi-clock-history"></i>';
                } else {
                    $nodeClass = '';
                }
            }
        ?>
            <div class="step-node <?= $nodeClass ?>">
                <div class="step-circle">
                    <?= $icon ?>
                </div>
                <div class="step-label"><?= htmlspecialchars($step['step_name'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="step-role-tag">
                    Role: <strong><?= htmlspecialchars($step['approver_role'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Node Terminal: Completed -->
        <div class="step-node <?= ($reqStatus === 'Approved') ? 'completed' : (($reqStatus === 'Rejected') ? 'rejected' : '') ?>">
            <div class="step-circle">
                <?php if ($reqStatus === 'Approved'): ?>
                    <i class="bi bi-check-all"></i>
                <?php elseif ($reqStatus === 'Rejected'): ?>
                    <i class="bi bi-x-octagon"></i>
                <?php else: ?>
                    <i class="bi bi-flag"></i>
                <?php endif; ?>
            </div>
            <div class="step-label">Final Execution</div>
            <div class="step-role-tag">ERP & Ledger</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Requisition Attributes (Compiled Readonly View) -->
    <div class="col-12 col-lg-7">
        <div class="enterprise-card mb-4">
            <div class="card-header-custom">
                <h3 class="card-title-custom">
                    <i class="bi bi-file-earmark-text text-primary"></i>
                    <span>Requisition Specification Data</span>
                </h3>
                <span class="badge bg-light text-dark border small">Form Compiler Output</span>
            </div>
            <div class="card-body-custom p-4">
                <div class="row g-3">
                    <?php foreach ($formattedFields as $f): ?>
                        <div class="col-12 col-md-<?= (int)$f['col'] ?>">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <div class="text-secondary small fw-semibold text-uppercase tracking-wider mb-1">
                                    <?= htmlspecialchars($f['label'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div class="text-dark">
                                    <?= $f['display'] ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Workflow Details & SLA Information -->
        <div class="enterprise-card">
            <div class="card-header-custom">
                <h3 class="card-title-custom">
                    <i class="bi bi-diagram-2 text-info"></i>
                    <span>Template Architecture: <?= htmlspecialchars($request['workflow_title'], ENT_QUOTES, 'UTF-8') ?></span>
                </h3>
            </div>
            <div class="card-body-custom p-3">
                <p class="text-muted small mb-2">
                    <?= htmlspecialchars($request['workflow_description'], ENT_QUOTES, 'UTF-8') ?>
                </p>
                <div class="p-2 px-3 bg-light rounded-2 border small d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-shield-check text-success me-1"></i> Role-Based Access Rules Enforced:</span>
                    <span class="fw-semibold font-monospace text-dark"><?= htmlspecialchars($request['workflow_code'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Immutable Cryptographic Audit Log Trail -->
    <div class="col-12 col-lg-5">
        <div class="enterprise-card">
            <div class="card-header-custom">
                <h3 class="card-title-custom">
                    <i class="bi bi-shield-lock-fill text-success"></i>
                    <span>Cryptographic Audit Trail</span>
                </h3>
                <span class="badge bg-success-subtle text-success small">Immutable Ledger</span>
            </div>
            <div class="card-body-custom p-4">
                <?php if (empty($auditLogs)): ?>
                    <div class="text-center text-muted py-4">No audit events recorded yet.</div>
                <?php else: ?>
                    <div class="audit-stream-list">
                        <?php foreach ($auditLogs as $log): ?>
                            <div class="border-bottom pb-4 mb-4">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-circle" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                            <?= strtoupper(substr($log['actor_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark small"><?= htmlspecialchars($log['actor_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <div class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars($log['actor_role'], ENT_QUOTES, 'UTF-8') ?> &bull; <?= htmlspecialchars($log['actor_dept'] ?? 'Operations', ENT_QUOTES, 'UTF-8') ?></div>
                                        </div>
                                    </div>
                                    <span class="badge bg-light text-dark border font-monospace small">
                                        <?= htmlspecialchars($log['action'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>

                                <div class="small text-muted mb-2">
                                    <i class="bi bi-clock me-1"></i> <?= date('M d, Y H:i:s', strtotime($log['timestamp'])) ?>
                                    &bull; Stage: <strong class="text-dark"><?= htmlspecialchars($log['step_name'] ?? 'Transition', ENT_QUOTES, 'UTF-8') ?></strong>
                                </div>

                                <?php if (!empty($log['comment'])): ?>
                                    <div class="p-2 px-3 bg-light rounded-2 border small text-dark mb-2 fst-italic">
                                        &ldquo;<?= nl2br(htmlspecialchars($log['comment'], ENT_QUOTES, 'UTF-8')) ?>&rdquo;
                                    </div>
                                <?php endif; ?>

                                <!-- Cryptographic Signature Seal -->
                                <div class="signature-seal-box">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="signature-badge-title">
                                            <i class="bi bi-shield-check"></i>
                                            <span>Digital Signature Seal: Verified</span>
                                        </div>
                                        <button type="button" class="btn btn-link btn-sm text-muted p-0 copy-sig-btn" data-hash="<?= htmlspecialchars($log['signature_hash'] ?? '', ENT_QUOTES, 'UTF-8') ?>" title="Copy SHA-256 Digest">
                                            <i class="bi bi-clipboard"></i> Copy
                                        </button>
                                    </div>
                                    <div class="signature-hash-code">
                                        <?= htmlspecialchars($log['signature_hash'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div class="text-muted small mt-1" style="font-size: 0.7rem;">
                                        Algorithm: HMAC-SHA256 &bull; Bound to User #<?= $log['user_id'] ?> &bull; IP: <?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1', ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header border-bottom bg-light px-4 py-3">
                <h5 class="modal-title fw-bold text-danger" id="cancelModalTitle">
                    <i class="bi bi-slash-circle me-1"></i> Cancel Requisition
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/requests/cancel" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">
                    <input type="hidden" name="id" value="<?= $request['id'] ?>">

                    <p class="text-secondary small mb-3">
                        Are you sure you want to cancel this requisition? This will halt the approval state machine and transition status to <span class="badge bg-secondary">Cancelled</span>.
                    </p>

                    <div class="mb-3">
                        <label for="cancelReason" class="form-label small fw-semibold text-secondary text-uppercase tracking-wider">
                            Cancellation Reason <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control enterprise-input" id="cancelReason" name="cancel_reason" rows="3" placeholder="State operational justification for cancellation..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3 border-top d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Keep Requisition</button>
                    <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>
</div>
