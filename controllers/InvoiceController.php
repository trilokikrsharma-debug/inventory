<?php
/**
 * Invoice / Voucher Controller
 * 
 * Handles printable document generation, PDF download, and statements
 * for all document types: sales, purchases, payments, returns, quotations.
 * 
 * Routes:
 *   ?page=invoice&type=sale&id=123              Print view
 *   ?page=invoice&action=download&type=sale&id=123   PDF download
 *   ?page=invoice&action=print_view&type=sale&id=123 Print-friendly view
 *   ?page=invoice&action=statement&party=customer&id=5  Statement PDF
 */
class InvoiceController extends Controller {

    protected $allowedActions = ['index', 'download', 'print_view', 'statement'];

    /**
     * Document type registry.
     * Maps type string → [Model class, detail method, view template, redirect page]
     */
    private static $typeRegistry = [
        'sale'      => ['SalesModel',      'getWithDetails', 'invoice.print',           'sales'],
        'purchase'  => ['PurchaseModel',   'getWithDetails', 'invoice.print',           'purchases'],
        'receipt'   => ['PaymentModel',    'getWithDetails', 'invoice.print_receipt',   'payments'],
        'payment'   => ['PaymentModel',    'getWithDetails', 'invoice.print_receipt',   'payments'],
        'return'    => ['SaleReturnModel', 'getWithDetails', 'invoice.print_return',    'sale_returns'],
        'quotation' => ['QuotationModel',  'getWithDetails', 'invoice.print_quotation', 'quotations'],
    ];

    /**
     * Print view — renders the existing print templates.
     */
    public function index() {
        $this->requireAuth();
        $id   = (int)$this->get('id');
        $type = $this->get('type', 'sale');

        if (!isset(self::$typeRegistry[$type])) {
            $this->setFlash('error', 'Invalid document type.');
            $this->redirect('index.php?page=dashboard');
            return;
        }

        [$modelClass, $method, $template, $redirectPage] = self::$typeRegistry[$type];

        $company = (new SettingsModel())->getSettings();
        $model   = new $modelClass();
        $data    = $model->$method($id);

        $this->authorizeRecordAccess($data, 'index.php?page=' . $redirectPage);

        $this->renderPartial($template, [
            'data'    => $data,
            'company' => $company,
            'type'    => $type,
        ]);
    }

    /**
     * PDF download — generates and downloads a PDF for any voucher type.
     */
    public function download() {
        $this->requireAuth();
        $id   = (int)$this->get('id');
        $type = $this->get('type', 'sale');

        if (!isset(self::$typeRegistry[$type])) {
            $this->setFlash('error', 'Invalid document type.');
            $this->redirect('index.php?page=dashboard');
            return;
        }

        // Verify access
        [$modelClass, $method, , $redirectPage] = self::$typeRegistry[$type];
        $model = new $modelClass();
        $data  = $model->$method($id);
        $this->authorizeRecordAccess($data, 'index.php?page=' . $redirectPage);

        // For sale/purchase use the exact print template layout for PDF generation
        // so downloaded PDF matches what users see in the Invoice view.
        if (in_array($type, ['sale', 'purchase'], true)) {
            $company = (new SettingsModel())->getSettings();
            $this->downloadUsingPrintTemplate($type, $data, $company);
            return;
        }

        VoucherPdfService::download($type, $id);
    }

    /**
     * Print-friendly view — same as index, alias for clarity.
     */
    public function print_view() {
        $this->index();
    }

    /**
     * Customer/supplier ledger or receipt/payment register PDF download.
     * 
     * ?page=invoice&action=statement&party=customer&id=5&from_date=2025-01-01&to_date=2025-12-31
     */
    public function statement() {
        $this->requireAuth();
        $partyType = $this->get('party', 'customer');
        $id = (int)$this->get('id');
        $from = $this->get('from_date', '');
        $to = $this->get('to_date', '');

        if (!in_array($partyType, ['customer', 'supplier', 'receipt', 'payment'], true)) {
            $this->setFlash('error', 'Invalid party type.');
            $this->redirect('index.php?page=dashboard');
            return;
        }

        // Verify the party exists and belongs to this tenant for party-specific ledgers.
        if ($partyType === 'customer' || $partyType === 'supplier') {
            if ($id <= 0) {
                $this->setFlash('error', 'Invalid party id.');
                $this->redirect('index.php?page=' . ($partyType === 'customer' ? 'customers' : 'suppliers'));
                return;
            }

            if ($partyType === 'customer') {
                $party = (new CustomerModel())->find($id);
                $this->authorizeRecordAccess($party, 'index.php?page=customers', true);
            } else {
                $party = (new SupplierModel())->find($id);
                $this->authorizeRecordAccess($party, 'index.php?page=suppliers', true);
            }
        } else {
            // Register statements do not require a party id.
            $id = 0;
        }

        VoucherPdfService::downloadStatement($partyType, $id, $from, $to);
    }

    /**
     * Generate PDF from the same HTML template used by invoice print view.
     */
    private function downloadUsingPrintTemplate(string $type, array $data, array $company): void {
        $html = $this->renderTemplateToString('invoice.print', [
            'data' => $data,
            'company' => $company,
            'type' => $type,
            'forPdf' => true,
        ]);

        $number = $data['invoice_number'] ?? $data['reference_number'] ?? (string)($data['id'] ?? time());
        $safeNumber = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string)$number);
        $prefix = $type === 'sale' ? 'INV_' : 'PUR_';
        $filename = $prefix . $safeNumber . '.pdf';

        if (class_exists('\\Dompdf\\Dompdf')) {
            $dompdf = new \Dompdf\Dompdf([
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $content = $dompdf->output();

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($content));
            header('Cache-Control: private, max-age=0');
            echo $content;
            exit;
        }

        // Fallback: if PDF engine missing, return HTML download.
        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . pathinfo($filename, PATHINFO_FILENAME) . '.html"');
        echo $html;
        exit;
    }

    /**
     * Render a view file to string without layout.
     */
    private function renderTemplateToString(string $viewPath, array $viewData = []): string {
        extract($viewData, EXTR_SKIP);
        $viewFile = VIEW_PATH . '/' . str_replace('.', '/', $viewPath) . '.php';
        if (!is_file($viewFile) || !is_readable($viewFile)) {
            throw new RuntimeException('View not found: ' . $viewPath);
        }

        ob_start();
        require $viewFile;
        return (string)ob_get_clean();
    }
}
