<?php
/**
 * Promo Code Management (super admin only)
 */
class PromoCodeController extends Controller {
    protected $allowedActions = ['index', 'create', 'edit', 'delete', 'toggle'];

    private PromoCode $promoModel;
    private ?PromoCodeAdminService $promoAdminService = null;

    public function __construct() {
        $this->requireSuperAdmin();
        $this->promoModel = new PromoCode();
    }

    public function index() {
        $promos = $this->workflowService()->listPromos();

        $this->view('platform.promos', [
            'pageTitle' => 'Promo Codes',
            'promos' => $promos,
        ]);
    }

    public function create() {
        if ($this->isPost()) {
            $this->validateCSRF();
            if ($this->demoGuard()) {
                return;
            }

            $result = $this->workflowService()->createPromo($this->post());
            if (!$result['success']) {
                $this->setFlash('error', implode(' ', $result['errors'] ?? ['Failed to create promo code.']));
                $this->redirect('index.php?page=promos&action=create');
                return;
            }

            $this->logActivity('Promo created', 'promo_codes', (int)$result['id']);
            $this->setFlash('success', 'Promo code created successfully.');
            $this->redirect('index.php?page=promos');
            return;
        }

        $this->view('platform.promo-form', [
            'pageTitle' => 'Create Promo Code',
            'mode' => 'create',
            'promo' => null,
        ]);
    }

    public function edit() {
        $id = (int)$this->get('id');
        if ($id <= 0) {
            $this->setFlash('error', 'Invalid promo id.');
            $this->redirect('index.php?page=promos');
            return;
        }

        $promo = $this->workflowService()->loadPromo($id);
        if (!$promo) {
            $this->setFlash('error', 'Promo code not found.');
            $this->redirect('index.php?page=promos');
            return;
        }

        if ($this->isPost()) {
            $this->validateCSRF();
            if ($this->demoGuard()) {
                return;
            }

            $result = $this->workflowService()->updatePromo($id, $this->post());
            if (!$result['success']) {
                $this->setFlash('error', implode(' ', $result['errors'] ?? ['Failed to update promo code.']));
                $this->redirect('index.php?page=promos&action=edit&id=' . $id);
                return;
            }

            $this->logActivity('Promo updated', 'promo_codes', $id);
            $this->setFlash('success', 'Promo code updated successfully.');
            $this->redirect('index.php?page=promos');
            return;
        }

        $this->view('platform.promo-form', [
            'pageTitle' => 'Edit Promo Code',
            'mode' => 'edit',
            'promo' => $promo,
        ]);
    }

    public function delete() {
        if (!$this->isPost()) {
            $this->redirect('index.php?page=promos');
            return;
        }
        $this->validateCSRF();
        if ($this->demoGuard()) {
            return;
        }

        $id = (int)$this->post('id');
        $result = $this->workflowService()->deletePromo($id);
        if (!empty($result['success'])) {
            $this->logActivity('Promo deleted/disabled', 'promo_codes', $id, $result['message'] ?? null);
            $this->setFlash('success', $result['message'] ?? 'Promo updated.');
        } else {
            $this->setFlash('error', $result['message'] ?? 'Failed to delete promo code.');
        }
        $this->redirect('index.php?page=promos');
    }

    public function toggle() {
        if (!$this->isPost()) {
            $this->redirect('index.php?page=promos');
            return;
        }
        $this->validateCSRF();
        if ($this->demoGuard()) {
            return;
        }

        $id = (int)$this->post('id');
        $result = $this->workflowService()->toggleStatus($id);
        if (!$result) {
            $this->setFlash('error', 'Promo code not found.');
            $this->redirect('index.php?page=promos');
            return;
        }

        $this->logActivity('Promo status changed', 'promo_codes', $id, 'Status: ' . $result['status']);
        $this->setFlash('success', 'Promo status updated.');
        $this->redirect('index.php?page=promos');
    }

    private function workflowService(): PromoCodeAdminService {
        if ($this->promoAdminService === null) {
            $this->promoAdminService = new PromoCodeAdminService($this->promoModel);
        }

        return $this->promoAdminService;
    }
}
