<?php
require_once __DIR__ . '/../public/_partials/brand.php';

$assets = tsa_brand_assets();
$nonce = htmlspecialchars($GLOBALS['csp_nonce'] ?? ($cspNonce ?? ''), ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?= Helper::escape(APP_NAME) ?></title>
    <meta name="robots" content="noindex,nofollow">
    <link rel="icon" type="image/svg+xml" href="<?= Helper::escape($assets['favicon']) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= Helper::escape($assets['brand_css']) ?>">
</head>
<body class="tsa-public tsa-auth-page">
<main class="tsa-auth-shell">
    <div class="tsa-container">
        <div class="tsa-auth-grid">
            <aside class="tsa-auth-side">
                <div class="tsa-auth-brand"><a href="<?= APP_URL ?>/"><img src="<?= Helper::escape($assets['logo_light']) ?>" alt="<?= Helper::escape(APP_NAME) ?>"></a></div>
                <div class="tsa-eyebrow"><span class="dot"></span>Workspace Access</div>
                <h3>Sign in to keep billing, inventory, and operations moving.</h3>
                <p>Use your workspace login to access invoices, stock movement, reports, and role-based controls.</p>
                <div class="tsa-auth-trust">
                    <div class="tsa-pill"><i class="fas fa-shield-halved"></i> Enterprise-grade security & encrypted sessions</div>
                    <div class="tsa-pill"><i class="fas fa-boxes-stacked"></i> Inventory, billing, dues, and reports in one place</div>
                    <div class="tsa-pill"><i class="fas fa-users-gear"></i> Multi-user access for staff, managers, and owners</div>
                </div>
                <div class="tsa-auth-links">
                    <a href="<?= APP_URL ?>/signup">Create account</a>
                    <a href="<?= APP_URL ?>/pricing">See pricing</a>
                    <a href="<?= APP_URL ?>/demo">Instant Demo Access</a>
                </div>
            </aside>

            <section class="tsa-auth-card animate-fade-in-up">
                <h2><?= Helper::escape(APP_NAME) ?></h2>
                <p class="tsa-auth-subtitle">Sign in to your account</p>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2" style="font-size:0.85rem;">
                        <i class="fas fa-exclamation-circle me-1"></i> <?= Helper::escape($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= APP_URL ?>/login" id="loginForm" novalidate>
                    <?= CSRF::field() ?>
                    <div class="mb-3">
                        <label class="form-label">Username or Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input
                                type="text"
                                class="form-control"
                                name="username"
                                value="<?= Helper::escape($username ?? '') ?>"
                                placeholder="Enter username or email"
                                required
                                autofocus
                                autocomplete="username"
                            >
                            <div class="invalid-feedback w-100">Please enter your username or email.</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input
                                type="password"
                                class="form-control"
                                name="password"
                                placeholder="Enter password"
                                required
                                id="passwordField"
                                autocomplete="current-password"
                            >
                            <button
                                class="input-group-text"
                                type="button"
                                id="togglePassword"
                                aria-label="Toggle password visibility"
                            >
                                <i class="fas fa-eye"></i>
                            </button>
                            <div class="invalid-feedback w-100">Please enter your password.</div>
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="rememberMe" name="remember_me">
                        <label class="form-check-label" for="rememberMe">Keep me signed in on this device</label>
                    </div>

                    <button type="submit" class="tsa-btn tsa-btn-primary tsa-btn-block">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                </form>

                <div class="tsa-sep"></div>

                <div class="tsa-auth-note" style="line-height:1.75; font-size:0.86rem; margin-bottom: 24px;">Instant Demo Access signs you into a sample workspace with broader modules enabled so you can review the product flow before creating an account. Simple, structured, reliable.</div>
                <div class="tsa-auth-links">
                    <a href="<?= APP_URL ?>/signup">Sign Up</a>
                    <a href="<?= APP_URL ?>/demo">Instant Demo Access</a>
                    <a href="<?= APP_URL ?>/">Home</a>
                    <a href="<?= APP_URL ?>/pricing">Pricing</a>
                </div>
            </section>
        </div>
    </div>
</main>

<script nonce="<?= $nonce ?>">
document.addEventListener('DOMContentLoaded', function () {
    const togglePasswordBtn = document.getElementById('togglePassword');
    const passwordField = document.getElementById('passwordField');
    const loginForm = document.getElementById('loginForm');

    if (togglePasswordBtn && passwordField) {
        togglePasswordBtn.addEventListener('click', function () {
            const icon = this.querySelector('i');
            const show = passwordField.type === 'password';
            passwordField.type = show ? 'text' : 'password';
            if (icon) {
                icon.classList.toggle('fa-eye', !show);
                icon.classList.toggle('fa-eye-slash', show);
            }
        });
    }

    if (loginForm) {
        loginForm.addEventListener('submit', function (event) {
            if (!loginForm.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            loginForm.classList.add('was-validated');
        });
    }

    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
});
</script>
</body>
</html>
