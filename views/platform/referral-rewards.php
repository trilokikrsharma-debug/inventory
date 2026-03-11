<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 mb-1"><i class="fas fa-gift me-2 text-info"></i>Referral Reward Logs</h2>
        <p class="text-muted mb-0">Every approval/rejection is recorded here for audit trail.</p>
    </div>
    <a href="<?= APP_URL ?>/index.php?page=referrals" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Back to Referrals
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Company</th>
                        <th>Referral Code</th>
                        <th>Reward Type</th>
                        <th>Reward Value</th>
                        <th>Note</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($rewards)): ?>
                    <?php foreach ($rewards as $row): ?>
                    <tr>
                        <td class="ps-4 fw-semibold"><?= e($row['company_name'] ?? 'N/A') ?></td>
                        <td><code><?= e($row['referral_code'] ?? '-') ?></code></td>
                        <td class="text-uppercase small"><?= e($row['reward_type'] ?? '-') ?></td>
                        <td><?= number_format((float)($row['reward_value'] ?? 0), 2) ?></td>
                        <td class="small text-muted"><?= e($row['reward_note'] ?? '-') ?></td>
                        <td class="small text-muted">
                            <?= !empty($row['created_at']) ? e(date('Y-m-d H:i', strtotime($row['created_at']))) : '-' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fas fa-inbox d-block mb-2"></i>No reward log records available.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
