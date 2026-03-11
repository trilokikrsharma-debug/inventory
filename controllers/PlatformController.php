<?php
/**
 * Platform Controller (Super Admin Dashboard)
 * 
 * Manages all tenants, subscriptions, MRR, and system health.
 * Strictly requires super admin privileges.
 */
class PlatformController extends Controller {

    protected $allowedActions = [
        'dashboard', 
        'tenants', 'suspend_tenant', 'reactivate_tenant', 'delete_tenant', 'impersonate_tenant',
        'subscriptions', 
        'payments',
        'promos',
        'referrals',
        'revenue', 
        'system',
        'stop_impersonation'
    ];

    public function __construct() {
        // SECURITY FIX (SES-1): stop_impersonation must work when the active
        // session is a tenant user (because we're impersonating). All other
        // actions still require super-admin privileges.
        $action = $_GET['action'] ?? 'index';
        if ($action === 'stop_impersonation') {
            // Only require basic authentication — the saved admin session
            // in _impersonating_from is verified inside the method.
            $this->requireAuth();
        } else {
            $this->requireSuperAdmin();
        }
    }

    public function index() {
        $this->redirect('index.php?page=platform&action=dashboard');
    }

    /**
     * Platform Owner Dashboard
     */
    public function dashboard() {
        $db = Database::getInstance();

        // 1. Tenant Metrics
        $totalTenants = $db->query("SELECT COUNT(*) FROM companies")->fetchColumn();
        $activeTenants = $db->query("SELECT COUNT(*) FROM companies WHERE subscription_status = 'active'")->fetchColumn();
        $trialTenants = $db->query("SELECT COUNT(*) FROM companies WHERE subscription_status = 'trial'")->fetchColumn();
        $suspendedTenants = $db->query("SELECT COUNT(*) FROM companies WHERE subscription_status = 'suspended'")->fetchColumn();

        // 2. User & System Metrics
        $totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $totalSales = $db->query("SELECT SUM(grand_total) FROM sales")->fetchColumn();
        
        // 3. MRR Calculation
        $mrr = $db->query("
            SELECT COALESCE(SUM(p.price), 0) 
            FROM companies c
            JOIN saas_plans p ON c.saas_plan_id = p.id
            WHERE c.subscription_status = 'active'
        ")->fetchColumn();

        // 4. Queue Status
        // Handle gracefully if jobs table missing
        $pendingJobs = 0;
        $failedJobs = 0;
        try {
            $stats = $db->query("SELECT status, COUNT(*) as cnt FROM jobs GROUP BY status")->fetchAll(\PDO::FETCH_KEY_PAIR);
            $pendingJobs = $stats['pending'] ?? 0;
            $failedJobs = $stats['failed'] ?? 0;
        } catch (\Exception $e) {}

        // 5. Build Health Context
        $sysHealth = [
            'latency' => 'N/A',
            'redis'   => extension_loaded('redis') ? 'Available' : 'Missing',
            'disk'    => round(@disk_free_space(BASE_PATH) / 1073741824, 2) . ' GB Free',
            'mem'     => round(memory_get_usage(true) / 1048576, 2) . ' MB'
        ];
        
        $start = microtime(true);
        $db->query("SELECT 1")->fetch();
        $sysHealth['latency'] = round((microtime(true) - $start) * 1000, 2) . ' ms';

        // SaaS Billing Metrics (fault tolerant if migration not yet applied)
        $activeSubscriptions = 0;
        $totalRevenue = 0;
        $planWiseSubscribers = [];
        $promoUsageStats = ['total_codes' => 0, 'total_usage' => 0, 'total_discount' => 0];
        $referralStats = ['pending' => 0, 'successful' => 0, 'rewarded' => 0];
        $recentPayments = [];
        $recentFailedPayments = [];
        $recentLifecycle = [];

        try {
            $activeSubscriptions = (int)$db->query(
                "SELECT COUNT(*) FROM tenant_subscriptions WHERE status = 'active'"
            )->fetchColumn();

            $totalRevenue = (float)$db->query(
                "SELECT COALESCE(SUM(amount), 0) FROM saas_payment_transactions WHERE status = 'captured'"
            )->fetchColumn();

            $planWiseSubscribers = $db->query(
                "SELECT sp.name, COUNT(*) AS subscribers
                 FROM tenant_subscriptions ts
                 JOIN saas_plans sp ON sp.id = ts.plan_id
                 WHERE ts.status = 'active'
                 GROUP BY sp.id, sp.name
                 ORDER BY subscribers DESC"
            )->fetchAll();

            $promoUsageStats = $db->query(
                "SELECT
                    (SELECT COUNT(*) FROM promo_codes) AS total_codes,
                    (SELECT COUNT(*) FROM promo_code_usages) AS total_usage,
                    (SELECT COALESCE(SUM(discount_amount), 0) FROM promo_code_usages) AS total_discount"
            )->fetch() ?: $promoUsageStats;

            $referralRows = $db->query(
                "SELECT referral_status, COUNT(*) AS cnt
                 FROM referrals
                 GROUP BY referral_status"
            )->fetchAll();
            foreach ($referralRows as $row) {
                $status = $row['referral_status'] ?? '';
                if (isset($referralStats[$status])) {
                    $referralStats[$status] = (int)$row['cnt'];
                }
            }

            $recentPayments = $db->query(
                "SELECT pt.*, c.name AS company_name, sp.name AS plan_name
                 FROM saas_payment_transactions pt
                 LEFT JOIN tenant_subscriptions ts ON ts.id = pt.subscription_id
                 LEFT JOIN companies c ON c.id = pt.company_id
                 LEFT JOIN saas_plans sp ON sp.id = ts.plan_id
                 ORDER BY pt.id DESC LIMIT 10"
            )->fetchAll();

            $recentFailedPayments = $db->query(
                "SELECT pt.*, c.name AS company_name
                 FROM saas_payment_transactions pt
                 LEFT JOIN companies c ON c.id = pt.company_id
                 WHERE pt.status IN ('failed', 'error')
                 ORDER BY pt.id DESC LIMIT 10"
            )->fetchAll();

            $recentLifecycle = $db->query(
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

        $this->view('platform.dashboard', [
            'pageTitle' => 'Platform Dashboard',
            'metrics' => [
                'totalTenants' => $totalTenants,
                'activeTenants' => $activeTenants,
                'trialTenants' => $trialTenants,
                'suspendedTenants' => $suspendedTenants,
                'totalUsers' => $totalUsers,
                'mrr' => $mrr,
                'totalSales' => $totalSales,
                'activeSubscriptions' => $activeSubscriptions,
                'totalRevenue' => $totalRevenue,
            ],
            'queue' => [
                'pending' => $pendingJobs,
                'failed'  => $failedJobs
            ],
            'sysHealth' => $sysHealth,
            'planWiseSubscribers' => $planWiseSubscribers,
            'promoUsageStats' => $promoUsageStats,
            'referralStats' => $referralStats,
            'recentPayments' => $recentPayments,
            'recentFailedPayments' => $recentFailedPayments,
            'recentLifecycle' => $recentLifecycle,
        ]);
    }

    /**
     * Tenant Management
     */
    public function tenants() {
        $db = Database::getInstance();
        $tenants = $db->query("
            SELECT t.*, p.name as plan_name, 
                   (SELECT email FROM users u WHERE u.company_id = t.id AND u.role_id = 1 LIMIT 1) as owner_email
            FROM companies t
            LEFT JOIN saas_plans p ON t.saas_plan_id = p.id
            ORDER BY t.created_at DESC
        ")->fetchAll();

        $this->view('platform.tenants', [
            'pageTitle' => 'Tenant Management',
            'tenants'   => $tenants
        ]);
    }

    public function suspend_tenant() {
        if ($this->demoGuard()) return;
        $id = $this->post('id');
        if ($id) {
            Database::getInstance()->query("UPDATE companies SET subscription_status = 'suspended' WHERE id = ?", [$id]);
            $this->logActivity('Tenant Suspended', 'platform', $id);
            $this->setFlash('success', "Tenant #$id suspended.");
        }
        $this->redirect('index.php?page=platform&action=tenants');
    }

    public function reactivate_tenant() {
        if ($this->demoGuard()) return;
        $id = $this->post('id');
        if ($id) {
            Database::getInstance()->query("UPDATE companies SET subscription_status = 'active' WHERE id = ?", [$id]);
            $this->logActivity('Tenant Reactivated', 'platform', $id);
            $this->setFlash('success', "Tenant #$id reactivated.");
        }
        $this->redirect('index.php?page=platform&action=tenants');
    }

    public function delete_tenant() {
        if ($this->demoGuard()) return;
        $id = $this->post('id');
        // Extreme operation — requires cascade delete natively in DB or raw deletes
        // We will just do a hard delete if foreign keys allow, or soft delete 
        // For SaaS, usually we just flag as 'deleted' but let's fire DB delete.
        if ($id) {
            try {
                Database::getInstance()->query("DELETE FROM companies WHERE id = ?", [$id]);
                $this->logActivity('Tenant Deleted', 'platform', $id);
                $this->setFlash('success', "Tenant #$id fully deleted.");
            } catch (\Exception $e) {
                $this->setFlash('error', "Cannot delete tenant. Ensure cascaded constraints or remove related users first.");
            }
        }
        $this->redirect('index.php?page=platform&action=tenants');
    }

    public function impersonate_tenant() {
        if ($this->demoGuard()) return;
        $id = (int)$this->post('id');
        if ($id) {
            $db = Database::getInstance();
            $owner = $db->query(
                "SELECT * FROM users WHERE company_id = ? AND role_id = 1 AND deleted_at IS NULL LIMIT 1",
                [$id]
            )->fetch(\PDO::FETCH_ASSOC);
            if ($owner) {
                // Load the target company for tenant context
                $company = $db->query(
                    "SELECT * FROM companies WHERE id = ?", [$id]
                )->fetch(\PDO::FETCH_ASSOC);

                if (!$company) {
                    $this->setFlash('error', 'Company not found.');
                    $this->redirect('index.php?page=platform&action=tenants');
                    return;
                }

                // SECURITY FIX (SES-1): Save original admin session before overwriting.
                // This allows the super-admin to return to their own session via
                // stop_impersonation without having to re-login.
                $originalAdmin = Session::get('user');
                $this->logActivity('SuperAdmin Impersonated Tenant', 'platform', $id,
                    'Admin ID: ' . ($originalAdmin['id'] ?? '?') . ' → Tenant Owner ID: ' . $owner['id']);

                session_regenerate_id(true);
                Session::set('_impersonating_from', $originalAdmin);
                Session::set('user', $owner);

                // Set tenant context so the impersonated session scopes correctly
                Tenant::set($id, $company);

                $this->redirect('index.php?page=dashboard');
                return;
            }
            $this->setFlash('error', 'No owner found for this tenant.');
        }
        $this->redirect('index.php?page=platform&action=tenants');
    }

    /**
     * Stop impersonation and restore the original super-admin session.
     * 
     * SECURITY FIX (SES-1): This action is exempt from requireSuperAdmin()
     * because the active session is that of a tenant user. Authenticity is
     * verified by checking that _impersonating_from exists in the session
     * (server-side only — cannot be forged from client).
     */
    public function stop_impersonation() {
        $originalAdmin = Session::get('_impersonating_from');

        if (!$originalAdmin || empty($originalAdmin['id'])) {
            $this->setFlash('error', 'You are not currently impersonating a tenant.');
            $this->redirect('index.php?page=dashboard');
            return;
        }

        // Verify the original admin still exists and is still a super-admin
        try {
            $db = Database::getInstance();
            $adminUser = $db->query(
                "SELECT * FROM users WHERE id = ? AND deleted_at IS NULL",
                [$originalAdmin['id']]
            )->fetch(\PDO::FETCH_ASSOC);

            if (!$adminUser) {
                Session::remove('_impersonating_from');
                $this->setFlash('error', 'Original admin account no longer exists. Please log in again.');
                Session::destroy();
                $this->redirect('index.php?page=login');
                return;
            }

            // Verify super-admin status (defense-in-depth)
            $isSA = !empty($adminUser['is_super_admin']);
            if (!$isSA && !empty($adminUser['role_id'])) {
                $role = $db->query(
                    "SELECT is_super_admin FROM roles WHERE id = ?", [$adminUser['role_id']]
                )->fetch();
                $isSA = !empty($role['is_super_admin']);
            }

            if (!$isSA) {
                Session::remove('_impersonating_from');
                $this->setFlash('error', 'Original account is no longer a super admin. Please log in again.');
                Session::destroy();
                $this->redirect('index.php?page=login');
                return;
            }

            // Restore the admin session
            session_regenerate_id(true);
            $adminUser['is_super_admin'] = true;
            Session::set('user', $adminUser);
            Session::remove('_impersonating_from');
            Session::clearPermissionCache();

            // Clear tenant context — super-admin has no tenant
            Tenant::reset();

            // Log the return
            try {
                $db->query(
                    "INSERT INTO activity_log (user_id, action, module, ip_address) VALUES (?, ?, ?, ?)",
                    [$adminUser['id'], 'SuperAdmin Stopped Impersonation', 'platform', $_SERVER['REMOTE_ADDR'] ?? null]
                );
            } catch (\Exception $e) {}

            $this->setFlash('success', 'Returned to super-admin session.');
            $this->redirect('index.php?page=platform&action=dashboard');

        } catch (\Exception $e) {
            error_log('[Platform] stop_impersonation failed: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to restore admin session. Please log in again.');
            Session::destroy();
            $this->redirect('index.php?page=login');
        }
    }

    /**
     * Subscription Management
     */
    public function subscriptions() {
        $db = Database::getInstance();
        $subs = $db->query("
            SELECT ts.*, c.name as company_name, p.name as plan_name 
            FROM tenant_subscriptions ts
            JOIN companies c ON ts.company_id = c.id
            JOIN saas_plans p ON ts.plan_id = p.id
            ORDER BY ts.created_at DESC
        ")->fetchAll();

        // Since it's management, maybe they want to see all companies and their sub status too
        // But requested: show active, expired, trial users...
        
        $this->view('platform.subscriptions', [
            'pageTitle' => 'Subscription Management',
            'subscriptions' => $subs
        ]);
    }

    /**
     * Platform payment logs from SaaS gateway.
     */
    public function payments() {
        $db = Database::getInstance();
        $logs = [];
        try {
            $logs = $db->query(
                "SELECT pt.*, c.name AS company_name, sp.name AS plan_name
                 FROM saas_payment_transactions pt
                 LEFT JOIN tenant_subscriptions ts ON ts.id = pt.subscription_id
                 LEFT JOIN companies c ON c.id = pt.company_id
                 LEFT JOIN saas_plans sp ON sp.id = ts.plan_id
                 ORDER BY pt.id DESC LIMIT 250"
            )->fetchAll();
        } catch (\Throwable $e) {
            $this->setFlash('warning', 'Payment logs table not available yet. Run latest migration.');
        }

        $this->view('platform.payments', [
            'pageTitle' => 'SaaS Payment Logs',
            'logs' => $logs,
        ]);
    }

    /**
     * Friendly route handoff for /platform/promos
     */
    public function promos() {
        $this->redirect('index.php?page=promos');
    }

    /**
     * Friendly route handoff for /platform/referrals
     */
    public function referrals() {
        $this->redirect('index.php?page=referrals');
    }

    /**
     * Revenue Analytics
     */
    public function revenue() {
        $db = Database::getInstance();
        $history = $db->query("
            SELECT th.*, c.name as company_name 
            FROM tenant_billing_history th
            JOIN companies c ON th.company_id = c.id
            ORDER BY th.billing_date DESC LIMIT 500
        ")->fetchAll();

        // Calculate simple ARR/MRR
        $mrr = $db->query("
            SELECT COALESCE(SUM(p.price), 0) 
            FROM companies c
            JOIN saas_plans p ON c.saas_plan_id = p.id
            WHERE c.subscription_status = 'active'
        ")->fetchColumn();
        $arr = $mrr * 12;
        
        $this->view('platform.revenue', [
            'pageTitle' => 'Revenue Analytics',
            'history' => $history,
            'mrr' => $mrr,
            'arr' => $arr
        ]);
    }

    /**
     * System Monitoring
     */
    public function system() {
        $db = Database::getInstance();
        // Fetch queue info
        $queueStats = [];
        try {
            $queueStats = $db->query("SELECT status, COUNT(*) as cnt FROM jobs GROUP BY status")->fetchAll(\PDO::FETCH_KEY_PAIR);
        } catch (\Exception $e) {}

        // Fetch logs
        $logDir = BASE_PATH . '/logs';
        $errorLogs = [];
        if (file_exists($logDir . '/error.log')) {
            // grab last 50 lines
            $lines = file($logDir . '/error.log');
            if ($lines) {
                $errorLogs = array_slice($lines, -50);
                $errorLogs = array_reverse($errorLogs);
            }
        }

        $sysHealth = [
            'redis'   => extension_loaded('redis') ? 'Connected' : 'Missing',
            'disk'    => round(@disk_free_space(BASE_PATH) / 1073741824, 2) . ' GB Free',
            'mem'     => round(memory_get_usage(true) / 1048576, 2) . ' MB',
            'php'     => PHP_VERSION,
            'opcache' => function_exists('opcache_get_status') ? (opcache_get_status() ? 'Enabled' : 'Disabled') : 'Disabled'
        ];

        $this->view('platform.system', [
            'pageTitle' => 'System Health',
            'queueStats' => $queueStats,
            'errorLogs' => $errorLogs,
            'sysHealth' => $sysHealth
        ]);
    }
}
