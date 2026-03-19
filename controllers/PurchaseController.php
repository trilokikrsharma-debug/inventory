<?php
/**
 * Purchase Controller
 * 
 * Handles purchase entries with multiple items, auto stock updates.
 */
class PurchaseController extends Controller {

    protected $allowedActions = ['index', 'create', 'edit', 'view_purchase', 'delete'];

    public function index() {
        $this->requirePermission('purchases.view');
        $purchases = (new PurchaseModel())->getAllWithSupplier(
            $this->get('search', ''),
            $this->get('from_date', ''),
            $this->get('to_date', ''),
            $this->get('supplier_id', ''),
            $this->get('status', ''),
            max(1, (int)$this->get('pg', 1))
        );
        $suppliers = (new SupplierModel())->allActive();

        $this->view('purchases.index', [
            'pageTitle' => 'Purchases',
            'purchases' => $purchases,
            'suppliers' => $suppliers,
            'filters'   => $this->safeFilters(),
        ]);
    }

    public function create() {
        $this->requirePermission('purchases.create');

        if ($this->isPost()) {
            $this->validateCSRF();
            $settingsModel = new SettingsModel();
            $invoiceNumber = $settingsModel->getNextNumber('purchase');
            $settings = $settingsModel->getSettings();
            $isTaxEnabled = !isset($settings['enable_tax']) || !empty($settings['enable_tax']);
            $isGstEnabled = !isset($settings['enable_gst']) || !empty($settings['enable_gst']);
            $allowTax = $isTaxEnabled && $isGstEnabled;

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
                    if (!$allowTax) {
                        $taxRate = 0.0;
                    }
                    $lineBase = $qty * $price;

                    if ($qty <= 0 || $price < 0 || $taxRate < 0 || $taxRate > 100 || $disc < 0 || $disc > $lineBase) {
                        error_log("Invalid input values in Purchase create: qty=$qty, price=$price, tax=$taxRate, disc=$disc");
                        $this->setFlash('error', 'Invalid item values. Quantity must be greater than 0, tax must be 0-100, and discount cannot exceed line amount.');
                        $this->redirect('index.php?page=purchases&action=create');
                        return;
                    }

                    $itemSubtotal = $lineBase - $disc;
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
                $this->redirect('index.php?page=purchases&action=create');
                return;
            }

            // Validate financial fields (prevent fraud via negative values)
            $discountAmount = max(0, (float)$this->post('discount_amount', 0));
            $shippingCost = max(0, (float)$this->post('shipping_cost', 0));

            if ($discountAmount > $subtotal) {
                $this->setFlash('error', 'Discount cannot exceed subtotal.');
                $this->redirect('index.php?page=purchases&action=create');
                return;
            }

            // Validate purchase date format
            $purchaseDate = $this->post('purchase_date');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $purchaseDate) || !strtotime($purchaseDate)) {
                $this->setFlash('error', 'Invalid purchase date format. Use YYYY-MM-DD.');
                $this->redirect('index.php?page=purchases&action=create');
                return;
            }

            $grandTotal = $subtotal - $discountAmount + $totalTax + $shippingCost;
            $paidAmount = max(0, (float)$this->post('paid_amount', 0));
            $dueAmount = $grandTotal - $paidAmount;

            $paymentStatus = 'unpaid';
            if ($paidAmount >= $grandTotal) $paymentStatus = 'paid';
            elseif ($paidAmount > 0) $paymentStatus = 'partial';

            $purchaseData = [
                'invoice_number'  => $invoiceNumber,
                'supplier_id'     => (int)$this->post('supplier_id'),
                'purchase_date'   => $purchaseDate,
                'reference_number'=> $this->sanitize($this->post('reference_number')),
                'subtotal'        => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount'      => $totalTax,
                'shipping_cost'   => $shippingCost,
                'grand_total'     => $grandTotal,
                'paid_amount'     => $paidAmount,
                'due_amount'      => $dueAmount,
                'payment_status'  => $paymentStatus,
                'status'          => $this->post('status', 'received'),
                'note'            => $this->sanitize($this->post('note')),
            ];

            try {
                $purchaseModel = new PurchaseModel();
                $purchaseId = $purchaseModel->createPurchase($purchaseData, $items, Session::get('user')['id']);

                // Create payment record if paid
                if ($paidAmount > 0) {
                    $paymentModel = new PaymentModel();
                    $paymentNumber = $settingsModel->getNextNumber('payment');
                    $paymentModel->create([
                        'payment_number' => $paymentNumber,
                        'type'          => 'payment',
                        'supplier_id'   => (int)$this->post('supplier_id'),
                        'purchase_id'   => $purchaseId,
                        'amount'        => $paidAmount,
                        'payment_method'=> $this->normalizePaymentMethod($this->post('payment_method', 'cash')),
                        'payment_date'  => $this->post('purchase_date'),
                        'note'          => 'Payment for ' . $invoiceNumber,
                        'created_by'    => Session::get('user')['id'],
                    ]);
                }

                $this->logActivity('Created purchase: ' . $invoiceNumber, 'purchases', $purchaseId);
                $this->setFlash('success', 'Purchase created successfully. Invoice: ' . $invoiceNumber);
                $this->redirect('index.php?page=purchases&action=view_purchase&id=' . $purchaseId);
            } catch (Exception $e) {
                error_log($e->getMessage());
                $this->setFlash('error', 'An unexpected error occurred. Please try again.');
                $this->redirect('index.php?page=purchases&action=create');
            }
        }

        $suppliers = (new SupplierModel())->allActive();
        $settings = (new SettingsModel())->getSettings();

        $this->view('purchases.create', [
            'pageTitle' => 'New Purchase',
            'suppliers' => $suppliers,
            'settings'  => $settings,
        ]);
    }

    public function edit() {
        $this->requirePermission('purchases.edit');
        $id = (int)$this->get('id');

        $purchaseModel = new PurchaseModel();
        $purchase = $purchaseModel->getWithDetails($id);
        if (!$purchase) {
            $this->setFlash('error', 'Purchase not found.');
            $this->redirect('index.php?page=purchases');
            return;
        }

        if ($this->isPost()) {
            $this->validateCSRF();
            $items = [];
            $subtotal = 0;
            $totalTax = 0;
            $settings = (new SettingsModel())->getSettings();
            $isTaxEnabled = !isset($settings['enable_tax']) || !empty($settings['enable_tax']);
            $isGstEnabled = !isset($settings['enable_gst']) || !empty($settings['enable_gst']);
            $allowTax = $isTaxEnabled && $isGstEnabled;

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
                    if (!$allowTax) {
                        $taxRate = 0.0;
                    }
                    $lineBase = $qty * $price;

                    if ($qty <= 0 || $price < 0 || $taxRate < 0 || $taxRate > 100 || $disc < 0 || $disc > $lineBase) {
                        error_log("Invalid input values in Purchase edit: qty=$qty, price=$price, tax=$taxRate, disc=$disc");
                        $this->setFlash('error', 'Invalid item values. Quantity must be greater than 0, tax must be 0-100, and discount cannot exceed line amount.');
                        $this->redirect('index.php?page=purchases&action=edit&id=' . $id);
                        return;
                    }
                    $itemSub  = $lineBase - $disc;
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
                $this->redirect('index.php?page=purchases&action=edit&id=' . $id);
                return;
            }

            $discountAmount = (float)$this->post('discount_amount', 0);
            $shippingCost   = (float)$this->post('shipping_cost', 0);
            $grandTotal     = $subtotal - $discountAmount + $totalTax + $shippingCost;
            $paidAmount     = (float)$this->post('paid_amount', 0);
            $dueAmount      = $grandTotal - $paidAmount;
            $paymentStatus  = $paidAmount >= $grandTotal ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');

            $purchaseData = [
                'supplier_id'     => (int)$this->post('supplier_id'),
                'purchase_date'   => $this->post('purchase_date'),
                'reference_number'=> $this->sanitize($this->post('reference_number')),
                'subtotal'        => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount'      => $totalTax,
                'shipping_cost'   => $shippingCost,
                'grand_total'     => $grandTotal,
                'paid_amount'     => $paidAmount,
                'due_amount'      => $dueAmount,
                'payment_status'  => $paymentStatus,
                'note'            => $this->sanitize($this->post('note')),
            ];

            try {
                $purchaseModel->updatePurchase($id, $purchaseData, $items, Session::get('user')['id']);
                $this->logActivity('Edited purchase: ' . $purchase['invoice_number'], 'purchases', $id);
                $this->setFlash('success', 'Purchase updated. Stock and balances have been reconciled.');
                $this->redirect('index.php?page=purchases&action=view_purchase&id=' . $id);
            } catch (Exception $e) {
                error_log($e->getMessage());
                $this->setFlash('error', 'An unexpected error occurred. Please try again.');
                $this->redirect('index.php?page=purchases&action=edit&id=' . $id);
            }
        }

        $suppliers = (new SupplierModel())->allActive();
        $settings  = (new SettingsModel())->getSettings();
        $this->view('purchases.edit', [
            'pageTitle' => 'Edit Purchase: ' . $purchase['invoice_number'],
            'purchase'  => $purchase,
            'suppliers' => $suppliers,
            'settings'  => $settings,
        ]);
    }

    public function view_purchase() {
        $this->requirePermission('purchases.view');
        $id = (int)$this->get('id');
        $purchase = (new PurchaseModel())->getWithDetails($id);
        $this->authorizeRecordAccess($purchase, 'index.php?page=purchases');

        $this->view('purchases.view', [
            'pageTitle' => 'Purchase Details',
            'purchase'  => $purchase,
        ]);
    }

    public function delete() {
        $this->requirePermission('purchases.delete');
        if (!$this->isPost()) { $this->redirect('index.php?page=purchases'); }
        $this->validateCSRF();

        $id     = (int)$this->post('id');
        $userId = Session::get('user')['id'];

        try {
            $purchase = (new PurchaseModel())->getWithDetails($id);
            (new PurchaseModel())->deletePurchase($id, $userId);
            $this->logActivity('Deleted purchase: ' . ($purchase['invoice_number'] ?? $id), 'purchases', $id, 'Grand total: ' . ($purchase['grand_total'] ?? 0));
            $this->setFlash('success', 'Purchase deleted. Stock and supplier balance have been reversed.');
        } catch (Exception $e) {
            error_log($e->getMessage());
            $this->setFlash('error', 'An unexpected error occurred. Please try again.');
        }
        $this->redirect('index.php?page=purchases');
    }
}
