<?php $pageTitle = 'Edit Customer'; ?>
<div class="page-header"><nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=customers">Customers</a></li><li class="breadcrumb-item active">Edit</li></ol></nav></div>
<div class="card"><div class="card-body">
<form method="POST"><?= CSRF::field() ?>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Name <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" value="<?= Helper::escape($customer['name']) ?>" minlength="2" maxlength="100" required></div>
        <div class="col-md-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= Helper::escape($customer['email'] ?? '') ?>" maxlength="255"></div>
        <div class="col-md-3"><label class="form-label">Phone</label><input type="tel" name="phone" class="form-control" value="<?= Helper::escape($customer['phone'] ?? '') ?>" maxlength="20" pattern="[0-9+()\\-\\s]{7,20}" inputmode="tel"></div>
        <div class="col-md-12"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2" maxlength="500"><?= Helper::escape($customer['address'] ?? '') ?></textarea></div>
        <div class="col-md-4"><label class="form-label">City</label><input type="text" name="city" class="form-control" value="<?= Helper::escape($customer['city'] ?? '') ?>" maxlength="100"></div>
        <div class="col-md-4"><label class="form-label">State</label><input type="text" name="state" class="form-control" value="<?= Helper::escape($customer['state'] ?? '') ?>" maxlength="100"></div>
        <div class="col-md-4"><label class="form-label">ZIP</label><input type="text" name="zip" class="form-control" value="<?= Helper::escape($customer['zip'] ?? '') ?>" maxlength="20" pattern="[A-Za-z0-9\\-\\s]{2,20}" inputmode="text"></div>
        <div class="col-md-6"><label class="form-label">Tax Number</label><input type="text" name="tax_number" class="form-control" value="<?= Helper::escape($customer['tax_number'] ?? '') ?>" maxlength="20" pattern="[A-Za-z0-9\\/-]{6,20}"></div>
    </div>
    <div class="mt-4"><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update</button> <a href="<?= APP_URL ?>/index.php?page=customers" class="btn btn-outline-secondary">Cancel</a></div>
</form>
</div></div>
