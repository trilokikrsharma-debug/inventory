<?php
/**
 * Unit Tests - BackupController restore safety
 */

require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/Session.php';
require_once dirname(__DIR__, 2) . '/core/Tenant.php';
require_once dirname(__DIR__, 2) . '/core/Controller.php';
require_once dirname(__DIR__, 2) . '/services/BackupService.php';
require_once dirname(__DIR__, 2) . '/controllers/BackupController.php';

class BackupControllerTest extends BaseTestCase {
    private BackupController $controller;
    private string $managedRoot;

    protected function setUp(): void {
        parent::setUp();

        if (!defined('CONFIG_PATH')) {
            define('CONFIG_PATH', BASE_PATH . '/config');
        }
        if (!defined('APP_VERSION')) {
            define('APP_VERSION', 'test');
        }

        $this->controller = new BackupController();
        $this->managedRoot = sys_get_temp_dir() . '/backup-controller-test-' . uniqid('', true);
        @mkdir($this->managedRoot, 0775, true);
        @mkdir($this->managedRoot . '/full', 0775, true);

        $backupDirRef = new ReflectionProperty(BackupController::class, 'backupDir');
        $backupDirRef->setAccessible(true);
        $backupDirRef->setValue($this->controller, $this->managedRoot);
    }

    protected function tearDown(): void {
        foreach (glob($this->managedRoot . '/full/*') ?: [] as $file) {
            @unlink($file);
        }
        foreach (glob($this->managedRoot . '/*') ?: [] as $entry) {
            if (is_file($entry)) {
                @unlink($entry);
            }
        }
        @rmdir($this->managedRoot . '/full');
        @rmdir($this->managedRoot);

        parent::tearDown();
    }

    public function testValidateRestoreSqlRejectsBlockedStatements(): void {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('prohibited statements');

        $this->invokePrivate('validateRestoreSql', [
            "CREATE TABLE test (id INT);\nGRANT ALL PRIVILEGES ON *.* TO 'x'@'%';",
        ]);
    }

    public function testValidateRestoreSqlRejectsSchemaLessDump(): void {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('full schema backup');

        $this->invokePrivate('validateRestoreSql', [
            "INSERT INTO sample (id, name) VALUES (1, 'Only data');",
        ]);
    }

    public function testValidateRestoreSqlAcceptsSchemaBackup(): void {
        $statements = $this->invokePrivate('validateRestoreSql', [
            "SET FOREIGN_KEY_CHECKS = 0;\n" .
            "DROP TABLE IF EXISTS `demo`;\n" .
            "CREATE TABLE `demo` (`id` INT, `note` VARCHAR(255));\n" .
            "INSERT INTO `demo` (`id`, `note`) VALUES (1, 'hello');\n"
        ]);

        $this->assertIsArray($statements);
        $this->assertCount(4, $statements);
        $this->assertStringStartsWith('CREATE TABLE', $statements[2]);
    }

    public function testValidateRestoreSqlRejectsTriggerAndDefinerStatements(): void {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('prohibited statements');

        $this->invokePrivate('validateRestoreSql', [
            "CREATE TABLE `demo` (`id` INT);\n" .
            "CREATE DEFINER=`root`@`localhost` TRIGGER `demo_after_insert` AFTER INSERT ON `demo` FOR EACH ROW SET @x = 1;\n",
        ]);
    }

    public function testSplitSqlStatementsKeepsSemicolonsInsideQuotedStrings(): void {
        $statements = $this->invokePrivate('splitSqlStatements', [
            "-- comment line\n" .
            "CREATE TABLE `demo` (`id` INT, `note` TEXT);\n" .
            "INSERT INTO `demo` (`id`, `note`) VALUES (1, 'hello;world');\n" .
            "# another comment\n" .
            "INSERT INTO `demo` (`id`, `note`) VALUES (2, \"quoted;value\");\n"
        ]);

        $this->assertCount(3, $statements);
        $this->assertStringContainsString("'hello;world'", $statements[1]);
        $this->assertStringContainsString('"quoted;value"', $statements[2]);
    }

    public function testIsValidBackupFilenameOnlyAllowsManagedSqlNames(): void {
        $this->assertTrue($this->invokePrivate('isValidBackupFilename', ['full_backup_2026-04-06_18-45-33.sql']));
        $this->assertFalse($this->invokePrivate('isValidBackupFilename', ['../../etc/passwd']));
        $this->assertFalse($this->invokePrivate('isValidBackupFilename', ['backup.sql.php']));
        $this->assertFalse($this->invokePrivate('isValidBackupFilename', ['backup.txt']));
    }

    public function testAssertManagedBackupPathRejectsFilesOutsideAllowedRoots(): void {
        $outsideFile = sys_get_temp_dir() . '/backup-test-outside-' . uniqid('', true) . '.sql';
        file_put_contents($outsideFile, '-- outside backup');

        try {
            $result = $this->invokePrivate('assertManagedBackupPath', [$outsideFile]);
            $this->assertNull($result);
        } finally {
            @unlink($outsideFile);
        }
    }

    public function testAssertManagedBackupPathAcceptsFilesInsideManagedRoot(): void {
        $managedDir = $this->managedRoot . '/tenant-test';
        if (!is_dir($managedDir)) {
            mkdir($managedDir, 0775, true);
        }

        $managedFile = $managedDir . '/managed_backup.sql';
        $bytes = file_put_contents($managedFile, '-- managed backup');

        try {
            $this->assertNotFalse($bytes);
            $this->assertFileExists($managedFile);
            $result = $this->invokePrivate('assertManagedBackupPath', [$managedFile]);
            $this->assertSame(realpath($managedFile), $result);
        } finally {
            @unlink($managedFile);
            @rmdir($managedDir);
        }
    }

    /**
     * @param array<int, mixed> $args
     * @return mixed
     */
    private function invokePrivate(string $method, array $args = []) {
        $ref = new ReflectionMethod($this->controller, $method);
        $ref->setAccessible(true);
        return $ref->invokeArgs($this->controller, $args);
    }
}
