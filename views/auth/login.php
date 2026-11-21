<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Enterprise Login - ApexGuild', ENT_QUOTES, 'UTF-8') ?></title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="/assets/css/enterprise.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .login-card-container {
            width: 100%;
            max-width: 980px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        @media (min-width: 992px) {
            .login-card-container {
                flex-direction: row;
            }
            .login-form-side {
                width: 48%;
            }
            .demo-switcher-side {
                width: 52%;
                background: #f8fafc;
                border-left: 1px solid #e2e8f0;
            }
        }
        .demo-user-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem 1.15rem;
            margin-bottom: 0.75rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
            text-align: left;
            width: 100%;
        }
        .demo-user-card:hover {
            border-color: #3b82f6;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }
    </style>
</head>
<body>

<div class="login-card-container">
    <!-- Left: Credentials Login Form -->
    <div class="login-form-side p-4 p-md-5 d-flex flex-direction-column justify-content-center">
        <div>
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="brand-icon" style="width:44px; height:44px; font-size:1.4rem;">
                    <i class="bi bi-diagram-3-fill"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-0 text-dark" style="letter-spacing:-0.03em;">ApexGuild</h3>
                    <div class="text-muted small fw-semibold text-uppercase tracking-wider">Enterprise Workflow & RBAC</div>
                </div>
            </div>

            <h4 class="fw-bold mb-1">Corporate Single Sign-On</h4>
            <p class="text-muted small mb-4">Sign in to manage multi-tier approvals and dynamic state machine workflows.</p>

            <!-- Flashes -->
            <?php if (!empty($flashes)): ?>
                <?php foreach ($flashes as $type => $messages): ?>
                    <?php foreach ($messages as $msg): ?>
                        <div class="alert alert-<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show small mb-3" role="alert">
                            <?= $msg ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>

            <form action="/login" method="POST">
                <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">

                <div class="mb-3">
                    <label for="emailInput" class="form-label small fw-semibold text-secondary text-uppercase tracking-wider">Work Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                        <input type="email" id="emailInput" name="email" class="form-control enterprise-input" placeholder="admin@apexguild.com" required value="admin@apexguild.com">
                    </div>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between">
                        <label for="passInput" class="form-label small fw-semibold text-secondary text-uppercase tracking-wider">Password</label>
                        <span class="small text-muted">Demo: admin123</span>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-key"></i></span>
                        <input type="password" id="passInput" name="password" class="form-control enterprise-input" placeholder="••••••••" required value="admin123">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="bi bi-shield-lock me-1"></i> Sign In to ApexGuild
                </button>
            </form>

            <div class="mt-4 pt-3 border-top text-center text-muted small">
                <i class="bi bi-lock-fill text-success me-1"></i> End-to-end audit trail secured with SHA-256 HMAC digital signatures.
            </div>
        </div>
    </div>

    <!-- Right: 1-Click Demo Profiles -->
    <div class="demo-switcher-side p-4 p-md-5">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="fw-bold mb-0 text-dark">1-Click Demo Switcher</h5>
                <p class="text-muted small mb-0">Experience different RBAC roles instantly:</p>
            </div>
            <span class="badge bg-primary-subtle text-primary fw-bold text-uppercase" style="font-size:0.7rem;">Interactive</span>
        </div>

        <!-- Requester -->
        <form action="/auth/switch-role" method="POST" class="m-0">
            <input type="hidden" name="role" value="Requester">
            <input type="hidden" name="redirect_to" value="/dashboard">
            <button type="submit" class="demo-user-card">
                <div class="avatar-circle" style="background:#3b82f6; width:44px; height:44px;">A</div>
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center justify-content-between">
                        <strong class="text-dark">Alex Chen</strong>
                        <span class="badge bg-secondary-subtle text-secondary small">Requester</span>
                    </div>
                    <div class="text-muted small">Innovation &bull; Creates dynamic POs & Leave requests</div>
                </div>
                <i class="bi bi-arrow-right-short text-primary fs-4"></i>
            </button>
        </form>

        <!-- Manager -->
        <form action="/auth/switch-role" method="POST" class="m-0">
            <input type="hidden" name="role" value="Manager">
            <input type="hidden" name="redirect_to" value="/dashboard">
            <button type="submit" class="demo-user-card">
                <div class="avatar-circle" style="background:#d97706; width:44px; height:44px;">M</div>
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center justify-content-between">
                        <strong class="text-dark">Marcus Thorne</strong>
                        <span class="badge bg-warning-subtle text-warning-emphasis small">Manager</span>
                    </div>
                    <div class="text-muted small">Engineering Ops &bull; Tier-1 operational sign-offs</div>
                </div>
                <i class="bi bi-arrow-right-short text-warning fs-4"></i>
            </button>
        </form>

        <!-- CFO -->
        <form action="/auth/switch-role" method="POST" class="m-0">
            <input type="hidden" name="role" value="CFO">
            <input type="hidden" name="redirect_to" value="/dashboard">
            <button type="submit" class="demo-user-card">
                <div class="avatar-circle" style="background:#059669; width:44px; height:44px;">S</div>
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center justify-content-between">
                        <strong class="text-dark">Sophia Sterling</strong>
                        <span class="badge bg-success-subtle text-success small">CFO</span>
                    </div>
                    <div class="text-muted small">Treasury &bull; High-threshold requisitions (&gt; $5,000)</div>
                </div>
                <i class="bi bi-arrow-right-short text-success fs-4"></i>
            </button>
        </form>

        <!-- Admin -->
        <form action="/auth/switch-role" method="POST" class="m-0">
            <input type="hidden" name="role" value="Admin">
            <input type="hidden" name="redirect_to" value="/dashboard">
            <button type="submit" class="demo-user-card">
                <div class="avatar-circle" style="background:#7c3aed; width:44px; height:44px;">E</div>
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center justify-content-between">
                        <strong class="text-dark">Eleanor Vance</strong>
                        <span class="badge bg-primary small">Admin</span>
                    </div>
                    <div class="text-muted small">Governance &bull; System builder & override authority</div>
                </div>
                <i class="bi bi-arrow-right-short text-primary fs-4"></i>
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
