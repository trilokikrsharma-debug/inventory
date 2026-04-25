<?php

require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/Tenant.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';
require_once dirname(__DIR__, 2) . '/services/ContactImportService.php';

class ContactImportServiceTest extends BaseTestCase {
    private ContactImportService $service;

    protected function setUp(): void {
        parent::setUp();
        $this->service = new ContactImportService();
        Tenant::reset();
    }

    protected function tearDown(): void {
        Tenant::reset();
        $this->setDatabaseInstance(null);
        parent::tearDown();
    }

    public function testTemplateCsvIncludesExpectedHeaders(): void {
        $csv = $this->service->templateCsv('customer');
        $this->assertStringContainsString('name,email,phone,address,city,state,zip,tax_number,opening_balance,is_active', $csv);
    }

    public function testAnalyzeCustomerCsvRejectsExistingEmailAndDuplicatePhone(): void {
        $analysis = $this->service->analyzeCsvString(
            'customer',
            "name,email,phone,opening_balance\n" .
            "Alpha,used@example.com,+1 555 0100,0\n" .
            "Beta,beta@example.com,+1 555 0100,10\n",
            [
                'existing_emails' => ['used@example.com' => true],
                'existing_phones' => [],
            ]
        );

        $this->assertSame(2, $analysis['summary']['invalid_rows']);
        $this->assertStringContainsString('Email already exists.', implode(' ', $analysis['rows'][0]['errors']));
        $this->assertStringContainsString('Duplicate phone in file.', implode(' ', $analysis['rows'][1]['errors']));
    }

    public function testAnalyzeSupplierCsvAcceptsValidRows(): void {
        $analysis = $this->service->analyzeCsvString(
            'supplier',
            "name,email,phone,opening_balance,is_active\n" .
            "Acme,acme@example.com,+1 555 0200,1250,yes\n",
            [
                'existing_emails' => [],
                'existing_phones' => [],
            ]
        );

        $this->assertSame(1, $analysis['summary']['valid_rows']);
        $row = $analysis['rows'][0]['normalized'];
        $this->assertSame('Acme', $row['name']);
        $this->assertSame('acme@example.com', $row['email']);
        $this->assertSame('+1 555 0200', $row['phone']);
        $this->assertSame(1250.0, $row['opening_balance']);
        $this->assertSame(1, $row['is_active']);
    }

    public function testBuildContextScopesLookupToCurrentTenant(): void {
        Tenant::set(44, ['id' => 44]);
        $db = new RecordingImportDatabase([
            ['email' => 'tenant@example.com', 'phone' => '+1 555 0001'],
        ]);
        $this->setDatabaseInstance($db);

        $context = $this->service->buildContext('customer');

        $this->assertSame(
            'SELECT email, phone FROM customers WHERE deleted_at IS NULL AND company_id = ?',
            $db->queries[0]['sql'] ?? ''
        );
        $this->assertSame([44], $db->queries[0]['params'] ?? []);
        $this->assertTrue($context['existing_emails']['tenant@example.com'] ?? false);
        $this->assertTrue($context['existing_phones']['+1 555 0001'] ?? false);
    }

    private function setDatabaseInstance($instance): void {
        $ref = new ReflectionProperty(Database::class, 'instance');
        $ref->setAccessible(true);
        $ref->setValue(null, $instance);
    }
}

class RecordingImportDatabase {
    public array $queries = [];
    private array $rows;

    public function __construct(array $rows) {
        $this->rows = $rows;
    }

    public function query($sql, $params = []) {
        $this->queries[] = ['sql' => $sql, 'params' => $params];
        return new RecordingImportResult($this->rows);
    }
}

class RecordingImportResult {
    private array $rows;

    public function __construct(array $rows) {
        $this->rows = $rows;
    }

    public function fetchAll() {
        return $this->rows;
    }
}
