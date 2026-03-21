<div class="card bg-dark text-white shadow-sm border-secondary mt-3">
    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-code text-primary me-2"></i> API Access Keys</h5>
    </div>
    <div class="card-body">
        <p class="text-muted">Use API keys to authenticate your requests. Do not share your API keys in publicly accessible areas such as GitHub, client-side code, and so forth.</p>
        
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i> Only your most recently generated key will be active.
        </div>

        <div class="bg-darker p-3 rounded border border-secondary mb-4 d-flex align-items-center justify-content-between">
            <div>
                <span class="text-white-50 small text-uppercase">Current Active Key</span><br>
                <code class="fs-5 text-primary"><?= htmlspecialchars($api_key ?? 'sk_live_...') ?></code>
            </div>
            <button class="btn btn-outline-secondary btn-sm" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($api_key ?? '') ?>'); alert('Key copied to clipboard!')">
                <i class="fas fa-copy"></i> Copy
            </button>
        </div>

        <form action="index.php?page=api&action=generate" method="POST">
            <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">
            <button type="submit" class="btn btn-primary" onclick="return confirm('Generate a new API key? This will invalidate your old key.')">
                <i class="fas fa-sync-alt me-1"></i> Generate New Key
            </button>
        </form>
    </div>
</div>
