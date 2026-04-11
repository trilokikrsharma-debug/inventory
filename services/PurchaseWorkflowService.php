<?php
class PurchaseWorkflowService {
    private SupplierModel $supplierModel;
    private LineItemProcessor $lineItemProcessor;

    public function __construct(?SupplierModel $supplierModel = null, ?LineItemProcessor $lineItemProcessor = null) {
        $this->supplierModel = $supplierModel ?: new SupplierModel();
        $this->lineItemProcessor = $lineItemProcessor ?: new LineItemProcessor();
    }

    public function buildCreatePayload(array $input, array $settings, string $invoiceNumber, bool $warehouseFeatureEnabled, array $warehouseOptions = []): array {
        $payload = $this->buildPersistPayload($input, $settings, $warehouseFeatureEnabled, $warehouseOptions);
        $payload['purchase']['invoice_number'] = $invoiceNumber;
        $payload['purchase']['status'] = $this->sanitize($input['status'] ?? 'received') ?: 'received';

        return $payload;
    }

    public function buildUpdatePayload(array $input, array $settings, bool $warehouseFeatureEnabled, array $warehouseOptions = []): array {
        return $this->buildPersistPayload($input, $settings, $warehouseFeatureEnabled, $warehouseOptions);
    }

    public function buildPaymentPayload(array $input, array $purchaseData, string $paymentNumber, int $purchaseId, int $userId): ?array {
        $paidAmount = (float)($purchaseData['paid_amount'] ?? 0);
        if ($paidAmount <= 0) {
            return null;
        }

        return [
            'payment_number' => $paymentNumber,
            'type' => 'payment',
            'supplier_id' => (int)($purchaseData['supplier_id'] ?? 0),
            'purchase_id' => $purchaseId,
            'amount' => $paidAmount,
            'payment_method' => $this->normalizePaymentMethod($input['payment_method'] ?? 'cash'),
            'payment_date' => trim((string)($input['purchase_date'] ?? '')),
            'note' => 'Payment for ' . (string)($purchaseData['invoice_number'] ?? ''),
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

        $shippingCost = max(0, (float)($input['shipping_cost'] ?? 0));
        $purchaseDate = trim((string)($input['purchase_date'] ?? ''));
        if (!$this->isValidDateYmd($purchaseDate)) {
            throw new InvalidArgumentException('Invalid purchase date format. Use YYYY-MM-DD.');
        }

        $supplierId = (int)($input['supplier_id'] ?? 0);
        $supplier = $this->supplierModel->find($supplierId);
        if ($supplierId <= 0 || !$supplier) {
            throw new InvalidArgumentException('Please select a valid supplier.');
        }

        $grandTotal = $subtotal - $discountAmount + $totalTax + $shippingCost;
        if ($grandTotal < 0) {
            throw new InvalidArgumentException('Grand total cannot be negative.');
        }

        $paidAmount = max(0, (float)($input['paid_amount'] ?? 0));
        if ($paidAmount > ($grandTotal + 0.009)) {
            throw new InvalidArgumentException('Paid amount cannot exceed grand total.');
        }

        return [
            'items' => $items,
            'purchase' => [
                'supplier_id' => $supplierId,
                'warehouse_id' => $this->resolveWarehouseId($input, $warehouseFeatureEnabled, $warehouseOptions),
                'purchase_date' => $purchaseDate,
                'reference_number' => $this->sanitize($input['reference_number'] ?? null),
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $totalTax,
                'shipping_cost' => $shippingCost,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'due_amount' => max(0, $grandTotal - $paidAmount),
                'payment_status' => $paidAmount >= $grandTotal ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid'),
                'note' => $this->sanitize($input['note'] ?? null),
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

    private function taxEnabled(array $settings): bool {
        $isTaxEnabled = !isset($settings['enable_tax']) || !empty($settings['enable_tax']);
        $isGstEnabled = !isset($settings['enable_gst']) || !empty($settings['enable_gst']);
        return $isTaxEnabled && $isGstEnabled;
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
