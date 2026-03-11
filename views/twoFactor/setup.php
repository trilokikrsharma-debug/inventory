<?php $flash = Session::getFlash(); ?>
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex align-items-center">
                    <i class="fas fa-shield-alt me-2"></i>
                    <h5 class="mb-0">Two-Factor Authentication</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($flash['error'])): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($flash['error']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($flash['success'])): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($flash['success']) ?></div>
                    <?php endif; ?>

                    <?php if ($isEnabled): ?>
                        <!-- 2FA is currently enabled -->
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <span class="badge bg-success px-3 py-2 fs-6">
                                    <i class="fas fa-check-circle me-1"></i> 2FA is Enabled
                                </span>
                            </div>
                            <p class="text-muted">Your account is protected with two-factor authentication.</p>
                            
                            <hr>
                            <h6 class="text-danger mb-3">Disable Two-Factor Authentication</h6>
                            <form method="POST" action="<?= APP_URL ?>/index.php?page=twoFactor&action=disable" id="disable2faForm">
                                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= Session::get(CSRF_TOKEN_NAME) ?>">
                                <div class="mb-3" style="max-width:300px; margin:0 auto;">
                                    <input type="password" name="password" class="form-control" placeholder="Enter your password to confirm" required>
                                </div>
                                <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Are you sure? This will make your account less secure.')">
                                    <i class="fas fa-times-circle me-1"></i> Disable 2FA
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <!-- Setup 2FA -->
                        <div class="row">
                            <div class="col-md-6 text-center border-end">
                                <h6 class="mb-3">Step 1: Scan QR Code</h6>
                                <p class="text-muted small">Open your authenticator app (Google Authenticator, Authy, etc.) and scan this QR code:</p>
                                <div class="mb-3">
                                    <img src="<?= htmlspecialchars($qrUrl) ?>" alt="2FA QR Code" class="img-fluid border rounded" style="max-width:220px;">
                                </div>
                                <details class="text-start">
                                    <summary class="text-muted small cursor-pointer">Can't scan? Enter manually</summary>
                                    <div class="mt-2 p-2 bg-light rounded">
                                        <code class="user-select-all" style="word-break:break-all;"><?= htmlspecialchars($secret) ?></code>
                                    </div>
                                </details>
                            </div>
                            <div class="col-md-6">
                                <h6 class="mb-3">Step 2: Enter Verification Code</h6>
                                <p class="text-muted small">Enter the 6-digit code shown in your authenticator app:</p>
                                <form method="POST" action="<?= APP_URL ?>/index.php?page=twoFactor&action=enable" id="enable2faForm">
                                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= Session::get(CSRF_TOKEN_NAME) ?>">
                                    <div class="mb-3">
                                        <input type="text" name="otp_code" class="form-control form-control-lg text-center"
                                               maxlength="6" pattern="[0-9]{6}" placeholder="000000"
                                               autocomplete="one-time-code" inputmode="numeric" required autofocus
                                               style="letter-spacing:8px; font-size:24px; font-weight:bold;">
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-shield-alt me-1"></i> Enable Two-Factor Authentication
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
