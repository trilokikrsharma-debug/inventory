<?php

require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/services/ContactImportService.php';

class ContactImportServiceTest extends BaseTestCase {
    private ContactImportService $service;

    protected function setUp(): void {
        parent::setUp();
        $this->service = new ContactImportService();
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
}
