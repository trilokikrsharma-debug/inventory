<?php
class SaleReturnWorkflowService {
    public function buildCreatePayload(array $input, array $sale, float $remainingAmount, string $returnNumber): array {
        if ($remainingAmount <= 0.009) {
            throw new InvalidArgumentException('This invoice has already been fully returned.');
        }

        $items = $this->normalizeItems($input, $sale);
        $totalAmount = 0.0;
        foreach ($items as $item) {
            $totalAmount += (float)$item['total'];
        }

        if ($totalAmount > ($remainingAmount + 0.001)) {
            throw new InvalidArgumentException(
                'Return amount (' . number_format($totalAmount, 2) . ') exceeds the remaining returnable amount (' . number_format($remainingAmount, 2) . ').'
            );
        }

        $returnDate = trim((string)($input['return_date'] ?? date('Y-m-d')));
        if (!$this->isValidDateYmd($returnDate)) {
            throw new InvalidArgumentException('Invalid return date format. Use YYYY-MM-DD.');
        }

        return [
            'items' => $items,
            'return' => [
                'return_number' => $returnNumber,
                'sale_id' => (int)($sale['id'] ?? 0),
                'total_amount' => round($totalAmount, 2),
                'return_date' => $returnDate,
                'note' => $this->sanitize($input['reason'] ?? null),
            ],
        ];
    }

    public function ensureSaleIsReturnable(?array $sale, float $remainingAmount): void {
        if (!$sale) {
            throw new InvalidArgumentException('Invalid sale.');
        }

        if ($remainingAmount <= 0.009) {
            throw new InvalidArgumentException('This invoice has already been fully returned.');
        }
    }

    private function normalizeItems(array $input, array $sale): array {
        $productIds = $input['product_id'] ?? [];
        $quantities = $input['quantity'] ?? [];
        $saleItemMap = $this->buildSaleItemMap($sale['items'] ?? []);

        if (!is_array($productIds)) {
            $productIds = [];
        }

        $items = [];
        foreach ($productIds as $i => $pid) {
            $productId = (int)$pid;
            $qty = (float)($quantities[$i] ?? 0);

            if ($qty < 0) {
                throw new InvalidArgumentException('Invalid quantities or prices provided. Values must be positive.');
            }

            if ($qty <= 0 || !$productId) {
                continue;
            }

            if (empty($saleItemMap[$productId])) {
                throw new InvalidArgumentException('One or more selected products are not part of the original sale.');
            }

            $original = $saleItemMap[$productId];
            $soldQty = (float)$original['quantity'];
            if ($soldQty <= 0 || $qty > ($soldQty + 0.001)) {
                throw new InvalidArgumentException('Return quantity exceeds original sale quantity.');
            }

            $ratio = $qty / $soldQty;
            $subtotal = round((float)$original['subtotal'] * $ratio, 2);
            $taxAmount = round((float)$original['tax_amount'] * $ratio, 2);
            $total = round((float)$original['total'] * $ratio, 2);

            $items[] = [
                'product_id' => $productId,
                'quantity' => $qty,
                'unit_price' => round((float)$original['unit_price'], 2),
                'subtotal' => $subtotal,
                'tax_rate' => round((float)$original['tax_rate'], 2),
                'tax_amount' => $taxAmount,
                'total' => $total,
            ];
        }

        if (empty($items)) {
            throw new InvalidArgumentException('Please add at least one item to return.');
        }

        return $items;
    }

    private function buildSaleItemMap(array $saleItems): array {
        $map = [];
        foreach ($saleItems as $item) {
            $productId = (int)($item['product_id'] ?? 0);
            if ($productId <= 0) {
                continue;
            }

            if (!isset($map[$productId])) {
                $map[$productId] = [
                    'quantity' => 0.0,
                    'unit_price' => (float)($item['unit_price'] ?? 0),
                    'subtotal' => 0.0,
                    'tax_rate' => (float)($item['tax_rate'] ?? 0),
                    'tax_amount' => 0.0,
                    'total' => 0.0,
                ];
            }

            $map[$productId]['quantity'] += (float)($item['quantity'] ?? 0);
            $map[$productId]['subtotal'] += (float)($item['subtotal'] ?? 0);
            $map[$productId]['tax_amount'] += (float)($item['tax_amount'] ?? 0);
            $map[$productId]['total'] += (float)($item['total'] ?? 0);
        }

        return $map;
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
