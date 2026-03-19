<?php $pageTitle = 'Company Settings'; ?>
<?php
$planName = (string)($currentPlan['name'] ?? Tenant::planName() ?? 'Starter');
$planStatus = strtolower((string)($company['subscription_status'] ?? 'trial'));
$planStatusClass = 'secondary';
$planStatusTextClass = '';
if ($planStatus === 'active') {
    $planStatusClass = 'success';
} elseif ($planStatus === 'trial') {
    $planStatusClass = 'warning';
    $planStatusTextClass = 'text-dark';
} elseif (in_array($planStatus, ['inactive', 'suspended', 'cancelled'], true)) {
    $planStatusClass = 'danger';
}
?>

<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-building me-2 text-primary"></i>Company Settings</h4>
            <?php if (Tenant::company()): ?>
            <span class="badge bg-<?= $planStatusClass ?> <?= $planStatusTextClass ?>" style="font-size:0.8rem;">
                <i class="fas fa-tag me-1"></i><?= Helper::escape($planName) ?> Plan
            </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (Tenant::isDemo()): ?>
<div class="alert alert-info py-2 mb-3">
    <i class="fas fa-info-circle me-1"></i> Demo Mode: Settings changes won't be saved.
</div>
<?php endif; ?>

<form method="POST" action="<?= APP_URL ?>/index.php?page=company" enctype="multipart/form-data">
    <?= CSRF::field() ?>

    <div class="row g-3">
        <!-- Company Information -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-store me-2"></i>Business Information</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Company / Shop Name</label>
                            <input type="text" class="form-control" name="company_name" 
                                value="<?= Helper::escape($settings['company_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="company_email" 
                                value="<?= Helper::escape($settings['company_email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="company_phone" 
                                value="<?= Helper::escape($settings['company_phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Website</label>
                            <input type="url" class="form-control" name="company_website" 
                                value="<?= Helper::escape($settings['company_website'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="company_address" rows="2"><?= Helper::escape($settings['company_address'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control" name="company_city" 
                                value="<?= Helper::escape($settings['company_city'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">State</label>
                            <input type="text" class="form-control" name="company_state" 
                                value="<?= Helper::escape($settings['company_state'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">PIN Code</label>
                            <input type="text" class="form-control" name="company_zip" 
                                value="<?= Helper::escape($settings['company_zip'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Country</label>
                            <input type="text" class="form-control" name="company_country" 
                                value="<?= Helper::escape($settings['company_country'] ?? 'India') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">GST / Tax Number</label>
                            <input type="text" class="form-control" name="tax_number" 
                                value="<?= Helper::escape($settings['tax_number'] ?? '') ?>"
                                placeholder="e.g. 29AADCB2230M1ZP">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logo & Plan Info -->
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-image me-2"></i>Company Logo</h6></div>
                <div class="card-body text-center">
                    <?php if (!empty($settings['company_logo'])): ?>
                        <img src="<?= APP_URL ?>/<?= $settings['company_logo'] ?>" alt="Logo" 
                             style="max-width:150px;max-height:100px;border-radius:8px;margin-bottom:1rem;" class="d-block mx-auto">
                    <?php else: ?>
                        <div style="width:100px;height:100px;background:rgba(78,115,223,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                            <i class="fas fa-building fa-2x text-primary"></i>
                        </div>
                    <?php endif; ?>
                    <input type="file" class="form-control form-control-sm" name="company_logo" accept="image/*">
                    <div class="form-text">PNG, JPG up to 2MB</div>
                </div>
            </div>

            <?php if ($company): ?>
            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Account Info</h6></div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><td class="text-muted">Company ID</td><td class="fw-bold">#<?= (int)($company['id'] ?? 0) ?></td></tr>
                        <tr>
                            <td class="text-muted">Plan</td>
                            <td>
                                <span class="badge bg-primary"><?= Helper::escape($planName) ?></span>
                                <?php if (!empty($currentPlan['id'])): ?>
                                    <small class="text-muted ms-1">#<?= (int)$currentPlan['id'] ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr><td class="text-muted">Subscription</td><td><span class="badge bg-<?= $planStatusClass ?> <?= $planStatusTextClass ?>"><?= ucfirst($planStatus) ?></span></td></tr>
                        <tr><td class="text-muted">Account</td><td><span class="badge bg-success"><?= ucfirst($company['status'] ?? 'active') ?></span></td></tr>
                        <tr><td class="text-muted">Created</td><td><?= isset($company['created_at']) ? Helper::formatDate($company['created_at']) : '' ?></td></tr>
                    </table>
                    <div class="mt-2 text-center">
                        <a href="<?= APP_URL ?>/index.php?page=saas_billing&action=subscribe" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-arrow-up me-1"></i>Upgrade Plan
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="text-end mt-3">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i>Save Company Settings
        </button>
    </div>
</form>
