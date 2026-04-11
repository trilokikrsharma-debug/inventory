<?php
/**
 * Quotation Controller
 */
class QuotationController extends Controller {

    protected $allowedActions = ['index', 'create', 'detail', 'updateStatus', 'convert', 'delete'];
    private ?QuotationWorkflowService $quotationWorkflowService = null;

    public function index() {
        $this->requireFeature('quotations');
        $this->requirePermission('quotations.view');
        $search   = $this->get('search', '');
        $fromDate = $this->get('from_date', '');
        $toDate   = $this->get('to_date', '');
        $status   = $this->get('status', '');
        $page     = max(1, (int)$this->get('pg', 1));
        $model    = new QuotationModel();

        $this->view('quotations.index', [
            'pageTitle'  => 'Quotations',
            'quotations' => $model->getAllWithCustomer($search, $fromDate, $toDate, $status, $page),
            'totals'     => $model->getTotals(),
            'search'     => $search,
            'fromDate'   => $fromDate,
            'toDate'     => $toDate,
            'status'     => $status,
        ]);
    }

    public function create() {
        $this->requireFeature('quotations');
        $this->requirePermission('quotations.create');

        if ($this->isPost()) {
            $this->validateCSRF();
            $settings = (new SettingsModel())->getSettings();
            $model        = new QuotationModel();
            try {
                $prepared = $this->workflowService()->buildCreatePayload(
                    $this->post(),
                    $settings,
                    $model->getNextNumber()
                );
                $data = $prepared['quotation'];
                $items = $prepared['items'];
            } catch (\RuntimeException | \InvalidArgumentException $e) {
                $this->setFlash('error', $e->getMessage());
                $this->redirect('index.php?page=quotations&action=create');
                return;
            }

            try {
                $userId = Session::get('user')['id'];
                $qId    = $model->createQuotation($data, $items, $userId);
                $this->logActivity('Created quotation: ' . $data['quotation_number'], 'quotations', $qId, 'Grand total: ' . $data['grand_total']);
                $this->setFlash('success', 'Quotation created successfully.');
                $this->redirect('index.php?page=quotations&action=detail&id=' . $qId);
            } catch (Exception $e) {
                error_log($e->getMessage());
                $this->setFlash('error', 'An unexpected error occurred. Please try again.');
                $this->redirect('index.php?page=quotations&action=create');
            }
        }

        $customers = (new CustomerModel())->allActive();
        $products  = (new ProductModel())->getAllWithRelations('', '', 1, 1000);

        $settings  = (new SettingsModel())->getSettings();

        $this->view('quotations.create', [
            'pageTitle' => 'New Quotation',
            'customers' => $customers,
            'settings'  => $settings,
            'products'  => $products['data'],
        ]);
    }

    public function detail() {
        $this->requirePermission('quotations.view');
        $id    = (int)$this->get('id');
        $quote = (new QuotationModel())->getWithDetails($id);
        $this->authorizeRecordAccess($quote, 'index.php?page=quotations');
        $this->view('quotations.view', ['pageTitle' => 'Quotation #' . $quote['quotation_number'], 'quote' => $quote]);
    }

    public function updateStatus() {
        $this->requirePermission('quotations.create');
        if (!$this->isPost()) { $this->redirect('index.php?page=quotations'); }
        $this->validateCSRF();
        $id     = (int)$this->post('id');
        $status = $this->post('status');

        // Verify ownership before allowing status change
        $quote = (new QuotationModel())->getWithDetails($id);
        $this->authorizeRecordAccess($quote, 'index.php?page=quotations');

        if (in_array($status, ['draft', 'sent', 'cancelled'])) {
            $oldStatus = $quote['status'] ?? 'unknown';
            (new QuotationModel())->update($id, ['status' => $status]);
            $this->logActivity('Quotation status: ' . $oldStatus . ' → ' . $status, 'quotations', $id, $quote['quotation_number'] ?? '');
            $this->setFlash('success', 'Status updated.');
        }
        $this->redirect('index.php?page=quotations&action=detail&id=' . $id);
    }

