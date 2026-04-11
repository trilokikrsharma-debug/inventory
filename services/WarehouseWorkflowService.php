<?php
class WarehouseWorkflowService {
    private WarehouseModel $warehouseModel;

    public function __construct(?WarehouseModel $warehouseModel = null) {
        $this->warehouseModel = $warehouseModel ?: new WarehouseModel();
    }

    public function validateWarehousePayload(array $input): array {
        $name = trim((string)($input['name'] ?? ''));
        $code = strtoupper(trim((string)($input['code'] ?? '')));
        $location = trim((string)($input['location'] ?? ''));
        $description = trim((string)($input['description'] ?? ''));

        if ($name === '') {
            throw new RuntimeException('Warehouse name is required.');
        }
        if ($code !== '' && !preg_match('/^[A-Z0-9_-]{2,40}$/', $code)) {
            throw new RuntimeException('Warehouse code must be 2 to 40 characters using letters, numbers, dash or underscore.');
        }

        return [
            'name' => $this->sanitize($name),
            'code' => $code !== '' ? $this->sanitize($code) : null,
            'location' => $location !== '' ? $this->sanitize($location) : null,
            'description' => $description !== '' ? $this->sanitize($description) : null,
            'is_default' => !empty($input['is_default']) ? 1 : 0,
            'is_active' => array_key_exists('is_active', $input) ? (!empty($input['is_active']) ? 1 : 0) : 1,
        ];
    }

    public function createTransferRequest(array $input, int $userId): array {
        $activeWarehouses = $this->warehouseModel->allActiveOrdered();
        if (count($activeWarehouses) < 2) {
            throw new RuntimeException('At least two active warehouses are required for a transfer.');
        }

        $warehouseMap = [];
        foreach ($activeWarehouses as $warehouse) {
            $warehouseMap[(int)$warehouse['id']] = $warehouse;
        }

        $sourceWarehouseId = (int)($input['source_warehouse_id'] ?? 0);
        $destinationWarehouseId = (int)($input['destination_warehouse_id'] ?? 0);
        if (!isset($warehouseMap[$sourceWarehouseId]) || !isset($warehouseMap[$destinationWarehouseId])) {
            throw new RuntimeException('Please select valid warehouses.');
        }
        if ($sourceWarehouseId === $destinationWarehouseId) {
            throw new RuntimeException('Source and destination warehouses must be different.');
        }

        $transferDate = trim((string)($input['transfer_date'] ?? date('Y-m-d')));
        if (!$this->isValidDateYmd($transferDate)) {
            throw new RuntimeException('Invalid transfer date. Use YYYY-MM-DD.');
        }

        $items = WarehouseStockService::normalizeTransferItems(
            (array)($input['product_id'] ?? []),
            (array)($input['quantity'] ?? [])
        );
        if (empty($items)) {
            throw new RuntimeException('Add at least one transfer line.');
        }

        return $this->warehouseModel->createTransfer([
            'source_warehouse_id' => $sourceWarehouseId,
            'destination_warehouse_id' => $destinationWarehouseId,
            'transfer_date' => $transferDate,
            'reference_number' => $this->sanitize((string)($input['reference_number'] ?? '')) ?: null,
            'note' => $this->sanitize((string)($input['note'] ?? '')) ?: null,
        ], $items, $userId);
    }

    public function approveTransfer(int $transferId, int $userId): array {
        return $this->warehouseModel->approveTransfer($transferId, $userId);
    }

    public function rejectTransfer(int $transferId, int $userId, string $reason): array {
        return $this->warehouseModel->rejectTransfer($transferId, $userId, $this->sanitize($reason));
    }

    private function isValidDateYmd(string $value): bool {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
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
