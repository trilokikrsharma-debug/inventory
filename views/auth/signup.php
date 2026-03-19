<?php
$errors = is_array($errors ?? null) ? $errors : [];
$minPasswordLength = defined('PASSWORD_MIN_LENGTH') ? max(6, (int)PASSWORD_MIN_LENGTH) : 6;
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | <?= APP_NAME ?></title>
    <meta name="description" content="Create your free <?= APP_NAME ?> account. Inventory & billing for small businesses in India.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .signup-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
            padding: 2rem 1rem;
        }
        .signup-card {
            background: rgba(30, 30, 60, 0.95);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 1.2rem;
            padding: 2.5rem;
            width: 100%;
            max-width: 520px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            backdrop-filter: blur(20px);
        }
        .signup-card h2 { color: #fff; margin-bottom: 0.25rem; font-weight: 700; }
        .signup-subtitle { color: #858796; margin-bottom: 1.5rem; }
        .signup-card .form-control {
            background: rgba(255,255,255,0.06);
            border-color: rgba(255,255,255,0.1);
            color: #fff;
            border-radius: 0.5rem;
        }
        .signup-card .form-control:focus {
            background: rgba(255,255,255,0.1);
            border-color: #4e73df;
            color: #fff;
            box-shadow: 0 0 0 0.2rem rgba(78,115,223,0.25);
        }
        .signup-card .form-label { color: #b7b9cc; font-weight: 500; font-size: 0.85rem; }
        .signup-card .btn-primary {
            width: 100%;
            padding: 0.75rem;
            font-weight: 600;
            border-radius: 0.5rem;
            background: linear-gradient(135deg, #4e73df, #224abe);
            border: none;
            transition: all 0.3s;
        }
        .signup-card .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(78,115,223,0.4);
        }
        .divider { display: flex; align-items: center; margin: 1.5rem 0; }
        .divider::before, .divider::after { content: ''; flex: 1; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .divider span { padding: 0 1rem; color: #858796; font-size: 0.8rem; }
    </style>
</head>
<body>
<div class="signup-wrapper">
    <div class="signup-card animate-fade-in-up">
        <div class="text-center mb-3">
            <div class="brand-icon"><i class="fas fa-bolt"></i></div>
            <h2><?= APP_NAME ?></h2>
            <p class="signup-subtitle">Create your free account</p>
        </div>

        <?php if (!empty($error)): ?>
        <div class="alert alert-danger py-2" style="font-size:0.85rem;border-radius:0.5rem;">
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

        <form method="POST" action="<?= APP_URL ?>/index.php?page=signup" id="signupForm" autocomplete="off" novalidate>
            <?= CSRF::field() ?>

            <div class="mb-3">
                <label class="form-label">Company / Shop Name *</label>
                <input
                    type="text"
                    class="form-control <?= isset($errors['company_name']) ? 'is-invalid' : '' ?>"
                    name="company_name"
                    value="<?= Helper::escape($formData['companyName'] ?? '') ?>"
                    placeholder="e.g. Sharma Electronics"
                    required
                    minlength="2"
                    maxlength="120"
                >
                <div class="invalid-feedback"><?= Helper::escape($errors['company_name'] ?? 'Company name is required.') ?></div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Your Full Name *</label>
                    <input
                        type="text"
                        class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>"
                        name="full_name"
                        value="<?= Helper::escape($formData['ownerName'] ?? '') ?>"
                        placeholder="e.g. Rahul Sharma"
                        required
                        minlength="2"
                        maxlength="120"
                    >
                    <div class="invalid-feedback"><?= Helper::escape($errors['full_name'] ?? 'Full name is required.') ?></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone Number</label>
                    <input
                        type="tel"
                        class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                        name="phone"
                        value="<?= Helper::escape($formData['phone'] ?? '') ?>"
                        placeholder="e.g. 9876543210"
                        inputmode="tel"
                        maxlength="20"
                        pattern="\+?[0-9\s\-()]{7,20}"
                    >
                    <div class="invalid-feedback"><?= Helper::escape($errors['phone'] ?? 'Please enter a valid phone number.') ?></div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Email Address *</label>
                <input
                    type="email"
                    class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                    name="email"
                    value="<?= Helper::escape($formData['email'] ?? '') ?>"
                    placeholder="you@example.com"
                    required
                    maxlength="190"
                    autocomplete="email"
                >
                <div class="invalid-feedback"><?= Helper::escape($errors['email'] ?? 'Please enter a valid email address.') ?></div>
            </div>

            <div class="mb-3">
                <label class="form-label">Username *</label>
                <input
                    type="text"
                    class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                    name="username"
                    id="signupUsername"
                    value="<?= Helper::escape($formData['username'] ?? '') ?>"
                    placeholder="Choose a username (lowercase)"
                    required
                    minlength="3"
                    maxlength="40"
                    pattern="[a-z0-9_]{3,40}"
                    autocomplete="username"
                >
                <div class="form-text" style="color:#6c757d;font-size:0.75rem;">Lowercase letters, numbers, and underscores only.</div>
                <div class="invalid-feedback"><?= Helper::escape($errors['username'] ?? 'Username must be 3-40 lowercase characters.') ?></div>
            </div>

            <div class="mb-3">
                <label class="form-label">Referral Code (Optional)</label>
                <input
                    type="text"
                    class="form-control <?= isset($errors['referral_code']) ? 'is-invalid' : '' ?>"
                    name="referral_code"
                    value="<?= Helper::escape($formData['referralCode'] ?? ($_GET['ref'] ?? '')) ?>"
                    placeholder="e.g. ABC123XYZ"
                    maxlength="40"
                    pattern="[A-Za-z0-9_-]{4,40}"
                >
                <div class="form-text" style="color:#6c757d;font-size:0.75rem;">If someone invited you, enter their code.</div>
                <div class="invalid-feedback"><?= Helper::escape($errors['referral_code'] ?? 'Referral code format is invalid.') ?></div>
            </div>

            <div class="row g-2 mb-2">
                <div class="col-md-6">
                    <label class="form-label">Password *</label>
                    <div class="input-group">
                        <input
                            type="password"
                            class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                            id="signupPassword"
                            name="password"
                            placeholder="Min <?= (int)$minPasswordLength ?> characters"
                            required
                            minlength="<?= (int)$minPasswordLength ?>"
                            autocomplete="new-password"
                        >
                        <button
                            class="input-group-text"
                            type="button"
                            data-toggle-target="signupPassword"
                            style="background:rgba(255,255,255,0.06);border-color:rgba(255,255,255,0.1);color:#858796;cursor:pointer;"
                            aria-label="Toggle password visibility"
                        >
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback"><?= Helper::escape($errors['password'] ?? "Password must be at least {$minPasswordLength} characters.") ?></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Confirm Password *</label>
                    <div class="input-group">
                        <input
                            type="password"
                            class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>"
                            id="signupConfirmPassword"
                            name="confirm_password"
                            placeholder="Repeat password"
                            required
                            minlength="<?= (int)$minPasswordLength ?>"
                            autocomplete="new-password"
                        >
                        <button
                            class="input-group-text"
                            type="button"
                            data-toggle-target="signupConfirmPassword"
                            style="background:rgba(255,255,255,0.06);border-color:rgba(255,255,255,0.1);color:#858796;cursor:pointer;"
                            aria-label="Toggle confirm password visibility"
                        >
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback"><?= Helper::escape($errors['confirm_password'] ?? 'Passwords must match.') ?></div>
                </div>
            </div>
            <div class="form-text mb-4" style="color:#6c757d;font-size:0.75rem;">
                Use at least <?= (int)$minPasswordLength ?> characters. Include uppercase + number if your security policy requires it.
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-rocket me-2"></i>Create My Account
            </button>
        </form>

        <div class="divider"><span>OR</span></div>

        <div class="text-center">
            <a href="<?= APP_URL ?>/index.php?page=demo_login" class="btn btn-outline-info btn-sm me-2">
                <i class="fas fa-play-circle me-1"></i>Try Demo
            </a>
            <a href="<?= APP_URL ?>/index.php?page=login" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-sign-in-alt me-1"></i>Already have account? Login
            </a>
        </div>

        <div class="text-center mt-3">
            <a href="<?= APP_URL ?>/index.php?page=pricing" style="color:#858796;font-size:0.8rem;text-decoration:none;">
                <i class="fas fa-tag me-1"></i>View Pricing Plans
            </a>
        </div>
    </div>
</div>

<script nonce="<?= $cspNonce ?? '' ?>">
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
});
</script>
</body>
</html>
