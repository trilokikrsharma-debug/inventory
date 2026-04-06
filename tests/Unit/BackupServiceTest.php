<?php

require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/services/BackupService.php';

class BackupServiceTest extends BaseTestCase {
    private string $testDir;

    protected function setUp(): void {
        parent::setUp();

        $this->testDir = sys_get_temp_dir() . '/backup-service-test-' . uniqid('', true);
        if (!is_dir($this->testDir)) {
            mkdir($this->testDir, 0775, true);
        }
    }

    protected function tearDown(): void {
        foreach (glob($this->testDir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->testDir);

        parent::tearDown();
    }

    public function testVerifyIntegrityFailsWhenManifestIsMissing(): void {
        $filePath = $this->createBackupFile('missing_manifest.sql', '-- sql dump');

        $result = BackupService::verifyIntegrity($filePath);

        $this->assertFalse($result['ok']);
        $this->assertSame('Backup manifest not found.', $result['reason']);
    }

    public function testVerifyIntegritySucceedsWhenManifestMatchesFile(): void {
        $filePath = $this->createBackupFile('valid_manifest.sql', '-- sql dump');
        $this->writeManifest($filePath, hash_file('sha256', $filePath), filesize($filePath));

        $result = BackupService::verifyIntegrity($filePath);

        $this->assertTrue($result['ok']);
        $this->assertSame('full', $result['manifest']['backup_type']);
    }

    public function testVerifyIntegrityFailsWhenChecksumDoesNotMatch(): void {
        $filePath = $this->createBackupFile('tampered_manifest.sql', '-- sql dump');
        $this->writeManifest($filePath, str_repeat('a', 64), filesize($filePath));

        $result = BackupService::verifyIntegrity($filePath);

        $this->assertFalse($result['ok']);
        $this->assertSame('Backup checksum verification failed.', $result['reason']);
    }

    private function createBackupFile(string $name, string $contents): string {
        $path = $this->testDir . '/' . $name;
        $bytes = file_put_contents($path, $contents);
        $this->assertNotFalse($bytes);
        $this->assertFileExists($path);
        return $path;
    }

    private function writeManifest(string $filePath, string $checksum, int $size): void {
        $manifest = [
            'manifest_version' => 1,
            'file_name' => basename($filePath),
            'file_size_bytes' => $size,
            'checksum_sha256' => $checksum,
            'generated_at_utc' => gmdate('Y-m-d H:i:s'),
            'app_env' => 'testing',
            'backup_type' => 'full',
            'company_id' => null,
            'company_name' => null,
        ];

        file_put_contents(
            BackupService::manifestPath($filePath),
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL
        );
    }
}
