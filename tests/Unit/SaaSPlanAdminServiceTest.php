<?php
require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/Logger.php';
require_once dirname(__DIR__, 2) . '/core/Model.php';
if (!defined('DATETIME_FORMAT_DB')) {
    define('DATETIME_FORMAT_DB', 'Y-m-d H:i:s');
}
require_once dirname(__DIR__, 2) . '/models/SaaSPlan.php';
require_once dirname(__DIR__, 2) . '/services/SaaSPlanAdminService.php';

if (!class_exists('SaaSPlanAdminFakeModel')) {
    class SaaSPlanAdminFakeModel extends SaaSPlan {
        public array $plans = [];
        public array $updateCalls = [];
        public bool $throwOnCreate = false;
        public bool $throwOnDelete = false;

        public function __construct() {}

        public function listForAdmin(): array {
            return array_values($this->plans);
        }

        public function find($id) {
            return $this->plans[(int)$id] ?? null;
        }

        public function createPlan(array $input): array {
            if ($this->throwOnCreate) {
                throw new RuntimeException('boom');
            }
            return ['success' => true, 'id' => 9];
        }

        public function updatePlan(int $id, array $input): array {
            return ['success' => true, 'id' => $id];
        }

        public function deletePlan(int $id): array {
            if ($this->throwOnDelete) {
                throw new RuntimeException('boom');
            }
            return ['success' => true, 'message' => 'Plan deleted successfully.'];
        }

        public function update($id, $data) {
            $this->updateCalls[] = ['id' => (int)$id, 'data' => $data];
            return true;
        }
    }
}

class SaaSPlanAdminServiceTest extends BaseTestCase {
    public function testCreatePlanReturnsFriendlyErrorWhenModelThrows(): void {
        $model = new SaaSPlanAdminFakeModel();
        $model->throwOnCreate = true;
        $service = new SaaSPlanAdminService($model);

        $result = $service->createPlan(['name' => 'Starter']);

        $this->assertFalse($result['success']);
        $this->assertSame(['Unable to create plan right now.'], $result['errors']);
    }

    public function testToggleStatusUpdatesPlanFlags(): void {
        $model = new SaaSPlanAdminFakeModel();
        $model->plans[5] = ['id' => 5, 'status' => 'active'];
        $service = new SaaSPlanAdminService($model);

        $result = $service->toggleStatus(5);

        $this->assertSame('inactive', $result['status']);
        $this->assertCount(1, $model->updateCalls);
        $this->assertSame(0, $model->updateCalls[0]['data']['is_active']);
    }

    public function testDeletePlanReturnsFriendlyErrorWhenModelThrows(): void {
        $model = new SaaSPlanAdminFakeModel();
        $model->throwOnDelete = true;
        $service = new SaaSPlanAdminService($model);

        $result = $service->deletePlan(4);

        $this->assertFalse($result['success']);
        $this->assertSame('Failed to delete plan.', $result['message']);
    }
}
