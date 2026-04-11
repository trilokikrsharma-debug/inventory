<?php
/**
 * Unit Tests - ReportExportService
 */

require_once __DIR__ . '/../BaseTestCase.php';

if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', sys_get_temp_dir() . '/report-export-service-uploads');
}

require_once dirname(__DIR__, 2) . '/services/ReportExportService.php';

class ReportExportServiceTest extends BaseTestCase {
    private string $companyDir;

    protected function setUp(): void {
        parent::setUp();
        $this->companyDir = UPLOAD_PATH . '/exports/company_44';
        @mkdir($this->companyDir, 0775, true);
    }

    protected function tearDown(): void {
        $this->removeTree(UPLOAD_PATH);
        parent::tearDown();
    }

    public function testBuildsStableCacheKeys(): void {
        $service = new ReportExportService();

        $this->assertSame('c44_report_export_19', $service->resultCacheKey(44, 19));
        $this->assertSame('c44_report_export_token_abc', $service->tokenCacheKey(44, 'abc'));
    }

    public function testResolveDownloadPayloadRejectsMissingCachePayload(): void {
        $service = new ReportExportService();
        $this->assertNull($service->resolveDownloadPayload(44, 'missing'));
    }

    public function testResolveDownloadPayloadAcceptsManagedExportFile(): void {
        $service = new ReportExportService();
        $filePath = $this->companyDir . '/report_sales.csv';
        file_put_contents($filePath, "Invoice,Amount\nINV-1,100\n");

        Cache::set(
            $service->tokenCacheKey(44, 'tok123'),
            ['name' => 'download.csv', 'path' => $filePath],
            60
        );

        $payload = $service->resolveDownloadPayload(44, 'tok123');

        $this->assertIsArray($payload);
        $this->assertSame(realpath($filePath), $payload['path']);
        $this->assertSame('download.csv', $payload['name']);
    }

    private function removeTree(string $path): void {
        if (!is_dir($path)) {
            return;
        }
        $items = scandir($path);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $fullPath = $path . '/' . $item;
            if (is_dir($fullPath)) {
                $this->removeTree($fullPath);
            } else {
                @unlink($fullPath);
            }
        }
        @rmdir($path);
    }
}
