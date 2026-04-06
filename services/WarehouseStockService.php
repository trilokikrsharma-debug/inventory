<?php

class WarehouseStockService {
    /**
     * @param array<string|int, mixed> $input
     * @param array<int, int> $allowedWarehouseIds
     * @return array<int, array{warehouse_id:int, quantity:float}>
     */
    public static function normalizeAllocations(array $input, array $allowedWarehouseIds): array {
        $allowedMap = array_fill_keys(array_map('intval', $allowedWarehouseIds), true);
        $normalized = [];

        foreach ($input as $warehouseId => $quantity) {
            $id = (int)$warehouseId;
            if ($id <= 0 || !isset($allowedMap[$id])) {
                continue;
            }

            if ($quantity === '' || $quantity === null) {
                continue;
            }

            if (!is_numeric($quantity)) {
                throw new \RuntimeException('Warehouse stock quantities must be numeric.');
            }

            $qty = round((float)$quantity, 3);
            if ($qty < 0) {
                throw new \RuntimeException('Warehouse stock quantities cannot be negative.');
            }

            if ($qty == 0.0) {
                continue;
            }

            $normalized[] = [
                'warehouse_id' => $id,
                'quantity' => $qty,
            ];
        }

        usort($normalized, static function (array $a, array $b): int {
            return $a['warehouse_id'] <=> $b['warehouse_id'];
        });

        return $normalized;
    }

    /**
     * @param array<int, array{warehouse_id:int, quantity:float}> $allocations
     */
    public static function totalQuantity(array $allocations): float {
        $total = 0.0;
        foreach ($allocations as $allocation) {
            $total += (float)($allocation['quantity'] ?? 0);
        }
        return round($total, 3);
    }

    /**
     * @param array<int|string, mixed> $productIds
     * @param array<int|string, mixed> $quantities
     * @return array<int, array{product_id:int, quantity:float}>
     */
    public static function normalizeTransferItems(array $productIds, array $quantities): array {
        $items = [];
        $count = max(count($productIds), count($quantities));

        for ($i = 0; $i < $count; $i++) {
            $productId = (int)($productIds[$i] ?? 0);
            $rawQuantity = $quantities[$i] ?? null;

            if ($productId <= 0 && ($rawQuantity === '' || $rawQuantity === null)) {
                continue;
            }

            if ($productId <= 0) {
                throw new \RuntimeException('Each transfer row must have a valid product.');
            }

            if (!is_numeric($rawQuantity)) {
                throw new \RuntimeException('Transfer quantities must be numeric.');
            }

            $quantity = round((float)$rawQuantity, 3);
            if ($quantity <= 0) {
                throw new \RuntimeException('Transfer quantities must be greater than zero.');
            }

            if (!isset($items[$productId])) {
                $items[$productId] = [
                    'product_id' => $productId,
                    'quantity' => 0.0,
                ];
            }

            $items[$productId]['quantity'] = round($items[$productId]['quantity'] + $quantity, 3);
        }

        return array_values($items);
    }
}
