<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?= Helper::escape(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #020617;
            background-image: 
                radial-gradient(circle at 15% 50%, rgba(99, 102, 241, 0.15), transparent 25%),
                radial-gradient(circle at 85% 30%, rgba(6, 182, 212, 0.15), transparent 25%);
            padding: 2rem 1rem;
            position: relative;
            overflow: hidden;
        }
        .login-wrapper::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(99,102,241,0.5), transparent);
        }
        .login-card {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 20px;
            padding: 3rem 2.5rem;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(20px);
            position: relative;
            z-index: 1;
        }
        .brand-icon {
            width: 48px; height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, #6366f1, #06b6d4);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem; color: #fff;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4);
        }
        .login-card h2 { color: #fff; font-weight: 800; text-align: center; margin-bottom: 0.5rem; font-size: 1.75rem; }
        .login-subtitle { color: #94a3b8; text-align: center; margin-bottom: 2rem; font-size: 0.95rem; }
        .form-label { color: #cbd5e1; font-weight: 500; font-size: 0.85rem; margin-bottom: 0.5rem; }
        .form-control {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            transition: all 0.2s;
        }
        .form-control:focus {
            background: rgba(255,255,255,0.08);
            border-color: #6366f1;
            color: #fff;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
        .input-group-text {
            background: rgba(255,255,255,0.02) !important;
            border-color: rgba(255,255,255,0.1) !important;
            color: #64748b !important;
            border-radius: 10px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            border: none;
            border-radius: 10px;
            padding: 0.75rem;
            font-weight: 600;
            margin-top: 1rem;
            transition: all 0.3s;
            display: flex; align-items: center; justify-content: center;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4);
        }
        .footer-links { text-align: center; margin-top: 2rem; display: flex; flex-direction: column; gap: 1rem; }
        .footer-links .row-flex { display: flex; justify-content: center; gap: 0.75rem; }
        .footer-links a.btn { border-radius: 8px; font-weight: 500; font-size: 0.85rem; padding: 0.5rem 1rem; }
        .btn-outline-success { color: #10b981; border-color: rgba(16,185,129,0.3); }
        .btn-outline-success:hover { background: rgba(16,185,129,0.1); color: #10b981; border-color: rgba(16,185,129,0.5); }
        .btn-outline-info { color: #06b6d4; border-color: rgba(6,182,212,0.3); }
        .btn-outline-info:hover { background: rgba(6,182,212,0.1); color: #06b6d4; border-color: rgba(6,182,212,0.5); }
        .text-link { color: #64748b; text-decoration: none; font-size: 0.85rem; transition: color 0.2s; }
        .text-link:hover { color: #cbd5e1; }
    </style>
</head>
<body>
<div class="login-wrapper">
    <div class="login-card animate-fade-in-up">
        <div class="brand-icon"><i class="fas fa-bolt"></i></div>
        <h2><?= Helper::escape(APP_NAME) ?></h2>
        <p class="login-subtitle">Sign in to your account</p>

        <?php if (!empty($error)): ?>
        <div class="alert alert-danger py-2" style="font-size:0.85rem;border-radius:0.5rem;">
            <i class="fas fa-exclamation-circle me-1"></i> <?= Helper::escape($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= APP_URL ?>/login" id="loginForm" novalidate>
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

        <div class="footer-links">
            <div class="row-flex">
                <a href="<?= APP_URL ?>/signup" class="btn btn-outline-success">
                    <i class="fas fa-user-plus me-1"></i>Sign Up
                </a>
                <a href="<?= APP_URL ?>/index.php?page=demo_login" class="btn btn-outline-info">
                    <i class="fas fa-play-circle me-1"></i>Try Live Demo
                </a>
            </div>
            <div>
                <a href="<?= APP_URL ?>/" class="text-link me-3"><i class="fas fa-home me-1"></i>Home</a>
                <a href="<?= APP_URL ?>/pricing" class="text-link"><i class="fas fa-tag me-1"></i>Pricing</a>
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

    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
});
</script>
</body>
</html>
