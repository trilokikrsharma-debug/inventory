<?php
/**
 * Customer Controller
 */
class CustomerController extends Controller {

    protected $allowedActions = ['index', 'create', 'edit', 'view_customer', 'delete', 'recalculate_balance'];

    public function index() {
        $this->requirePermission('customers.view');
        $search = $this->get('search', '');
        $page = max(1, (int)$this->get('pg', 1));
        $customers = (new CustomerModel())->getAllPaginated($search, $page);

        $this->view('customers.index', [
            'pageTitle' => 'Customers',
            'customers' => $customers,
            'search'    => $search,
        ]);
    }

    public function create() {
        $this->requirePermission('customers.create');
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
                $this->view('customers.create', ['pageTitle' => 'Add Customer']);
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
            $customerId = (new CustomerModel())->create($data);
            $this->logActivity('Created customer: ' . $data['name'], 'customers', $customerId, 'Opening balance: ' . $data['opening_balance']);
            Logger::audit('customer_created', 'customers', $customerId, ['name' => $data['name'], 'balance' => $data['opening_balance']]);
            $this->setFlash('success', 'Customer created successfully.');
            $this->redirect('index.php?page=customers');
        }
        $this->view('customers.create', ['pageTitle' => 'Add Customer']);
    }

    public function edit() {
        $this->requirePermission('customers.edit');
        $id = (int)$this->get('id');
        $customerModel = new CustomerModel();
        $customer = $customerModel->find($id);

        // Shared resource: non-admin users cannot edit (no created_by field)
        $this->authorizeRecordAccess($customer, 'index.php?page=customers', false);

        if ($this->isPost()) {
            $this->validateCSRF();
            $data = [
                'name'       => $this->sanitize($this->post('name')),
                'email'      => $this->sanitize($this->post('email')) ?: null,
                'phone'      => $this->sanitize($this->post('phone')) ?: null,
                'address'    => $this->sanitize($this->post('address')),
                'city'       => $this->sanitize($this->post('city')),
                'state'      => $this->sanitize($this->post('state')),
                'zip'        => $this->sanitize($this->post('zip')),
                'tax_number' => $this->sanitize($this->post('tax_number')),
            ];
            $customerModel->update($id, $data);
            $this->logActivity('Updated customer: ' . $data['name'], 'customers', $id);
            $this->setFlash('success', 'Customer updated successfully.');
            $this->redirect('index.php?page=customers');
        }

        $this->view('customers.edit', ['pageTitle' => 'Edit Customer', 'customer' => $customer]);
    }

    public function view_customer() {
        $this->requirePermission('customers.view');
        $id = (int)$this->get('id');
        $customerModel = new CustomerModel();
        $customer = $customerModel->find($id);

        // Shared resource: all authenticated users can view customers
        $this->authorizeRecordAccess($customer, 'index.php?page=customers', true);

        $ledger = $customerModel->getLedger($id, $this->get('from_date'), $this->get('to_date'));

        $this->view('customers.view', [
            'pageTitle' => 'Customer Details',
            'customer'  => $customer,
            'ledger'    => $ledger,
        ]);
    }

    public function delete() {
        $this->requirePermission('customers.delete');
        if (!$this->isPost()) { $this->redirect('index.php?page=customers'); }
        $this->validateCSRF();
        $id = (int)$this->post('id');
        $db = Database::getInstance();

        // Check for linked sales
        $salesCount = $db->query(
            "SELECT COUNT(*) FROM sales WHERE customer_id = ? AND deleted_at IS NULL" . (Tenant::id() !== null ? " AND company_id = ?" : ""),
            Tenant::id() !== null ? [$id, Tenant::id()] : [$id]
        )->fetchColumn();

        if ($salesCount > 0) {
            $this->setFlash('error', 'Cannot delete customer: ' . $salesCount . ' active sale(s) exist. Delete or reassign the sales first.');
            $this->redirect('index.php?page=customers');
            return;
        }

        // Check for linked payments/receipts
        $paymentsCount = $db->query(
            "SELECT COUNT(*) FROM payments WHERE customer_id = ? AND deleted_at IS NULL" . (Tenant::id() !== null ? " AND company_id = ?" : ""),
            Tenant::id() !== null ? [$id, Tenant::id()] : [$id]
        )->fetchColumn();

        if ($paymentsCount > 0) {
            $this->setFlash('error', 'Cannot delete customer: ' . $paymentsCount . ' active payment(s)/receipt(s) exist. Delete them first.');
            $this->redirect('index.php?page=customers');
            return;
        }

        $customer = (new CustomerModel())->find($id);
        (new CustomerModel())->delete($id);
        $this->logActivity('Deleted customer: ' . ($customer['name'] ?? $id), 'customers', $id, 'Balance: ' . ($customer['current_balance'] ?? 0));
        $this->setFlash('success', 'Customer deleted.');
        $this->redirect('index.php?page=customers');
    }

    /**
     * Recalculate customer balance from transactions
     */
    public function recalculate_balance() {
        $this->requirePermission('customers.edit');
        if (!$this->isPost()) { $this->redirect('index.php?page=customers'); }
        $this->validateCSRF();

        $id = (int)$this->post('id');
        $customerModel = new CustomerModel();
        $oldBalance = ($customerModel->find($id))['current_balance'] ?? 0;
        $newBalance = $customerModel->recalculateBalance($id);
        $this->logActivity('Recalculated customer balance', 'customers', $id, 'Old: ' . $oldBalance . ' → New: ' . $newBalance);

        $this->setFlash('success', 'Balance recalculated successfully. New balance: ₹' . number_format($newBalance, 2));
        $this->redirect('index.php?page=customers&action=view_customer&id=' . $id);
    }
}
