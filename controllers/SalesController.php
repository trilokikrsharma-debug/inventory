<?php
/**
 * Sales Controller
 * 
 * Handles sales entries with multi items, discounts, tax, round-off.
 */
class SalesController extends Controller {

    protected $allowedActions = ['index', 'create', 'edit', 'view_sale', 'delete'];

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

            $items = [];
            $subtotal = 0;
            $totalTax = 0;

            $productIds = $this->post('product_id');
            $quantities = $this->post('quantity');
            $unitPrices = $this->post('unit_price');
            $discounts = $this->post('item_discount');
            $taxRates = $this->post('item_tax_rate');

            if (!empty($productIds)) {
                for ($i = 0; $i < count($productIds); $i++) {
                    if (empty($productIds[$i])) continue;
                    $qty = (float)$quantities[$i];
                    $price = (float)$unitPrices[$i];
                    $disc = (float)($discounts[$i] ?? 0);
                    $taxRate = (float)($taxRates[$i] ?? 0);

                    if ($qty < 0 || $price < 0 || $taxRate < 0 || $taxRate > 100 || $disc < 0 || $disc > 100) {
                        error_log("Invalid input values in Sales create: qty=$qty, price=$price, tax=$taxRate, disc=$disc");
                        $this->setFlash('error', 'Invalid quantities, prices, taxes, or discounts provided. Values must be positive and percentages must be 0-100.');
                        $this->redirect('index.php?page=sales&action=create');
                        return;
                    }

                    $itemSubtotal = ($qty * $price) - $disc;
                    $taxAmt = $itemSubtotal * ($taxRate / 100);
                    $itemTotal = $itemSubtotal + $taxAmt;

                    $items[] = [
                        'product_id' => (int)$productIds[$i],
                        'quantity'   => $qty,
                        'unit_price' => $price,
                        'discount'   => $disc,
                        'tax_rate'   => $taxRate,
                        'tax_amount' => $taxAmt,
                        'subtotal'   => $itemSubtotal,
                        'total'      => $itemTotal,
                    ];

                    $subtotal += $itemSubtotal;
                    $totalTax += $taxAmt;
                }
            }
            // Validate: at least one item required
            if (empty($items)) {
                $this->setFlash('error', 'At least one item is required.');
                $this->redirect('index.php?page=sales&action=create');
                return;
            }

            // Validate financial fields (prevent fraud via negative values)
            $discountAmount = max(0, (float)$this->post('discount_amount', 0));
            $shippingCost = max(0, (float)$this->post('shipping_cost', 0));
            $roundOff = (float)$this->post('round_off', 0);

            if (abs($roundOff) > 10) {
                $this->setFlash('error', 'Round-off cannot exceed ±10.');
                $this->redirect('index.php?page=sales&action=create');
                return;
            }
            if ($discountAmount > $subtotal) {
                $this->setFlash('error', 'Discount cannot exceed subtotal.');
                $this->redirect('index.php?page=sales&action=create');
                return;
            }