    /** Convert quotation -> sale (fully atomic) */
    public function convert() {
        $this->requirePermission('quotations.convert');
        if (!$this->isPost()) { $this->redirect('index.php?page=quotations'); }
        $this->validateCSRF();

        $id    = (int)$this->post('id');
        $model = new QuotationModel();

        try {
            // Pre-flight check (fast, outside transaction)
            $quote = $model->getWithDetails($id);
            $this->authorizeRecordAccess($quote, 'index.php?page=quotations');
            if ($quote['status'] === 'converted') {
                $this->setFlash('warning', 'This quotation has already been converted to a sale.');
                $this->redirect('index.php?page=quotations&action=detail&id=' . $id);
                return;
            }
            if ($quote['status'] === 'cancelled') {
                $this->setFlash('error', 'Cannot convert a cancelled quotation.');
                $this->redirect('index.php?page=quotations&action=detail&id=' . $id);
                return;
            }

            // Prepare sale data from quotation
            $settingsModel = new SettingsModel();
            $invoiceNo     = $settingsModel->getNextNumber('invoice');
            $userId        = Session::get('user')['id'];

            $prepared = $this->workflowService()->buildSaleConversionPayload($quote, $invoiceNo, date('Y-m-d'));
            $saleData = $prepared['sale'];
            $saleItems = $prepared['items'];

            // Single atomic operation: creates sale + items + stock + balance + marks converted
            $result = $model->convertToSale($id, $saleData, $saleItems, $userId);

            // Handle idempotent race-condition abort from model
            if (!empty($result['already_converted'])) {
                $this->setFlash('warning', 'This quotation has already been converted to a sale.');
                $this->redirect('index.php?page=quotations&action=detail&id=' . $id);
                return;
            }

            // Audit log (exact format: "Converted quotation #QUO-XXXX to sale #INV-XXXX")
            $this->logActivity(
                'Converted quotation #' . ($quote['quotation_number'] ?? $id) . ' to sale #' . $result['invoice_number'],
                'quotations',
                $id,
                'Sale ID: ' . $result['sale_id']
            );

            $this->setFlash('success', 'Quotation converted to Sale ' . $result['invoice_number'] . ' successfully!');
            $this->setFlash('_swal_success', 'Quotation #' . ($quote['quotation_number'] ?? $id) . ' successfully converted to Sale #' . $result['invoice_number'] . '.');
            $this->redirect('index.php?page=sales&action=view_sale&id=' . $result['sale_id']);
        } catch (Exception $e) {
            error_log('Quotation conversion failed (ID: ' . $id . '): ' . $e->getMessage());
            // Show safe message — never expose raw DB/exception messages to user
            $safeMsg = in_array($e->getMessage(), [
                'Quotation not found.',
                'Cannot convert a cancelled quotation.',
                'Quotation has no items to convert.',
            ]) ? $e->getMessage() : 'Conversion failed. Please try again or contact support.';
            $this->setFlash('error', $safeMsg);
            $this->redirect('index.php?page=quotations&action=detail&id=' . $id);
        }
    }

    public function delete() {
        $this->requirePermission('quotations.delete');
        if (!$this->isPost()) { $this->redirect('index.php?page=quotations'); }
        $this->validateCSRF();
        $id = (int)$this->post('id');
        $quote = (new QuotationModel())->getWithDetails($id);
        $this->authorizeRecordAccess($quote, 'index.php?page=quotations');
        (new QuotationModel())->delete($id);
        $this->logActivity('Deleted quotation: ' . ($quote['quotation_number'] ?? $id), 'quotations', $id);
        $this->setFlash('success', 'Quotation deleted.');
        $this->redirect('index.php?page=quotations');
    }

    private function workflowService(): QuotationWorkflowService {
        if ($this->quotationWorkflowService === null) {
            $this->quotationWorkflowService = new QuotationWorkflowService();
        }

        return $this->quotationWorkflowService;
    }
}
