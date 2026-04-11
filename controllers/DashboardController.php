<?php
/**
 * Dashboard Controller
 *
 * Displays dashboard summary with stats, charts, and alerts.
 */
class DashboardController extends Controller {

    protected $allowedActions = ['index'];
    private ?DashboardWorkflowService $dashboardWorkflowService = null;

    public function index() {
        $this->requireAuth();

        // SECURITY: Platform super-admins have no tenant context.
        // Redirect them to the platform dashboard where they belong.
        if (Session::isSuperAdmin()) {
            $this->redirect('platform/dashboard');
            return;
        }
        $this->view('dashboard.index', $this->workflowService()->buildViewData());
    }

    /**
     * Invalidate all dashboard caches for the current tenant.
     * Call from SalesController::create(), PurchaseController::create(), etc.
     */
    public static function invalidateCache(): void {
        $prefix = 'c' . (Tenant::id() ?? 0) . '_dash_';
        Cache::flushPrefix($prefix);
    }

    private function workflowService(): DashboardWorkflowService {
        if ($this->dashboardWorkflowService === null) {
            $this->dashboardWorkflowService = new DashboardWorkflowService();
        }

        return $this->dashboardWorkflowService;
    }
}
