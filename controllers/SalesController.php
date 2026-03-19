<?php
/**
 * Sales Controller
 * 
 * Handles sales entries with multi items, discounts, tax, round-off.
 */
class SalesController extends Controller {

    protected $allowedActions = ['index', 'create', 'edit', 'view_sale', 'delete'];

    /**
     * Cached sales table columns for optional-field compatibility.
     *
     * @var array<string, bool>|null
     */
    private static $salesColumnMap = null;

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
            $isTaxEnabled = !isset($settings['enable_tax']) || !empty($settings['enable_tax']);
            $isGstEnabled = !isset($settings['enable_gst']) || !empty($settings['enable_gst']);
            $allowTax = $isTaxEnabled && $isGstEnabled;
            $isAutoRoundOff = !empty($settings['auto_round_off_rupee']);

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
                        error_log("Invalid input values in Sales create: qty=$qty, price=$price, tax=$taxRate, disc=$disc");
                        $this->setFlash('error', 'Invalid item values. Quantity must be greater than 0, tax must be 0-100, and discount cannot exceed line amount.');
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
            $freightCharge = max(0, (float)$this->post('freight_charge', 0));
            $loadingCharge = max(0, (float)$this->post('loading_charge', 0));
            $shippingCost = $freightCharge + $loadingCharge;
            $shippingInput = max(0, (float)$this->post('shipping_cost', 0));
            if ($shippingCost <= 0 && $shippingInput > 0) {
                // Backward compatibility with old form payloads.
                $shippingCost = $shippingInput;
                $freightCharge = $shippingInput;
            }
            $roundOff = (float)$this->post('round_off', 0);

            if (!$isAutoRoundOff && abs($roundOff) > 10) {
                $this->setFlash('error', 'Round-off cannot exceed +/-10.');
                $this->redirect('index.php?page=sales&action=create');
                return;
            }
            if ($discountAmount > $subtotal) {
                $this->setFlash('error', 'Discount cannot exceed subtotal.');
                $this->redirect('index.php?page=sales&action=create');
                return;
            }

            // Validate sale date format
            $saleDate = (string)$this->post('sale_date');
            if (!$this->isValidDateYmd($saleDate)) {
                $this->setFlash('error', 'Invalid sale date format. Use YYYY-MM-DD.');
                $this->redirect('index.php?page=sales&action=create');
                return;
            }

            $customerId = (int)$this->post('customer_id');
            $customer = (new CustomerModel())->find($customerId);
            if ($customerId <= 0 || !$customer) {
                $this->setFlash('error', 'Please select a valid customer.');
                $this->redirect('index.php?page=sales&action=create');
                return;
            }

            $baseTotal = $subtotal - $discountAmount + $totalTax + $shippingCost;
            if ($isAutoRoundOff) {
                $roundOff = round(round($baseTotal) - $baseTotal, 2);
            } else {
                $roundOff = round($roundOff, 2);
            }
            $grandTotal = $baseTotal + $roundOff;
            if ($grandTotal < 0) {
                $this->setFlash('error', 'Grand total cannot be negative.');
                $this->redirect('index.php?page=sales&action=create');
                return;
            }

            $paidAmount = max(0, (float)$this->post('paid_amount', 0));
            if ($paidAmount > ($grandTotal + 0.009)) {
                $this->setFlash('error', 'Paid amount cannot exceed grand total.');
                $this->redirect('index.php?page=sales&action=create');
                return;
            }
            $dueAmount = max(0, $grandTotal - $paidAmount);
            $gstType = $this->resolveSaleGstType($customerId, (string)$this->post('gst_type', 'auto'), $settings);

            $paymentStatus = 'unpaid';
            if ($paidAmount >= $grandTotal) $paymentStatus = 'paid';
            elseif ($paidAmount > 0) $paymentStatus = 'partial';

