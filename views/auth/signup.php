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
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= APP_URL ?>/index.php?page=signup" id="signupForm" autocomplete="off">
            <?= CSRF::field() ?>

            <div class="mb-3">
                <label class="form-label">Company / Shop Name *</label>
                <input type="text" class="form-control" name="company_name" 
                    value="<?= Helper::escape($formData['companyName'] ?? '') ?>" 
                    placeholder="e.g. Sharma Electronics" required minlength="2">
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Your Full Name *</label>
                    <input type="text" class="form-control" name="full_name" 
                        value="<?= Helper::escape($formData['ownerName'] ?? '') ?>"
                        placeholder="e.g. Rahul Sharma" required minlength="2">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" name="phone" 
                        value="<?= Helper::escape($formData['phone'] ?? '') ?>"
                        placeholder="e.g. 9876543210">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Email Address *</label>
                <input type="email" class="form-control" name="email" 
                    value="<?= Helper::escape($formData['email'] ?? '') ?>"
                    placeholder="you@example.com" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Username *</label>
                <input type="text" class="form-control" name="username" 
                    value="<?= Helper::escape($formData['username'] ?? '') ?>"
                    placeholder="Choose a username (lowercase)" required minlength="3" pattern="[a-z0-9_]+">
                <div class="form-text" style="color:#6c757d;font-size:0.75rem;">Lowercase letters, numbers, and underscores only.</div>
            </div>

            <div class="mb-3">
                <label class="form-label">Referral Code (Optional)</label>
                <input type="text" class="form-control" name="referral_code"
                    value="<?= Helper::escape($formData['referralCode'] ?? ($_GET['ref'] ?? '')) ?>"
                    placeholder="e.g. ABC123XYZ">
                <div class="form-text" style="color:#6c757d;font-size:0.75rem;">If someone invited you, enter their code.</div>
            </div>

            <div class="row g-2 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Password *</label>
                    <input type="password" class="form-control" name="password" placeholder="Min 6 characters" required minlength="6">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Confirm Password *</label>
                    <input type="password" class="form-control" name="confirm_password" placeholder="Repeat password" required>
                </div>
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
</body>
</html>
