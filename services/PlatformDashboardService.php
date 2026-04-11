<?php
/**
 * Platform dashboard aggregation service.
 *
 * Centralizes super-admin dashboard metrics so the controller only handles
 * access control and view rendering.
 */
class PlatformDashboardService {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?: Database::getInstance();
    }

    public function buildViewData(): array {
        $metrics = $this->loadCoreMetrics();
        $queue = $this->loadQueueStats();
        $sysHealth = $this->buildSystemHealth();
        $billing = $this->loadBillingMetrics();

        return [
            'pageTitle' => 'Platform Dashboard',
            'metrics' => array_merge($metrics, [
                'activeSubscriptions' => $billing['activeSubscriptions'],
                'totalRevenue' => $billing['totalRevenue'],
            ]),
            'queue' => $queue,
            'sysHealth' => $sysHealth,
            'planWiseSubscribers' => $billing['planWiseSubscribers'],
            'promoUsageStats' => $billing['promoUsageStats'],
            'referralStats' => $billing['referralStats'],
            'recentPayments' => $billing['recentPayments'],
            'recentFailedPayments' => $billing['recentFailedPayments'],
            'recentLifecycle' => $billing['recentLifecycle'],
        ];
    }

    private function loadCoreMetrics(): array {
        return [
            'totalTenants' => $this->db->query("SELECT COUNT(*) FROM companies")->fetchColumn(),
            'activeTenants' => $this->db->query("SELECT COUNT(*) FROM companies WHERE subscription_status = 'active'")->fetchColumn(),
            'trialTenants' => $this->db->query("SELECT COUNT(*) FROM companies WHERE subscription_status = 'trial'")->fetchColumn(),
            'suspendedTenants' => $this->db->query("SELECT COUNT(*) FROM companies WHERE subscription_status = 'suspended'")->fetchColumn(),
            'totalUsers' => $this->db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'totalSales' => $this->db->query("SELECT SUM(grand_total) FROM sales")->fetchColumn(),
            'mrr' => $this->db->query(
                "SELECT COALESCE(SUM(p.price), 0)
                 FROM companies c
                 JOIN saas_plans p ON c.saas_plan_id = p.id
                 WHERE c.subscription_status = 'active'"
            )->fetchColumn(),
        ];
    }

    private function loadQueueStats(): array {
        $queue = ['pending' => 0, 'failed' => 0];

        try {
            $stats = $this->db->query("SELECT status, COUNT(*) as cnt FROM jobs GROUP BY status")->fetchAll(\PDO::FETCH_KEY_PAIR);
            $queue['pending'] = $stats['pending'] ?? 0;
            $queue['failed'] = $stats['failed'] ?? 0;
        } catch (\Throwable $e) {
        }

        return $queue;
    }

    private function buildSystemHealth(): array {
        $sysHealth = [
            'latency' => 'N/A',
            'redis' => extension_loaded('redis') ? 'Available' : 'Missing',
            'disk' => round(@disk_free_space(BASE_PATH) / 1073741824, 2) . ' GB Free',
            'mem' => round(memory_get_usage(true) / 1048576, 2) . ' MB',
        ];

        $start = microtime(true);
        $this->db->query("SELECT 1")->fetch();
        $sysHealth['latency'] = round((microtime(true) - $start) * 1000, 2) . ' ms';

        return $sysHealth;
    }

    private function loadBillingMetrics(): array {
        $payload = [
            'activeSubscriptions' => 0,
            'totalRevenue' => 0.0,
            'planWiseSubscribers' => [],
            'promoUsageStats' => ['total_codes' => 0, 'total_usage' => 0, 'total_discount' => 0],
            'referralStats' => ['pending' => 0, 'successful' => 0, 'rewarded' => 0],
            'recentPayments' => [],
            'recentFailedPayments' => [],
            'recentLifecycle' => [],
        ];

        try {
            $payload['activeSubscriptions'] = (int)$this->db->query(
                "SELECT COUNT(*) FROM tenant_subscriptions WHERE status = 'active'"
            )->fetchColumn();

            $payload['totalRevenue'] = (float)$this->db->query(
                "SELECT COALESCE(SUM(amount), 0) FROM saas_payment_transactions WHERE status = 'captured'"
            )->fetchColumn();

            $payload['planWiseSubscribers'] = $this->db->query(
                "SELECT sp.name, COUNT(*) AS subscribers
                 FROM tenant_subscriptions ts
                 JOIN saas_plans sp ON sp.id = ts.plan_id
                 WHERE ts.status = 'active'
                 GROUP BY sp.id, sp.name
                 ORDER BY subscribers DESC"
            )->fetchAll();

            $payload['promoUsageStats'] = $this->db->query(
                "SELECT
                    (SELECT COUNT(*) FROM promo_codes) AS total_codes,
                    (SELECT COUNT(*) FROM promo_code_usages) AS total_usage,
                    (SELECT COALESCE(SUM(discount_amount), 0) FROM promo_code_usages) AS total_discount"
            )->fetch() ?: $payload['promoUsageStats'];

            $referralRows = $this->db->query(
                "SELECT referral_status, COUNT(*) AS cnt
                 FROM referrals
                 GROUP BY referral_status"
            )->fetchAll();
            foreach ($referralRows as $row) {
                $status = $row['referral_status'] ?? '';
                if (isset($payload['referralStats'][$status])) {
                    $payload['referralStats'][$status] = (int)$row['cnt'];
                }
            }

            $payload['recentPayments'] = $this->db->query(
                "SELECT pt.*, c.name AS company_name, sp.name AS plan_name
                 FROM saas_payment_transactions pt
                 LEFT JOIN tenant_subscriptions ts ON ts.id = pt.subscription_id
                 LEFT JOIN companies c ON c.id = pt.company_id
                 LEFT JOIN saas_plans sp ON sp.id = ts.plan_id
                 ORDER BY pt.id DESC LIMIT 10"
            )->fetchAll();

            $payload['recentFailedPayments'] = $this->db->query(
                "SELECT pt.*, c.name AS company_name
                 FROM saas_payment_transactions pt
                 LEFT JOIN companies c ON c.id = pt.company_id
                 WHERE pt.status IN ('failed', 'error')
                 ORDER BY pt.id DESC LIMIT 10"
            )->fetchAll();

            $payload['recentLifecycle'] = $this->db->query(
                "SELECT ts.id, ts.company_id, ts.plan_id, ts.status, ts.change_type, ts.updated_at,
                        c.name AS company_name, sp.name AS plan_name
                 FROM tenant_subscriptions ts
                 JOIN companies c ON c.id = ts.company_id
                 JOIN saas_plans sp ON sp.id = ts.plan_id
                 WHERE ts.status IN ('active', 'cancelled', 'halted', 'completed', 'upgraded')
                 ORDER BY ts.updated_at DESC LIMIT 12"
            )->fetchAll();
        } catch (\Throwable $e) {
            Logger::warning('Platform billing dashboard metrics unavailable', ['error' => $e->getMessage()]);
        }

        return $payload;
    }
}
