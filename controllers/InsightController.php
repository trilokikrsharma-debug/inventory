<?php
/**
 * Insight Controller — Lightweight automated business insights
 *
 * Provides simple rule-based, data-driven insights for the dashboard.
 * Uses database aggregations to generate actionable recommendations.
 * No external AI API required — pure SQL analytics.
 */
class InsightController extends Controller {
    protected $allowedActions = ['index', 'get_insights'];
    private BusinessInsightService $insightService;

    public function __construct() {
        $this->insightService = new BusinessInsightService();
    }

    public function index() {
        $this->requireTenantInsightsAccess();
        $this->requirePermission('dashboard.view');
        $insights = $this->generateInsightsSafe();
        $this->view('insights.index', [
            'pageTitle' => 'Automated Insights',
            'insights' => $insights,
        ]);
    }

    /**
     * AJAX endpoint for embedding insights in dashboard
     */
    public function get_insights() {
        $this->requireTenantInsightsAccess();
        $this->requirePermission('dashboard.view');
        $insights = $this->generateInsightsSafe();
        $this->json(['success' => true, 'insights' => $insights]);
    }

    private function requireTenantInsightsAccess(): void {
        $this->requireFeature('ai_insights');

        if (Tenant::id() !== null) {
            return;
        }

        if ($this->isAjax()) {
            $this->json(['success' => false, 'message' => 'Insights are available only inside a tenant account.'], 403);
        }

        Session::setFlash('error', 'Insights are available only inside a tenant account.');
        $this->redirect('index.php?page=dashboard');
    }

    private function generateInsightsSafe(): array {
        try {
            return $this->insightService->generateForCurrentTenant();
        } catch (\Throwable $e) {
            error_log('[INSIGHTS_ERROR] ' . $e->getMessage());
            return [];
        }
    }
}
