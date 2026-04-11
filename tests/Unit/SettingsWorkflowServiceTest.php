<?php
require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/Helper.php';
require_once dirname(__DIR__, 2) . '/core/Tenant.php';
if (!defined('REDIS_ENABLED')) {
    define('REDIS_ENABLED', false);
}
if (!defined('CACHE_PATH')) {
    define('CACHE_PATH', sys_get_temp_dir() . '/tsalegacy-cache-tests');
}
require_once dirname(__DIR__, 2) . '/core/Cache.php';
require_once dirname(__DIR__, 2) . '/services/SettingsWorkflowService.php';

class SettingsWorkflowServiceTest extends BaseTestCase {
    protected function tearDown(): void {
        Tenant::reset();
        parent::tearDown();
    }

    public function testBuildPayloadNormalizesNonGstSettingsAndUploadedPaths(): void {
        $service = new SettingsWorkflowService();

        $payload = $service->buildPayload([
            'company_name' => ' <b>Acme</b> ',
            'tax_number' => ' GSTIN ',
            'tax_number_nongst' => ' BILL-9 ',
            'enable_gst' => '',
            'enable_tax' => '1',
            'tax_rate' => '18',
            'tax_rate_nongst' => '5',
            'invoice_title' => '',
            'invoice_signature_label' => '',
            'theme_color' => '#123456',
        ], [
            'company_logo' => 'uploads/logo.png',
        ]);

        $this->assertSame('Acme', $payload['company_name']);
        $this->assertSame(0, $payload['enable_gst']);
        $this->assertSame(0, $payload['enable_tax']);
        $this->assertSame(0.0, $payload['tax_rate']);
        $this->assertSame('BILL-9', $payload['tax_number']);
        $this->assertSame('', $payload['invoice_title']);
        $this->assertSame('', $payload['invoice_signature_label']);
        $this->assertSame('uploads/logo.png', $payload['company_logo']);
    }

    public function testSummarizeChangesListsHighLevelSettingsChanges(): void {
        $service = new SettingsWorkflowService();

        $summary = $service->summarizeChanges([
            'enable_gst' => 1,
            'enable_tax' => 1,
            'tax_rate' => 18,
            'invoice_prefix' => 'INV-',
            'purchase_prefix' => 'PUR-',
        ], [
            'enable_gst' => 0,
            'enable_tax' => 0,
            'tax_rate' => 0.0,
            'invoice_prefix' => 'SINV-',
            'purchase_prefix' => 'SPUR-',
        ]);

        $this->assertSame(
            'GST: off, Tax: off, Tax rate: 0%, Invoice prefix changed, Purchase prefix changed',
            $summary
        );
    }

    public function testFlushSettingCachesClearsExpectedTenantKeys(): void {
        Tenant::set(44, ['id' => 44]);
        Cache::set('c44_sidebar_lowstock', '1', 60);
        Cache::set('c44_dash_metric', '1', 60);
        Cache::set('c44_report_metric', '1', 60);
        Cache::set('c44_products_metric', '1', 60);

        $service = new SettingsWorkflowService();
        $service->flushSettingCaches();

        $this->assertNull(Cache::get('c44_sidebar_lowstock'));
        $this->assertNull(Cache::get('c44_dash_metric'));
        $this->assertNull(Cache::get('c44_report_metric'));
        $this->assertNull(Cache::get('c44_products_metric'));
    }
}
