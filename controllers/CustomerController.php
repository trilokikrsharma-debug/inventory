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
        $old = [];
        if ($this->isPost()) {
            $this->validateCSRF();
            $old = $_POST;

            $v = Validator::make($_POST, [
                'name'            => 'required|string|min:2|max:100',
                'email'           => 'nullable|email|max:255',
                'phone'           => 'nullable|string|min:7|max:20|regex:/^[0-9+()\\-\\s]{7,20}$/',
                'address'         => 'nullable|string|max:500',
                'city'            => 'nullable|string|max:100',
                'state'           => 'nullable|string|max:100',
                'zip'             => 'nullable|string|min:2|max:20|regex:/^[A-Za-z0-9\\-\\s]{2,20}$/',
                'tax_number'      => 'nullable|string|min:6|max:20|regex:/^[A-Za-z0-9\\/-]{6,20}$/',
                'opening_balance' => 'nullable|numeric|min:-999999999|max:999999999',
            ]);

            if ($v->fails()) {
                $this->setFlash('error', $v->firstError());
                $this->view('customers.create', ['pageTitle' => 'Add Customer', 'old' => $old]);
                return;
            }

            $clean = $v->validated();
            $customerModel = new CustomerModel();

            $name = $this->sanitize($clean['name'] ?? '');
            $email = !empty($clean['email']) ? strtolower($this->sanitize($clean['email'])) : null;
            $phone = !empty($clean['phone']) ? $this->sanitize($clean['phone']) : null;
            $openingBalance = round((float)($clean['opening_balance'] ?? 0), 2);

            if ($name === '') {
                $this->setFlash('error', 'Name is required.');
                $this->view('customers.create', ['pageTitle' => 'Add Customer', 'old' => $old]);
                return;
            }

            if ($email && $customerModel->emailExists($email)) {
                $this->setFlash('error', 'Email already exists for another customer.');
                $this->view('customers.create', ['pageTitle' => 'Add Customer', 'old' => $old]);
                return;
            }

            if ($phone && $customerModel->phoneExists($phone)) {
                $this->setFlash('error', 'Phone already exists for another customer.');
                $this->view('customers.create', ['pageTitle' => 'Add Customer', 'old' => $old]);
                return;
            }

            $data = [
                'name'            => $name,
                'email'           => $email,
                'phone'           => $phone,
                'address'         => $this->sanitize($clean['address'] ?? ''),
                'city'            => $this->sanitize($clean['city'] ?? ''),
                'state'           => $this->sanitize($clean['state'] ?? ''),
                'zip'             => $this->sanitize($clean['zip'] ?? ''),
                'tax_number'      => !empty($clean['tax_number']) ? strtoupper($this->sanitize($clean['tax_number'])) : '',
                'opening_balance' => $openingBalance,
                'current_balance' => $openingBalance,
            ];

            try {
                $customerId = $customerModel->create($data);
                $this->logActivity('Created customer: ' . $data['name'], 'customers', $customerId, 'Opening balance: ' . $data['opening_balance']);
                Logger::audit('customer_created', 'customers', $customerId, ['name' => $data['name'], 'balance' => $data['opening_balance']]);
                $this->setFlash('success', 'Customer created successfully.');
                $this->redirect('index.php?page=customers');
            } catch (Throwable $e) {
                error_log('[CustomerController::create] ' . $e->getMessage());
                $this->setFlash('error', 'Unable to create customer right now. Please try again.');
                $this->view('customers.create', ['pageTitle' => 'Add Customer', 'old' => $old]);
                return;
            }
        }
        $this->view('customers.create', ['pageTitle' => 'Add Customer', 'old' => $old]);
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
            $old = $_POST;

            $v = Validator::make($_POST, [
                'name'       => 'required|string|min:2|max:100',
                'email'      => 'nullable|email|max:255',
                'phone'      => 'nullable|string|min:7|max:20|regex:/^[0-9+()\\-\\s]{7,20}$/',
                'address'    => 'nullable|string|max:500',
                'city'       => 'nullable|string|max:100',
                'state'      => 'nullable|string|max:100',
                'zip'        => 'nullable|string|min:2|max:20|regex:/^[A-Za-z0-9\\-\\s]{2,20}$/',
                'tax_number' => 'nullable|string|min:6|max:20|regex:/^[A-Za-z0-9\\/-]{6,20}$/',
            ]);

            if ($v->fails()) {
                $this->setFlash('error', $v->firstError());
                $this->view('customers.edit', ['pageTitle' => 'Edit Customer', 'customer' => array_merge($customer, $old)]);
                return;
            }

            $clean = $v->validated();
            $name = $this->sanitize($clean['name'] ?? '');
            $email = !empty($clean['email']) ? strtolower($this->sanitize($clean['email'])) : null;
            $phone = !empty($clean['phone']) ? $this->sanitize($clean['phone']) : null;

            if ($name === '') {
                $this->setFlash('error', 'Name is required.');
                $this->view('customers.edit', ['pageTitle' => 'Edit Customer', 'customer' => array_merge($customer, $old)]);
                return;
            }

            if ($email && $email !== ($customer['email'] ?? null) && $customerModel->emailExists($email, $id)) {
                $this->setFlash('error', 'Email already exists for another customer.');
                $this->view('customers.edit', ['pageTitle' => 'Edit Customer', 'customer' => array_merge($customer, $old)]);
                return;
            }

            if ($phone && $phone !== ($customer['phone'] ?? null) && $customerModel->phoneExists($phone, $id)) {
                $this->setFlash('error', 'Phone already exists for another customer.');
                $this->view('customers.edit', ['pageTitle' => 'Edit Customer', 'customer' => array_merge($customer, $old)]);
                return;
            }

            $data = [
                'name'       => $name,
                'email'      => $email,
                'phone'      => $phone,
                'address'    => $this->sanitize($clean['address'] ?? ''),
                'city'       => $this->sanitize($clean['city'] ?? ''),
                'state'      => $this->sanitize($clean['state'] ?? ''),
                'zip'        => $this->sanitize($clean['zip'] ?? ''),
                'tax_number' => !empty($clean['tax_number']) ? strtoupper($this->sanitize($clean['tax_number'])) : '',
            ];

            try {
                $customerModel->update($id, $data);
                $this->logActivity('Updated customer: ' . $data['name'], 'customers', $id);
                $this->setFlash('success', 'Customer updated successfully.');
                $this->redirect('index.php?page=customers');
            } catch (Throwable $e) {
                error_log('[CustomerController::edit] ' . $e->getMessage());
                $this->setFlash('error', 'Unable to update customer right now. Please try again.');
                $this->view('customers.edit', ['pageTitle' => 'Edit Customer', 'customer' => array_merge($customer, $old)]);
                return;
            }
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

        // Check for linked sale returns (including returns on soft-deleted sales).
        $returnsCount = $db->query(
            "SELECT COUNT(*)
             FROM sale_returns sr
             JOIN sales s ON sr.sale_id = s.id
             WHERE s.customer_id = ? AND sr.deleted_at IS NULL" . (Tenant::id() !== null ? " AND s.company_id = ?" : ""),
            Tenant::id() !== null ? [$id, Tenant::id()] : [$id]
        )->fetchColumn();

        if ($returnsCount > 0) {
            $this->setFlash('error', 'Cannot delete customer: ' . $returnsCount . ' active sale return(s) exist. Delete/cancel return records first.');
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
