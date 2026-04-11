<?php
/**
 * Purchase Controller
 *
 * Handles purchase entries with multiple items, auto stock updates.
 */
class PurchaseController extends Controller {

    protected $allowedActions = ['index', 'create', 'edit', 'view_purchase', 'delete'];
    private ?PurchaseWorkflowService $purchaseWorkflowService = null;

    private function warehouseFeatureEnabled(): bool {
        return !Session::isSuperAdmin()
            && Tenant::id() !== null
            && Tenant::canUse('multi_warehouse');
    }

    public function index() {
        $this->requirePermission('purchases.view');
        $purchases = (new PurchaseModel())->getAllWithSupplier(
            $this->get('search', ''),
            $this->get('from_date', ''),
            $this->get('to_date', ''),
            $this->get('supplier_id', ''),
            $this->get('status', ''),
            max(1, (int)$this->get('pg', 1))
        );
        $suppliers = (new SupplierModel())->allActive();

        $this->view('purchases.index', [
            'pageTitle' => 'Purchases',
            'purchases' => $purchases,
            'suppliers' => $suppliers,
            'filters'   => $this->safeFilters(),
        ]);
    }

    public function create() {
        $this->requirePermission('purchases.create');

        if ($this->isPost()) {
            $this->validateCSRF();
            $settingsModel = new SettingsModel();
            $invoiceNumber = $settingsModel->getNextNumber('purchase');
            $settings = $settingsModel->getSettings();
            $warehouseOptions = $this->warehouseFeatureEnabled() ? (new PurchaseModel())->activeWarehouses() : [];
            try {
                $prepared = $this->workflowService()->buildCreatePayload(
                    $this->post(),
                    $settings,
                    $invoiceNumber,
                    $this->warehouseFeatureEnabled(),
                    $warehouseOptions
                );
                $purchaseData = $prepared['purchase'];
                $items = $prepared['items'];
            } catch (\RuntimeException | \InvalidArgumentException $e) {
                $this->setFlash('error', $e->getMessage());
                $this->redirect('index.php?page=purchases&action=create');
                return;
            }

            try {
                $purchaseModel = new PurchaseModel();
                $purchaseId = $purchaseModel->createPurchase($purchaseData, $items, Session::get('user')['id']);

                // Create payment record if paid
                if (($purchaseData['paid_amount'] ?? 0) > 0) {
                    $paymentModel = new PaymentModel();
                    $paymentNumber = $settingsModel->getNextNumber('payment');
                    $paymentPayload = $this->workflowService()->buildPaymentPayload(
                        $this->post(),
                        $purchaseData,
                        $paymentNumber,
                        $purchaseId,
                        (int)(Session::get('user')['id'] ?? 0)
                    );
                    if ($paymentPayload !== null) {
                        $paymentModel->create($paymentPayload);
                    }
                }

                $this->logActivity('Created purchase: ' . $invoiceNumber, 'purchases', $purchaseId);
                $this->setFlash('success', 'Purchase created successfully. Invoice: ' . $invoiceNumber);
                $this->redirect('index.php?page=purchases&action=view_purchase&id=' . $purchaseId);
            } catch (Exception $e) {
                error_log($e->getMessage());
                $this->setFlash('error', 'An unexpected error occurred. Please try again.');
                $this->redirect('index.php?page=purchases&action=create');
            }
        }

        $suppliers = (new SupplierModel())->allActive();
        $settings = (new SettingsModel())->getSettings();

        $this->view('purchases.create', [
            'pageTitle' => 'New Purchase',
            'suppliers' => $suppliers,
            'settings'  => $settings,
            'warehouses' => $this->warehouseFeatureEnabled() ? (new PurchaseModel())->activeWarehouses() : [],
            'hasWarehouseFeature' => $this->warehouseFeatureEnabled(),
        ]);
    }

    public function edit() {
        $this->requirePermission('purchases.edit');
        $id = (int)$this->get('id');

        $purchaseModel = new PurchaseModel();
        $purchase = $purchaseModel->getWithDetails($id);
        if (!$purchase) {
            $this->setFlash('error', 'Purchase not found.');
            $this->redirect('index.php?page=purchases');
            return;
        }

        if ($this->isPost()) {
            $this->validateCSRF();
            $warehouseOptions = $this->warehouseFeatureEnabled() ? (new PurchaseModel())->activeWarehouses() : [];
            $settings = (new SettingsModel())->getSettings();
            try {
                $prepared = $this->workflowService()->buildUpdatePayload(
                    $this->post(),
                    $settings,
                    $this->warehouseFeatureEnabled(),
                    $warehouseOptions
                );
                $purchaseData = $prepared['purchase'];
                $items = $prepared['items'];
            } catch (\RuntimeException | \InvalidArgumentException $e) {
                $this->setFlash('error', $e->getMessage());
                $this->redirect('index.php?page=purchases&action=edit&id=' . $id);
                return;
            }

            try {
                $purchaseModel->updatePurchase($id, $purchaseData, $items, Session::get('user')['id']);
                $this->logActivity('Edited purchase: ' . $purchase['invoice_number'], 'purchases', $id);
                $this->setFlash('success', 'Purchase updated. Stock and balances have been reconciled.');
                $this->redirect('index.php?page=purchases&action=view_purchase&id=' . $id);
            } catch (Exception $e) {
                error_log($e->getMessage());
                $this->setFlash('error', 'An unexpected error occurred. Please try again.');
                $this->redirect('index.php?page=purchases&action=edit&id=' . $id);
            }
        }

        $suppliers = (new SupplierModel())->allActive();
        $settings  = (new SettingsModel())->getSettings();
        $this->view('purchases.edit', [
            'pageTitle' => 'Edit Purchase: ' . $purchase['invoice_number'],
            'purchase'  => $purchase,
            'suppliers' => $suppliers,
            'settings'  => $settings,
            'warehouses' => $this->warehouseFeatureEnabled() ? (new PurchaseModel())->activeWarehouses() : [],
            'hasWarehouseFeature' => $this->warehouseFeatureEnabled(),
        ]);
    }

    public function view_purchase() {
        $this->requirePermission('purchases.view');
        $id = (int)$this->get('id');
        $purchase = (new PurchaseModel())->getWithDetails($id);
        $this->authorizeRecordAccess($purchase, 'index.php?page=purchases');

        $this->view('purchases.view', [
            'pageTitle' => 'Purchase Details',
            'purchase'  => $purchase,
            'warehouseName' => $purchase['warehouse_name'] ?? null,
        ]);
    }

    public function delete() {
        $this->requirePermission('purchases.delete');
        if (!$this->isPost()) { $this->redirect('index.php?page=purchases'); }
        $this->validateCSRF();

        $id     = (int)$this->post('id');
        $userId = Session::get('user')['id'];

        try {
            $purchase = (new PurchaseModel())->getWithDetails($id);
            (new PurchaseModel())->deletePurchase($id, $userId);
            $this->logActivity('Deleted purchase: ' . ($purchase['invoice_number'] ?? $id), 'purchases', $id, 'Grand total: ' . ($purchase['grand_total'] ?? 0));
            $this->setFlash('success', 'Purchase deleted. Stock and supplier balance have been reversed.');
        } catch (Exception $e) {
            error_log($e->getMessage());
            $this->setFlash('error', 'An unexpected error occurred. Please try again.');
        }
        $this->redirect('index.php?page=purchases');
    }

    private function workflowService(): PurchaseWorkflowService {
        if ($this->purchaseWorkflowService === null) {
            $this->purchaseWorkflowService = new PurchaseWorkflowService();
        }

        return $this->purchaseWorkflowService;
    }
}
