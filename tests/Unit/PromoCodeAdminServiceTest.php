<?php
require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/Logger.php';
require_once dirname(__DIR__, 2) . '/core/Model.php';
if (!defined('DATETIME_FORMAT_DB')) {
    define('DATETIME_FORMAT_DB', 'Y-m-d H:i:s');
}
require_once dirname(__DIR__, 2) . '/models/PromoCode.php';
require_once dirname(__DIR__, 2) . '/services/PromoCodeAdminService.php';

if (!class_exists('PromoCodeAdminFakeModel')) {
    class PromoCodeAdminFakeModel extends PromoCode {
        public array $promos = [];
        public array $updateCalls = [];
        public bool $throwOnCreate = false;
        public bool $throwOnDelete = false;

        public function __construct() {}

        public function listForAdmin(): array {
            return array_values($this->promos);
        }

        public function find($id) {
            return $this->promos[(int)$id] ?? null;
        }

        public function createPromo(array $input): array {
            if ($this->throwOnCreate) {
                throw new RuntimeException('boom');
            }
            return ['success' => true, 'id' => 6];
        }

        public function updatePromo(int $id, array $input): array {
            return ['success' => true, 'id' => $id];
        }

        public function deletePromo(int $id): array {
            if ($this->throwOnDelete) {
                throw new RuntimeException('boom');
            }
            return ['success' => true, 'message' => 'Promo deleted successfully.'];
        }

        public function update($id, $data) {
            $this->updateCalls[] = ['id' => (int)$id, 'data' => $data];
            return true;
        }
    }
}

class PromoCodeAdminServiceTest extends BaseTestCase {
    public function testCreatePromoReturnsFriendlyErrorWhenModelThrows(): void {
        $model = new PromoCodeAdminFakeModel();
        $model->throwOnCreate = true;
        $service = new PromoCodeAdminService($model);

        $result = $service->createPromo(['code' => 'SAVE10']);

        $this->assertFalse($result['success']);
        $this->assertSame(['Failed to create promo code.'], $result['errors']);
    }

    public function testToggleStatusUpdatesPromoStatus(): void {
        $model = new PromoCodeAdminFakeModel();
        $model->promos[3] = ['id' => 3, 'status' => 'inactive'];
        $service = new PromoCodeAdminService($model);

        $result = $service->toggleStatus(3);

        $this->assertSame('active', $result['status']);
        $this->assertCount(1, $model->updateCalls);
        $this->assertSame('active', $model->updateCalls[0]['data']['status']);
    }

    public function testDeletePromoReturnsFriendlyErrorWhenModelThrows(): void {
        $model = new PromoCodeAdminFakeModel();
        $model->throwOnDelete = true;
        $service = new PromoCodeAdminService($model);

        $result = $service->deletePromo(7);

        $this->assertFalse($result['success']);
        $this->assertSame('Failed to delete promo code.', $result['message']);
    }
}
