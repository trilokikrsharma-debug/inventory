<?php
/**
 * Sales Controller
 *
 * Handles sales entries with multi items, discounts, tax, round-off.
 */
class SalesController extends Controller {

    protected $allowedActions = ['export_gstr1', 'index', 'create', 'edit', 'view_sale', 'delete'];
    private ?SalesWorkflowService $salesWorkflowService = null;

    private function warehouseFeatureEnabled(): bool {
        return !Session::isSuperAdmin()
            && Tenant::id() !== null
            && Tenant::canUse('multi_warehouse');
    }

    public function index() {
        $this->requirePermission('sales.view');
        $sales = (new SalesModel())->getAllWithCustomer(
            $this->get('search', ''),
            $this->get('from_date', ''),
            $this->get('to_date', ''),
            $this->get('customer_id', ''),
            $this->get('status', ''),
            max(1, (int)$this->get('pg', 1))
        );
        $customers = (new CustomerModel())->allActive();

        $this->view('sales.index', [
            'pageTitle' => 'Sales',
            'sales'     => $sales,
            'customers' => $customers,
            'filters'   => $this->safeFilters(),
        ]);
    }

    public function create() {
        $this->requirePermission('sales.create');

        if ($this->isPost()) {
            $this->validateCSRF();
            $settingsModel = new SettingsModel();
            $invoiceNumber = $settingsModel->getNextNumber('invoice');
            $settings = $settingsModel->getSettings();
            $warehouseOptions = $this->warehouseFeatureEnabled() ? (new SalesModel())->activeWarehouses() : [];
            try {
                $prepared = $this->workflowService()->buildCreatePayload(
                    $this->post(),
                    $settings,
                    $invoiceNumber,
                    $this->warehouseFeatureEnabled(),
                    $warehouseOptions
                );
                $saleData = $prepared['sale'];
                $items = $prepared['items'];
            } catch (\RuntimeException | \InvalidArgumentException $e) {
                $this->setFlash('error', $e->getMessage());
                $this->redirect('index.php?page=sales&action=create');
                return;
            }

            try {
                $salesModel = new SalesModel();
                $saleId = $salesModel->createSale($saleData, $items, Session::get('user')['id']);

                // Create receipt if paid
                if (($saleData['paid_amount'] ?? 0) > 0) {
                    $paymentModel = new PaymentModel();
                    $receiptNumber = $settingsModel->getNextNumber('receipt');
                    $receiptPayload = $this->workflowService()->buildReceiptPayload(
                        $this->post(),
                        $saleData,
                        $receiptNumber,
                        $saleId,
                        (int)(Session::get('user')['id'] ?? 0)
                    );
                    if ($receiptPayload !== null) {
                        $paymentModel->create($receiptPayload);
                    }
                }

                $this->logActivity('Created sale: ' . $invoiceNumber, 'sales', $saleId);
                $this->setFlash('success', 'Sale created successfully. Invoice: ' . $invoiceNumber);
                $this->redirect('index.php?page=sales&action=view_sale&id=' . $saleId);
            } catch (Exception $e) {
                error_log($e->getMessage());
                $this->setFlash('error', 'An unexpected error occurred. Please try again.');
                $this->redirect('index.php?page=sales&action=create');
            }
        }

        $customers = (new CustomerModel())->allActive();
        $settings = (new SettingsModel())->getSettings();

        $this->view('sales.create', [
            'pageTitle' => 'New Sale',
            'customers' => $customers,
            'settings'  => $settings,
            'warehouses' => $this->warehouseFeatureEnabled() ? (new SalesModel())->activeWarehouses() : [],
            'hasWarehouseFeature' => $this->warehouseFeatureEnabled(),
        ]);
    }

    public function edit() {
        $this->requirePermission('sales.edit');
        $id = (int)$this->get('id');

        $salesModel = new SalesModel();
        $sale = $salesModel->getWithDetails($id);
        if (!$sale) {
            $this->setFlash('error', 'Sale not found.');
            $this->redirect('index.php?page=sales');
            return;
        }

        if ($this->isPost()) {
            $this->validateCSRF();
            $warehouseOptions = $this->warehouseFeatureEnabled() ? (new SalesModel())->activeWarehouses() : [];
            $settings = (new SettingsModel())->getSettings();
            try {
                $prepared = $this->workflowService()->buildUpdatePayload(
                    $this->post(),
                    $settings,
                    $this->warehouseFeatureEnabled(),
                    $warehouseOptions
                );
                $saleData = $prepared['sale'];
                $items = $prepared['items'];
            } catch (\RuntimeException | \InvalidArgumentException $e) {
                $this->setFlash('error', $e->getMessage());
                $this->redirect('index.php?page=sales&action=edit&id=' . $id);
                return;
            }

            try {
                $salesModel->updateSale($id, $saleData, $items, Session::get('user')['id']);
                $this->logActivity('Edited sale: ' . $sale['invoice_number'], 'sales', $id);
                $this->setFlash('success', 'Sale updated. Stock and balances have been reconciled.');
                $this->redirect('index.php?page=sales&action=view_sale&id=' . $id);
            } catch (Exception $e) {
                error_log($e->getMessage());
                $this->setFlash('error', 'An unexpected error occurred. Please try again.');
                $this->redirect('index.php?page=sales&action=edit&id=' . $id);
            }
        }

        $customers = (new CustomerModel())->allActive();
        $settings  = (new SettingsModel())->getSettings();
        $this->view('sales.edit', [
            'pageTitle' => 'Edit Sale: ' . $sale['invoice_number'],
            'sale'      => $sale,
            'customers' => $customers,
            'settings'  => $settings,
            'warehouses' => $this->warehouseFeatureEnabled() ? (new SalesModel())->activeWarehouses() : [],
            'hasWarehouseFeature' => $this->warehouseFeatureEnabled(),
        ]);
    }

