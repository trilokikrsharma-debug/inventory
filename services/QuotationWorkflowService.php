<?php
class QuotationWorkflowService {
    private CustomerModel $customerModel;
    private LineItemProcessor $lineItemProcessor;

    public function __construct(?CustomerModel $customerModel = null, ?LineItemProcessor $lineItemProcessor = null) {
        $this->customerModel = $customerModel ?: new CustomerModel();
        $this->lineItemProcessor = $lineItemProcessor ?: new LineItemProcessor();
    }

    public function buildCreatePayload(array $input, array $settings, string $quotationNumber): array {
        $allowTax = $this->taxEnabled($settings);
        $items = $this->normalizeItems($input, $allowTax);
        $totals = $this->lineItemProcessor->calculateTotals($items);
        $subtotal = (float)$totals['subtotal'];
        $taxTotal = (float)$totals['total_tax'];

        $customerId = (int)($input['customer_id'] ?? 0);
        $customer = $this->customerModel->find($customerId);
        if ($customerId <= 0 || !$customer) {
            throw new InvalidArgumentException('Please select a valid customer.');
        }

        $discountAmount = max(0, (float)($input['discount_amount'] ?? 0));
        if ($discountAmount > $subtotal) {
            throw new InvalidArgumentException('Discount cannot exceed subtotal.');
        }

        $shippingCost = max(0, (float)($input['shipping_cost'] ?? 0));
        $quotationDate = trim((string)($input['quotation_date'] ?? date('Y-m-d')));
        if (!$this->isValidDateYmd($quotationDate)) {
            throw new InvalidArgumentException('Invalid quotation date format. Use YYYY-MM-DD.');
        }

        $validUntilRaw = trim((string)($input['valid_until'] ?? ''));
        $validUntil = null;
        if ($validUntilRaw !== '') {
            if (!$this->isValidDateYmd($validUntilRaw)) {
                throw new InvalidArgumentException('Invalid valid-until date format. Use YYYY-MM-DD.');
            }
            $validUntil = $validUntilRaw;
        }

        $grandTotal = $subtotal + $taxTotal - $discountAmount + $shippingCost;
        if ($grandTotal < 0) {
            throw new InvalidArgumentException('Grand total cannot be negative.');
        }

        return [
            'items' => $items,
            'quotation' => [
                'quotation_number' => $quotationNumber,
                'customer_id' => $customerId,
                'quotation_date' => $quotationDate,
                'valid_until' => $validUntil,
                'subtotal' => $subtotal,
                'tax_amount' => $taxTotal,
                'discount_amount' => $discountAmount,
                'shipping_cost' => $shippingCost,
                'grand_total' => $grandTotal,
                'status' => 'draft',
                'note' => $this->sanitize($input['note'] ?? null),
                'terms' => $this->sanitize($input['terms'] ?? null),
            ],
        ];
    }

    public function buildSaleConversionPayload(array $quote, string $invoiceNumber, string $saleDate): array {
        if (empty($quote['items']) || !is_array($quote['items'])) {
            throw new InvalidArgumentException('Quotation has no items to convert.');
        }

        if (!$this->isValidDateYmd($saleDate)) {
            throw new InvalidArgumentException('Invalid sale date format. Use YYYY-MM-DD.');
        }

        $saleItems = array_map(function ($item) {
            return [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount' => $item['discount'],
                'tax_rate' => $item['tax_rate'],
                'tax_amount' => $item['tax_amount'],
                'subtotal' => $item['subtotal'],
                'total' => $item['total'],
            ];
        }, $quote['items']);

        return [
            'sale' => [
                'invoice_number' => $invoiceNumber,
                'customer_id' => $quote['customer_id'],
                'sale_date' => $saleDate,
                'subtotal' => $quote['subtotal'],
                'tax_amount' => $quote['tax_amount'],
                'discount_amount' => $quote['discount_amount'],
                'shipping_cost' => $quote['shipping_cost'],
                'grand_total' => $quote['grand_total'],
                'paid_amount' => 0,
                'due_amount' => $quote['grand_total'],
                'payment_status' => 'unpaid',
                'quotation_id' => $quote['id'] ?? null,
                'note' => $quote['note'] ?? '',
            ],
            'items' => $saleItems,
        ];
    }

    private function normalizeItems(array $input, bool $allowTax): array {
        $normalizedInput = [
            'product_id' => $input['product_id'] ?? [],
            'quantity' => $input['quantity'] ?? [],
            'unit_price' => $input['unit_price'] ?? [],
            'item_discount' => $input['discount'] ?? [],
            'item_tax_rate' => $input['tax_rate'] ?? [],
        ];

        if (!$allowTax) {
            $productIds = $normalizedInput['product_id'];
            $count = is_array($productIds) ? count($productIds) : 0;
            $normalizedInput['item_tax_rate'] = array_fill(0, $count, 0);
        }

        try {
            $items = $this->lineItemProcessor->parseFromPost($normalizedInput);
        } catch (InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'At least one valid line item is required.')) {
                throw new InvalidArgumentException('Add at least one product.');
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
}
