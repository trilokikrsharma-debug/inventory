<?php
class SupplierWorkflowService {
    private SupplierModel $supplierModel;

    public function __construct(?SupplierModel $supplierModel = null) {
        $this->supplierModel = $supplierModel ?: new SupplierModel();
    }

    public function buildPayload(array $input, bool $includeOpeningBalance = true): array {
        $openingBalance = round((float)($input['opening_balance'] ?? 0), 2);
        $payload = [
            'name' => $this->sanitize($input['name'] ?? null),
            'email' => !empty($input['email']) ? strtolower($this->sanitize((string)$input['email'])) : null,
            'phone' => !empty($input['phone']) ? $this->sanitize((string)$input['phone']) : null,
            'address' => $this->sanitize($input['address'] ?? null),
            'city' => $this->sanitize($input['city'] ?? null),
            'state' => $this->sanitize($input['state'] ?? null),
            'zip' => $this->sanitize($input['zip'] ?? null),
            'tax_number' => !empty($input['tax_number']) ? strtoupper($this->sanitize((string)$input['tax_number'])) : '',
        ];

        if ($includeOpeningBalance) {
            $payload['opening_balance'] = $openingBalance;
            $payload['current_balance'] = array_key_exists('current_balance', $input)
                ? round((float)$input['current_balance'], 2)
                : $openingBalance;
        }

        return $payload;
    }

    public function persistImportedContacts(array $rows): int {
        $count = 0;
        foreach ($rows as $row) {
            $normalized = (array)($row['normalized'] ?? []);
            $this->supplierModel->create($this->buildPayload($normalized, true));
            $count++;
        }

        return $count;
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
