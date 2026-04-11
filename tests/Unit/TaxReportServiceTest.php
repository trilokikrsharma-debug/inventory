<?php
require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/services/TaxReportService.php';

class TaxReportServiceTest extends BaseTestCase {
    public function testSummarizeRowsSplitsGstAndNonGstTurnover(): void {
        $service = new TaxReportService(new stdClass());

        $report = $service->summarizeRows([
            [
                'tax_rate' => 18,
                'gst_type' => 'cgst_sgst',
                'voucher_count' => 2,
                'taxable_amount' => 1000,
                'tax_amount' => 180,
            ],
            [
                'tax_rate' => 5,
                'gst_type' => 'igst',
                'voucher_count' => 1,
                'taxable_amount' => 500,
                'tax_amount' => 25,
            ],
            [
                'tax_rate' => 0,
                'gst_type' => 'none',
                'voucher_count' => 1,
                'taxable_amount' => 300,
                'tax_amount' => 0,
            ],
        ], [
            [
                'tax_rate' => 18,
                'voucher_count' => 1,
                'taxable_amount' => 400,
                'tax_amount' => 72,
            ],
        ], '2026-04-01', '2026-04-30', [
            'enable_tax' => 1,
            'enable_gst' => 1,
        ]);

        $summary = $report['summary'];
        $this->assertTrue($report['gst_enabled']);
        $this->assertSame(1800.0, $summary['sales_taxable']);
        $this->assertSame(300.0, $summary['sales_non_gst']);
        $this->assertSame(90.0, $summary['output_cgst']);
        $this->assertSame(90.0, $summary['output_sgst']);
        $this->assertSame(25.0, $summary['output_igst']);
        $this->assertSame(205.0, $summary['output_tax']);
        $this->assertSame(72.0, $summary['input_tax']);
        $this->assertSame(133.0, $summary['net_tax_payable']);
    }

    public function testSummarizeRowsMarksSettingsAsNonGstButKeepsHistoricalTaxSnapshots(): void {
        $service = new TaxReportService(new stdClass());

        $report = $service->summarizeRows([
            [
                'tax_rate' => 18,
                'gst_type' => 'auto',
                'voucher_count' => 1,
                'taxable_amount' => 100,
                'tax_amount' => 18,
            ],
        ], [], '2026-04-01', '2026-04-30', [
            'enable_tax' => 0,
            'enable_gst' => 0,
        ]);

        $this->assertFalse($report['gst_enabled']);
        $this->assertSame(18.0, $report['summary']['output_tax']);
        $this->assertSame('cgst_sgst', $report['sales_breakdown'][0]['gst_type']);
    }

    public function testSummarizeRowsNetsPostedSaleReturnTaxFromOutputTax(): void {
        $service = new TaxReportService(new stdClass());

        $report = $service->summarizeRows([
            [
                'tax_rate' => 18,
                'gst_type' => 'cgst_sgst',
                'voucher_count' => 1,
                'taxable_amount' => 1000,
                'tax_amount' => 180,
            ],
        ], [], '2026-04-01', '2026-04-30', [
            'enable_tax' => 1,
            'enable_gst' => 1,
        ], [
            [
                'tax_rate' => 18,
                'gst_type' => 'cgst_sgst',
                'voucher_count' => 1,
                'taxable_amount' => 200,
                'tax_amount' => 36,
            ],
        ]);

        $this->assertSame(800.0, $report['summary']['sales_taxable']);
        $this->assertSame(200.0, $report['summary']['sales_return_taxable']);
        $this->assertSame(72.0, $report['summary']['output_cgst']);
        $this->assertSame(72.0, $report['summary']['output_sgst']);
        $this->assertSame(144.0, $report['summary']['output_tax']);
        $this->assertSame(800.0, $report['sales_breakdown'][0]['taxable_amount']);
        $this->assertSame(144.0, $report['sales_breakdown'][0]['tax_amount']);
    }

    public function testSummarizeRowsNetsPurchaseReturnsFromInputTax(): void {
        $service = new TaxReportService(new stdClass());

        $report = $service->summarizeRows([], [
            [
                'tax_rate' => 18,
                'voucher_count' => 1,
                'taxable_amount' => 1000,
                'tax_amount' => 180,
            ],
        ], '2026-04-01', '2026-04-30', [
            'enable_tax' => 1,
            'enable_gst' => 1,
        ], [], [
            [
                'tax_rate' => 18,
                'voucher_count' => 1,
                'taxable_amount' => 250,
                'tax_amount' => 45,
            ],
        ]);

        $this->assertSame(750.0, $report['summary']['purchase_taxable']);
        $this->assertSame(250.0, $report['summary']['purchase_return_taxable']);
        $this->assertSame(135.0, $report['summary']['input_tax']);
        $this->assertSame(-135.0, $report['summary']['net_tax_payable']);
        $this->assertSame(750.0, $report['purchase_breakdown'][0]['taxable_amount']);
        $this->assertSame(135.0, $report['purchase_breakdown'][0]['tax_amount']);
    }
}
