<?php
$isEdit = ($mode ?? 'create') === 'edit';
$action = $isEdit
    ? APP_URL . '/index.php?page=promos&action=edit&id=' . (int)($promo['id'] ?? 0)
    : APP_URL . '/index.php?page=promos&action=create';
$selectedPlanIds = [];
if (!empty($promo['applicable_plan_ids'])) {
    $selectedPlanIds = SaaSBillingHelper::parsePlanIds($promo['applicable_plan_ids']);
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 mb-1"><i class="fas fa-tags me-2 text-success"></i><?= $isEdit ? 'Edit Promo Code' : 'Create Promo Code' ?></h2>
        <p class="text-muted mb-0">Discounts are validated fully on the server side during checkout.</p>
    </div>
    <a href="<?= APP_URL ?>/index.php?page=promos" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Back
    </a>
</div>

<form method="POST" action="<?= $action ?>">
    <?= CSRF::field() ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Promo Code *</label>
                    <input type="text" name="code" required maxlength="30" class="form-control text-uppercase"
                           value="<?= e($promo['code'] ?? '') ?>" placeholder="NEWUSER50">
                </div>

                <div class="col-md-8">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" required maxlength="120" class="form-control"
                           value="<?= e($promo['title'] ?? '') ?>" placeholder="New Customer Offer">
                </div>

                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Optional admin notes"><?= e($promo['description'] ?? '') ?></textarea>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Discount Type *</label>
                    <?php $type = $promo['discount_type'] ?? 'fixed'; ?>
                    <select name="discount_type" class="form-select" required>
                        <option value="fixed" <?= $type === 'fixed' ? 'selected' : '' ?>>Fixed Amount</option>
                        <option value="percentage" <?= $type === 'percentage' ? 'selected' : '' ?>>Percentage</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Discount Value *</label>
                    <input type="number" name="discount_value" min="0.01" step="0.01" required class="form-control"
                           value="<?= e(isset($promo['discount_value']) ? (string)$promo['discount_value'] : '0') ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Max Discount</label>
                    <input type="number" name="max_discount_amount" min="0" step="0.01" class="form-control"
                           value="<?= e(isset($promo['max_discount_amount']) && $promo['max_discount_amount'] !== null ? (string)$promo['max_discount_amount'] : '') ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Minimum Purchase</label>
                    <input type="number" name="minimum_amount" min="0" step="0.01" class="form-control"
                           value="<?= e(isset($promo['minimum_amount']) ? (string)$promo['minimum_amount'] : '0') ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Usage Limit (Total)</label>
                    <input type="number" name="usage_limit_total" min="0" class="form-control"
                           value="<?= e(isset($promo['usage_limit_total']) ? (string)$promo['usage_limit_total'] : '0') ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Usage Limit (Per Company)</label>
                    <input type="number" name="usage_limit_per_company" min="0" class="form-control"
                           value="<?= e(isset($promo['usage_limit_per_company']) ? (string)$promo['usage_limit_per_company'] : '0') ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Valid From</label>
                    <input type="date" name="valid_from" class="form-control"
                           value="<?= !empty($promo['valid_from']) ? e(date('Y-m-d', strtotime($promo['valid_from']))) : '' ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Valid To</label>
                    <input type="date" name="valid_to" class="form-control"
                           value="<?= !empty($promo['valid_to']) ? e(date('Y-m-d', strtotime($promo['valid_to']))) : '' ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Applicable Plan IDs (optional)</label>
                    <input type="text" name="applicable_plan_ids" class="form-control"
                           value="<?= e(!empty($selectedPlanIds) ? implode(',', $selectedPlanIds) : '') ?>"
                           placeholder="e.g. 1,2,4 (leave blank for all plans)">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <?php $status = $promo['status'] ?? 'active'; ?>
                    <select name="status" class="form-select">
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="new_customers_only" id="new_customers_only" value="1"
                                   <?= !empty($promo['new_customers_only']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="new_customers_only">New customers only</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="allow_below_one" id="allow_below_one" value="1"
                                   <?= !empty($promo['allow_below_one']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="allow_below_one">Allow final amount below Rs 1</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-success">
            <i class="fas fa-save me-1"></i><?= $isEdit ? 'Update Promo' : 'Create Promo' ?>
        </button>
        <a href="<?= APP_URL ?>/index.php?page=promos" class="btn btn-light border">Cancel</a>
    </div>
</form>
