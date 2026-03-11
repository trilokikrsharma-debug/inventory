<?php $pageTitle = 'Edit Role: ' . Helper::escape($role['display_name']); ?>
<div class="page-header">
    <nav aria-label="breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=roles">Roles</a></li>
        <li class="breadcrumb-item active">Edit: <?= Helper::escape($role['display_name']) ?></li>
    </ol></nav>
</div>

<form method="POST">
    <?= CSRF::field() ?>
    <div class="row">
        <!-- Role Info -->
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-shield-halved me-2"></i>Role Details</h6></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Role Slug</label>
                        <input type="text" class="form-control" value="<?= Helper::escape($role['name']) ?>" disabled>
                        <small class="text-muted">Cannot be changed after creation.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Display Name <span class="text-danger">*</span></label>
                        <input type="text" name="display_name" class="form-control" required
                               value="<?= Helper::escape($role['display_name']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= Helper::escape($role['description'] ?? '') ?></textarea>
                    </div>

                    <?php if ($role['is_super_admin']): ?>
                    <div class="alert alert-warning py-2 small mb-0">
                        <i class="fas fa-crown me-1"></i>
                        <strong>Super Admin</strong> roles bypass all permission checks.
                        Permission assignments below are for reference only.
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex gap-2 mb-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Update Role</button>
                <a href="<?= APP_URL ?>/index.php?page=roles" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>

        <!-- Permission Matrix -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-key me-2"></i>Permissions</h6>
                    <?php if (!$role['is_super_admin']): ?>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="toggleAll(true)">
                            <i class="fas fa-check-double me-1"></i>Select All
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAll(false)">
                            <i class="fas fa-times me-1"></i>Clear All
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($permissions)): ?>
                        <p class="text-muted">No permissions found in the database.</p>
                    <?php else: ?>
                        <?php
                        $moduleIcons = [
                            'dashboard'  => 'fa-th-large',
                            'sales'      => 'fa-receipt',
                            'purchases'  => 'fa-cart-shopping',
                            'payments'   => 'fa-money-bill-transfer',
                            'products'   => 'fa-boxes-stacked',
                            'customers'  => 'fa-user-group',
                            'suppliers'  => 'fa-truck',
                            'quotations' => 'fa-file-alt',
                            'returns'    => 'fa-undo',
                            'reports'    => 'fa-chart-pie',
                            'catalog'    => 'fa-tags',
                            'users'      => 'fa-users-cog',
                            'settings'   => 'fa-gear',
                            'backup'     => 'fa-shield-halved',
                            'roles'      => 'fa-user-shield',
                        ];
                        $isSA = $role['is_super_admin'];
                        ?>
                        <div class="row g-3">
                        <?php foreach ($permissions as $module => $perms): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="border rounded p-3 h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0">
                                            <i class="fas <?= $moduleIcons[$module] ?? 'fa-puzzle-piece' ?> me-1 text-primary"></i>
                                            <?= ucfirst($module) ?>
                                        </h6>
                                        <?php if (!$isSA): ?>
                                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none"
                                                onclick="toggleModule('<?= $module ?>')">
                                            <small>Toggle</small>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    <?php foreach ($perms as $p): ?>
                                    <div class="form-check">
                                        <input class="form-check-input perm-checkbox module-<?= $module ?>"
                                               type="checkbox" name="permissions[]"
                                               value="<?= $p['id'] ?>" id="perm_<?= $p['id'] ?>"
                                               <?= isset($rolePermIdsMap[$p['id']]) ? 'checked' : '' ?>
                                               <?= $isSA ? 'checked disabled' : '' ?>>
                                        <label class="form-check-label" for="perm_<?= $p['id'] ?>">
                                            <?= Helper::escape($p['display_name']) ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function toggleAll(state) {
    document.querySelectorAll('.perm-checkbox:not(:disabled)').forEach(cb => cb.checked = state);
}
function toggleModule(module) {
    const cbs = document.querySelectorAll('.module-' + module + ':not(:disabled)');
    const allChecked = Array.from(cbs).every(cb => cb.checked);
    cbs.forEach(cb => cb.checked = !allChecked);
}
</script>
