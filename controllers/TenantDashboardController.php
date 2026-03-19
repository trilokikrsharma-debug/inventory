<?php
/**
 * Tenant Dashboard Controller
 * 
 * SaaS usage dashboard for MSME owners. 
 * Allows them to view current plan, usage limits, and billing history.
 */
class TenantDashboardController extends Controller {

    protected $allowedActions = ['index', 'billing_history'];

    /**
     * Show SaaS dashboard / current plan details
     */
    public function index() {
        $this->requireAuth();
        // Ideally enforce that only the 'admin' or 'billing' role can see this page
        // For MVP, we pass it if they have auth
        
        $tenantId = Tenant::id();
        $db = Database::getInstance();

        // Get tenant's current plan info
        $tenant = $db->query(
            "SELECT t.*, p.name as plan_name, p.max_users, p.price, p.offer_price, p.billing_cycle, p.billing_type, p.duration_days, p.features
             FROM companies t 
             LEFT JOIN saas_plans p ON t.saas_plan_id = p.id 
             WHERE t.id = ?", 
            [$tenantId]
        )->fetch();

        if (!$tenant) {
            $this->setFlash('error', 'Company not found.');
            $this->redirect('index.php?page=dashboard');
            return;
        }

        // Get actual user count
        $userCount = $db->query("SELECT COUNT(*) FROM users WHERE company_id = ? AND deleted_at IS NULL", [$tenantId])->fetchColumn();

        // Get available plans to upgrade
        $plans = (new SaaSPlan())->listForCheckout();

        // Get recent billing history (last 5)
        $history = $db->query(
            "SELECT * FROM tenant_billing_history WHERE company_id = ? ORDER BY id DESC LIMIT 5",
            [$tenantId]
        )->fetchAll();

        $this->view('saas.dashboard', [
            'pageTitle'  => 'SaaS Billing & Plan',
            'tenant'     => $tenant,
            'userCount'  => $userCount,
            'plans'      => $plans,
            'history'    => $history
        ]);
    }

    /**
     * API or List View for full billing history
     */
    public function billing_history() {
        $this->requireAuth();
        $tenantId = Tenant::id();
        $db = Database::getInstance();

        $history = $db->query(
            "SELECT * FROM tenant_billing_history WHERE company_id = ? ORDER BY id DESC",
            [$tenantId]
        )->fetchAll();

        if ($this->isAjax()) {
            echo json_encode(['success' => true, 'history' => $history]);
        } else {
            // Can render a full view if necessary
            $this->view('saas.billing_history', [
                'pageTitle' => 'Billing History',
                'history'   => $history
            ]);
        }
    }
}
