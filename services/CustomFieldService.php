<?php
/**
 * Minimal JSON custom-field parsing and rendering helpers.
 */
class CustomFieldService {
    /**
     * @return array<string, scalar|null>
     */
    public static function decode($raw): array {
        if (is_array($raw)) {
            $decoded = $raw;
        } elseif (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
        } else {
            $decoded = [];
        }

        if (!is_array($decoded)) {
            return [];
        }

        $result = [];
        foreach ($decoded as $key => $value) {
            $label = self::normalizeKey((string)$key);
            if ($label === '') {
                continue;
            }
            if (is_array($value) || is_object($value)) {
                continue;
            }
            $result[$label] = $value;
        }

        return $result;
    }

    /**
     * @return string|null
     */
    public static function encodeFromInput(string $json): ?string {
        $json = trim($json);
        if ($json === '') {
            return null;
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded) || array_keys($decoded) === range(0, count($decoded) - 1)) {
            throw new \RuntimeException('Custom fields must be a valid JSON object.');
        }

        $normalized = [];
        foreach ($decoded as $key => $value) {
            $label = self::normalizeKey((string)$key);
            if ($label === '') {
                continue;
            }
            if (is_array($value) || is_object($value)) {
                throw new \RuntimeException('Custom field values must be text, number, boolean, or null.');
            }
            $normalized[$label] = $value;
        }

        if (empty($normalized)) {
            return null;
        }

        ksort($normalized);
        return json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function pretty($raw): string {
        $decoded = self::decode($raw);
        if (empty($decoded)) {
            return '';
        }

        return (string)json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private static function normalizeKey(string $key): string {
        $key = trim($key);
        $key = preg_replace('/\s+/', ' ', $key) ?: '';
        return mb_substr($key, 0, 100);
    }
}
