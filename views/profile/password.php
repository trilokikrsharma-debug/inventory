<?php $pageTitle = 'Change Password'; ?>
<div class="page-header"><nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=profile">Profile</a></li><li class="breadcrumb-item active">Password</li></ol></nav></div>
<div class="row justify-content-center"><div class="col-lg-5">
    <div class="card"><div class="card-header"><h6><i class="fas fa-lock me-2"></i>Change Password</h6></div><div class="card-body">
        <form method="POST"><?= CSRF::field() ?>
            <div class="mb-3"><label class="form-label">Current Password</label><input type="password" name="current_password" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control" required minlength="6"></div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Change Password</button>
        </form>
    </div></div>
</div></div>
