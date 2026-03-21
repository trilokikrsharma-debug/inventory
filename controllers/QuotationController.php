<?php
/**
 * Quotation Controller
 */
class QuotationController extends Controller {

    protected $allowedActions = ['index', 'create', 'detail', 'updateStatus', 'convert', 'delete'];

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
            $isTaxEnabled = !isset($settings['enable_tax']) || !empty($settings['enable_tax']);
            $isGstEnabled = !isset($settings['enable_gst']) || !empty($settings['enable_gst']);
            $allowTax = $isTaxEnabled && $isGstEnabled;

            $productIds = $this->post('product_id', []);
            $quantities = $this->post('quantity', []);
            $unitPrices = $this->post('unit_price', []);
            $discounts  = $this->post('discount', []);
            $taxRates   = $this->post('tax_rate', []);

            $items    = [];
            $subtotal = 0;
            $taxTotal = 0;
            foreach ($productIds as $i => $pid) {
                if (!$pid) continue;
                $qty   = (float)($quantities[$i] ?? 0);
                $up    = (float)($unitPrices[$i] ?? 0);
                $disc  = (float)($discounts[$i] ?? 0);
                $taxR  = (float)($taxRates[$i] ?? 0);
                if (!$allowTax) {
                    $taxR = 0.0;
                }
                $lineBase = $qty * $up;

                if ($qty <= 0 || $up < 0 || $taxR < 0 || $taxR > 100 || $disc < 0 || $disc > $lineBase) {
                    error_log("Invalid input values in Quotation create: qty=$qty, price=$up, tax=$taxR, disc=$disc");
                    $this->setFlash('error', 'Invalid item values. Quantity must be greater than 0, tax must be 0-100, and discount cannot exceed line amount.');
                    $this->redirect('index.php?page=quotations&action=create');
                    return;
                }
                $sub   = $lineBase - $disc;
                $taxA  = $sub * $taxR / 100;
                $total = $sub + $taxA;
                $subtotal += $sub;
                $taxTotal += $taxA;
                $items[] = ['product_id'=>(int)$pid,'quantity'=>$qty,'unit_price'=>$up,'discount'=>$disc,'tax_rate'=>$taxR,'tax_amount'=>$taxA,'subtotal'=>$sub,'total'=>$total];
            }

            if (empty($items)) {
                $this->setFlash('error', 'Add at least one product.');
                $this->redirect('index.php?page=quotations&action=create');
                return;
            }

            $discountAmt  = (float)$this->post('discount_amount', 0);
            $shippingCost = (float)$this->post('shipping_cost', 0);
            $grandTotal   = $subtotal + $taxTotal - $discountAmt + $shippingCost;
            $model        = new QuotationModel();

            $data = [
                'quotation_number'  => $model->getNextNumber(),
                'customer_id'       => (int)$this->post('customer_id'),
                'quotation_date'    => $this->post('quotation_date', date('Y-m-d')),
                'valid_until'       => $this->post('valid_until') ?: null,
                'subtotal'          => $subtotal,
                'tax_amount'        => $taxTotal,
                'discount_amount'   => $discountAmt,
                'shipping_cost'     => $shippingCost,
                'grand_total'       => $grandTotal,
                'status'            => 'draft',
                'note'              => $this->sanitize($this->post('note')),
                'terms'             => $this->sanitize($this->post('terms')),
            ];

            try {
                $userId = Session::get('user')['id'];
                $qId    = $model->createQuotation($data, $items, $userId);
                $this->logActivity('Created quotation: ' . $data['quotation_number'], 'quotations', $qId, 'Grand total: ' . $grandTotal);
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

            $saleData = [
                'invoice_number'  => $invoiceNo,
                'customer_id'     => $quote['customer_id'],
                'sale_date'       => date('Y-m-d'),
                'subtotal'        => $quote['subtotal'],
                'tax_amount'      => $quote['tax_amount'],
                'discount_amount' => $quote['discount_amount'],
                'shipping_cost'   => $quote['shipping_cost'],
                'grand_total'     => $quote['grand_total'],
                'paid_amount'     => 0,
                'due_amount'      => $quote['grand_total'],
                'payment_status'  => 'unpaid',
                'quotation_id'    => $id,
                'note'            => $quote['note'],
            ];

            // Map quotation items to sale items format
            $saleItems = array_map(function($item) {
                return [
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount'   => $item['discount'],
                    'tax_rate'   => $item['tax_rate'],
                    'tax_amount' => $item['tax_amount'],
                    'subtotal'   => $item['subtotal'],
                    'total'      => $item['total'],
                ];
            }, $quote['items']);

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
}
