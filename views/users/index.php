<?php $pageTitle = 'User Management'; ?>
<div class="page-header">
    <div>
        <h4 class="mb-0">User Management</h4>
        <small class="text-muted">Manage system users and their access</small>
    </div>
    <a href="<?= APP_URL ?>/index.php?page=users&action=create" class="btn btn-primary">
        <i class="fas fa-user-plus me-2"></i>Add User
    </a>
</div>

<!-- Search -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <input type="hidden" name="page" value="users">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search name, username, email..." value="<?= Helper::escape($search) ?>">
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary">Search</button>
                <?php if ($search): ?><a href="<?= APP_URL ?>/index.php?page=users" class="btn btn-outline-secondary">Clear</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($users['data'])): ?>
                    <tr><td colspan="8" class="text-center py-5 text-muted"><i class="fas fa-users fa-2x mb-2 opacity-25 d-block"></i>No users found.</td></tr>
                <?php else: $i = ($users['page'] - 1) * $users['perPage']; foreach ($users['data'] as $u): $i++; ?>
                <tr>
                    <td><?= $i ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width:36px;height:36px;min-width:36px;">
                                <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="fw-semibold"><?= Helper::escape($u['full_name']) ?></div>
                                <small class="text-muted"><?= Helper::escape($u['phone'] ?? '') ?></small>
                            </div>
                        </div>
                    </td>
                    <td><code><?= Helper::escape($u['username']) ?></code></td>
                    <td><?= Helper::escape($u['email']) ?></td>
                    <td>
                        <?php
                            $roleName = $u['role_display_name'] ?? ucfirst($u['role']);
                            $roleColors = ['admin' => 'bg-danger', 'manager' => 'bg-info text-dark', 'accountant' => 'bg-warning text-dark', 'cashier' => 'bg-success'];
                            $badgeClass = $roleColors[$u['role']] ?? ($u['role'] === 'admin' ? 'bg-danger' : 'bg-primary');
                        ?>
                        <span class="badge <?= $badgeClass ?>">
                            <?= Helper::escape($roleName) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= $u['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                            <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td><small class="text-muted"><?= $u['last_login'] ? Helper::formatDate($u['last_login']) : 'Never' ?></small></td>
                    <td class="text-end">
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="<?= APP_URL ?>/index.php?page=users&action=edit&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <!-- Toggle Active -->
                            <form method="POST" data-confirm="<?= $u['is_active'] ? 'Deactivate this user?' : 'Activate this user?' ?>">
                                <?= CSRF::field() ?>
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-<?= $u['is_active'] ? 'warning' : 'success' ?>" 
                                    formaction="<?= APP_URL ?>/index.php?page=users&action=toggleActive"
                                    title="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                    <i class="fas fa-<?= $u['is_active'] ? 'ban' : 'check-circle' ?>"></i>
                                </button>
                            </form>
                            <!-- Reset Password -->
                            <button type="button" class="btn btn-sm btn-outline-info" title="Reset Password"
                                onclick="openResetModal(<?= $u['id'] ?>, '<?= Helper::escape($u['full_name']) ?>')">
                                <i class="fas fa-key"></i>
                            </button>
                            <!-- Delete -->
                            <form method="POST" data-confirm="Delete user '<?= Helper::escape($u['full_name']) ?>'? This cannot be undone.">
                                <?= CSRF::field() ?>
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                    formaction="<?= APP_URL ?>/index.php?page=users&action=delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($users['totalPages'] > 1): ?>
    <div class="card-footer">
        <nav><ul class="pagination pagination-sm mb-0">
            <?php for ($p = 1; $p <= $users['totalPages']; $p++): ?>
            <li class="page-item <?= $p == $users['page'] ? 'active' : '' ?>">
                <a class="page-link" href="?page=users&search=<?= urlencode($search) ?>&pg=<?= $p ?>"><?= $p ?></a>
            </li>
            <?php endfor; ?>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="fas fa-key me-2"></i>Reset Password</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= APP_URL ?>/index.php?page=users&action=resetPassword">
                <?= CSRF::field() ?>
                <input type="hidden" name="id" id="resetUserId">
                <div class="modal-body">
                    <p class="small text-muted">Resetting password for: <strong id="resetUserName"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">New Password <span class="text-danger">*</span></label>
                        <input type="password" name="new_password" class="form-control" placeholder="Min 6 chars" required minlength="6" autocomplete="new-password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-key me-1"></i>Reset</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function openResetModal(id, name) {
    document.getElementById('resetUserId').value = id;
    document.getElementById('resetUserName').textContent = name;
    new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
}
</script>
