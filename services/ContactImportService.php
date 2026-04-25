<?php
/**
 * Shared CSV import analysis for customers and suppliers.
 */
class ContactImportService {
    private const MAX_ROWS = 1000;

    /**
     * @return array<int, string>
     */
    public function templateHeaders(): array {
        return [
            'name',
            'email',
            'phone',
            'address',
            'city',
            'state',
            'zip',
            'tax_number',
            'opening_balance',
            'is_active',
        ];
    }

    public function templateCsv(string $entity): string {
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            throw new \RuntimeException('Unable to generate CSV template.');
        }

        fputcsv($stream, $this->templateHeaders());
        fputcsv($stream, $entity === 'supplier'
            ? ['Acme Supplies', 'procurement@acme.test', '+1 555 0100', 'Warehouse Road', 'Dallas', 'Texas', '75001', 'GST-ACME-001', '2500', '1']
            : ['Retail Customer', 'buyer@example.com', '+1 555 0101', 'Market Street', 'Austin', 'Texas', '73301', 'GST-CUST-001', '0', '1']
        );

        rewind($stream);
        $csv = (string)stream_get_contents($stream);
        fclose($stream);
        return $csv;
    }

    /**
     * @return array<string, mixed>
     */
    public function analyzeUploadedFile(string $entity, array $file, array $context): array {
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

        return $this->analyzeCsvString($entity, $contents, $context);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function analyzeCsvString(string $entity, string $csv, array $context): array {
        $entity = $this->normalizeEntity($entity);
        $rows = $this->parseCsv($csv);
        if (count($rows) < 2) {
            throw new \RuntimeException('The CSV file is empty.');
        }

        $headers = array_map([$this, 'normalizeHeader'], array_shift($rows));
        if (!in_array('name', $headers, true)) {
            throw new \RuntimeException('Missing required CSV column: name');
        }

        $analysisRows = [];
        $validRows = [];
        $invalidRows = [];
        $seenEmails = [];
        $seenPhones = [];

        foreach ($rows as $index => $row) {
            if ($this->isEmptyCsvRow($row)) {
                continue;
            }

            $rowNumber = $index + 2;
            $mapped = $this->mapRow($headers, $row);
            $normalized = $this->normalizeRow($entity, $mapped, $context);

            $emailKey = strtolower((string)$normalized['email_key']);
            if ($emailKey !== '') {
                if (isset($seenEmails[$emailKey])) {
                    $normalized['errors'][] = 'Duplicate email in file.';
                } else {
                    $seenEmails[$emailKey] = true;
                }
            }

            $phoneKey = strtolower((string)$normalized['phone_key']);
            if ($phoneKey !== '') {
                if (isset($seenPhones[$phoneKey])) {
                    $normalized['errors'][] = 'Duplicate phone in file.';
                } else {
                    $seenPhones[$phoneKey] = true;
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
    public function buildContext(string $entity): array {
        $table = $this->normalizeEntity($entity) === 'supplier' ? 'suppliers' : 'customers';
        $tenantId = Tenant::id();
        $params = [];
        $sql = "SELECT email, phone FROM {$table} WHERE deleted_at IS NULL";
        if ($tenantId !== null) {
            $sql .= " AND company_id = ?";
            $params[] = $tenantId;
        }

        $rows = Database::getInstance()->query($sql, $params)->fetchAll();

        $emails = [];
        $phones = [];
        foreach ($rows as $row) {
            $email = strtolower(trim((string)($row['email'] ?? '')));
            $phone = strtolower(trim((string)($row['phone'] ?? '')));
            if ($email !== '') {
                $emails[$email] = true;
            }
            if ($phone !== '') {
                $phones[$phone] = true;
            }
        }

        return [
            'existing_emails' => $emails,
            'existing_phones' => $phones,
        ];
    }

    private function normalizeEntity(string $entity): string {
        return strtolower(trim($entity)) === 'supplier' ? 'supplier' : 'customer';
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
     * @return array{data: array<string, mixed>, errors: array<int, string>, email_key: string, phone_key: string}
     */
    private function normalizeRow(string $entity, array $row, array $context): array {
        $errors = [];
        $name = trim((string)($row['name'] ?? ''));
        if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $errors[] = 'Name must be between 2 and 100 characters.';
        }

        $email = strtolower(trim((string)($row['email'] ?? '')));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email must be valid.';
        }
        if ($email !== '' && !empty($context['existing_emails'][$email])) {
            $errors[] = 'Email already exists.';
        }

        $phone = trim((string)($row['phone'] ?? ''));
        if ($phone !== '' && !preg_match('/^[0-9+()\-\s]{7,20}$/', $phone)) {
            $errors[] = 'Phone must be 7 to 20 valid characters.';
        }
        if ($phone !== '' && !empty($context['existing_phones'][strtolower($phone)])) {
            $errors[] = 'Phone already exists.';
        }

        $zip = trim((string)($row['zip'] ?? ''));
        if ($zip !== '' && !preg_match('/^[A-Za-z0-9\-\s]{2,20}$/', $zip)) {
            $errors[] = 'ZIP must be 2 to 20 valid characters.';
        }

        $taxNumber = strtoupper(trim((string)($row['tax_number'] ?? '')));
        if ($entity === 'customer' && $taxNumber !== '' && !preg_match('/^[A-Za-z0-9\/-]{6,20}$/', $taxNumber)) {
            $errors[] = 'Tax number must be 6 to 20 valid characters.';
        }
        if ($entity === 'supplier' && $taxNumber !== '' && mb_strlen($taxNumber) > 50) {
            $errors[] = 'Tax number cannot exceed 50 characters.';
        }

        $openingBalanceRaw = trim((string)($row['opening_balance'] ?? '0'));
        if ($openingBalanceRaw === '') {
            $openingBalanceRaw = '0';
        }
        if (!is_numeric($openingBalanceRaw)) {
            $errors[] = 'Opening balance must be numeric.';
            $openingBalance = 0.0;
        } else {
            $openingBalance = (float)$openingBalanceRaw;
            if ($entity === 'supplier' && $openingBalance < 0) {
                $errors[] = 'Supplier opening balance cannot be negative.';
            }
        }

        return [
            'data' => [
                'name' => $name,
                'email' => $email !== '' ? $email : null,
                'phone' => $phone !== '' ? $phone : null,
                'address' => trim((string)($row['address'] ?? '')),
                'city' => trim((string)($row['city'] ?? '')),
                'state' => trim((string)($row['state'] ?? '')),
                'zip' => $zip,
                'tax_number' => $taxNumber,
                'opening_balance' => round($openingBalance, 2),
                'current_balance' => round($openingBalance, 2),
                'is_active' => $this->normalizeBoolean((string)($row['is_active'] ?? '1')),
            ],
            'errors' => $errors,
            'email_key' => $email,
            'phone_key' => strtolower($phone),
        ];
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
}
