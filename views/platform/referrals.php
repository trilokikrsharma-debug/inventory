<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 mb-1"><i class="fas fa-user-plus me-2 text-info"></i>Referral Management</h2>
        <p class="text-muted mb-0">Track referral lifecycle and approve/reject rewards.</p>
    </div>
    <a href="<?= APP_URL ?>/index.php?page=referrals&action=rewards" class="btn btn-outline-info">
        <i class="fas fa-gift me-1"></i>Reward Logs
    </a>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <h6 class="mb-0 text-dark"><i class="fas fa-sliders-h me-2"></i>Reward Rule</h6>
            </div>
            <div class="card-body">
                <?php $rule = $activeRule ?? []; ?>
                <form method="POST" action="<?= APP_URL ?>/index.php?page=referrals&action=save_rule">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="id" value="<?= (int)($rule['id'] ?? 0) ?>">

                    <div class="mb-3">
                        <label class="form-label">Rule Name *</label>
                        <input type="text" class="form-control" name="name" required
                               value="<?= e($rule['name'] ?? 'Default Referral Rule') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reward Type *</label>
                        <?php $rt = $rule['reward_type'] ?? 'wallet_credit'; ?>
                        <select name="reward_type" class="form-select" required>
                            <option value="wallet_credit" <?= $rt === 'wallet_credit' ? 'selected' : '' ?>>Wallet Credit</option>
                            <option value="fixed_discount" <?= $rt === 'fixed_discount' ? 'selected' : '' ?>>Fixed Discount</option>
                            <option value="bonus_trial_days" <?= $rt === 'bonus_trial_days' ? 'selected' : '' ?>>Bonus Trial Days</option>
                            <option value="one_time_commission_record" <?= $rt === 'one_time_commission_record' ? 'selected' : '' ?>>Commission Record</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Reward Value *</label>
                            <input type="number" class="form-control" name="reward_value" min="0" step="0.01" required
                                   value="<?= e(isset($rule['reward_value']) ? (string)$rule['reward_value'] : '100') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Min Paid Amount</label>
                            <input type="number" class="form-control" name="minimum_paid_amount" min="0" step="0.01"
                                   value="<?= e(isset($rule['minimum_paid_amount']) ? (string)$rule['minimum_paid_amount'] : '1') ?>">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Sort Order</label>
                            <input type="number" class="form-control" name="sort_order" min="0"
                                   value="<?= e(isset($rule['sort_order']) ? (string)$rule['sort_order'] : '0') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Status</label>
                            <?php $rs = $rule['status'] ?? 'active'; ?>
                            <select name="status" class="form-select">
                                <option value="active" <?= $rs === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $rs === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="auto_approve" id="auto_approve" value="1"
                               <?= !empty($rule['auto_approve']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="auto_approve">Auto-approve rewards after eligibility</label>
                    </div>

                    <button type="submit" class="btn btn-info text-white">
                        <i class="fas fa-save me-1"></i>Save Rule
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Referrals</h6>
                <form method="GET" class="d-flex gap-2">
                    <input type="hidden" name="page" value="referrals">
                    <input type="hidden" name="action" value="index">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="" <?= ($status ?? '') === '' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="pending" <?= ($status ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="successful" <?= ($status ?? '') === 'successful' ? 'selected' : '' ?>>Successful</option>
                        <option value="rewarded" <?= ($status ?? '') === 'rewarded' ? 'selected' : '' ?>>Rewarded</option>
                        <option value="cancelled" <?= ($status ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Referrer</th>
                                <th>Referred</th>
                                <th>Status</th>
                                <th>Reward</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($referrals)): ?>
                            <?php foreach ($referrals as $ref): ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-semibold"><?= e($ref['referrer_company_name'] ?? 'N/A') ?></div>
                                    <div class="small text-muted">Code: <?= e($ref['referral_code'] ?? '-') ?></div>
                                </td>
                                <td><?= e($ref['referred_company_name'] ?? 'N/A') ?></td>
                                <td>
                                    <span class="badge bg-<?= ($ref['referral_status'] ?? '') === 'rewarded' ? 'success' : (($ref['referral_status'] ?? '') === 'successful' ? 'primary' : (($ref['referral_status'] ?? '') === 'cancelled' ? 'danger' : 'secondary')) ?> text-uppercase">
                                        <?= e($ref['referral_status'] ?? 'pending') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="small text-uppercase text-muted"><?= e($ref['reward_type'] ?? '-') ?></div>
                                    <div class="fw-semibold"><?= number_format((float)($ref['reward_value'] ?? 0), 2) ?></div>
                                </td>
                                <td class="text-end pe-3">
                                    <?php if (($ref['reward_status'] ?? '') !== 'rewarded' && ($ref['referral_status'] ?? '') !== 'cancelled'): ?>
                                    <form method="POST" action="<?= APP_URL ?>/index.php?page=referrals&action=approve_reward" class="d-inline">
                                        <?= CSRF::field() ?>
                                        <input type="hidden" name="referral_id" value="<?= (int)$ref['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success me-1" title="Approve Reward">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="<?= APP_URL ?>/index.php?page=referrals&action=reject_reward" class="d-inline"
                                          onsubmit="return confirm('Reject this referral reward?');">
                                        <?= CSRF::field() ?>
                                        <input type="hidden" name="referral_id" value="<?= (int)$ref['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Reject Reward">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                        <span class="text-muted small">No action</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No referral records found.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
