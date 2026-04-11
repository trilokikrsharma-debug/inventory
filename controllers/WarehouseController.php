<?php

class WarehouseController extends Controller {
    protected $allowedActions = ['index', 'create', 'edit', 'delete', 'set_default', 'transfer', 'approve_transfer', 'reject_transfer', 'search_products'];
    private ?WarehouseWorkflowService $warehouseWorkflowService = null;

    public function index() {
        $this->guardWarehouseAccess();

        $warehouseModel = new WarehouseModel();
        $warehouses = $warehouseModel->listWithStats();
        $activeWarehouses = $warehouseModel->allActiveOrdered();
        $editId = (int)$this->get('edit_id', 0);
        $editingWarehouse = $editId > 0 ? $warehouseModel->find($editId) : null;

        $this->view('warehouses.index', [
            'pageTitle' => 'Warehouses',
            'warehouses' => $warehouses,
            'activeWarehouses' => $activeWarehouses,
            'editingWarehouse' => $editingWarehouse,
            'recentTransfers' => $warehouseModel->recentTransfers(),
            'canTransferStock' => count($activeWarehouses) >= 2,
        ]);
    }

    public function create() {
        $this->guardWarehouseAccess();
        if (!$this->isPost()) {
            $this->redirect('index.php?page=warehouses');
            return;
        }

        $this->validateCSRF();
        $warehouseModel = new WarehouseModel();
        try {
            $payload = $this->workflowService()->validateWarehousePayload($this->post());
        } catch (\RuntimeException $e) {
            $this->setFlash('error', $e->getMessage());
            $this->redirect('index.php?page=warehouses');
            return;
        }

        if (!empty($payload['code']) && $warehouseModel->codeExists($payload['code'])) {
            $this->setFlash('error', 'Warehouse code already exists.');
            $this->redirect('index.php?page=warehouses');
            return;
        }

        $warehouseId = (int)$warehouseModel->create($payload);
        if ($warehouseModel->warehouseCount() === 1 || !empty($payload['is_default'])) {
            $warehouseModel->setDefaultWarehouse($warehouseId);
        }

        $this->logActivity('Created warehouse: ' . $payload['name'], 'warehouses', $warehouseId);
        $this->setFlash('success', 'Warehouse created successfully.');
        $this->redirect('index.php?page=warehouses');
    }

    public function edit() {
        $this->guardWarehouseAccess();
        if (!$this->isPost()) {
            $this->redirect('index.php?page=warehouses');
            return;
        }

        $this->validateCSRF();
        $id = (int)$this->post('id', 0);
        $warehouseModel = new WarehouseModel();
        $warehouse = $warehouseModel->find($id);
        if (!$warehouse) {
            $this->setFlash('error', 'Warehouse not found.');
            $this->redirect('index.php?page=warehouses');
            return;
        }

        try {
            $payload = $this->workflowService()->validateWarehousePayload($this->post());
        } catch (\RuntimeException $e) {
            $this->setFlash('error', $e->getMessage());
            $this->redirect('index.php?page=warehouses&edit_id=' . $id);
            return;
        }
        if (!empty($payload['code']) && $warehouseModel->codeExists($payload['code'], $id)) {
            $this->setFlash('error', 'Warehouse code already exists.');
            $this->redirect('index.php?page=warehouses&edit_id=' . $id);
            return;
        }

        $warehouseModel->update($id, $payload);
        if (!empty($payload['is_default'])) {
            $warehouseModel->setDefaultWarehouse($id);
        }

        $this->logActivity('Updated warehouse: ' . $payload['name'], 'warehouses', $id);
        $this->setFlash('success', 'Warehouse updated successfully.');
        $this->redirect('index.php?page=warehouses');
    }

    public function delete() {
        $this->guardWarehouseAccess();
        if (!$this->isPost()) {
            $this->redirect('index.php?page=warehouses');
            return;
        }

        $this->validateCSRF();
        $id = (int)$this->post('id', 0);
        $warehouseModel = new WarehouseModel();
        $warehouse = $warehouseModel->find($id);
        if (!$warehouse) {
            $this->setFlash('error', 'Warehouse not found.');
            $this->redirect('index.php?page=warehouses');
            return;
        }

        if (!empty($warehouse['is_default'])) {
            $this->setFlash('error', 'Default warehouse cannot be deleted. Set another warehouse as default first.');
            $this->redirect('index.php?page=warehouses');
            return;
        }

        if ($warehouseModel->hasBlockingStock($id)) {
            $this->setFlash('error', 'Warehouse cannot be deleted while stock is assigned to it.');
            $this->redirect('index.php?page=warehouses');
            return;
        }

        $warehouseModel->delete($id);
        Database::getInstance()->query(
            "DELETE FROM product_warehouse_stock WHERE company_id = ? AND warehouse_id = ?",
            [Tenant::require(), $id]
        );
        $this->logActivity('Deleted warehouse: ' . ($warehouse['name'] ?? $id), 'warehouses', $id);
        $this->setFlash('success', 'Warehouse deleted successfully.');
        $this->redirect('index.php?page=warehouses');
    }

