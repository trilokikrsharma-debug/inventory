<?php
class CatalogLookupService {
    public function buildNamedPayload(array $input, bool $includeDescription = true): array {
        $payload = [
            'name' => $this->sanitize($input['name'] ?? null),
            'is_active' => !empty($input['is_active']) ? 1 : 0,
        ];

        if ($includeDescription) {
            $payload['description'] = $this->sanitize($input['description'] ?? null);
        }

        return $payload;
    }

    public function buildUnitPayload(array $input): array {
        return [
            'name' => $this->sanitize($input['name'] ?? null),
            'short_name' => $this->sanitize($input['short_name'] ?? null),
            'is_active' => !empty($input['is_active']) ? 1 : 0,
        ];
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
