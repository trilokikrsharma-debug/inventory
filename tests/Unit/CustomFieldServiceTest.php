<?php

require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/services/CustomFieldService.php';

class CustomFieldServiceTest extends BaseTestCase {
    public function testEncodeFromInputNormalizesAndSortsObject(): void {
        $json = CustomFieldService::encodeFromInput('{" Priority ":"Gold","Route":"North"}');

        $this->assertSame('{"Priority":"Gold","Route":"North"}', $json);
    }

    public function testEncodeFromInputRejectsNonObjectPayload(): void {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('valid JSON object');

        CustomFieldService::encodeFromInput('["x","y"]');
    }

    public function testDecodeFiltersNestedValues(): void {
        $decoded = CustomFieldService::decode('{"Route":"North","Nested":{"x":1},"Active":true}');

        $this->assertSame([
            'Route' => 'North',
            'Active' => true,
        ], $decoded);
    }
}
