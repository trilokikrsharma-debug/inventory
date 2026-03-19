<?php
$isEdit = ($mode ?? 'create') === 'edit';
$action = $isEdit
    ? APP_URL . '/index.php?page=saas_plans&action=edit&id=' . (int)($plan['id'] ?? 0)
    : APP_URL . '/index.php?page=saas_plans&action=create';
$featuresPretty = '';
if (!empty($plan['features'])) {
    $decodedFeatures = json_decode((string)$plan['features'], true);
    if (is_array($decodedFeatures)) {
        $featuresPretty = (string)json_encode($decodedFeatures, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        $featuresPretty = (string)$plan['features'];
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 mb-1">
            <i class="fas fa-layer-group me-2 text-primary"></i><?= $isEdit ? 'Edit SaaS Plan' : 'Create SaaS Plan' ?>
        </h2>
        <p class="text-muted mb-0">Pricing is fully dynamic. You can switch between test and production prices anytime.</p>
    </div>
    <a href="<?= APP_URL ?>/index.php?page=saas_plans" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Back
    </a>
</div>

<form method="POST" action="<?= $action ?>">
    <?= CSRF::field() ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Plan Name *</label>
                    <input type="text" name="name" required maxlength="120" class="form-control"
                           value="<?= e($plan['name'] ?? '') ?>" placeholder="e.g. Starter Monthly">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" maxlength="120" class="form-control"
                           value="<?= e($plan['slug'] ?? '') ?>" placeholder="auto-generated-if-empty">
                </div>

                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Plan summary"><?= e($plan['description'] ?? '') ?></textarea>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Price (Rs) *</label>
                    <input type="number" name="price" min="0" step="0.01" required class="form-control"
                           value="<?= e(isset($plan['price']) ? (string)$plan['price'] : '0') ?>">
                    <div class="form-text">Use Rs 1 / Rs 2 / Rs 3 for real payment testing.</div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Offer Price (Rs)</label>
                    <input type="number" name="offer_price" min="0" step="0.01" class="form-control"
                           value="<?= e(isset($plan['offer_price']) && $plan['offer_price'] !== null ? (string)$plan['offer_price'] : '') ?>">
                    <div class="form-text">Checkout uses offer price when valid.</div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Billing Type *</label>
                    <?php $bt = $plan['billing_type'] ?? 'monthly'; ?>
                    <select name="billing_type" class="form-select" required>
                        <option value="one_time" <?= $bt === 'one_time' ? 'selected' : '' ?>>One Time</option>
                        <option value="monthly" <?= $bt === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                        <option value="yearly" <?= $bt === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Duration (Days) *</label>
                    <input type="number" name="duration_days" min="1" max="3650" required class="form-control"
                           value="<?= e(isset($plan['duration_days']) ? (string)$plan['duration_days'] : '30') ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Max Users *</label>
                    <input type="number" name="max_users" min="1" max="1000000" required class="form-control"
                           value="<?= e(isset($plan['max_users']) ? (string)$plan['max_users'] : '1') ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Razorpay Plan ID</label>
                    <input type="text" name="razorpay_plan_id" class="form-control"
                           value="<?= e($plan['razorpay_plan_id'] ?? '') ?>" placeholder="plan_XXXXXXXXXX">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" min="0" class="form-control"
                           value="<?= e(isset($plan['sort_order']) ? (string)$plan['sort_order'] : '0') ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <?php $status = $plan['status'] ?? 'active'; ?>
                    <select name="status" class="form-select">
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured"
                               value="1" <?= !empty($plan['is_featured']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_featured">
                            Mark as Featured / Popular
                        </label>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">Features (JSON)</label>
                    <textarea name="features" rows="6" class="form-control" placeholder='{"inventory":true,"invoicing":true,"api":false}'><?= e($featuresPretty) ?></textarea>
                    <div class="form-text">Use JSON object (feature => true/false) or JSON list (enabled features).</div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i><?= $isEdit ? 'Update Plan' : 'Create Plan' ?>
        </button>
        <a href="<?= APP_URL ?>/index.php?page=saas_plans" class="btn btn-light border">Cancel</a>
    </div>
</form>
