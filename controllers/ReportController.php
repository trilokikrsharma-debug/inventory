<?php
class ReportController extends Controller {

    protected $allowedActions = ['index', 'sales', 'purchases', 'stock', 'profit', 'customer_dues', 'supplier_dues'];

    public function index() {
        $this->requirePermission('reports.view');
        $this->view('reports.index', ['pageTitle' => 'Reports']);
    }

    public function sales() {
        $this->requirePermission('reports.view');
        $fromDate = $this->normalizeDate($this->get('from_date', ''), date('Y-m-01'));
        $toDate = $this->normalizeDate($this->get('to_date', ''), date('Y-m-d'));
        [$fromDate, $toDate] = $this->normalizeDateRange($fromDate, $toDate);
        $customerId = $this->normalizeEntityId($this->get('customer_id', ''));
        $maxRows = defined('REPORT_MAX_ROWS') ? REPORT_MAX_ROWS : 2000;
        $sales = (new SalesModel())->getAllWithCustomer('', $fromDate, $toDate, $customerId > 0 ? $customerId : '', '', 1, $maxRows);
        $customers = (new CustomerModel())->allActive();
        $this->view('reports.sales', [
            'pageTitle' => 'Sales Report', 'sales' => $sales, 'customers' => $customers,
            'fromDate' => $fromDate, 'toDate' => $toDate, 'customerId' => $customerId,
        ]);
    }

    public function purchases() {
        $this->requirePermission('reports.view');
        $fromDate = $this->normalizeDate($this->get('from_date', ''), date('Y-m-01'));
        $toDate = $this->normalizeDate($this->get('to_date', ''), date('Y-m-d'));
        [$fromDate, $toDate] = $this->normalizeDateRange($fromDate, $toDate);
        $supplierId = $this->normalizeEntityId($this->get('supplier_id', ''));
        $maxRows = defined('REPORT_MAX_ROWS') ? REPORT_MAX_ROWS : 2000;
        $purchases = (new PurchaseModel())->getAllWithSupplier('', $fromDate, $toDate, $supplierId > 0 ? $supplierId : '', '', 1, $maxRows);
        $suppliers = (new SupplierModel())->allActive();
        $this->view('reports.purchases', [
            'pageTitle' => 'Purchase Report', 'purchases' => $purchases, 'suppliers' => $suppliers,
            'fromDate' => $fromDate, 'toDate' => $toDate, 'supplierId' => $supplierId,
        ]);
    }

    public function stock() {
        $this->requirePermission('reports.view');
        $maxRows = defined('REPORT_MAX_ROWS') ? REPORT_MAX_ROWS : 2000;
        $search = $this->sanitize($this->get('search', ''));
        $categoryId = $this->normalizeEntityId($this->get('category_id', ''));
        $products = (new ProductModel())->getAllWithRelations($search, $categoryId > 0 ? $categoryId : '', 1, $maxRows);
        $categories = (new CategoryModel())->allActive();
        $this->view('reports.stock', [
            'pageTitle' => 'Stock Report',
            'products' => $products,
            'categories' => $categories,
            'search' => $search,
            'categoryId' => $categoryId,
        ]);
    }

    public function profit() {
        $this->requirePermission('reports.view');
        $fromDate = $this->normalizeDate($this->get('from_date', ''), date('Y-m-01'));
        $toDate = $this->normalizeDate($this->get('to_date', ''), date('Y-m-d'));
        [$fromDate, $toDate] = $this->normalizeDateRange($fromDate, $toDate);
        $profitData = (new SalesModel())->getProfitData($fromDate, $toDate);
        $this->view('reports.profit', ['pageTitle' => 'Profit & Loss', 'profitData' => $profitData, 'fromDate' => $fromDate, 'toDate' => $toDate]);
    }

    public function customer_dues() {
        $this->requirePermission('reports.view');
        $this->view('reports.customer_dues', ['pageTitle' => 'Customer Dues', 'customers' => (new CustomerModel())->getWithDues()]);
    }

    public function supplier_dues() {
        $this->requirePermission('reports.view');
        $this->view('reports.supplier_dues', ['pageTitle' => 'Supplier Dues', 'suppliers' => (new SupplierModel())->getWithDues()]);
    }

    private function normalizeDate(string $date, string $default): string {
        $date = trim($date);
        if ($date === '') {
            return $default;
        }

        $dt = \DateTime::createFromFormat('Y-m-d', $date);
        $errors = \DateTime::getLastErrors();
        if (!$dt || ($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0) {
            return $default;
        }
        return $dt->format('Y-m-d');
    }

    private function normalizeDateRange(string $fromDate, string $toDate): array {
        if (strtotime($fromDate) > strtotime($toDate)) {
            return [$toDate, $fromDate];
        }
        return [$fromDate, $toDate];
    }

    private function normalizeEntityId(mixed $value): int {
        $id = (int)$value;
        return $id > 0 ? $id : 0;
    }
}
