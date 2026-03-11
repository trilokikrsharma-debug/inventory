<?php
/**
 * Line Item Processor Service
 * 
 * Shared service that handles line item parsing, validation, and calculation
 * for Sales, Purchases, and Quotations. Eliminates the ~30-line loop
 * that was duplicated across 6 controller methods.
 * 
 * Usage:
 *   $processor = new LineItemProcessor();
 *   $items  = $processor->parseFromPost($_POST);
 *   $totals = $processor->calculateTotals($items);
 */
class LineItemProcessor {
    /**
     * Parse POST arrays into validated, structured item arrays.
     * 
     * Expects POST data containing parallel arrays:
     *   product_id[], quantity[], unit_price[], item_discount[], item_tax_rate[]
     * 
     * @param  array $post       Raw POST data ($_POST or Request::all())
     * @param  string $prefix    Optional prefix for field names (e.g., 'item_')
     * @return array             Array of validated item structs
     * @throws \InvalidArgumentException on validation failure
     */
    public function parseFromPost(array $post, string $prefix = ''): array {
        $items = [];
        $productIds = $post[$prefix . 'product_id'] ?? [];
        $quantities = $post[$prefix . 'quantity'] ?? [];
        $unitPrices = $post[$prefix . 'unit_price'] ?? [];
        $discounts  = $post[$prefix . 'item_discount'] ?? [];
        $taxRates   = $post[$prefix . 'item_tax_rate'] ?? [];

        if (!is_array($productIds)) {
            return [];
        }

        for ($i = 0, $count = count($productIds); $i < $count; $i++) {
            if (empty($productIds[$i])) continue;

            $qty     = (float)($quantities[$i] ?? 0);
            $price   = (float)($unitPrices[$i] ?? 0);
            $disc    = (float)($discounts[$i] ?? 0);
            $taxRate = (float)($taxRates[$i] ?? 0);

            $this->validateItem($qty, $price, $disc, $taxRate, $i);

            $subtotal  = ($qty * $price) - $disc;
            $taxAmount = round($subtotal * ($taxRate / 100), 2);
            $total     = round($subtotal + $taxAmount, 2);

            $items[] = [
                'product_id'      => (int)$productIds[$i],
                'quantity'        => $qty,
                'unit_price'      => $price,
                'discount_amount' => $disc,
                'tax_rate'        => $taxRate,
                'tax_amount'      => $taxAmount,
                'subtotal'        => round($subtotal, 2),
                'total'           => $total,
            ];
        }

        if (empty($items)) {
            throw new \InvalidArgumentException('At least one valid line item is required.');
        }

        return $items;
    }

    /**
     * Calculate aggregate totals from an array of parsed items.
     * 
     * @param  array $items  Parsed items from parseFromPost()
     * @return array         ['subtotal' => float, 'total_tax' => float, 'grand_total' => float]
     */
    public function calculateTotals(array $items): array {
        $subtotal = 0.0;
        $totalTax = 0.0;

        foreach ($items as $item) {
            $subtotal += $item['subtotal'];
            $totalTax += $item['tax_amount'];
        }

        return [
            'subtotal'    => round($subtotal, 2),
            'total_tax'   => round($totalTax, 2),
            'grand_total' => round($subtotal + $totalTax, 2),
            'item_count'  => count($items),
        ];
    }

    /**
     * Reconcile items for an edit operation.
     * Determines which items to add, update, or remove.
     * 
     * @param  array $newItems      New items from form submission
     * @param  array $existingItems Current items from database
     * @param  string $idKey        Key for item ID in existing items
     * @return array                ['add' => [...], 'update' => [...], 'remove' => [...]]
     */
    public function reconcile(array $newItems, array $existingItems, string $idKey = 'id'): array {
        $existingById = [];
        foreach ($existingItems as $item) {
            $existingById[$item[$idKey]] = $item;
        }

        $add = [];
        $update = [];
        $processedIds = [];

        foreach ($newItems as $newItem) {
            if (isset($newItem[$idKey]) && isset($existingById[$newItem[$idKey]])) {
                $update[] = $newItem;
                $processedIds[] = $newItem[$idKey];
            } else {
                $add[] = $newItem;
            }
        }

        $remove = array_filter($existingItems, function ($item) use ($idKey, $processedIds) {
            return !in_array($item[$idKey], $processedIds);
        });

        return [
            'add'    => $add,
            'update' => $update,
            'remove' => array_values($remove),
        ];
    }

    /**
     * Validate a single item's values.
     * 
     * @throws \InvalidArgumentException
     */
    private function validateItem(float $qty, float $price, float $disc, float $taxRate, int $index): void {
        $errors = [];

        if ($qty <= 0) {
            $errors[] = "Item #{$index}: quantity must be greater than zero";
        }
        if ($price < 0) {
            $errors[] = "Item #{$index}: unit price cannot be negative";
        }
        if ($disc < 0) {
            $errors[] = "Item #{$index}: discount cannot be negative";
        }
        if ($taxRate < 0 || $taxRate > 100) {
            $errors[] = "Item #{$index}: tax rate must be between 0 and 100";
        }
        if ($disc > ($qty * $price)) {
            $errors[] = "Item #{$index}: discount cannot exceed subtotal";
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode('; ', $errors));
        }
    }
}
