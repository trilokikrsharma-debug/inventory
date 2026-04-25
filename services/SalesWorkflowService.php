<?php
class SalesWorkflowService {
    private CustomerModel $customerModel;
    private LineItemProcessor $lineItemProcessor;

    public function __construct(?CustomerModel $customerModel = null, ?LineItemProcessor $lineItemProcessor = null) {
        $this->customerModel = $customerModel ?: new CustomerModel();
        $this->lineItemProcessor = $lineItemProcessor ?: new LineItemProcessor();
    }

    public function buildCreatePayload(array $input, array $settings, string $invoiceNumber, bool $warehouseFeatureEnabled, array $warehouseOptions = []): array {
        $payload = $this->buildPersistPayload($input, $settings, $warehouseFeatureEnabled, $warehouseOptions);
        $payload['sale']['invoice_number'] = $invoiceNumber;
        $payload['sale']['status'] = $this->sanitize($input['status'] ?? 'completed') ?: 'completed';

        return $payload;
    }

    public function buildUpdatePayload(array $input, array $settings, bool $warehouseFeatureEnabled, array $warehouseOptions = []): array {
        return $this->buildPersistPayload($input, $settings, $warehouseFeatureEnabled, $warehouseOptions);
    }

    public function buildReceiptPayload(array $input, array $saleData, string $receiptNumber, int $saleId, int $userId): ?array {
        $paidAmount = (float)($saleData['paid_amount'] ?? 0);
        if ($paidAmount <= 0) {
            return null;
        }

        return [
            'payment_number' => $receiptNumber,
            'type' => 'receipt',
            'customer_id' => (int)($saleData['customer_id'] ?? 0),
            'sale_id' => $saleId,
            'amount' => $paidAmount,
            'payment_method' => $this->normalizePaymentMethod($input['payment_method'] ?? 'cash'),
            'payment_date' => trim((string)($input['sale_date'] ?? '')),
            'note' => 'Payment for ' . (string)($saleData['invoice_number'] ?? ''),
            'created_by' => $userId > 0 ? $userId : null,
        ];
    }

    private function buildPersistPayload(array $input, array $settings, bool $warehouseFeatureEnabled, array $warehouseOptions): array {
        $allowTax = $this->taxEnabled($settings);
        $items = $this->normalizeItems($input, $allowTax);
        $totals = $this->lineItemProcessor->calculateTotals($items);
        $subtotal = (float)$totals['subtotal'];
        $totalTax = (float)$totals['total_tax'];

        $discountAmount = max(0, (float)($input['discount_amount'] ?? 0));
        if ($discountAmount > $subtotal) {
            throw new InvalidArgumentException('Discount cannot exceed subtotal.');
        }

        $freightCharge = max(0, (float)($input['freight_charge'] ?? 0));
        $loadingCharge = max(0, (float)($input['loading_charge'] ?? 0));
        $shippingCost = $freightCharge + $loadingCharge;
        $shippingInput = max(0, (float)($input['shipping_cost'] ?? 0));
        if ($shippingCost <= 0 && $shippingInput > 0) {
            $shippingCost = $shippingInput;
            $freightCharge = $shippingInput;
        }

        $saleDate = trim((string)($input['sale_date'] ?? ''));
        if (!$this->isValidDateYmd($saleDate)) {
            throw new InvalidArgumentException('Invalid sale date format. Use YYYY-MM-DD.');
        }

        $isAutoRoundOff = !empty($settings['auto_round_off_rupee']);
        $roundOff = (float)($input['round_off'] ?? 0);
        if (!$isAutoRoundOff && abs($roundOff) > 10) {
            throw new InvalidArgumentException('Round-off cannot exceed +/-10.');
        }

        $customerId = (int)($input['customer_id'] ?? 0);
        $customer = $this->customerModel->find($customerId);
        if ($customerId <= 0 || !$customer) {
            throw new InvalidArgumentException('Please select a valid customer.');
        }

        $baseTotal = $subtotal - $discountAmount + $totalTax + $shippingCost;
        $roundOff = $isAutoRoundOff
            ? round(round($baseTotal) - $baseTotal, 2)
            : round($roundOff, 2);
        $grandTotal = $baseTotal + $roundOff;
        if ($grandTotal < 0) {
            throw new InvalidArgumentException('Grand total cannot be negative.');
        }

        $paidAmount = max(0, (float)($input['paid_amount'] ?? 0));
        if ($paidAmount > ($grandTotal + 0.009)) {
            throw new InvalidArgumentException('Paid amount cannot exceed grand total.');
        }

        $warehouseId = $this->resolveWarehouseId($input, $warehouseFeatureEnabled, $warehouseOptions);
        $dueAmount = max(0, $grandTotal - $paidAmount);

        return [
            'items' => $items,
            'sale' => [
                'customer_id' => $customerId,
                'warehouse_id' => $warehouseId,
                'sale_date' => $saleDate,
                'reference_number' => $this->sanitize($input['reference_number'] ?? null),
                'dispatch_vehicle' => $this->sanitize($input['dispatch_vehicle'] ?? null),
                'dispatch_transporter' => $this->sanitize($input['dispatch_transporter'] ?? null),
                'dispatch_lr_no' => $this->sanitize($input['dispatch_lr_no'] ?? null),
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $totalTax,
                'shipping_cost' => $shippingCost,
                'freight_charge' => $freightCharge,
                'loading_charge' => $loadingCharge,
                'round_off' => $roundOff,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'payment_status' => $paidAmount >= $grandTotal ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid'),
                'note' => $this->sanitize($input['note'] ?? null),
                'gst_type' => $this->resolveSaleGstType($customer, (string)($input['gst_type'] ?? 'auto'), $settings),
            ],
        ];
    }

