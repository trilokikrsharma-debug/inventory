<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 mb-1"><i class="fas fa-layer-group me-2 text-primary"></i>SaaS Plan Management</h2>
        <p class="text-muted mb-0">Create live test plans (Rs 1 / Rs 2 / Rs 3), offers, and production pricing.</p>
    </div>
    <a href="<?= APP_URL ?>/index.php?page=saas_plans&action=create" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Create Plan
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Plan</th>
                        <th>Billing</th>
                        <th>Price</th>
                        <th>Offer</th>
                        <th>Razorpay Plan</th>
                        <th>Sort</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($plans)): ?>
                    <?php foreach ($plans as $plan): ?>
                    <?php
                        $planName = (string)($plan['name'] ?? 'Unnamed Plan');
                        $planSlug = (string)($plan['slug'] ?? '');
                        $planBilling = (string)($plan['billing_type'] ?? ($plan['billing_cycle'] ?? 'monthly'));
                        $planDuration = (int)($plan['duration_days'] ?? ($planBilling === 'yearly' ? 365 : 30));
                        $planPrice = (float)($plan['price'] ?? 0);
                        $planOffer = isset($plan['offer_price']) ? (float)$plan['offer_price'] : null;
                    ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-semibold">
                                <?= e($planName) ?>
                                <?php if (!empty($plan['is_featured'])): ?>
                                    <span class="badge bg-warning text-dark ms-1">Featured</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-muted small"><?= e($planSlug !== '' ? $planSlug : 'n/a') ?></div>
                        </td>
                        <td>
                            <span class="badge bg-secondary text-uppercase"><?= e($planBilling) ?></span>
                            <div class="small text-muted"><?= $planDuration ?> days</div>
                        </td>
                        <td class="fw-semibold">Rs <?= number_format($planPrice, 2) ?></td>
                        <td>
                            <?php if ($planOffer !== null && $planOffer > 0): ?>
                                <span class="text-success fw-semibold">Rs <?= number_format($planOffer, 2) ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($plan['razorpay_plan_id'])): ?>
                                <code><?= e($plan['razorpay_plan_id']) ?></code>
                            <?php else: ?>
                                <span class="text-muted">Not linked</span>
                            <?php endif; ?>
                        </td>
                        <td><?= (int)($plan['sort_order'] ?? 0) ?></td>
                        <td>
                            <?php if (($plan['status'] ?? 'inactive') === 'active'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <a href="<?= APP_URL ?>/index.php?page=saas_plans&action=edit&id=<?= (int)$plan['id'] ?>"
                               class="btn btn-sm btn-outline-primary me-1">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="<?= APP_URL ?>/index.php?page=saas_plans&action=toggle"
                                  class="d-inline">
                                <?= CSRF::field() ?>
                                <input type="hidden" name="id" value="<?= (int)$plan['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-warning me-1" title="Toggle Status">
                                    <i class="fas fa-power-off"></i>
                                </button>
                            </form>
                            <form method="POST" action="<?= APP_URL ?>/index.php?page=saas_plans&action=delete"
                                  class="d-inline"
                                  onsubmit="return confirm('Delete this plan? If active subscriptions exist, the plan will be disabled instead.');">
                                <?= CSRF::field() ?>
                                <input type="hidden" name="id" value="<?= (int)$plan['id'] ?>">
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
                            <i class="fas fa-inbox d-block mb-2"></i>No plans found.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
