<?php
/**
 * SaaS Plan Management (super admin only)
 */
class SaaSPlanController extends Controller {
    protected $allowedActions = ['index', 'create', 'edit', 'delete', 'toggle'];

    private SaaSPlan $planModel;
    private ?SaaSPlanAdminService $planAdminService = null;

    public function __construct() {
        $this->requireSuperAdmin();
        $this->planModel = new SaaSPlan();
    }

    public function index() {
        $plans = [];
        try {
            $plans = $this->workflowService()->listPlans();
        } catch (\Throwable $e) {
            Logger::error('Failed to load SaaS plans', ['error' => $e->getMessage()]);
            $this->setFlash(
                'error',
                'SaaS billing schema is not ready. Run `database/014_saas_billing_system.sql` and refresh.'
            );
        }

        $this->view('platform.plans', [
            'pageTitle' => 'SaaS Plans',
            'plans' => $plans,
        ]);
    }

    public function create() {
        if ($this->isPost()) {
            $this->validateCSRF();
            if ($this->demoGuard()) {
                return;
            }

            $result = $this->workflowService()->createPlan($this->post());
            if (!$result['success']) {
                $this->setFlash('error', implode(' ', $result['errors'] ?? ['Failed to create plan.']));
                $this->redirect('index.php?page=saas_plans&action=create');
                return;
            }

            $this->logActivity('SaaS plan created', 'saas_plans', (int)$result['id']);
            $this->setFlash('success', 'Plan created successfully.');
            $this->redirect('index.php?page=saas_plans');
            return;
        }

        $this->view('platform.plan-form', [
            'pageTitle' => 'Create SaaS Plan',
            'mode' => 'create',
            'plan' => null,
        ]);
    }

    public function edit() {
        $id = (int)$this->get('id');
        if ($id <= 0) {
            $this->setFlash('error', 'Invalid plan id.');
            $this->redirect('index.php?page=saas_plans');
            return;
        }

        try {
            $plan = $this->workflowService()->loadPlan($id);
        } catch (\Throwable $e) {
            Logger::error('Failed to load SaaS plan', ['id' => $id, 'error' => $e->getMessage()]);
            $plan = null;
        }
        if (!$plan) {
            $this->setFlash('error', 'Plan not found.');
            $this->redirect('index.php?page=saas_plans');
            return;
        }

        if ($this->isPost()) {
            $this->validateCSRF();
            if ($this->demoGuard()) {
                return;
            }

            $result = $this->workflowService()->updatePlan($id, $this->post());
            if (!$result['success']) {
                $this->setFlash('error', implode(' ', $result['errors'] ?? ['Failed to update plan.']));
                $this->redirect('index.php?page=saas_plans&action=edit&id=' . $id);
                return;
            }

            $this->logActivity('SaaS plan updated', 'saas_plans', $id);
            $this->setFlash('success', 'Plan updated successfully.');
            $this->redirect('index.php?page=saas_plans');
            return;
        }

        $this->view('platform.plan-form', [
            'pageTitle' => 'Edit SaaS Plan',
            'mode' => 'edit',
            'plan' => $plan,
        ]);
    }

    public function delete() {
        if (!$this->isPost()) {
            $this->redirect('index.php?page=saas_plans');
            return;
        }
        $this->validateCSRF();
        if ($this->demoGuard()) {
            return;
        }

        $id = (int)$this->post('id');
        $result = $this->workflowService()->deletePlan($id);
        if (!empty($result['success'])) {
            $this->logActivity('SaaS plan deleted/disabled', 'saas_plans', $id, $result['message'] ?? null);
            $this->setFlash('success', $result['message'] ?? 'Plan updated.');
        } else {
            $this->setFlash('error', $result['message'] ?? 'Failed to delete plan.');
        }
        $this->redirect('index.php?page=saas_plans');
    }

    public function toggle() {
        if (!$this->isPost()) {
            $this->redirect('index.php?page=saas_plans');
            return;
        }
        $this->validateCSRF();
        if ($this->demoGuard()) {
            return;
        }

        $id = (int)$this->post('id');
        $result = $this->workflowService()->toggleStatus($id);
        if (!$result) {
            $this->setFlash('error', 'Plan not found.');
            $this->redirect('index.php?page=saas_plans');
            return;
        }

        $this->logActivity('SaaS plan status changed', 'saas_plans', $id, 'Status: ' . $result['status']);
        $this->setFlash('success', 'Plan status updated.');
        $this->redirect('index.php?page=saas_plans');
    }

    private function workflowService(): SaaSPlanAdminService {
        if ($this->planAdminService === null) {
            $this->planAdminService = new SaaSPlanAdminService($this->planModel);
        }

        return $this->planAdminService;
    }
}
