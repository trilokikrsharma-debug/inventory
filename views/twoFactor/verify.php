<?php $flash = Session::getFlash(); ?>
<div class="container d-flex justify-content-center align-items-center" style="min-height:80vh;">
    <div class="card shadow-sm" style="max-width:420px; width:100%;">
        <div class="card-header bg-primary text-white text-center">
            <i class="fas fa-key fa-2x mb-2"></i>
            <h5 class="mb-0">Two-Factor Verification</h5>
        </div>
        <div class="card-body p-4">
            <?php if (!empty($flash['error'])): ?>
                <div class="alert alert-danger small"><?= htmlspecialchars($flash['error']) ?></div>
            <?php endif; ?>

            <p class="text-muted text-center small mb-4">
                Enter the 6-digit code from your authenticator app.
            </p>

            <form method="POST" action="<?= APP_URL ?>/index.php?page=twoFactor&action=verifyPost" id="otpVerifyForm">
                <?= CSRF::field() ?>
                
                <div class="mb-4">
                    <input type="text" name="otp_code" class="form-control form-control-lg text-center"
                           maxlength="6" pattern="[0-9]{6}" placeholder="000000"
                           autocomplete="one-time-code" inputmode="numeric" required autofocus
                           style="letter-spacing:10px; font-size:28px; font-weight:bold;">
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3">
                    <i class="fas fa-check me-1"></i> Verify
                </button>
            </form>

            <div class="text-center">
                <a href="<?= APP_URL ?>/index.php?page=twoFactor&action=recovery" class="text-muted small">
                    <i class="fas fa-life-ring me-1"></i> Use a recovery code instead
                </a>
            </div>
        </div>
    </div>
</div>
