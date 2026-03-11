<?php
/**
 * Referral Model (platform scoped)
 */
class Referral extends Model {
    protected $table = 'referrals';
    protected $tenantScoped = false;
    protected $softDelete = false;

    /**
     * Ensure a company has a unique referral code.
     */
    public function ensureCompanyReferralCode(int $companyId): ?string {
        $company = $this->db->query(
            "SELECT id, name, referral_code FROM companies WHERE id = ? LIMIT 1",
            [$companyId]
        )->fetch();

        if (!$company) {
            return null;
        }

        if (!empty($company['referral_code'])) {
            return (string)$company['referral_code'];
        }

        $code = $this->generateUniqueCode((string)$company['name']);
        $this->db->query(
            "UPDATE companies SET referral_code = ? WHERE id = ?",
            [$code, $companyId]
        );
        return $code;
    }

    /**
     * Assign referral at signup time using code.
     */
    public function assignReferralToCompany(int $newCompanyId, string $referralCode): array {
        $referralCode = strtoupper(trim($referralCode));
        if ($referralCode === '') {
            return ['success' => false, 'message' => 'Referral code is empty.'];
        }

        $referred = $this->db->query(
            "SELECT id, referred_by_company_id FROM companies WHERE id = ? LIMIT 1",
            [$newCompanyId]
        )->fetch();

        if (!$referred) {
            return ['success' => false, 'message' => 'Company not found.'];
        }

        if (!empty($referred['referred_by_company_id'])) {
            return ['success' => false, 'message' => 'Referral already set for this company.'];
        }

        $referrer = $this->db->query(
            "SELECT id, referral_code FROM companies WHERE referral_code = ? LIMIT 1",
            [$referralCode]
        )->fetch();

        if (!$referrer) {
            Logger::security('Invalid referral code attempt', [
                'company_id' => $newCompanyId,
                'referral_code' => $referralCode,
            ]);
            return ['success' => false, 'message' => 'Invalid referral code.'];
        }

        $referrerId = (int)$referrer['id'];
        if ($referrerId === $newCompanyId) {
            Logger::security('Self referral blocked', ['company_id' => $newCompanyId]);
            return ['success' => false, 'message' => 'Self referral is not allowed.'];
        }

        // Anti-abuse: referral only before first paid subscription.
        $paidCount = (int)$this->db->query(
            "SELECT COUNT(*) FROM tenant_subscriptions
             WHERE company_id = ? AND payment_status = 'paid'",
            [$newCompanyId]
        )->fetchColumn();

        if ($paidCount > 0) {
            return ['success' => false, 'message' => 'Referral cannot be applied after paid subscription.'];
        }

        $db = $this->db;
        $db->beginTransaction();
        try {
            $db->query(
                "UPDATE companies
                 SET referred_by_company_id = ?
                 WHERE id = ? AND (referred_by_company_id IS NULL OR referred_by_company_id = 0)",
                [$referrerId, $newCompanyId]
            );

            $existing = $db->query(
                "SELECT id FROM referrals WHERE referred_company_id = ? LIMIT 1",
                [$newCompanyId]
            )->fetch();

            if (!$existing) {
                $rule = $this->getActiveRewardRule();
                $rewardType = $rule['reward_type'] ?? 'wallet_credit';
                $rewardValue = (float)($rule['reward_value'] ?? 0);

                $db->query(
                    "INSERT INTO referrals
                     (referrer_company_id, referred_company_id, referral_code, referral_status, reward_type, reward_value, reward_status, created_at, updated_at)
                     VALUES (?, ?, ?, 'pending', ?, ?, 'pending', ?, ?)",
                    [
                        $referrerId,
                        $newCompanyId,
                        $referralCode,
                        $rewardType,
                        SaaSBillingHelper::money($rewardValue),
                        SaaSBillingHelper::now(),
                        SaaSBillingHelper::now(),
                    ]
                );
            }

            $db->commit();
            return ['success' => true, 'message' => 'Referral code applied successfully.'];
        } catch (\Throwable $e) {
            $db->rollback();
            Logger::security('Failed to assign referral', [
                'new_company_id' => $newCompanyId,
                'referral_code' => $referralCode,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => 'Could not apply referral code.'];
        }
    }

    /**
     * Called after successful first paid subscription.
     */
    public function markSuccessfulAfterPayment(int $companyId, int $subscriptionId, float $paidAmount): void {
        $company = $this->db->query(
            "SELECT id, referred_by_company_id FROM companies WHERE id = ? LIMIT 1",
            [$companyId]
        )->fetch();

        if (!$company || empty($company['referred_by_company_id'])) {
            return;
        }

        $referrerId = (int)$company['referred_by_company_id'];
        if ($referrerId === $companyId) {
            Logger::security('Referral fraud prevented: self-payment reward', ['company_id' => $companyId]);
            return;
        }

        $rule = $this->getActiveRewardRule();
        $minPaid = (float)($rule['minimum_paid_amount'] ?? 0);
        if ($paidAmount < $minPaid) {
            return;
        }

        $db = $this->db;
        $db->beginTransaction();
        try {
            $referral = $db->query(
                "SELECT * FROM referrals WHERE referred_company_id = ? LIMIT 1 FOR UPDATE",
                [$companyId]
            )->fetch();

            if (!$referral) {
                $refCode = $this->ensureCompanyReferralCode($referrerId);
                $db->query(
                    "INSERT INTO referrals
                     (referrer_company_id, referred_company_id, referral_code, referral_status, reward_type, reward_value, reward_status, created_at, updated_at)
                     VALUES (?, ?, ?, 'pending', ?, ?, 'pending', ?, ?)",
                    [
                        $referrerId,
                        $companyId,
                        $refCode,
                        $rule['reward_type'] ?? 'wallet_credit',
                        SaaSBillingHelper::money((float)($rule['reward_value'] ?? 0)),
                        SaaSBillingHelper::now(),
                        SaaSBillingHelper::now(),
                    ]
                );

                $referral = $db->query(
                    "SELECT * FROM referrals WHERE referred_company_id = ? LIMIT 1 FOR UPDATE",
                    [$companyId]
                )->fetch();
            }

            if (!$referral) {
                $db->commit();
                return;
            }

            if (($referral['reward_status'] ?? '') === 'rewarded') {
                $db->commit();
                return;
            }

            $db->query(
                "UPDATE referrals
                 SET referral_status = 'successful',
                     reward_type = ?,
                     reward_value = ?,
                     updated_at = ?
                 WHERE id = ?",
                [
                    $rule['reward_type'] ?? $referral['reward_type'],
                    SaaSBillingHelper::money((float)($rule['reward_value'] ?? $referral['reward_value'])),
                    SaaSBillingHelper::now(),
                    $referral['id'],
                ]
            );

            $autoApprove = !empty($rule['auto_approve']);
            if ($autoApprove) {
                $this->approveRewardInternal((int)$referral['id'], 'Auto-approved after first successful payment');
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollback();
            Logger::security('Failed to mark referral successful', [
                'company_id' => $companyId,
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function listReferrals(string $status = ''): array {
        $where = [];
        $params = [];
        if ($status !== '') {
            $where[] = "r.referral_status = ?";
            $params[] = $status;
        }

        $sql = "SELECT r.*,
                       rc.name AS referrer_company_name,
                       nc.name AS referred_company_name
                FROM referrals r
                JOIN companies rc ON rc.id = r.referrer_company_id
                JOIN companies nc ON nc.id = r.referred_company_id";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY r.id DESC";

        return $this->db->query($sql, $params)->fetchAll();
    }

    public function listRewards(): array {
        return $this->db->query(
            "SELECT rr.*,
                    r.referral_code,
                    rc.name AS company_name
             FROM referral_rewards rr
             JOIN referrals r ON r.id = rr.referral_id
             JOIN companies rc ON rc.id = rr.company_id
             ORDER BY rr.id DESC"
        )->fetchAll();
    }

    public function approveReward(int $referralId, string $note = ''): bool {
        $db = $this->db;
        $db->beginTransaction();
        try {
            $this->approveRewardInternal($referralId, $note);
            $db->commit();
            return true;
        } catch (\Throwable $e) {
            $db->rollback();
            Logger::security('Referral reward approval failed', [
                'referral_id' => $referralId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function rejectReward(int $referralId, string $note = ''): bool {
        $db = $this->db;
        $db->beginTransaction();
        try {
            $referral = $db->query(
                "SELECT * FROM referrals WHERE id = ? LIMIT 1 FOR UPDATE",
                [$referralId]
            )->fetch();
            if (!$referral) {
                $db->rollback();
                return false;
            }

            $db->query(
                "UPDATE referrals
                 SET reward_status = 'rejected',
                     referral_status = 'cancelled',
                     updated_at = ?
                 WHERE id = ?",
                [SaaSBillingHelper::now(), $referralId]
            );

            if ($note !== '') {
                $db->query(
                    "INSERT INTO referral_rewards
                    (referral_id, company_id, reward_type, reward_value, reward_note, created_at)
                    VALUES (?, ?, ?, ?, ?, ?)",
                    [
                        $referralId,
                        (int)$referral['referrer_company_id'],
                        'rejected',
                        0,
                        $note,
                        SaaSBillingHelper::now(),
                    ]
                );
            }

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            $db->rollback();
            return false;
        }
    }

    public function getActiveRewardRule(): ?array {
        $rule = $this->db->query(
            "SELECT * FROM referral_reward_rules
             WHERE status = 'active'
             ORDER BY sort_order ASC, id ASC
             LIMIT 1"
        )->fetch();
        return $rule ?: null;
    }

    public function listRewardRules(): array {
        return $this->db->query(
            "SELECT * FROM referral_reward_rules ORDER BY sort_order ASC, id ASC"
        )->fetchAll();
    }

    public function saveRewardRule(array $input, ?int $id = null): array {
        $name = trim((string)($input['name'] ?? 'Default Rule'));
        $rewardType = trim((string)($input['reward_type'] ?? 'wallet_credit'));
        $rewardValue = SaaSBillingHelper::money($input['reward_value'] ?? 0);
        $minimumPaidAmount = SaaSBillingHelper::money($input['minimum_paid_amount'] ?? 0);
        $autoApprove = !empty($input['auto_approve']) ? 1 : 0;
        $sortOrder = max(0, (int)($input['sort_order'] ?? 0));
        $status = !empty($input['status']) && strtolower((string)$input['status']) === 'inactive'
            ? 'inactive'
            : 'active';

        if ($name === '') {
            return ['success' => false, 'message' => 'Rule name is required.'];
        }
        if (!in_array($rewardType, ['fixed_discount', 'wallet_credit', 'bonus_trial_days', 'one_time_commission_record'], true)) {
            return ['success' => false, 'message' => 'Invalid reward type.'];
        }
        if ($rewardValue < 0) {
            return ['success' => false, 'message' => 'Reward value cannot be negative.'];
        }

        $payload = [
            'name' => $name,
            'reward_type' => $rewardType,
            'reward_value' => $rewardValue,
            'minimum_paid_amount' => $minimumPaidAmount,
            'auto_approve' => $autoApprove,
            'sort_order' => $sortOrder,
            'status' => $status,
            'updated_at' => SaaSBillingHelper::now(),
        ];

        if ($id === null) {
            $payload['created_at'] = SaaSBillingHelper::now();
            $newId = $this->db->query(
                "INSERT INTO referral_reward_rules
                (name, reward_type, reward_value, minimum_paid_amount, auto_approve, sort_order, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $payload['name'],
                    $payload['reward_type'],
                    $payload['reward_value'],
                    $payload['minimum_paid_amount'],
                    $payload['auto_approve'],
                    $payload['sort_order'],
                    $payload['status'],
                    $payload['created_at'],
                    $payload['updated_at'],
                ]
            );
            return ['success' => true, 'id' => (int)$this->db->lastInsertId()];
        }

        $this->db->query(
            "UPDATE referral_reward_rules
             SET name = ?, reward_type = ?, reward_value = ?, minimum_paid_amount = ?,
                 auto_approve = ?, sort_order = ?, status = ?, updated_at = ?
             WHERE id = ?",
            [
                $payload['name'],
                $payload['reward_type'],
                $payload['reward_value'],
                $payload['minimum_paid_amount'],
                $payload['auto_approve'],
                $payload['sort_order'],
                $payload['status'],
                $payload['updated_at'],
                $id,
            ]
        );

        return ['success' => true, 'id' => $id];
    }

    private function generateUniqueCode(string $companyName): string {
        $base = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $companyName) ?: 'REF', 0, 6));
        if ($base === '') {
            $base = 'REF';
        }
        if (strlen($base) < 3) {
            $base = str_pad($base, 3, 'X');
        }

        do {
            $code = $base . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $exists = (int)$this->db->query(
                "SELECT COUNT(*) FROM companies WHERE referral_code = ?",
                [$code]
            )->fetchColumn();
        } while ($exists > 0);

        return $code;
    }

    /**
     * Reward application logic kept transaction-safe.
     */
    private function approveRewardInternal(int $referralId, string $note = ''): void {
        $db = $this->db;
        $referral = $db->query(
            "SELECT * FROM referrals WHERE id = ? LIMIT 1 FOR UPDATE",
            [$referralId]
        )->fetch();

        if (!$referral) {
            throw new \RuntimeException('Referral not found.');
        }

        if (($referral['reward_status'] ?? '') === 'rewarded') {
            return;
        }

        $referrerCompanyId = (int)$referral['referrer_company_id'];
        $rewardType = (string)$referral['reward_type'];
        $rewardValue = SaaSBillingHelper::money((float)$referral['reward_value']);

        // Idempotent reward log insert.
        $existingLog = $db->query(
            "SELECT id FROM referral_rewards WHERE referral_id = ? LIMIT 1",
            [$referralId]
        )->fetch();

        if (!$existingLog) {
            $db->query(
                "INSERT INTO referral_rewards
                 (referral_id, company_id, reward_type, reward_value, reward_note, created_at)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $referralId,
                    $referrerCompanyId,
                    $rewardType,
                    $rewardValue,
                    $note,
                    SaaSBillingHelper::now(),
                ]
            );
        }

        if ($rewardType === 'wallet_credit' || $rewardType === 'fixed_discount') {
            $db->query(
                "UPDATE companies SET wallet_credit = COALESCE(wallet_credit, 0) + ? WHERE id = ?",
                [$rewardValue, $referrerCompanyId]
            );
        } elseif ($rewardType === 'bonus_trial_days') {
            $days = max(0, (int)$rewardValue);
            if ($days > 0) {
                $db->query(
                    "UPDATE companies
                     SET trial_ends_at = CASE
                         WHEN trial_ends_at IS NULL OR trial_ends_at < NOW() THEN DATE_ADD(NOW(), INTERVAL ? DAY)
                         ELSE DATE_ADD(trial_ends_at, INTERVAL ? DAY)
                     END
                     WHERE id = ?",
                    [$days, $days, $referrerCompanyId]
                );
            }
        }

        $db->query(
            "UPDATE referrals
             SET reward_status = 'rewarded',
                 referral_status = 'rewarded',
                 updated_at = ?
             WHERE id = ?",
            [SaaSBillingHelper::now(), $referralId]
        );
    }
}

