<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 mb-1"><i class="fas fa-tags me-2 text-success"></i>Promo Code Management</h2>
        <p class="text-muted mb-0">Manage discounts, limits, date windows, and plan restrictions.</p>
    </div>
    <a href="<?= APP_URL ?>/index.php?page=promos&action=create" class="btn btn-success">
        <i class="fas fa-plus me-1"></i>Create Promo
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Code</th>
                        <th>Type</th>
                        <th>Value</th>
                        <th>Min Amount</th>
                        <th>Usage</th>
                        <th>Validity</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($promos)): ?>
                    <?php foreach ($promos as $promo): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-semibold"><?= e($promo['code']) ?></div>
                            <div class="small text-muted"><?= e($promo['title'] ?? '') ?></div>
                        </td>
                        <td>
                            <span class="badge bg-secondary text-uppercase"><?= e($promo['discount_type']) ?></span>
                        </td>
                        <td>
                            <?php if (($promo['discount_type'] ?? '') === 'percentage'): ?>
                                <?= number_format((float)$promo['discount_value'], 2) ?>%
                            <?php else: ?>
                                Rs <?= number_format((float)$promo['discount_value'], 2) ?>
                            <?php endif; ?>
                            <?php if (!empty($promo['max_discount_amount'])): ?>
                                <div class="small text-muted">Max Rs <?= number_format((float)$promo['max_discount_amount'], 2) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>Rs <?= number_format((float)($promo['minimum_amount'] ?? 0), 2) ?></td>
                        <td>
                            <div><?= (int)($promo['used_count'] ?? 0) ?> used</div>
                            <div class="small text-muted">
                                Total limit: <?= (int)($promo['usage_limit_total'] ?? 0) > 0 ? (int)$promo['usage_limit_total'] : 'Unlimited' ?>
                            </div>
                        </td>
                        <td class="small">
                            <div>From: <?= !empty($promo['valid_from']) ? e(date('Y-m-d', strtotime($promo['valid_from']))) : '-' ?></div>
                            <div>To: <?= !empty($promo['valid_to']) ? e(date('Y-m-d', strtotime($promo['valid_to']))) : '-' ?></div>
                        </td>
                        <td>
                            <?php if (($promo['status'] ?? 'inactive') === 'active'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <a href="<?= APP_URL ?>/index.php?page=promos&action=edit&id=<?= (int)$promo['id'] ?>"
                               class="btn btn-sm btn-outline-primary me-1">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="<?= APP_URL ?>/index.php?page=promos&action=toggle" class="d-inline">
                                <?= CSRF::field() ?>
                                <input type="hidden" name="id" value="<?= (int)$promo['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-warning me-1">
                                    <i class="fas fa-power-off"></i>
                                </button>
                            </form>
                            <form method="POST" action="<?= APP_URL ?>/index.php?page=promos&action=delete" class="d-inline"
                                  onsubmit="return confirm('Delete this promo code? If usage exists it will be disabled instead.');">
                                <?= CSRF::field() ?>
                                <input type="hidden" name="id" value="<?= (int)$promo['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fas fa-inbox d-block mb-2"></i>No promo codes configured.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
