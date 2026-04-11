<?php
/**
 * Report export orchestration helpers.
 *
 * Keeps export cache keying, status persistence, and secure download path
 * resolution out of the controller layer.
 */
class ReportExportService {
    public function resultCacheKey(int $companyId, int $jobId): string {
        return 'c' . $companyId . '_report_export_' . $jobId;
    }

    public function tokenCacheKey(int $companyId, string $token): string {
        return 'c' . $companyId . '_report_export_token_' . $token;
    }

    public function markQueued(int $companyId, int $jobId, int $ttl): void {
        Cache::set($this->resultCacheKey($companyId, $jobId), ['status' => 'queued'], $ttl);
    }

    public function fetchJobResult(int $companyId, int $jobId) {
        return Cache::get($this->resultCacheKey($companyId, $jobId));
    }

    public function buildDownloadUrl(string $token): string {
        return APP_URL . '/index.php?page=reports&action=download_export&token=' . urlencode($token);
    }

    public function resolveDownloadPayload(int $companyId, string $token): ?array {
        $tokenPayload = Cache::get($this->tokenCacheKey($companyId, $token));
        if (!is_array($tokenPayload) || empty($tokenPayload['path'])) {
            return null;
        }

        $allowedRoot = realpath(UPLOAD_PATH . '/exports/company_' . $companyId);
        $filePath = realpath((string)$tokenPayload['path']);
        if (
            !$allowedRoot ||
            !$filePath ||
            !is_file($filePath) ||
            !str_starts_with($filePath, $allowedRoot . DIRECTORY_SEPARATOR)
        ) {
            return null;
        }

        return [
            'path' => $filePath,
            'name' => basename((string)($tokenPayload['name'] ?? basename($filePath))),
        ];
    }
}
