<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="login-wrapper">
    <div class="login-card animate-fade-in-up">
        <div class="brand-icon"><i class="fas fa-bolt"></i></div>
        <h2><?= APP_NAME ?></h2>
        <p class="login-subtitle">Sign in to your account</p>

        <?php if (!empty($error)): ?>
        <div class="alert alert-danger py-2" style="font-size:0.85rem;border-radius:0.5rem;">
            <i class="fas fa-exclamation-circle me-1"></i> <?= Helper::escape($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= APP_URL ?>/index.php?page=login" id="loginForm" novalidate>
            <?= CSRF::field() ?>
            <div class="mb-3">
                <label class="form-label">Username or Email</label>
                <div class="input-group">
                    <span class="input-group-text" style="background:rgba(255,255,255,0.06);border-color:rgba(255,255,255,0.1);color:#858796;">
                        <i class="fas fa-user"></i>
                    </span>
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
                    <span class="input-group-text" style="background:rgba(255,255,255,0.06);border-color:rgba(255,255,255,0.1);color:#858796;">
                        <i class="fas fa-lock"></i>
                    </span>
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
                        style="background:rgba(255,255,255,0.06);border-color:rgba(255,255,255,0.1);color:#858796;cursor:pointer;"
                        aria-label="Toggle password visibility"
                    >
                        <i class="fas fa-eye"></i>
                    </button>
                    <div class="invalid-feedback w-100">Please enter your password.</div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-sign-in-alt me-2"></i> Sign In
            </button>
        </form>

        <div class="text-center mt-3" style="font-size:0.8rem;color:#858796;">
            <a href="<?= APP_URL ?>/index.php?page=signup" class="btn btn-outline-success btn-sm me-2">
                <i class="fas fa-user-plus me-1"></i>Sign Up Free
            </a>
            <a href="<?= APP_URL ?>/index.php?page=demo_login" class="btn btn-outline-info btn-sm">
                <i class="fas fa-play-circle me-1"></i>Try Demo
            </a>
            <div class="mt-2">
                <a href="<?= APP_URL ?>/index.php?page=pricing" style="color:#858796;text-decoration:none;">
                    <i class="fas fa-tag me-1"></i>View Pricing
                </a>
            </div>
        </div>
    </div>
</div>

<script nonce="<?= $cspNonce ?? '' ?>">
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
});
</script>
</body>
</html>