    public function view_sale() {
        $this->requirePermission('sales.view');
        $id = (int)$this->get('id');
        $sale = (new SalesModel())->getWithDetails($id);
        $this->authorizeRecordAccess($sale, 'index.php?page=sales');

        $company = (new SettingsModel())->getSettings();
        $returnSummary = (new SaleReturnModel())->getSaleReturnSummary($id);
        $this->view('sales.view', [
            'pageTitle' => 'Sale Details',
            'sale' => $sale,
            'company' => $company,
            'returnSummary' => $returnSummary,
            'warehouseName' => $sale['warehouse_name'] ?? null,
        ]);
    }

    public function delete() {
        $this->requirePermission('sales.delete');
        if (!$this->isPost()) { $this->redirect('index.php?page=sales'); }
        $this->validateCSRF();

        $id     = (int)$this->post('id');
        $userId = Session::get('user')['id'];
        $db = Database::getInstance();

        // Guard: deleting a sale that has active returns will overstate stock and credit.
        $returnCount = (int)$db->query(
            "SELECT COUNT(*) FROM sale_returns WHERE sale_id = ? AND deleted_at IS NULL" . (Tenant::id() !== null ? " AND company_id = ?" : ""),
            Tenant::id() !== null ? [$id, Tenant::id()] : [$id]
        )->fetchColumn();
        if ($returnCount > 0) {
            $this->setFlash('error', 'Cannot delete this sale because ' . $returnCount . ' active return(s) exist. Cancel/delete return first.');
            $this->redirect('index.php?page=sales&action=view_sale&id=' . $id);
            return;
        }

        try {
            $sale = (new SalesModel())->getWithDetails($id);
            (new SalesModel())->deleteSale($id, $userId);
            $this->logActivity('Deleted sale: ' . ($sale['invoice_number'] ?? $id), 'sales', $id, 'Grand total: ' . ($sale['grand_total'] ?? 0));
            $this->setFlash('success', 'Sale deleted. Stock and customer balance have been reversed.');
        } catch (Exception $e) {
            error_log($e->getMessage());
            $this->setFlash('error', 'An unexpected error occurred. Please try again.');
        }
        $this->redirect('index.php?page=sales');
    }

    private function workflowService(): SalesWorkflowService {
        if ($this->salesWorkflowService === null) {
            $this->salesWorkflowService = new SalesWorkflowService();
        }

        return $this->salesWorkflowService;
    }


    public function export_gstr1() {
        $this->requirePermission('sales.view');
        $db = Database::getInstance();
        $cid = Tenant::id();
        
        $type = $_GET['type'] ?? 'b2b';
        $month = $_GET['month'] ?? date('Y-m');
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        
        if ($type === 'b2b') {
            // Customers with Tax Number (GSTIN) >= 15 chars
            $sql = "SELECT s.invoice_number, s.sale_date, s.grand_total, s.tax_amount, c.name, c.tax_number, c.state 
                    FROM sales s 
                    JOIN customers c ON s.customer_id = c.id 
                    WHERE s.company_id = ? AND s.sale_date BETWEEN ? AND ? 
                    AND c.tax_number IS NOT NULL AND LENGTH(c.tax_number) >= 15
                    AND s.status != 'cancelled' AND s.deleted_at IS NULL";
            $data = $db->query($sql, [$cid, $startDate, $endDate])->fetchAll();
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="GSTR1_B2B_' . $month . '.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['GSTIN/UIN of Recipient', 'Receiver Name', 'Invoice Number', 'Invoice date', 'Invoice Value', 'Place Of Supply', 'Reverse Charge', 'Invoice Type', 'Rate', 'Taxable Value']);
            foreach ($data as $row) {
                // Simplified B2B structure
                fputcsv($out, [
                    $row['tax_number'], $row['name'], $row['invoice_number'], 
                    date('d-M-y', strtotime($row['sale_date'])), 
                    $row['grand_total'], $row['state'], 'N', 'Regular', 
                    '', ($row['grand_total'] - $row['tax_amount'])
                ]);
            }
            fclose($out);
            exit;
        } else if ($type === 'b2c') {
            // Customers without proper GSTIN
            $sql = "SELECT c.state, SUM(s.grand_total) as tot_val, SUM(s.tax_amount) as tot_tax 
                    FROM sales s 
                    JOIN customers c ON s.customer_id = c.id 
                    WHERE s.company_id = ? AND s.sale_date BETWEEN ? AND ? 
                    AND (c.tax_number IS NULL OR LENGTH(c.tax_number) < 15)
                    AND s.status != 'cancelled' AND s.deleted_at IS NULL
                    GROUP BY c.state";
            $data = $db->query($sql, [$cid, $startDate, $endDate])->fetchAll();
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="GSTR1_B2C_' . $month . '.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Place Of Supply', 'Rate', 'Taxable Value', 'Cess Amount', 'E-Commerce GSTIN']);
            foreach ($data as $row) {
                $pos = empty($row['state']) ? 'Other Territory' : $row['state'];
                fputcsv($out, [
                    $pos, '', ($row['tot_val'] - $row['tot_tax']), '', ''
                ]);
            }
            fclose($out);
            exit;
        }
    }
}