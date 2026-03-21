<?php $flash = Session::getFlash(); ?>
<div class="container d-flex justify-content-center align-items-center" style="min-height:80vh;">
    <div class="card shadow-sm" style="max-width:420px; width:100%;">
        <div class="card-header bg-warning text-dark text-center">
            <i class="fas fa-life-ring fa-2x mb-2"></i>
            <h5 class="mb-0">Recovery Code</h5>
        </div>
        <div class="card-body p-4">
            <?php if (!empty($flash['error'])): ?>
                <div class="alert alert-danger small"><?= htmlspecialchars($flash['error']) ?></div>
            <?php endif; ?>

            <p class="text-muted text-center small mb-4">
                Enter one of your backup recovery codes to sign in.
            </p>

            <form method="POST" action="<?= APP_URL ?>/index.php?page=twoFactor&action=recoveryPost" id="recoveryForm">
                <?= CSRF::field() ?>
                
                <div class="mb-4">
                    <input type="text" name="recovery_code" class="form-control form-control-lg text-center"
                           maxlength="9" placeholder="XXXX-XXXX"
                           autocomplete="off" required autofocus
                           style="letter-spacing:3px; font-size:20px; font-weight:bold; text-transform:uppercase;">
                </div>

                <button type="submit" class="btn btn-warning w-100 mb-3">
                    <i class="fas fa-unlock me-1"></i> Verify Recovery Code
                </button>
            </form>

            <div class="text-center">
                <a href="<?= APP_URL ?>/index.php?page=twoFactor&action=verify" class="text-muted small">
                    <i class="fas fa-arrow-left me-1"></i> Back to authenticator code
                </a>
            </div>
        </div>
    </div>
</div>