            // Validate sale date format
            $saleDate = $this->post('sale_date');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $saleDate) || !strtotime($saleDate)) {
                $this->setFlash('error', 'Invalid sale date format. Use YYYY-MM-DD.');
                $this->redirect('index.php?page=sales&action=create');
                return;
            }

            $grandTotal = $subtotal - $discountAmount + $totalTax + $shippingCost + $roundOff;
            $paidAmount = max(0, (float)$this->post('paid_amount', 0));
            $dueAmount = $grandTotal - $paidAmount;

            $paymentStatus = 'unpaid';
            if ($paidAmount >= $grandTotal) $paymentStatus = 'paid';
            elseif ($paidAmount > 0) $paymentStatus = 'partial';

            $saleData = [
                'invoice_number'  => $invoiceNumber,
                'customer_id'     => (int)$this->post('customer_id'),
                'sale_date'       => $saleDate,
                'reference_number'=> $this->sanitize($this->post('reference_number')),
                'subtotal'        => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount'      => $totalTax,
                'shipping_cost'   => $shippingCost,
                'round_off'       => $roundOff,
                'grand_total'     => $grandTotal,
                'paid_amount'     => $paidAmount,
                'due_amount'      => $dueAmount,
                'payment_status'  => $paymentStatus,
                'status'          => $this->post('status', 'completed'),
                'note'            => $this->sanitize($this->post('note')),
            ];

            try {
                $salesModel = new SalesModel();
                $saleId = $salesModel->createSale($saleData, $items, Session::get('user')['id']);

                // Create receipt if paid
                if ($paidAmount > 0) {
                    $paymentModel = new PaymentModel();
                    $receiptNumber = $settingsModel->getNextNumber('receipt');
                    $paymentModel->create([
                        'payment_number' => $receiptNumber,
                        'type'          => 'receipt',
                        'customer_id'   => (int)$this->post('customer_id'),
                        'sale_id'       => $saleId,
                        'amount'        => $paidAmount,
                        'payment_method'=> $this->normalizePaymentMethod($this->post('payment_method', 'cash')),
                        'payment_date'  => $this->post('sale_date'),
                        'note'          => 'Payment for ' . $invoiceNumber,
                        'created_by'    => Session::get('user')['id'],
                    ]);
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
            $items = [];
            $subtotal = 0;
            $totalTax = 0;

            $productIds = $this->post('product_id');
            $quantities = $this->post('quantity');
            $unitPrices = $this->post('unit_price');
            $discounts  = $this->post('item_discount');
            $taxRates   = $this->post('item_tax_rate');

            if (!empty($productIds)) {
                for ($i = 0; $i < count($productIds); $i++) {
                    if (empty($productIds[$i])) continue;
                    $qty      = (float)$quantities[$i];
                    $price    = (float)$unitPrices[$i];
                    $disc     = (float)($discounts[$i] ?? 0);
                    $taxRate  = (float)($taxRates[$i] ?? 0);
                    
                    if ($qty < 0 || $price < 0 || $taxRate < 0 || $taxRate > 100 || $disc < 0 || $disc > 100) {
                        error_log("Invalid input values in Sales edit: qty=$qty, price=$price, tax=$taxRate, disc=$disc");
                        $this->setFlash('error', 'Invalid quantities, prices, taxes, or discounts provided. Values must be positive and percentages must be 0-100.');
                        $this->redirect('index.php?page=sales&action=edit&id=' . $id);
                        return;
                    }
                    $itemSub  = ($qty * $price) - $disc;
                    $taxAmt   = $itemSub * ($taxRate / 100);
                    $items[]  = [
                        'product_id' => (int)$productIds[$i],
                        'quantity'   => $qty, 'unit_price' => $price,
                        'discount'   => $disc, 'tax_rate'  => $taxRate,
                        'tax_amount' => $taxAmt, 'subtotal' => $itemSub,
                        'total'      => $itemSub + $taxAmt,
                    ];
                    $subtotal += $itemSub; $totalTax += $taxAmt;
                }
            }

            if (empty($items)) {
                $this->setFlash('error', 'At least one item is required.');
                $this->redirect('index.php?page=sales&action=edit&id=' . $id);
                return;
            }

            $discountAmount = (float)$this->post('discount_amount', 0);
            $shippingCost   = (float)$this->post('shipping_cost', 0);
            $roundOff       = (float)$this->post('round_off', 0);
            $grandTotal     = $subtotal - $discountAmount + $totalTax + $shippingCost + $roundOff;
            $paidAmount     = (float)$this->post('paid_amount', 0);
            $dueAmount      = $grandTotal - $paidAmount;
            $paymentStatus  = $paidAmount >= $grandTotal ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');

            $saleData = [
                'customer_id'     => (int)$this->post('customer_id'),
                'sale_date'       => $this->post('sale_date'),
                'reference_number'=> $this->sanitize($this->post('reference_number')),
                'subtotal'        => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount'      => $totalTax,
                'shipping_cost'   => $shippingCost,
                'round_off'       => $roundOff,
                'grand_total'     => $grandTotal,
                'paid_amount'     => $paidAmount,
                'due_amount'      => $dueAmount,
                'payment_status'  => $paymentStatus,
                'note'            => $this->sanitize($this->post('note')),
            ];

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
        ]);
    }

    public function view_sale() {
        $this->requirePermission('sales.view');
        $id = (int)$this->get('id');
        $sale = (new SalesModel())->getWithDetails($id);
        $this->authorizeRecordAccess($sale, 'index.php?page=sales');

        $company = (new SettingsModel())->getSettings();
        $this->view('sales.view', ['pageTitle' => 'Sale Details', 'sale' => $sale, 'company' => $company]);
    }

    public function delete() {
        $this->requirePermission('sales.delete');
        if (!$this->isPost()) { $this->redirect('index.php?page=sales'); }
        $this->validateCSRF();

        $id     = (int)$this->post('id');
        $userId = Session::get('user')['id'];

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
}
