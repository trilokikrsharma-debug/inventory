<?php
class ProductWorkflowService {
    private ProductModel $productModel;
    private $db;

    public function __construct(?ProductModel $productModel = null, $db = null) {
        $this->productModel = $productModel ?: new ProductModel();
        $this->db = $db ?: Database::getInstance();
    }

    public function buildPayload(array $input, bool $allowCustomFields = false): array {
        $payload = [
            'name' => $this->sanitize($input['name'] ?? null),
            'sku' => $this->sanitize($input['sku'] ?? null) ?: null,
            'barcode' => $this->sanitize($input['barcode'] ?? null) ?: null,
            'category_id' => !empty($input['category_id']) ? $input['category_id'] : null,
            'brand_id' => !empty($input['brand_id']) ? $input['brand_id'] : null,
            'unit_id' => !empty($input['unit_id']) ? $input['unit_id'] : null,
            'purchase_price' => (float)($input['purchase_price'] ?? 0),
            'selling_price' => (float)($input['selling_price'] ?? 0),
            'mrp' => ($input['mrp'] ?? '') !== '' && ($input['mrp'] ?? null) !== null ? (float)$input['mrp'] : null,
            'tax_rate' => ($input['tax_rate'] ?? '') !== '' && ($input['tax_rate'] ?? null) !== null ? (float)$input['tax_rate'] : null,
            'opening_stock' => (float)($input['opening_stock'] ?? 0),
            'current_stock' => array_key_exists('current_stock', $input)
                ? (float)($input['current_stock'] ?? 0)
                : (float)($input['opening_stock'] ?? 0),
            'low_stock_alert' => ($input['low_stock_alert'] ?? '') !== '' && ($input['low_stock_alert'] ?? null) !== null
                ? (int)$input['low_stock_alert']
                : null,
            'description' => $this->sanitize($input['description'] ?? null),
            'is_active' => (int)($input['is_active'] ?? 1),
            'hsn_code' => $this->normalizeHsnCode((string)($input['hsn_code'] ?? '')),
        ];

        if ($allowCustomFields) {
            $payload['custom_fields'] = CustomFieldService::encodeFromInput((string)($input['custom_fields_json'] ?? ''));
        }

        return $payload;
    }

    public function persistImportedProducts(array $rows, int $userId, bool $warehouseFeatureEnabled): int {
        $created = 0;
        $this->db->beginTransaction();
        try {
            foreach ($rows as $row) {
                $normalized = (array)($row['normalized'] ?? []);
                $payload = $this->buildPayload($normalized, false);
                $productId = (int)$this->productModel->create($payload);
                $created++;

                if (($payload['opening_stock'] ?? 0) > 0) {
                    $this->db->query(
                        "INSERT INTO stock_history (company_id, product_id, type, quantity, stock_before, stock_after, note, created_by) VALUES (?, ?, 'opening', ?, 0, ?, 'Opening stock entry (bulk import)', ?)",
                        [Tenant::id() ?? 1, $productId, $payload['opening_stock'], $payload['opening_stock'], $userId > 0 ? $userId : null]
                    );

                    if ($warehouseFeatureEnabled) {
                        $this->productModel->allocateOpeningStock($productId, null, (float)$payload['opening_stock']);
                    }
                }
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        return $created;
    }

    private function normalizeHsnCode(string $value): ?string {
        $value = strtoupper(trim($value));
        return $value !== '' ? $this->sanitize($value) : null;
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
