<?php
require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/Tenant.php';
require_once dirname(__DIR__, 2) . '/services/BackupManagementService.php';

class BackupManagementServiceTest extends BaseTestCase {
    private string $managedRoot;
    private string $legacyRoot;
    private BackupManagementService $service;

    protected function setUp(): void {
        parent::setUp();
        $this->managedRoot = sys_get_temp_dir() . '/backup-management-test-' . uniqid('', true);
        $this->legacyRoot = sys_get_temp_dir() . '/backup-management-legacy-' . uniqid('', true);
        @mkdir($this->managedRoot . '/company_4', 0775, true);
        @mkdir($this->managedRoot . '/full', 0775, true);
        @mkdir($this->legacyRoot . '/full', 0775, true);
        Tenant::set(4, ['id' => 4, 'name' => 'Acme']);
        $this->service = new BackupManagementService(
            $this->managedRoot,
            $this->legacyRoot,
            $this->managedRoot . '/full',
            $this->legacyRoot . '/full',
            ['products', 'customers']
        );
    }

    protected function tearDown(): void {
        Tenant::reset();
        foreach (glob($this->managedRoot . '/company_4/*') ?: [] as $file) { @unlink($file); }
        foreach (glob($this->managedRoot . '/full/*') ?: [] as $file) { @unlink($file); }
        foreach (glob($this->legacyRoot . '/full/*') ?: [] as $file) { @unlink($file); }
        @rmdir($this->managedRoot . '/company_4');
        @rmdir($this->managedRoot . '/full');
        @rmdir($this->managedRoot);
        @rmdir($this->legacyRoot . '/full');
        @rmdir($this->legacyRoot);
        parent::tearDown();
    }

    public function testResolveVisibleBackupPathReturnsTenantBackup(): void {
        $file = $this->managedRoot . '/company_4/company_4_backup.sql';
        file_put_contents($file, '-- tenant backup');

        $resolved = $this->service->resolveVisibleBackupPath('company_4_backup.sql', 4, false);

        $this->assertSame(realpath($file), $resolved);
    }

    public function testGetBackupListIncludesFullBackupsForSuperAdmin(): void {
        $tenantFile = $this->managedRoot . '/company_4/tenant.sql';
        $fullFile = $this->managedRoot . '/full/full.sql';
        file_put_contents($tenantFile, '-- tenant');
        file_put_contents($fullFile, '-- full');

        $backups = $this->service->getBackupList(4, true);
        $names = array_column($backups, 'filename');

        $this->assertContains('tenant.sql', $names);
        $this->assertContains('full.sql', $names);
    }

    public function testAssertManagedBackupPathRejectsOutsideFiles(): void {
        $outside = sys_get_temp_dir() . '/outside-backup-' . uniqid('', true) . '.sql';
        file_put_contents($outside, '-- outside');

        try {
            $this->assertNull($this->service->assertManagedBackupPath($outside));
        } finally {
            @unlink($outside);
        }
    }
}
