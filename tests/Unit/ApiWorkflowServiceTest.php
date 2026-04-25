<?php

require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/ApiAuth.php';
require_once dirname(__DIR__, 2) . '/core/SaaSBillingHelper.php';
require_once dirname(__DIR__, 2) . '/services/ApiWorkflowService.php';

class ApiWorkflowServiceTest extends BaseTestCase {
    public function testNormalizeTokenRequestDefaultsNameAndRejectsUnknownScopes(): void {
        $service = new ApiWorkflowService(new ApiWorkflowFakeDb([]));

        $payload = $service->normalizeTokenRequest([
            'name' => '   ',
            'full_access' => '0',
            'scopes' => ['unknown.scope'],
            'expiry_days' => '30',
        ]);

        $this->assertSame('Default Integration', $payload['name']);
        $this->assertFalse($payload['full_access']);
        $this->assertSame([], $payload['scopes']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', (string)$payload['expires_at']);
    }

    public function testBuildSummaryPayloadFormatsTenantMetrics(): void {
        $service = new ApiWorkflowService(new ApiWorkflowFakeDb([1250.5, 9925.75, 310.25, 6]));

        $payload = $service->buildSummaryPayload(44);

        $this->assertTrue($payload['success']);
        $this->assertSame(44, $payload['meta']['company_id']);
        $this->assertSame(1250.5, $payload['data']['sales_today']);
        $this->assertSame(9925.75, $payload['data']['sales_month']);
        $this->assertSame(310.25, $payload['data']['outstanding_receivables']);
        $this->assertSame(6, $payload['data']['low_stock_count']);
    }
}

class ApiWorkflowFakeDb {
    private array $columns;
    private int $index = 0;

    public function __construct(array $columns) {
        $this->columns = $columns;
    }

    public function query($sql, $params = []) {
        $value = $this->columns[$this->index] ?? null;
        $this->index++;
        return new ApiWorkflowFakeResult($value);
    }
}

class ApiWorkflowFakeResult {
    private $value;

    public function __construct($value) {
        $this->value = $value;
    }

    public function fetchColumn() {
        return $this->value;
    }

    public function fetchAll() {
        return is_array($this->value) ? $this->value : [];
    }
}
