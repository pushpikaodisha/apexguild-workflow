<?php
use ApexGuild\Core\Session;
use ApexGuild\Core\Database;

$currentUser = Session::user();
$currentRole = $currentUser['role'] ?? 'Guest';
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

// Get pending approval count for current role
$db = Database::getInstance();
if ($currentRole === 'Admin') {
    $pendingBadge = (int)($db->fetch("SELECT COUNT(*) as c FROM requests WHERE status = 'In_Review'")['c'] ?? 0);
} elseif ($currentRole === 'Requester') {
    $pendingBadge = 0;
} else {
    $pendingBadge = (int)($db->fetch("SELECT COUNT(*) as c FROM requests WHERE status = 'In_Review' AND assigned_role = ?", [$currentRole])['c'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'ApexGuild Enterprise Workflow System', ENT_QUOTES, 'UTF-8') ?></title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Custom Enterprise Styles -->
    <link href="/assets/css/enterprise.css" rel="stylesheet">
</head>
<body>

<div class="app-container">
    <!-- Sidebar Navigation -->
    <aside class="app-sidebar" id="appSidebar">
        <div class="sidebar-brand">
            <a href="/dashboard" class="brand-logo">
                <div class="brand-icon">
                    <i class="bi bi-diagram-3-fill"></i>
                </div>
                <div>
                    <div>ApexGuild</div>
                    <div class="brand-badge">Enterprise RBAC</div>
                </div>
            </a>
        </div>

        <div class="sidebar-menu">
            <div class="menu-section-label">Operations</div>
            <a href="/dashboard" class="nav-link-custom <?= $currentPath === '/dashboard' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Executive Dashboard</span>
            </a>

            <?php if ($currentRole !== 'Requester'): ?>
            <a href="/approvals" class="nav-link-custom <?= $currentPath === '/approvals' ? 'active' : '' ?>">
                <i class="bi bi-check2-circle"></i>
                <span>Approval Inbox</span>
                <?php if ($pendingBadge > 0): ?>
                    <span class="nav-badge bg-warning text-dark"><?= $pendingBadge ?></span>
                <?php endif; ?>
            </a>
            <?php endif; ?>

            <div class="menu-section-label">Requisitions</div>
            <a href="/requests/create" class="nav-link-custom <?= $currentPath === '/requests/create' ? 'active' : '' ?>">
                <i class="bi bi-plus-circle"></i>
                <span>New Requisition</span>
            </a>
            <a href="/requests" class="nav-link-custom <?= $currentPath === '/requests' ? 'active' : '' ?>">
                <i class="bi bi-folder2-open"></i>
                <span>Enterprise Registry</span>
            </a>
            <a href="/requests/my" class="nav-link-custom <?= $currentPath === '/requests/my' ? 'active' : '' ?>">
                <i class="bi bi-person-lines-fill"></i>
                <span>My Requisitions</span>
            </a>

            <div class="menu-section-label">Workflows & Governance</div>
            <a href="/workflows" class="nav-link-custom <?= $currentPath === '/workflows' ? 'active' : '' ?>">
                <i class="bi bi-diagram-2"></i>
                <span>Workflow Pipelines</span>
            </a>

            <?php if ($currentRole === 'Admin'): ?>
            <a href="/workflows/create" class="nav-link-custom <?= $currentPath === '/workflows/create' ? 'active' : '' ?>">
                <i class="bi bi-gear-wide-connected"></i>
                <span>Visual Step Builder</span>
            </a>
            <?php endif; ?>
        </div>

        <!-- Sidebar User Footer -->
        <div class="sidebar-footer">
            <div class="user-profile-widget">
                <div class="avatar-circle">
                    <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="user-info-text">
                    <div class="user-name-title"><?= htmlspecialchars($currentUser['name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="user-role-badge"><?= htmlspecialchars($currentRole, ENT_QUOTES, 'UTF-8') ?> &bull; <?= htmlspecialchars($currentUser['department'] ?? 'Corp', ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <a href="/logout" class="text-white-50 hover-text-white ms-1" title="Sign Out">
                    <i class="bi bi-box-arrow-right fs-5"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Application Area -->
    <div class="app-main">
        <!-- Topbar -->
        <header class="app-topbar">
            <div class="topbar-left">
                <button type="button" class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle navigation">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="page-breadcrumb-title"><?= htmlspecialchars($title ?? 'Overview', ENT_QUOTES, 'UTF-8') ?></h1>
            </div>

            <div class="topbar-right">
                <!-- 1-Click Role Quick Switcher -->
                <div class="dropdown role-switcher-dropdown">
                    <button class="role-pill-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="role-dot"></span>
                        <span>Role: <strong><?= htmlspecialchars($currentRole, ENT_QUOTES, 'UTF-8') ?></strong></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-2" style="min-width: 280px; border-radius: 12px;">
                        <li class="px-2 py-1 text-muted small fw-bold text-uppercase">1-Click Demo Role Switcher</li>
                        <li><hr class="dropdown-divider my-1"></li>
                        
                        <li>
                            <form action="/auth/switch-role" method="POST" class="m-0">
                                <input type="hidden" name="role" value="Requester">
                                <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/dashboard', ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="dropdown-item d-flex align-items-center gap-2 py-2 rounded <?= $currentRole === 'Requester' ? 'active' : '' ?>">
                                    <div class="avatar-circle" style="width:28px;height:28px;font-size:0.75rem;">A</div>
                                    <div>
                                        <div class="fw-semibold">Alex Chen</div>
                                        <div class="small text-muted">Requester &bull; Innovation</div>
                                    </div>
                                    <?php if ($currentRole === 'Requester'): ?><i class="bi bi-check2 ms-auto"></i><?php endif; ?>
                                </button>
                            </form>
                        </li>

                        <li>
                            <form action="/auth/switch-role" method="POST" class="m-0">
                                <input type="hidden" name="role" value="Manager">
                                <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/dashboard', ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="dropdown-item d-flex align-items-center gap-2 py-2 rounded <?= $currentRole === 'Manager' ? 'active' : '' ?>">
                                    <div class="avatar-circle" style="width:28px;height:28px;font-size:0.75rem;background:#d97706;">M</div>
                                    <div>
                                        <div class="fw-semibold">Marcus Thorne</div>
                                        <div class="small text-muted">Operations Manager</div>
                                    </div>
                                    <?php if ($currentRole === 'Manager'): ?><i class="bi bi-check2 ms-auto"></i><?php endif; ?>
                                </button>
                            </form>
                        </li>

                        <li>
                            <form action="/auth/switch-role" method="POST" class="m-0">
                                <input type="hidden" name="role" value="CFO">
                                <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/dashboard', ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="dropdown-item d-flex align-items-center gap-2 py-2 rounded <?= $currentRole === 'CFO' ? 'active' : '' ?>">
                                    <div class="avatar-circle" style="width:28px;height:28px;font-size:0.75rem;background:#059669;">S</div>
                                    <div>
                                        <div class="fw-semibold">Sophia Sterling</div>
                                        <div class="small text-muted">CFO (High-Cap Approver)</div>
                                    </div>
                                    <?php if ($currentRole === 'CFO'): ?><i class="bi bi-check2 ms-auto"></i><?php endif; ?>
                                </button>
                            </form>
                        </li>

                        <li>
                            <form action="/auth/switch-role" method="POST" class="m-0">
                                <input type="hidden" name="role" value="Admin">
                                <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/dashboard', ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="dropdown-item d-flex align-items-center gap-2 py-2 rounded <?= $currentRole === 'Admin' ? 'active' : '' ?>">
                                    <div class="avatar-circle" style="width:28px;height:28px;font-size:0.75rem;background:#7c3aed;">E</div>
                                    <div>
                                        <div class="fw-semibold">Eleanor Vance</div>
                                        <div class="small text-muted">System Administrator</div>
                                    </div>
                                    <?php if ($currentRole === 'Admin'): ?><i class="bi bi-check2 ms-auto"></i><?php endif; ?>
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>

                <a href="/logout" class="btn btn-outline-secondary btn-sm" title="Log Out">
                    <i class="bi bi-box-arrow-right"></i>
                    <span class="d-none d-sm-inline">Sign Out</span>
                </a>
            </div>
        </header>

        <!-- Main Body Content -->
        <main class="app-content">
            <!-- Flash Notifications -->
            <?php if (!empty($flashes)): ?>
                <?php foreach ($flashes as $type => $messages): ?>
                    <?php foreach ($messages as $msg): ?>
                        <div class="alert alert-<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm" role="alert">
                            <i class="bi bi-info-circle-fill fs-5"></i>
                            <div><?= $msg ?></div>
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- View Payload -->
            <?= $content ?? '' ?>
        </main>
    </div>
</div>

<!-- Global Decision Modal (Approve / Reject) -->
<div class="modal fade" id="decisionModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
            <div class="modal-header border-bottom bg-light px-4 py-3">
                <h5 class="modal-title fw-bold" id="modalTitle">Authorization Decision</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/approvals/action" method="POST" id="decisionModalForm">
                <div class="modal-body p-4">
                    <input type="hidden" name="_csrf" value="<?= Session::csrfToken() ?>">
                    <input type="hidden" name="request_id" id="modalRequestId" value="">
                    <input type="hidden" name="decision" id="modalDecision" value="">
                    <input type="hidden" name="signature_data" id="signatureDataInput" value="">

                    <div class="mb-3">
                        <label for="modalComment" class="form-label fw-semibold text-secondary small text-uppercase" id="modalCommentLabel">
                            Audit Endorsement / Comments
                        </label>
                        <textarea class="form-control enterprise-input" id="modalComment" name="comment" rows="3" placeholder="Enter comments or audit rationale..."></textarea>
                    </div>

                    <!-- Digital Signature Pad -->
                    <div id="signatureCanvasContainer" class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label fw-semibold text-secondary small text-uppercase mb-0">
                                <i class="bi bi-pen"></i> Digital Signature & Identity Seal
                            </label>
                            <button type="button" class="btn btn-link btn-sm text-muted p-0 text-decoration-none" id="clearSigBtn">
                                <i class="bi bi-eraser"></i> Clear Pad
                            </button>
                        </div>
                        <div style="border: 2px dashed #cbd5e1; border-radius: 8px; background: #ffffff; text-align: center; position: relative;">
                            <canvas id="signatureCanvas" width="440" height="110" style="touch-action: none; cursor: crosshair; max-width: 100%;"></canvas>
                            <div class="text-muted small pb-1" style="font-size: 0.72rem;">Draw digital signature above or sign with active HMAC security key</div>
                        </div>
                    </div>

                    <div class="bg-light p-2 px-3 rounded small text-muted border">
                        <i class="bi bi-shield-lock-fill text-success me-1"></i>
                        Signing as: <strong><?= htmlspecialchars($currentUser['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong> (<?= htmlspecialchars($currentRole, ENT_QUOTES, 'UTF-8') ?>). Timestamp & SHA-256 HMAC digest recorded into immutable audit logs.
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3 border-top d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="modalSubmitBtn">Confirm & Sign</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap 5.3 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Enterprise Application JS -->
<script src="/assets/js/enterprise.js"></script>
</body>
</html>
