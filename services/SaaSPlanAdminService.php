<?php
class SaaSPlanAdminService {
    private SaaSPlan $planModel;

    public function __construct(?SaaSPlan $planModel = null) {
        $this->planModel = $planModel ?: new SaaSPlan();
    }

    public function listPlans(): array {
        return $this->planModel->listForAdmin();
    }

    public function loadPlan(int $id): ?array {
        if ($id <= 0) {
            return null;
        }

        return $this->planModel->find($id) ?: null;
    }

    public function createPlan(array $input): array {
        try {
            return $this->planModel->createPlan($input);
        } catch (\Throwable $e) {
            Logger::error('Failed to create SaaS plan', ['error' => $e->getMessage()]);
            return ['success' => false, 'errors' => ['Unable to create plan right now.']];
        }
    }

    public function updatePlan(int $id, array $input): array {
        try {
            return $this->planModel->updatePlan($id, $input);
        } catch (\Throwable $e) {
            Logger::error('Failed to update SaaS plan', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'errors' => ['Unable to update plan right now.']];
        }
    }

    public function deletePlan(int $id): array {
        try {
            return $this->planModel->deletePlan($id);
        } catch (\Throwable $e) {
            Logger::error('Failed to delete SaaS plan', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Failed to delete plan.'];
        }
    }

    public function toggleStatus(int $id): ?array {
        try {
            $plan = $this->planModel->find($id);
        } catch (\Throwable $e) {
            Logger::error('Failed to toggle SaaS plan', ['id' => $id, 'error' => $e->getMessage()]);
            return null;
        }

        if (!$plan) {
            return null;
        }

        $nextStatus = ($plan['status'] ?? 'inactive') === 'active' ? 'inactive' : 'active';
        $payload = [
            'status' => $nextStatus,
            'is_active' => $nextStatus === 'active' ? 1 : 0,
            'updated_at' => SaaSBillingHelper::now(),
        ];
        $this->planModel->update($id, $payload);

        return [
            'id' => $id,
            'status' => $nextStatus,
            'payload' => $payload,
        ];
    }
}
