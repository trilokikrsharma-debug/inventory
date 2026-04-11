<?php
class PromoCodeAdminService {
    private PromoCode $promoModel;

    public function __construct(?PromoCode $promoModel = null) {
        $this->promoModel = $promoModel ?: new PromoCode();
    }

    public function listPromos(): array {
        return $this->promoModel->listForAdmin();
    }

    public function loadPromo(int $id): ?array {
        if ($id <= 0) {
            return null;
        }

        return $this->promoModel->find($id) ?: null;
    }

    public function createPromo(array $input): array {
        try {
            return $this->promoModel->createPromo($input);
        } catch (\Throwable $e) {
            Logger::error('Failed to create promo code', ['error' => $e->getMessage()]);
            return ['success' => false, 'errors' => ['Failed to create promo code.']];
        }
    }

    public function updatePromo(int $id, array $input): array {
        try {
            return $this->promoModel->updatePromo($id, $input);
        } catch (\Throwable $e) {
            Logger::error('Failed to update promo code', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'errors' => ['Failed to update promo code.']];
        }
    }

    public function deletePromo(int $id): array {
        try {
            return $this->promoModel->deletePromo($id);
        } catch (\Throwable $e) {
            Logger::error('Failed to delete promo code', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Failed to delete promo code.'];
        }
    }

    public function toggleStatus(int $id): ?array {
        $promo = $this->loadPromo($id);
        if (!$promo) {
            return null;
        }

        $nextStatus = ($promo['status'] ?? 'inactive') === 'active' ? 'inactive' : 'active';
        $payload = [
            'status' => $nextStatus,
            'updated_at' => SaaSBillingHelper::now(),
        ];

        $this->promoModel->update($id, $payload);

        return [
            'id' => $id,
            'status' => $nextStatus,
            'payload' => $payload,
        ];
    }
}
