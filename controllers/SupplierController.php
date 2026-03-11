<?php
/**
 * Supplier Controller
 */
class SupplierController extends Controller {

    protected $allowedActions = ['index', 'create', 'edit', 'view_supplier', 'delete'];

    public function index() {
        $this->requirePermission('suppliers.view');
        $search = $this->get('search', '');
        $page = max(1, (int)$this->get('pg', 1));
        $suppliers = (new SupplierModel())->getAllPaginated($search, $page);
        $this->view('suppliers.index', ['pageTitle' => 'Suppliers', 'suppliers' => $suppliers, 'search' => $search]);
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
            $data = [
                'name'            => $clean['name'],
                'email'           => $clean['email'] ?: null,
                'phone'           => $clean['phone'] ?: null,
                'address'         => $clean['address'] ?? '',
                'city'            => $clean['city'] ?? '',
                'state'           => $clean['state'] ?? '',
                'zip'             => $clean['zip'] ?? '',
                'tax_number'      => $clean['tax_number'] ?? '',
                'opening_balance' => (float)($clean['opening_balance'] ?? 0),
                'current_balance' => (float)($clean['opening_balance'] ?? 0),
            ];
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
            $supplierModel->update($id, [
                'name' => $this->sanitize($this->post('name')),
                'email' => $this->sanitize($this->post('email')) ?: null,
                'phone' => $this->sanitize($this->post('phone')) ?: null,
                'address' => $this->sanitize($this->post('address')),
                'city' => $this->sanitize($this->post('city')),
                'state' => $this->sanitize($this->post('state')),
                'zip' => $this->sanitize($this->post('zip')),
                'tax_number' => $this->sanitize($this->post('tax_number')),
            ]);
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
}
