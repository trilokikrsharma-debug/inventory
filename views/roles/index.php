<?php $pageTitle = 'Roles & Permissions'; ?>
<div class="page-header">
    <div>
        <h4 class="mb-0">Roles & Permissions</h4>
        <small class="text-muted">Manage user roles and access control</small>
    </div>
    <a href="<?= APP_URL ?>/index.php?page=roles&action=create" class="btn btn-success">
        <i class="fas fa-plus me-2"></i>New Role
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Role</th>
                        <th>Description</th>
                        <th>Users</th>
                        <th>Type</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($roles)): ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted">
                        <i class="fas fa-shield-halved fa-2x mb-2 opacity-25 d-block"></i>No roles found.
                    </td></tr>
                <?php else: foreach ($roles as $i => $r): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td>
                        <div class="fw-bold"><?= Helper::escape($r['display_name']) ?></div>
                        <small class="text-muted font-monospace"><?= Helper::escape($r['name']) ?></small>
                    </td>
                    <td><small class="text-muted"><?= Helper::escape($r['description'] ?? '-') ?></small></td>
                    <td>
                        <span class="badge bg-primary rounded-pill"><?= (int)$r['user_count'] ?></span>
                    </td>
                    <td>
                        <?php if ($r['is_super_admin']): ?>
                            <span class="badge bg-danger"><i class="fas fa-crown me-1"></i>Super Admin</span>
                        <?php elseif ($r['is_system']): ?>
                            <span class="badge bg-secondary"><i class="fas fa-lock me-1"></i>System</span>
                        <?php else: ?>
                            <span class="badge bg-info text-dark">Custom</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="<?= APP_URL ?>/index.php?page=roles&action=edit&id=<?= $r['id'] ?>" 
                               class="btn btn-sm btn-outline-primary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if (!$r['is_super_admin'] && !$r['is_system']): ?>
                            <form method="POST" action="<?= APP_URL ?>/index.php?page=roles&action=delete" data-confirm="Delete this role? This cannot be undone.">
                                <?= CSRF::field() ?>
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"
                                    <?= $r['user_count'] > 0 ? 'disabled' : '' ?>>
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-body py-2">
        <small class="text-muted">
            <i class="fas fa-info-circle me-1"></i>
            <strong>Super Admin</strong> roles bypass all permission checks.
            <strong>System</strong> and <strong>Super Admin</strong> roles cannot be deleted.
            Roles with active users must have users reassigned before deletion.
        </small>
    </div>
</div>
