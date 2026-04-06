<?php
/**
 * Customer Controller
 */
class CustomerController extends Controller {

    protected $allowedActions = ['index', 'create', 'edit', 'view_customer', 'delete', 'recalculate_balance', 'import', 'download_template'];

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

    public function import() {
        $this->requireFeature('bulk_import');
        $this->requirePermission('customers.create');

        $analysis = null;
        $dryRun = true;
        $service = new ContactImportService();

        if ($this->isPost()) {
            $this->validateCSRF();
            $dryRun = $this->post('dry_run') === '1';

            try {
                $analysis = $service->analyzeUploadedFile('customer', $_FILES['import_file'] ?? [], $service->buildContext('customer'));
                if (!$dryRun) {
                    $validRows = (array)($analysis['valid_rows'] ?? []);
                    $invalidCount = (int)($analysis['summary']['invalid_rows'] ?? 0);

                    if ($invalidCount > 0) {
                        $this->setFlash('error', 'Fix invalid rows before importing customers.');
                    } elseif (empty($validRows)) {
                        $this->setFlash('error', 'No valid rows were found to import.');
                    } else {
                        $imported = $this->persistImportedContacts($validRows);
                        $this->setFlash('success', 'Imported ' . $imported . ' customer(s) successfully.');
                    }
                }
            } catch (\Throwable $e) {
                $this->setFlash('error', $e->getMessage());
            }
        }

        $this->view('shared.contact-import', [
            'pageTitle' => 'Bulk Import Customers',
            'entityLabel' => 'Customers',
            'entityKey' => 'customers',
            'templateAction' => 'download_template',
            'analysis' => $analysis,
            'dryRun' => $dryRun,
        ]);
    }

    public function download_template() {
        $this->requireFeature('bulk_import');
        $this->requirePermission('customers.create');

        $service = new ContactImportService();
        $filename = 'customer_import_template_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $service->templateCsv('customer');
        exit;
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

            try {
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
                $data = $this->appendOptionalCustomerFields($data, [
                    'custom_fields' => $this->extractCustomFieldsPayload(),
                ]);
            } catch (\RuntimeException $e) {
                $this->setFlash('error', $e->getMessage());
                $this->view('customers.create', ['pageTitle' => 'Add Customer', 'old' => $old, 'customFieldsPretty' => $this->customFieldsPretty($old['custom_fields_json'] ?? '')]);
                return;
            }

            try {
                $customerId = $customerModel->create($data);
                $this->logActivity('Created customer: ' . $data['name'], 'customers', $customerId, 'Opening balance: ' . $data['opening_balance']);
                Logger::audit('customer_created', 'customers', $customerId, ['name' => $data['name'], 'balance' => $data['opening_balance']]);
                $this->setFlash('success', 'Customer created successfully.');
                $this->redirect('index.php?page=customers');
            } catch (Throwable $e) {
                error_log('[CustomerController::create] ' . $e->getMessage());
                $this->setFlash('error', 'Unable to create customer right now. Please try again.');
                $this->view('customers.create', ['pageTitle' => 'Add Customer', 'old' => $old, 'customFieldsPretty' => $this->customFieldsPretty($old['custom_fields_json'] ?? '')]);
                return;
            }
        }
        $this->view('customers.create', ['pageTitle' => 'Add Customer', 'old' => $old, 'customFieldsPretty' => $this->customFieldsPretty($old['custom_fields_json'] ?? '')]);
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

            try {
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
                $data = $this->appendOptionalCustomerFields($data, [
                    'custom_fields' => $this->extractCustomFieldsPayload(),
                ]);
            } catch (\RuntimeException $e) {
                $this->setFlash('error', $e->getMessage());
                $this->view('customers.edit', ['pageTitle' => 'Edit Customer', 'customer' => array_merge($customer, $old), 'customFieldsPretty' => $this->customFieldsPretty($old['custom_fields_json'] ?? ($customer['custom_fields'] ?? ''))]);
                return;
            }

            try {
                $customerModel->update($id, $data);
                $this->logActivity('Updated customer: ' . $data['name'], 'customers', $id);
                $this->setFlash('success', 'Customer updated successfully.');
                $this->redirect('index.php?page=customers');
            } catch (Throwable $e) {
                error_log('[CustomerController::edit] ' . $e->getMessage());
                $this->setFlash('error', 'Unable to update customer right now. Please try again.');
                $this->view('customers.edit', ['pageTitle' => 'Edit Customer', 'customer' => array_merge($customer, $old), 'customFieldsPretty' => $this->customFieldsPretty($old['custom_fields_json'] ?? ($customer['custom_fields'] ?? ''))]);
                return;
            }
        }

        $this->view('customers.edit', [
            'pageTitle' => 'Edit Customer',
            'customer' => $customer,
            'customFieldsPretty' => $this->customFieldsPretty($customer['custom_fields'] ?? ''),
            'customFieldsDecoded' => CustomFieldService::decode($customer['custom_fields'] ?? null),
        ]);
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
            'customFields' => CustomFieldService::decode($customer['custom_fields'] ?? null),
        ]);
    }

    private function appendOptionalCustomerFields(array $data, array $optionalFields): array {
        $columns = [];
        try {
            $rows = Database::getInstance()->query("SHOW COLUMNS FROM customers")->fetchAll();
            foreach ($rows as $row) {
                if (!empty($row['Field'])) {
                    $columns[$row['Field']] = true;
                }
            }
        } catch (\Throwable $e) {
            $columns = [];
        }

        foreach ($optionalFields as $field => $value) {
            if (!empty($columns[$field])) {
                $data[$field] = $value;
            }
        }

        return $data;
    }

    private function extractCustomFieldsPayload(): ?string {
        if (!(Session::isSuperAdmin() || Tenant::canUse('custom_fields'))) {
            return null;
        }
        return CustomFieldService::encodeFromInput((string)$this->post('custom_fields_json', ''));
    }

    private function customFieldsPretty($raw): string {
        return CustomFieldService::pretty($raw);
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

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function persistImportedContacts(array $rows): int {
        $model = new CustomerModel();
        $count = 0;
        foreach ($rows as $row) {
            $data = (array)($row['normalized'] ?? []);
            $payload = [
                'name' => $this->sanitize((string)($data['name'] ?? '')),
                'email' => !empty($data['email']) ? $this->sanitize((string)$data['email']) : null,
                'phone' => !empty($data['phone']) ? $this->sanitize((string)$data['phone']) : null,
                'address' => $this->sanitize((string)($data['address'] ?? '')),
                'city' => $this->sanitize((string)($data['city'] ?? '')),
                'state' => $this->sanitize((string)($data['state'] ?? '')),
                'zip' => $this->sanitize((string)($data['zip'] ?? '')),
                'tax_number' => !empty($data['tax_number']) ? strtoupper($this->sanitize((string)$data['tax_number'])) : '',
                'opening_balance' => (float)($data['opening_balance'] ?? 0),
                'current_balance' => (float)($data['current_balance'] ?? 0),
                'is_active' => !empty($data['is_active']) ? 1 : 0,
            ];
            $payload = $this->appendOptionalCustomerFields($payload, ['custom_fields' => null]);
            $model->create($payload);
            $count++;
        }
        return $count;
    }
}
