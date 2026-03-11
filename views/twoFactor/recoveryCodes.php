<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white text-center">
                    <i class="fas fa-shield-alt fa-2x mb-2"></i>
                    <h5 class="mb-0">2FA Enabled Successfully!</h5>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <strong>Save these recovery codes!</strong> Each code can only be used once.
                        If you lose access to your authenticator app, use these backup codes to sign in.
                    </div>

                    <div class="bg-light rounded p-3 mb-3" id="recoveryCodes">
                        <div class="row">
                            <?php foreach ($codes as $i => $code): ?>
                                <div class="col-6 mb-2">
                                    <code class="fs-6 user-select-all"><?= htmlspecialchars($code) ?></code>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mb-3">
                        <button class="btn btn-outline-primary btn-sm" onclick="copyRecoveryCodes()">
                            <i class="fas fa-copy me-1"></i> Copy
                        </button>
                        <button class="btn btn-outline-primary btn-sm" onclick="downloadRecoveryCodes()">
                            <i class="fas fa-download me-1"></i> Download
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                            <i class="fas fa-print me-1"></i> Print
                        </button>
                    </div>

                    <div class="text-center mt-4">
                        <a href="<?= APP_URL ?>/index.php?page=dashboard" class="btn btn-primary">
                            <i class="fas fa-check me-1"></i> Done
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyRecoveryCodes() {
    const codes = <?= json_encode($codes) ?>;
    navigator.clipboard.writeText(codes.join('\n')).then(() => {
        Swal.fire({icon:'success', title:'Copied!', text:'Recovery codes copied to clipboard', timer:1500, showConfirmButton:false});
    });
}

function downloadRecoveryCodes() {
    const codes = <?= json_encode($codes) ?>;
    const text = "InvenBill Pro — 2FA Recovery Codes\n" + "Generated: " + new Date().toISOString() + "\n\n" + codes.join('\n') + "\n\nKeep these codes in a safe place.";
    const blob = new Blob([text], {type: 'text/plain'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'invenbill-recovery-codes.txt';
    a.click();
}
</script>
