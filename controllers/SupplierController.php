<?php
/**
 * Supplier Controller
 */
class SupplierController extends Controller {

    protected $allowedActions = ['index', 'create', 'edit', 'view_supplier', 'delete', 'import', 'download_template'];
    private ?SupplierWorkflowService $supplierWorkflowService = null;

    public function index() {
        $this->requirePermission('suppliers.view');
        $search = $this->get('search', '');
        $page = max(1, (int)$this->get('pg', 1));
        $suppliers = (new SupplierModel())->getAllPaginated($search, $page);
        $this->view('suppliers.index', ['pageTitle' => 'Suppliers', 'suppliers' => $suppliers, 'search' => $search]);
    }

    public function import() {
        $this->requireFeature('bulk_import');
        $this->requirePermission('suppliers.create');

        $analysis = null;
        $dryRun = true;
        $service = new ContactImportService();

        if ($this->isPost()) {
            $this->validateCSRF();
            $dryRun = $this->post('dry_run') === '1';

            try {
                $analysis = $service->analyzeUploadedFile('supplier', $_FILES['import_file'] ?? [], $service->buildContext('supplier'));
                if (!$dryRun) {
                    $validRows = (array)($analysis['valid_rows'] ?? []);
                    $invalidCount = (int)($analysis['summary']['invalid_rows'] ?? 0);

                    if ($invalidCount > 0) {
                        $this->setFlash('error', 'Fix invalid rows before importing suppliers.');
                    } elseif (empty($validRows)) {
                        $this->setFlash('error', 'No valid rows were found to import.');
                    } else {
                        $imported = $this->workflowService()->persistImportedContacts($validRows);
                        $this->setFlash('success', 'Imported ' . $imported . ' supplier(s) successfully.');
                    }
                }
            } catch (\Throwable $e) {
                $this->setFlash('error', $e->getMessage());
            }
        }

        $this->view('shared.contact-import', [
            'pageTitle' => 'Bulk Import Suppliers',
            'entityLabel' => 'Suppliers',
            'entityKey' => 'suppliers',
            'templateAction' => 'download_template',
            'analysis' => $analysis,
            'dryRun' => $dryRun,
        ]);
    }

    public function download_template() {
        $this->requireFeature('bulk_import');
        $this->requirePermission('suppliers.create');

        $service = new ContactImportService();
        $filename = 'supplier_import_template_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $service->templateCsv('supplier');
        exit;
    }

    public function create() {
        $this->requirePermission('suppliers.create');
        if ($this->isPost()) {
            $this->validateCSRF();

            // Enterprise validation
            $v = Validator::make($_POST, [
                'name'            => 'required|string|min:2|max:100',
                'email'           => 'nullable|email',
                'phone'           => 'nullable|string|max:20',
                'address'         => 'nullable|string|max:255',
                'city'            => 'nullable|string|max:100',
                'state'           => 'nullable|string|max:100',
                'zip'             => 'nullable|string|max:20',
                'tax_number'      => 'nullable|string|max:50',
                'opening_balance' => 'nullable|numeric|min:0',
            ]);

            if ($v->fails()) {
                $this->setFlash('error', $v->firstError());
                $this->view('suppliers.create', ['pageTitle' => 'Add Supplier']);
                return;
            }

            $clean = $v->validated();
            $data = $this->workflowService()->buildPayload($clean, true);
            $supplierId = (new SupplierModel())->create($data);
            $this->logActivity('Created supplier: ' . $data['name'], 'suppliers', $supplierId, 'Opening balance: ' . $data['opening_balance']);
            Logger::audit('supplier_created', 'suppliers', $supplierId, ['name' => $data['name'], 'balance' => $data['opening_balance']]);
            $this->setFlash('success', 'Supplier created successfully.');
            $this->redirect('index.php?page=suppliers');
        }
        $this->view('suppliers.create', ['pageTitle' => 'Add Supplier']);
    }

    public function edit() {
        $this->requirePermission('suppliers.edit');
        $id = (int)$this->get('id');
        $supplierModel = new SupplierModel();
        $supplier = $supplierModel->find($id);

        // Shared resource: non-admin users cannot edit (no created_by field)
        $this->authorizeRecordAccess($supplier, 'index.php?page=suppliers', false);

        if ($this->isPost()) {
            $this->validateCSRF();
            $supplierModel->update($id, $this->workflowService()->buildPayload($this->post(), false));
            $this->logActivity('Updated supplier: ' . $this->sanitize($this->post('name')), 'suppliers', $id);
            $this->setFlash('success', 'Supplier updated successfully.');
            $this->redirect('index.php?page=suppliers');
        }
        $this->view('suppliers.edit', ['pageTitle' => 'Edit Supplier', 'supplier' => $supplier]);
    }

    public function view_supplier() {
        $this->requirePermission('suppliers.view');
        $id = (int)$this->get('id');
        $supplierModel = new SupplierModel();
        $supplier = $supplierModel->find($id);

        // Shared resource: all authenticated users can view suppliers
        $this->authorizeRecordAccess($supplier, 'index.php?page=suppliers', true);

        $ledger = $supplierModel->getLedger($id, $this->get('from_date'), $this->get('to_date'));
        $this->view('suppliers.view', ['pageTitle' => 'Supplier Details', 'supplier' => $supplier, 'ledger' => $ledger]);
    }

    public function delete() {
        $this->requirePermission('suppliers.delete');
        if (!$this->isPost()) { $this->redirect('index.php?page=suppliers'); }
        $this->validateCSRF();
        $id = (int)$this->post('id');
        $db = Database::getInstance();

        // Check for linked purchases
        $purchaseCount = $db->query(
            "SELECT COUNT(*) FROM purchases WHERE supplier_id = ? AND deleted_at IS NULL" . (Tenant::id() !== null ? " AND company_id = ?" : ""),
            Tenant::id() !== null ? [$id, Tenant::id()] : [$id]
        )->fetchColumn();

        if ($purchaseCount > 0) {
            $this->setFlash('error', 'Cannot delete supplier: ' . $purchaseCount . ' active purchase(s) exist. Delete them first.');
            $this->redirect('index.php?page=suppliers');
            return;
        }

        // Check for linked payments
        $paymentsCount = $db->query(
            "SELECT COUNT(*) FROM payments WHERE supplier_id = ? AND deleted_at IS NULL" . (Tenant::id() !== null ? " AND company_id = ?" : ""),
            Tenant::id() !== null ? [$id, Tenant::id()] : [$id]
        )->fetchColumn();

        if ($paymentsCount > 0) {
            $this->setFlash('error', 'Cannot delete supplier: ' . $paymentsCount . ' active payment(s) exist. Delete them first.');
            $this->redirect('index.php?page=suppliers');
            return;
        }

        $supplier = (new SupplierModel())->find($id);
        (new SupplierModel())->delete($id);
        $this->logActivity('Deleted supplier: ' . ($supplier['name'] ?? $id), 'suppliers', $id, 'Balance: ' . ($supplier['current_balance'] ?? 0));
        $this->setFlash('success', 'Supplier deleted.');
        $this->redirect('index.php?page=suppliers');
    }

    private function workflowService(): SupplierWorkflowService {
        if ($this->supplierWorkflowService === null) {
            $this->supplierWorkflowService = new SupplierWorkflowService();
        }

        return $this->supplierWorkflowService;
    }
}
