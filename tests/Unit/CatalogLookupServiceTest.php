<?php
require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/Helper.php';
require_once dirname(__DIR__, 2) . '/services/CatalogLookupService.php';

class CatalogLookupServiceTest extends BaseTestCase {
    public function testBuildNamedPayloadNormalizesCatalogFields(): void {
        $service = new CatalogLookupService();
        $payload = $service->buildNamedPayload([
            'name' => ' <b>Appliances</b> ',
            'description' => '<script>x</script>General',
            'is_active' => '1',
        ], true);

        $this->assertSame('Appliances', $payload['name']);
        $this->assertSame('xGeneral', $payload['description']);
        $this->assertSame(1, $payload['is_active']);
    }

    public function testBuildUnitPayloadNormalizesShortNameAndInactiveState(): void {
        $service = new CatalogLookupService();
        $payload = $service->buildUnitPayload([
            'name' => ' <b>Kilograms</b> ',
            'short_name' => ' kg ',
            'is_active' => '',
        ]);

        $this->assertSame('Kilograms', $payload['name']);
        $this->assertSame('kg', $payload['short_name']);
        $this->assertSame(0, $payload['is_active']);
    }
}