    private function normalizeItems(array $input, bool $allowTax): array {
        $normalizedInput = $input;
        if (!$allowTax) {
            $productIds = $input['product_id'] ?? [];
            $count = is_array($productIds) ? count($productIds) : 0;
            $normalizedInput['item_tax_rate'] = array_fill(0, $count, 0);
        }

        try {
            $items = $this->lineItemProcessor->parseFromPost($normalizedInput);
        } catch (InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'At least one valid line item is required.')) {
                throw new InvalidArgumentException('At least one item is required.');
            }

            throw new InvalidArgumentException('Invalid item values. Quantity must be greater than 0, tax must be 0-100, and discount cannot exceed line amount.');
        }

        foreach ($items as &$item) {
            $item['discount'] = $item['discount_amount'];
            unset($item['discount_amount']);
        }
        unset($item);

        return $items;
    }

    private function resolveWarehouseId(array $input, bool $warehouseFeatureEnabled, array $warehouseOptions): ?int {
        if (!$warehouseFeatureEnabled) {
            return null;
        }

        $selected = (int)($input['warehouse_id'] ?? 0);
        foreach ($warehouseOptions as $warehouse) {
            if ((int)($warehouse['id'] ?? 0) === $selected) {
                return $selected;
            }
        }

        throw new RuntimeException('Please select a valid warehouse.');
    }

    private function resolveSaleGstType(array $customer, string $requestedType, array $settings): string {
        // If tax is completely disabled, no GST type needed
        if (!$this->taxEnabled($settings)) {
            return 'none';
        }

        // If tax is on but GST is off → non-GST simple tax mode
        if (!$this->gstEnabled($settings)) {
            return 'none';
        }

        // GST mode: resolve IGST vs CGST/SGST
        $requested = strtolower(trim($requestedType));
        if (in_array($requested, ['igst', 'cgst_sgst', 'none'], true)) {
            return $requested;
        }

        $companyState = $this->normalizeState((string)($settings['company_state'] ?? ''));
        $customerState = $this->normalizeState((string)($customer['state'] ?? ''));

        if ($companyState !== '' && $customerState !== '' && $companyState !== $customerState) {
            return 'igst';
        }

        return 'cgst_sgst';
    }

    /**
     * Check if tax/GST calculation is enabled.
     * In Indian billing, tax = GST (they are coupled).
     */
    private function taxEnabled(array $settings): bool {
        $isTaxEnabled = !isset($settings['enable_tax']) || !empty($settings['enable_tax']);
        $isGstEnabled = !isset($settings['enable_gst']) || !empty($settings['enable_gst']);
        return $isTaxEnabled && $isGstEnabled;
    }

    /**
     * Check if GST-specific features are enabled (CGST/SGST/IGST breakup).
     */
    private function gstEnabled(array $settings): bool {
        return $this->taxEnabled($settings)
            && (!isset($settings['enable_gst']) || !empty($settings['enable_gst']));
    }

    private function normalizeState(string $state): string {
        $state = trim(strtolower($state));
        if ($state === '') {
            return '';
        }

        return (string)preg_replace('/[^a-z0-9]/', '', $state);
    }

    private function isValidDateYmd(string $value): bool {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }

        $dt = DateTime::createFromFormat('Y-m-d', $value);
        return $dt instanceof DateTime && $dt->format('Y-m-d') === $value;
    }

    private function sanitize($value): string {
        if ($value === null || is_array($value)) {
            return '';
        }

        $clean = Helper::decodeHtmlEntities((string)$value);
        $clean = strip_tags($clean);
        return trim($clean);
    }

    private function normalizePaymentMethod($method, string $default = 'cash'): string {
        $raw = strtolower(trim((string)$method));
        if ($raw === 'upi') {
            return 'online';
        }

        $allowed = ['cash', 'bank', 'cheque', 'online', 'other'];
        return in_array($raw, $allowed, true) ? $raw : $default;
    }
}
