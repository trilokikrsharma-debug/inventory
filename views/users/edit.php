<?php $pageTitle = 'Edit User'; ?>
<div class="page-header">
    <nav aria-label="breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=users">Users</a></li>
        <li class="breadcrumb-item active">Edit User</li>
    </ol></nav>
</div>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h6><i class="fas fa-user-edit me-2"></i>Edit: <?= Helper::escape($user['full_name']) ?></h6></div>
            <div class="card-body">
                <form method="POST">
                    <?= CSRF::field() ?>
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" required value="<?= Helper::escape($user['full_name']) ?>">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" required value="<?= Helper::escape($user['username']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control" value="<?= Helper::escape($user['phone'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required value="<?= Helper::escape($user['email']) ?>">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select name="role_id" class="form-select" required>
                                <?php if (!empty($roles)): ?>
                                    <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['id'] ?>" <?= (int)($user['role_id'] ?? 0) === (int)$r['id'] ? 'selected' : '' ?>>
                                        <?= Helper::escape($r['display_name']) ?>
                                        <?= $r['is_super_admin'] ? ' (Full Access)' : '' ?>
                                    </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="5">Staff</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select">
                                <option value="1" <?= $user['is_active'] ? 'selected' : '' ?>>Active</option>
                                <option value="0" <?= !$user['is_active'] ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="alert alert-info py-2 small">
                        <i class="fas fa-info-circle me-1"></i>
                        To change password, use the <strong>Reset Password</strong> button on the user list page.
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Update User</button>
                        <a href="<?= APP_URL ?>/index.php?page=users" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
