<?php
require_once __DIR__ . '/../public/_partials/brand.php';

$errors = is_array($errors ?? null) ? $errors : [];
$minPasswordLength = defined('PASSWORD_MIN_LENGTH') ? max(6, (int)PASSWORD_MIN_LENGTH) : 6;
$assets = tsa_brand_assets();
$nonce = htmlspecialchars($GLOBALS['csp_nonce'] ?? ($cspNonce ?? ''), ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php tsa_render_adsense_verification(); ?>
    <title>Sign Up | <?= Helper::escape(APP_NAME) ?></title>
    <meta name="description" content="Create your free <?= Helper::escape(APP_NAME) ?> account. Inventory & billing for small businesses in India.">
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
                <div class="tsa-eyebrow"><span class="dot"></span>Free Trial Setup</div>
                <h3>Create your workspace and start billing in minutes.</h3>
                <p>Set up your business account, invite your first user, and move products, customers, and invoices into one operating system.</p>
                <div class="tsa-auth-trust">
                    <div class="tsa-pill"><i class="fas fa-file-invoice"></i> GST billing designed for Indian SMEs</div>
                    <div class="tsa-pill"><i class="fas fa-boxes-stacked"></i> Inventory, suppliers, and dues tracking</div>
                    <div class="tsa-pill"><i class="fas fa-cloud-arrow-up"></i> Cloud workspace with role-aware access</div>
                </div>
                <div class="tsa-auth-links">
                    <a href="<?= APP_URL ?>/pricing">View pricing</a>
                    <a href="<?= APP_URL ?>/demo">Instant Demo Access</a>
                    <a href="<?= APP_URL ?>/login">Already have an account?</a>
                </div>
            </aside>

            <section class="tsa-auth-card animate-fade-in-up">
                <h2><?= Helper::escape(APP_NAME) ?></h2>
                <p class="tsa-auth-subtitle">Create your free account</p>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 tsa-auth-alert">
                        <i class="fas fa-exclamation-circle me-1"></i> <?= Helper::escape($error) ?>
                        <?php if (!empty($errors)): ?>
                            <ul class="mb-0 mt-2 ps-3 small">
                                <?php foreach ($errors as $fieldError): ?>
                                    <li><?= Helper::escape((string)$fieldError) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= APP_URL ?>/signup" id="signupForm" autocomplete="off" novalidate>
                    <?= CSRF::field() ?>

                    <div class="mb-3">
                        <label class="form-label">Company / Shop Name *</label>
                        <input type="text" class="form-control <?= isset($errors['company_name']) ? 'is-invalid' : '' ?>" name="company_name" value="<?= Helper::escape($formData['companyName'] ?? '') ?>" placeholder="e.g. Sharma Electronics" required minlength="2" maxlength="120">
                        <div class="invalid-feedback"><?= Helper::escape($errors['company_name'] ?? 'Company name is required.') ?></div>
                    </div>

                    <div class="tsa-auth-section">
                        <h4 class="tsa-auth-section-title">Owner Details</h4>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name *</label>
                                <input type="text" class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>" name="full_name" value="<?= Helper::escape($formData['ownerName'] ?? '') ?>" placeholder="e.g. Rahul Sharma" required minlength="2" maxlength="120">
                                <div class="invalid-feedback"><?= Helper::escape($errors['full_name'] ?? 'Full name is required.') ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" name="phone" value="<?= Helper::escape($formData['phone'] ?? '') ?>" placeholder="e.g. 9876543210" inputmode="tel" maxlength="20" pattern="\+?[0-9\s\-()]{7,20}">
                                <div class="invalid-feedback"><?= Helper::escape($errors['phone'] ?? 'Please enter a valid phone number.') ?></div>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Email Address *</label>
                            <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" name="email" value="<?= Helper::escape($formData['email'] ?? '') ?>" placeholder="you@example.com" required maxlength="190" autocomplete="email">
                            <div class="invalid-feedback"><?= Helper::escape($errors['email'] ?? 'Please enter a valid email address.') ?></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>" name="username" id="signupUsername" value="<?= Helper::escape($formData['username'] ?? '') ?>" placeholder="Choose a username (lowercase)" required minlength="3" maxlength="40" pattern="[a-z0-9_]{3,40}" autocomplete="username">
                        <div class="form-text">Lowercase letters, numbers, and underscores only.</div>
                        <div class="invalid-feedback"><?= Helper::escape($errors['username'] ?? 'Username must be 3-40 lowercase characters.') ?></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Referral Code (Optional)</label>
                        <input type="text" class="form-control <?= isset($errors['referral_code']) ? 'is-invalid' : '' ?>" name="referral_code" value="<?= Helper::escape((string)($formData['referralCode'] ?? ($_GET['ref'] ?? ''))) ?>" placeholder="e.g. ABC123XYZ" maxlength="40" pattern="[A-Za-z0-9_-]{4,40}">
                        <div class="form-text">If someone invited you, enter their code.</div>
                        <div class="invalid-feedback"><?= Helper::escape($errors['referral_code'] ?? 'Referral code format is invalid.') ?></div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label">Password *</label>
                            <div class="input-group">
                                <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="signupPassword" name="password" placeholder="Min <?= (int)$minPasswordLength ?> characters" required minlength="<?= (int)$minPasswordLength ?>" autocomplete="new-password">
                                <button class="input-group-text" type="button" data-toggle-target="signupPassword" aria-label="Toggle password visibility">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback"><?= Helper::escape($errors['password'] ?? "Password must be at least {$minPasswordLength} characters.") ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm Password *</label>
                            <div class="input-group">
                                <input type="password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" id="signupConfirmPassword" name="confirm_password" placeholder="Repeat password" required minlength="<?= (int)$minPasswordLength ?>" autocomplete="new-password">
                                <button class="input-group-text" type="button" data-toggle-target="signupConfirmPassword" aria-label="Toggle confirm password visibility">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback"><?= Helper::escape($errors['confirm_password'] ?? 'Passwords must match.') ?></div>
                        </div>
                    </div>
                    <div class="form-text mb-4">Use at least <?= (int)$minPasswordLength ?> characters. Include uppercase + number if your security policy requires it.</div>

                    <button type="submit" class="tsa-btn tsa-btn-primary tsa-btn-block">
                        <i class="fas fa-rocket"></i> Create My Account
                    </button>
                    <div class="text-center mt-3 tsa-auth-tagline">
                        Simple, structured, reliable. Built for real workflows.
                    </div>
                </form>

                <div class="tsa-sep"></div>

                <div class="tsa-auth-links">
                    <a href="<?= APP_URL ?>/demo">Instant Demo Access</a>
                    <a href="<?= APP_URL ?>/login">Login</a>
                    <a href="<?= APP_URL ?>/">Home</a>
                    <a href="<?= APP_URL ?>/pricing">Pricing Plans</a>
                </div>
            </section>
        </div>
    </div>
</main>

<script nonce="<?= $nonce ?>">
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('signupForm');
    const password = document.getElementById('signupPassword');
    const confirmPassword = document.getElementById('signupConfirmPassword');
    const username = document.getElementById('signupUsername');

    document.querySelectorAll('[data-toggle-target]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-toggle-target');
            const field = targetId ? document.getElementById(targetId) : null;
            if (!field) return;
            const icon = this.querySelector('i');
            const show = field.type === 'password';
            field.type = show ? 'text' : 'password';
            if (icon) {
                icon.classList.toggle('fa-eye', !show);
                icon.classList.toggle('fa-eye-slash', show);
            }
        });
    });

    if (username) {
        username.addEventListener('input', function () {
            this.value = this.value.toLowerCase();
        });
    }

    const syncPasswordMatch = function () {
        if (!password || !confirmPassword) return;
        if (confirmPassword.value && password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Passwords do not match.');
        } else {
            confirmPassword.setCustomValidity('');
        }
    };

    if (password && confirmPassword) {
        password.addEventListener('input', syncPasswordMatch);
        confirmPassword.addEventListener('input', syncPasswordMatch);
    }

    if (form) {
        form.addEventListener('submit', function (event) {
            syncPasswordMatch();
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
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