            $saleData = [
                'invoice_number'  => $invoiceNumber,
                'customer_id'     => $customerId,
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
            $saleData = $this->appendOptionalSaleFields($saleData, [
                'freight_charge' => $freightCharge,
                'loading_charge' => $loadingCharge,
                'gst_type' => $gstType,
            ]);

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
                        'customer_id'   => $customerId,
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
            $settings = (new SettingsModel())->getSettings();
            $isTaxEnabled = !isset($settings['enable_tax']) || !empty($settings['enable_tax']);
            $isGstEnabled = !isset($settings['enable_gst']) || !empty($settings['enable_gst']);
            $allowTax = $isTaxEnabled && $isGstEnabled;
            $isAutoRoundOff = !empty($settings['auto_round_off_rupee']);

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
                        error_log("Invalid input values in Sales edit: qty=$qty, price=$price, tax=$taxRate, disc=$disc");
                        $this->setFlash('error', 'Invalid item values. Quantity must be greater than 0, tax must be 0-100, and discount cannot exceed line amount.');
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

            $discountAmount = max(0, (float)$this->post('discount_amount', 0));
            $freightCharge = max(0, (float)$this->post('freight_charge', 0));
            $loadingCharge = max(0, (float)$this->post('loading_charge', 0));
            $shippingCost = $freightCharge + $loadingCharge;
            $shippingInput = max(0, (float)$this->post('shipping_cost', 0));
            if ($shippingCost <= 0 && $shippingInput > 0) {
                $shippingCost = $shippingInput;
                $freightCharge = $shippingInput;
            }
            $roundOff       = (float)$this->post('round_off', 0);
            if (!$isAutoRoundOff && abs($roundOff) > 10) {
                $this->setFlash('error', 'Round-off cannot exceed +/-10.');
                $this->redirect('index.php?page=sales&action=edit&id=' . $id);
                return;
            }
            if ($discountAmount > $subtotal) {
                $this->setFlash('error', 'Discount cannot exceed subtotal.');
                $this->redirect('index.php?page=sales&action=edit&id=' . $id);
                return;
            }
            $saleDate = (string)$this->post('sale_date');
            if (!$this->isValidDateYmd($saleDate)) {
                $this->setFlash('error', 'Invalid sale date format. Use YYYY-MM-DD.');
                $this->redirect('index.php?page=sales&action=edit&id=' . $id);
                return;
            }
            $baseTotal = $subtotal - $discountAmount + $totalTax + $shippingCost;
            if ($isAutoRoundOff) {
                $roundOff = round(round($baseTotal) - $baseTotal, 2);
            } else {
                $roundOff = round($roundOff, 2);
            }
            $grandTotal = $baseTotal + $roundOff;
            $customerId = (int)$this->post('customer_id');
            $customer = (new CustomerModel())->find($customerId);
            if ($customerId <= 0 || !$customer) {
                $this->setFlash('error', 'Please select a valid customer.');
                $this->redirect('index.php?page=sales&action=edit&id=' . $id);
                return;
            }
            if ($grandTotal < 0) {
                $this->setFlash('error', 'Grand total cannot be negative.');
                $this->redirect('index.php?page=sales&action=edit&id=' . $id);
                return;
            }
            $paidAmount = max(0, (float)$this->post('paid_amount', 0));
            if ($paidAmount > ($grandTotal + 0.009)) {
                $this->setFlash('error', 'Paid amount cannot exceed grand total.');
                $this->redirect('index.php?page=sales&action=edit&id=' . $id);
                return;
            }
            $dueAmount = max(0, $grandTotal - $paidAmount);
            $paymentStatus  = $paidAmount >= $grandTotal ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');
            $gstType = $this->resolveSaleGstType($customerId, (string)$this->post('gst_type', 'auto'), $settings);

            $saleData = [
                'customer_id'     => $customerId,
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
                'note'            => $this->sanitize($this->post('note')),
            ];
            $saleData = $this->appendOptionalSaleFields($saleData, [
                'freight_charge' => $freightCharge,
                'loading_charge' => $loadingCharge,
                'gst_type' => $gstType,
            ]);

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
        $returnSummary = (new SaleReturnModel())->getSaleReturnSummary($id);
        $this->view('sales.view', [
            'pageTitle' => 'Sale Details',
            'sale' => $sale,
            'company' => $company,
            'returnSummary' => $returnSummary,
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

    /**
     * Resolve sale GST mode for invoice breakup.
     */
    private function resolveSaleGstType(int $customerId, string $requestedType, array $settings): string {
        $isTaxEnabled = !isset($settings['enable_tax']) || !empty($settings['enable_tax']);
        $isGstEnabled = !isset($settings['enable_gst']) || !empty($settings['enable_gst']);
        if (!$isTaxEnabled || !$isGstEnabled) {
            return 'none';
        }

        $requested = strtolower(trim($requestedType));
        if (in_array($requested, ['igst', 'cgst_sgst', 'none'], true)) {
            return $requested;
        }

        // Auto mode: derive by comparing company and customer states.
        $companyState = $this->normalizeState((string)($settings['company_state'] ?? ''));
        $customer = (new CustomerModel())->find($customerId);
        $customerState = $this->normalizeState((string)($customer['state'] ?? ''));

        if ($companyState !== '' && $customerState !== '' && $companyState !== $customerState) {
            return 'igst';
        }
        return 'cgst_sgst';
    }

    /**
     * Normalize state name for robust comparisons.
     */
    private function normalizeState(string $state): string {
        $state = trim(strtolower($state));
        if ($state === '') {
            return '';
        }
        $state = preg_replace('/[^a-z0-9]/', '', $state);
        return (string)$state;
    }

    /**
     * Strict YYYY-MM-DD validation that rejects overflow dates (e.g. 2026-02-31).
     */
    private function isValidDateYmd(string $value): bool {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }
        $dt = DateTime::createFromFormat('Y-m-d', $value);
        return $dt instanceof DateTime && $dt->format('Y-m-d') === $value;
    }

    /**
     * Append fields that only exist in newer schema versions.
     *
     * Prevents runtime SQL errors if migration has not run yet.
     */
    private function appendOptionalSaleFields(array $saleData, array $optionalFields): array {
        foreach ($optionalFields as $field => $value) {
            if ($this->salesColumnExists($field)) {
                $saleData[$field] = $value;
            }
        }
        return $saleData;
    }

    /**
     * Check sales table column existence with cached schema lookup.
     */
    private function salesColumnExists(string $column): bool {
        if (self::$salesColumnMap === null) {
            self::$salesColumnMap = [];
            try {
                $rows = Database::getInstance()->query("SHOW COLUMNS FROM sales")->fetchAll();
                foreach ($rows as $row) {
                    if (!empty($row['Field'])) {
                        self::$salesColumnMap[$row['Field']] = true;
                    }
                }
            } catch (Throwable $e) {
                // Safe fallback: treat optional columns as absent.
                self::$salesColumnMap = [];
            }
        }
        return !empty(self::$salesColumnMap[$column]);
    }
}




