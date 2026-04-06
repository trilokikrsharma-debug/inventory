<?php
/**
 * Product CSV import analysis and template generation.
 */
class ProductImportService {
    private const MAX_ROWS = 1000;

    /**
     * @return array<int, string>
     */
    public function templateHeaders(): array {
        return [
            'name',
            'sku',
            'barcode',
            'hsn_code',
            'category',
            'brand',
            'unit',
            'purchase_price',
            'selling_price',
            'mrp',
            'tax_rate',
            'opening_stock',
            'low_stock_alert',
            'description',
            'is_active',
        ];
    }

    public function templateCsv(): string {
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            throw new \RuntimeException('Unable to generate CSV template.');
        }

        fputcsv($stream, $this->templateHeaders());
        fputcsv($stream, [
            'Sample Product',
            'SKU-001',
            '8901234567890',
            '3401',
            'General',
            'Default',
            'pcs',
            '120.00',
            '150.00',
            '160.00',
            '18',
            '25',
            '5',
            'Starter sample product',
            '1',
        ]);

        rewind($stream);
        $csv = (string)stream_get_contents($stream);
        fclose($stream);
        return $csv;
    }

    /**
     * @return array<string, mixed>
     */
    public function analyzeUploadedFile(array $file, array $context): array {
        if (empty($file['tmp_name']) || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Please choose a CSV file to import.');
        }

        $name = strtolower((string)($file['name'] ?? ''));
        if ($name !== '' && !str_ends_with($name, '.csv')) {
            throw new \RuntimeException('Only CSV files are supported.');
        }

        $contents = @file_get_contents((string)$file['tmp_name']);
        if ($contents === false) {
            throw new \RuntimeException('Unable to read the uploaded file.');
        }

        return $this->analyzeCsvString($contents, $context);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function analyzeCsvString(string $csv, array $context): array {
        $rows = $this->parseCsv($csv);
        if (count($rows) < 2) {
            throw new \RuntimeException('The CSV file is empty.');
        }

        $headerRow = array_shift($rows);
        $headers = array_map([$this, 'normalizeHeader'], $headerRow);
        if (empty(array_filter($headers, static fn ($value) => $value !== ''))) {
            throw new \RuntimeException('The CSV header row is empty.');
        }

        $requiredHeaders = ['name', 'purchase_price', 'selling_price'];
        foreach ($requiredHeaders as $requiredHeader) {
            if (!in_array($requiredHeader, $headers, true)) {
                throw new \RuntimeException('Missing required CSV column: ' . $requiredHeader);
            }
        }

        $analysisRows = [];
        $validRows = [];
        $invalidRows = [];
        $seenSkus = [];

        foreach ($rows as $index => $row) {
            if ($this->isEmptyCsvRow($row)) {
                continue;
            }

            $rowNumber = $index + 2;
            $mapped = $this->mapRow($headers, $row);
            $normalized = $this->normalizeRow($mapped, $context);
            $skuKey = $normalized['sku_key'] ?? '';

            if ($skuKey !== '') {
                if (isset($seenSkus[$skuKey])) {
                    $normalized['errors'][] = 'Duplicate SKU in file.';
                } else {
                    $seenSkus[$skuKey] = true;
                }
            }

            $entry = [
                'row_number' => $rowNumber,
                'source' => $mapped,
                'normalized' => $normalized['data'],
                'errors' => $normalized['errors'],
                'valid' => empty($normalized['errors']),
            ];

            $analysisRows[] = $entry;
            if ($entry['valid']) {
                $validRows[] = $entry;
            } else {
                $invalidRows[] = $entry;
            }
        }

        return [
            'headers' => $headers,
            'rows' => $analysisRows,
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidRows,
            'summary' => [
                'total_rows' => count($analysisRows),
                'valid_rows' => count($validRows),
                'invalid_rows' => count($invalidRows),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildContext(): array {
        return [
            'categories_by_key' => $this->buildEntityLookup('categories'),
            'brands_by_key' => $this->buildEntityLookup('brands'),
            'units_by_key' => $this->buildUnitLookup(),
            'existing_skus' => $this->existingSkuLookup(),
        ];
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function parseCsv(string $csv): array {
        $csv = preg_replace('/^\xEF\xBB\xBF/', '', $csv) ?? $csv;

        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            throw new \RuntimeException('Unable to read CSV.');
        }

        fwrite($stream, $csv);
        rewind($stream);

        $rows = [];
        while (($row = fgetcsv($stream)) !== false) {
            if ($row === [null]) {
                continue;
            }
            $rows[] = array_map(static fn ($value) => trim((string)$value), $row);
            if (count($rows) > self::MAX_ROWS + 1) {
                fclose($stream);
                throw new \RuntimeException('CSV import is limited to 1000 data rows per upload.');
            }
        }

        fclose($stream);
        return $rows;
    }

    /**
     * @param array<int, string> $headers
     * @param array<int, string> $row
     * @return array<string, string>
     */
    private function mapRow(array $headers, array $row): array {
        $mapped = [];
        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }
            $mapped[$header] = trim((string)($row[$index] ?? ''));
        }
        return $mapped;
    }

    /**
     * @param array<string, string> $row
     * @param array<string, mixed> $context
     * @return array{data: array<string, mixed>, errors: array<int, string>, sku_key: string}
     */
    private function normalizeRow(array $row, array $context): array {
        $errors = [];
        $data = [];

        $name = trim((string)($row['name'] ?? ''));
        if ($name === '') {
            $errors[] = 'Product name is required.';
        } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 200) {
            $errors[] = 'Product name must be between 2 and 200 characters.';
        }
        $data['name'] = $name;

        $sku = trim((string)($row['sku'] ?? ''));
        $skuKey = strtolower($sku);
        if ($sku !== '' && mb_strlen($sku) > 100) {
            $errors[] = 'SKU cannot exceed 100 characters.';
        }
        if ($skuKey !== '' && !empty($context['existing_skus'][$skuKey])) {
            $errors[] = 'SKU already exists.';
        }
        $data['sku'] = $sku !== '' ? $sku : null;

        $barcode = trim((string)($row['barcode'] ?? ''));
        if ($barcode !== '' && mb_strlen($barcode) > 100) {
            $errors[] = 'Barcode cannot exceed 100 characters.';
        }
        $data['barcode'] = $barcode !== '' ? $barcode : null;

        $hsnCode = strtoupper(trim((string)($row['hsn_code'] ?? '')));
        if ($hsnCode !== '' && !preg_match('/^[A-Za-z0-9\\.\\/-]{4,20}$/', $hsnCode)) {
            $errors[] = 'HSN code must be 4 to 20 valid characters.';
        }
        $data['hsn_code'] = $hsnCode !== '' ? $hsnCode : null;

        $data['category_id'] = $this->resolveLookupValue(
            trim((string)($row['category'] ?? '')),
            $context['categories_by_key'] ?? [],
            'Category',
            $errors
        );
        $data['brand_id'] = $this->resolveLookupValue(
            trim((string)($row['brand'] ?? '')),
            $context['brands_by_key'] ?? [],
            'Brand',
            $errors
        );
        $data['unit_id'] = $this->resolveLookupValue(
            trim((string)($row['unit'] ?? '')),
            $context['units_by_key'] ?? [],
            'Unit',
            $errors
        );

        $data['purchase_price'] = $this->normalizeNumeric($row['purchase_price'] ?? '', 'Purchase price', $errors, false, 0);
        $data['selling_price'] = $this->normalizeNumeric($row['selling_price'] ?? '', 'Selling price', $errors, false, 0);
        $data['mrp'] = $this->normalizeNumeric($row['mrp'] ?? '', 'MRP', $errors, true, 0);
        $data['tax_rate'] = $this->normalizeNumeric($row['tax_rate'] ?? '', 'Tax rate', $errors, true, 0, 100);
        $data['opening_stock'] = $this->normalizeNumeric($row['opening_stock'] ?? '', 'Opening stock', $errors, true, 0);

        $lowStockAlert = trim((string)($row['low_stock_alert'] ?? ''));
        if ($lowStockAlert === '') {
            $data['low_stock_alert'] = null;
        } elseif (filter_var($lowStockAlert, FILTER_VALIDATE_INT) === false || (int)$lowStockAlert < 0) {
            $errors[] = 'Low stock alert must be a non-negative whole number.';
            $data['low_stock_alert'] = null;
        } else {
            $data['low_stock_alert'] = (int)$lowStockAlert;
        }

        $description = trim((string)($row['description'] ?? ''));
        $data['description'] = $description !== '' ? $description : null;
        $data['is_active'] = $this->normalizeBoolean($row['is_active'] ?? '1');
        $data['current_stock'] = (float)($data['opening_stock'] ?? 0);

        return [
            'data' => $data,
            'errors' => $errors,
            'sku_key' => $skuKey,
        ];
    }

    /**
     * @param array<string, int> $lookup
     * @param array<int, string> $errors
     */
    private function resolveLookupValue(string $raw, array $lookup, string $label, array &$errors): ?int {
        if ($raw === '') {
            return null;
        }

        $key = strtolower(trim($raw));
        if (isset($lookup[$key])) {
            return (int)$lookup[$key];
        }

        $errors[] = $label . ' "' . $raw . '" was not found.';
        return null;
    }

    /**
     * @param array<int, string> $errors
     */
    private function normalizeNumeric(
        string $raw,
        string $label,
        array &$errors,
        bool $nullable,
        float $min,
        ?float $max = null
    ): ?float {
        $raw = trim($raw);
        if ($raw === '') {
            if ($nullable) {
                return null;
            }
            $errors[] = $label . ' is required.';
            return 0.0;
        }

        if (!is_numeric($raw)) {
            $errors[] = $label . ' must be numeric.';
            return null;
        }

        $value = (float)$raw;
        if ($value < $min) {
            $errors[] = $label . ' cannot be negative.';
        }
        if ($max !== null && $value > $max) {
            $errors[] = $label . ' cannot exceed ' . rtrim(rtrim(number_format($max, 2, '.', ''), '0'), '.');
        }

        return (float)number_format($value, 2, '.', '');
    }

    private function normalizeBoolean(string $value): int {
        $value = strtolower(trim($value));
        if ($value === '' || in_array($value, ['1', 'true', 'yes', 'y', 'active'], true)) {
            return 1;
        }
        if (in_array($value, ['0', 'false', 'no', 'n', 'inactive'], true)) {
            return 0;
        }
        return 1;
    }

    private function normalizeHeader(string $value): string {
        $value = strtolower(trim($value));
        $value = str_replace([' ', '-'], '_', $value);
        return preg_replace('/[^a-z0-9_]/', '', $value) ?: '';
    }

    /**
     * @param array<int, string> $row
     */
    private function isEmptyCsvRow(array $row): bool {
        foreach ($row as $value) {
            if (trim((string)$value) !== '') {
                return false;
            }
        }
        return true;
    }

    /**
     * @return array<string, int>
     */
    private function buildEntityLookup(string $table): array {
        $rows = Database::getInstance()->query(
            "SELECT id, name FROM {$table} WHERE deleted_at IS NULL ORDER BY name ASC"
        )->fetchAll();

        $lookup = [];
        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            $name = strtolower(trim((string)($row['name'] ?? '')));
            if ($id <= 0 || $name === '') {
                continue;
            }
            $lookup[(string)$id] = $id;
            $lookup[$name] = $id;
        }
        return $lookup;
    }

    /**
     * @return array<string, int>
     */
    private function buildUnitLookup(): array {
        $rows = Database::getInstance()->query(
            "SELECT id, name, short_name FROM units WHERE deleted_at IS NULL ORDER BY name ASC"
        )->fetchAll();

        $lookup = [];
        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $lookup[(string)$id] = $id;
            foreach (['name', 'short_name'] as $key) {
                $value = strtolower(trim((string)($row[$key] ?? '')));
                if ($value !== '') {
                    $lookup[$value] = $id;
                }
            }
        }
        return $lookup;
    }

    /**
     * @return array<string, bool>
     */
    private function existingSkuLookup(): array {
        $rows = Database::getInstance()->query(
            "SELECT sku FROM products WHERE deleted_at IS NULL AND sku IS NOT NULL AND sku <> ''"
        )->fetchAll();

        $lookup = [];
        foreach ($rows as $row) {
            $sku = strtolower(trim((string)($row['sku'] ?? '')));
            if ($sku !== '') {
                $lookup[$sku] = true;
            }
        }
        return $lookup;
    }
}