    public function set_default() {
        $this->guardWarehouseAccess();
        if (!$this->isPost()) {
            $this->redirect('index.php?page=warehouses');
            return;
        }

        $this->validateCSRF();
        $id = (int)$this->post('id', 0);
        $warehouseModel = new WarehouseModel();
        $warehouse = $warehouseModel->find($id);
        if (!$warehouse) {
            $this->setFlash('error', 'Warehouse not found.');
            $this->redirect('index.php?page=warehouses');
            return;
        }

        $warehouseModel->setDefaultWarehouse($id);
        $this->logActivity('Set default warehouse: ' . ($warehouse['name'] ?? $id), 'warehouses', $id);
        $this->setFlash('success', 'Default warehouse updated.');
        $this->redirect('index.php?page=warehouses');
    }

    public function transfer() {
        $this->guardWarehouseAccess();
        if (!$this->isPost()) {
            $this->redirect('index.php?page=warehouses');
            return;
        }

        $this->validateCSRF();
        try {
            $result = $this->workflowService()->createTransferRequest($this->post(), (int)(Session::get('user')['id'] ?? 0));
        } catch (\RuntimeException $e) {
            $this->setFlash('error', $e->getMessage());
            $this->redirect('index.php?page=warehouses');
            return;
        } catch (\Throwable $e) {
            error_log('[WAREHOUSE_TRANSFER] ' . $e->getMessage());
            $this->setFlash('error', 'Transfer could not be completed. Please try again.');
            $this->redirect('index.php?page=warehouses');
            return;
        }

        $this->logActivity(
            'Created warehouse transfer request: ' . $result['transfer_number'],
            'warehouses',
            (int)$result['id'],
            'Route: ' . $result['source_warehouse_name'] . ' -> ' . $result['destination_warehouse_name']
        );
        $this->setFlash('success', 'Transfer request created. Pending approval: ' . $result['transfer_number']);
        $this->redirect('index.php?page=warehouses');
    }

    public function approve_transfer() {
        $this->guardWarehouseAccess();
        if (!$this->isPost()) {
            $this->redirect('index.php?page=warehouses');
            return;
        }

        $this->validateCSRF();
        $transferId = (int)$this->post('id', 0);
        try {
            $result = $this->workflowService()->approveTransfer($transferId, (int)(Session::get('user')['id'] ?? 0));
            $this->logActivity(
                'Approved warehouse transfer: ' . $result['transfer_number'],
                'warehouses',
                (int)$result['id'],
                'Route: ' . $result['source_warehouse_name'] . ' -> ' . $result['destination_warehouse_name']
            );
            $this->setFlash('success', 'Transfer approved and stock moved: ' . $result['transfer_number']);
        } catch (\RuntimeException $e) {
            $this->setFlash('error', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[WAREHOUSE_TRANSFER_APPROVE] ' . $e->getMessage());
            $this->setFlash('error', 'Transfer could not be approved. Please try again.');
        }

        $this->redirect('index.php?page=warehouses');
    }

    public function reject_transfer() {
        $this->guardWarehouseAccess();
        if (!$this->isPost()) {
            $this->redirect('index.php?page=warehouses');
            return;
        }

        $this->validateCSRF();
        $transferId = (int)$this->post('id', 0);
        $reason = trim((string)$this->post('rejection_reason', '')) ?: 'Rejected';
        try {
            $result = $this->workflowService()->rejectTransfer($transferId, (int)(Session::get('user')['id'] ?? 0), $reason);
            $this->logActivity(
                'Rejected warehouse transfer: ' . $result['transfer_number'],
                'warehouses',
                (int)$result['id'],
                'Route: ' . $result['source_warehouse_name'] . ' -> ' . $result['destination_warehouse_name']
            );
            $this->setFlash('success', 'Transfer rejected: ' . $result['transfer_number']);
        } catch (\RuntimeException $e) {
            $this->setFlash('error', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[WAREHOUSE_TRANSFER_REJECT] ' . $e->getMessage());
            $this->setFlash('error', 'Transfer could not be rejected. Please try again.');
        }

        $this->redirect('index.php?page=warehouses');
    }

    public function search_products() {
        $this->guardWarehouseAccess();

        $warehouseId = (int)$this->get('warehouse_id', 0);
        $term = trim((string)$this->get('term', ''));
        if ($warehouseId <= 0 || strlen($term) < 2) {
            $this->json([]);
            return;
        }

        $warehouseModel = new WarehouseModel();
        $allowed = false;
        foreach ($warehouseModel->allActiveOrdered() as $warehouse) {
            if ((int)$warehouse['id'] === $warehouseId) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) {
            $this->json([]);
            return;
        }

        $results = $warehouseModel->searchableProducts($term, $warehouseId);
        foreach ($results as &$row) {
            $rawName = Helper::decodeHtmlEntities($row['name'] ?? '');
            $row['name_raw'] = $rawName;
            $row['name'] = Helper::escape($rawName);
            $row['available_stock'] = round((float)($row['available_stock'] ?? 0), 3);
        }
        unset($row);

        $this->json($results);
    }

    private function guardWarehouseAccess(): void {
        $this->requireFeature('multi_warehouse');
        $this->requirePermission('catalog.manage');

        if (Session::isSuperAdmin() || Tenant::id() === null) {
            $this->setFlash('error', 'Warehouses can only be managed inside a tenant account.');
            $this->redirect('index.php?page=dashboard');
        }
    }

    private function workflowService(): WarehouseWorkflowService {
        if ($this->warehouseWorkflowService === null) {
            $this->warehouseWorkflowService = new WarehouseWorkflowService();
        }

        return $this->warehouseWorkflowService;
    }
}
