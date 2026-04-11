<?php
$tokens = is_array($tokens ?? null) ? $tokens : [];
$newToken = (string)($newToken ?? '');
$availableScopes = is_array($availableScopes ?? null) ? $availableScopes : [];
?>

<div class="card shadow-sm border-0 mt-3">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h4 class="mb-1"><i class="fas fa-code text-primary me-2"></i>API Access</h4>
                <p class="text-muted mb-0">Generate tenant-scoped bearer tokens for server-to-server integrations.</p>
            </div>
            <div class="small text-muted">
                Tokens are shown only once after creation.
            </div>
        </div>

        <?php if ($newToken !== ''): ?>
        <div class="alert alert-success">
            <div class="fw-semibold mb-2">Copy this token now.</div>
            <div class="small mb-2">For security reasons it will not be shown again after you leave this page.</div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <code class="flex-grow-1 p-2 border rounded bg-light text-break"><?= htmlspecialchars($newToken) ?></code>
                <button type="button" class="btn btn-outline-success btn-sm" id="copyApiTokenBtn" data-token="<?= htmlspecialchars($newToken, ENT_QUOTES) ?>">
                    <i class="fas fa-copy me-1"></i>Copy
                </button>
            </div>
        </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="border rounded p-3 h-100">
                    <h5 class="mb-3">Create Token</h5>
                    <form action="<?= APP_URL ?>/index.php?page=api&action=generate" method="POST">
                        <?= CSRF::field() ?>
                        <div class="mb-3">
                            <label class="form-label">Token Name</label>
                            <input type="text" name="name" class="form-control" maxlength="100" placeholder="e.g. Warehouse Sync">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Permissions</label>
                            <div class="border rounded p-3 bg-light">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="full_access" value="1" id="scope_all" checked>
                                    <label class="form-check-label fw-semibold" for="scope_all">Full Access</label>
                                </div>
                                <div class="small text-muted mb-3">Uncheck full access to issue a limited read-only token.</div>
                                <?php foreach ($availableScopes as $scope): ?>
                                <div class="form-check">
                                    <input class="form-check-input api-scope-checkbox" type="checkbox" name="scopes[]" value="<?= htmlspecialchars((string)$scope) ?>" id="scope_<?= htmlspecialchars(str_replace('.', '_', (string)$scope)) ?>">
                                    <label class="form-check-label" for="scope_<?= htmlspecialchars(str_replace('.', '_', (string)$scope)) ?>">
                                        <code><?= htmlspecialchars((string)$scope) ?></code>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expiry</label>
                            <select name="expiry_days" class="form-select">
                                <option value="never">Never expires</option>
                                <option value="1">1 day</option>
                                <option value="7">7 days</option>
                                <option value="30">30 days</option>
                                <option value="90">90 days</option>
                                <option value="365">365 days</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Generate Token
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="border rounded p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Active Tokens</h5>
                        <span class="badge text-bg-light"><?= count($tokens) ?> total</span>
                    </div>

                    <?php if (empty($tokens)): ?>
                    <div class="text-muted">No API tokens created yet.</div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Scopes</th>
                                    <th>Expiry</th>
                                    <th>Last Used</th>
                                    <th>Created</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tokens as $token): ?>
                                <?php
                                    $scopeList = json_decode((string)($token['scopes'] ?? '[]'), true);
                                    if (!is_array($scopeList) || empty($scopeList)) {
                                        $scopeList = ['*'];
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars((string)($token['name'] ?? 'Integration')) ?></div>
                                        <div class="small text-muted">#<?= (int)$token['id'] ?></div>
                                    </td>
                                    <td>
                                        <code><?= htmlspecialchars(implode(', ', $scopeList)) ?></code>
                                    </td>
                                    <td class="small text-muted">
                                        <?php if (!empty($token['expires_at'])): ?>
                                            <?php
                                                $expiresAt = strtotime((string)$token['expires_at']);
                                                $isExpired = $expiresAt !== false && $expiresAt < time();
                                            ?>
                                            <span class="<?= $isExpired ? 'text-danger fw-semibold' : '' ?>">
                                                <?= htmlspecialchars((string)$token['expires_at']) ?>
                                            </span>
                                        <?php else: ?>
                                            Never
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted">
                                        <?= !empty($token['last_used_at']) ? htmlspecialchars((string)$token['last_used_at']) : 'Never' ?>
                                    </td>
                                    <td class="small text-muted">
                                        <?= !empty($token['created_at']) ? htmlspecialchars((string)$token['created_at']) : '-' ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if (!empty($token['is_active'])): ?>
                                        <form action="<?= APP_URL ?>/index.php?page=api&action=revoke" method="POST" class="d-inline">
                                            <?= CSRF::field() ?>
                                            <input type="hidden" name="token_id" value="<?= (int)$token['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger js-confirm-revoke-token" data-confirm-message="Revoke this API token?">
                                                <i class="fas fa-ban me-1"></i>Revoke
                                            </button>
                                        </form>
                                        <?php else: ?>
                                        <span class="badge text-bg-secondary">Revoked</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="border rounded p-3 mt-4">
            <h5 class="mb-3">Available Endpoints</h5>
            <div class="small text-muted mb-3">Send your bearer token in the <code>Authorization</code> header.</div>
            <div class="mb-2"><code>GET <?= APP_URL ?>/api/v1/tenant/summary</code> <span class="text-muted">(requires <code>reports.read</code> or <code>*</code>)</span></div>
            <div class="mb-2"><code>GET <?= APP_URL ?>/api/v1/tenant/products?limit=25&page=1&search=soap</code> <span class="text-muted">(requires <code>catalog.read</code> or <code>*</code>)</span></div>
            <div class="mb-2"><code>GET <?= APP_URL ?>/api/v1/tenant/customers?limit=25&page=1&search=raj</code> <span class="text-muted">(requires <code>catalog.read</code> or <code>*</code>)</span></div>
            <pre class="bg-light border rounded p-3 mb-0 small"><code>Authorization: Bearer inv_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx</code></pre>
        </div>
    </div>
</div>

<script nonce="<?= $cspNonce ?? '' ?>">
document.addEventListener('DOMContentLoaded', function () {
    var fullAccess = document.getElementById('scope_all');
    var scopeInputs = Array.prototype.slice.call(document.querySelectorAll('.api-scope-checkbox'));
    if (!fullAccess || scopeInputs.length === 0) {
        return;
    }

    function syncStateFromFullAccess() {
        var disableGranular = fullAccess.checked;
        scopeInputs.forEach(function (input) {
            input.disabled = disableGranular;
            if (disableGranular) {
                input.checked = false;
            }
        });
    }

    function syncStateFromGranular() {
        var anyChecked = scopeInputs.some(function (input) { return input.checked; });
        if (anyChecked) {
            fullAccess.checked = false;
        }
        scopeInputs.forEach(function (input) {
            input.disabled = fullAccess.checked;
        });
    }

    fullAccess.addEventListener('change', syncStateFromFullAccess);
    scopeInputs.forEach(function (input) {
        input.addEventListener('change', syncStateFromGranular);
    });

    syncStateFromFullAccess();

    var copyButton = document.getElementById('copyApiTokenBtn');
    if (copyButton) {
        copyButton.addEventListener('click', function () {
            navigator.clipboard.writeText(copyButton.dataset.token || '');
        });
    }

    document.querySelectorAll('.js-confirm-revoke-token').forEach(function (button) {
        button.addEventListener('click', function (event) {
            var message = button.dataset.confirmMessage || 'Revoke this API token?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
});
</script>
