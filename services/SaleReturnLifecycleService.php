<?php

class SaleReturnLifecycleService {
    private $db;
    private $saleReturnModel;
    private $productModel;
    private $paymentModel;
    private $customerModel;

    public function __construct($db = null, $saleReturnModel = null, $productModel = null, $paymentModel = null, $customerModel = null) {
        $this->db = $db ?? Database::getInstance();
        $this->saleReturnModel = $saleReturnModel ?? new SaleReturnModel();
        $this->productModel = $productModel ?? new ProductModel();
        $this->paymentModel = $paymentModel ?? new PaymentModel();
        $this->customerModel = $customerModel ?? new CustomerModel();
    }

    public function normalizeCancelReason($reason): string {
        $reason = trim(strip_tags((string)$reason));
        if ($reason === '') {
            throw new InvalidArgumentException('Cancel reason is required.');
        }
        if (mb_strlen($reason) < 3) {
            throw new InvalidArgumentException('Cancel reason must be at least 3 characters.');
        }
        if (mb_strlen($reason) > 500) {
            $reason = mb_substr($reason, 0, 500);
        }

        return $reason;
    }

    public function cancelReturn(int $returnId, $reason, int $userId): array {
        $reason = $this->normalizeCancelReason($reason);
        $return = $this->saleReturnModel->getWithDetails($returnId);
        if (!$return) {
            throw new InvalidArgumentException('Sale return not found.');
        }
        if (($return['status'] ?? 'posted') === 'cancelled') {
            throw new InvalidArgumentException('Sale return is already cancelled.');
        }

        $this->db->beginTransaction();
        try {
            $this->saleReturnModel->markCancelled($returnId, $reason, $userId);

            foreach ((array)($return['items'] ?? []) as $item) {
                $this->productModel->updateStock(
                    (int)$item['product_id'],
                    -(float)$item['quantity'],
                    'adjustment',
                    $returnId,
                    $userId,
                    'Cancelled sale return ' . ($return['return_number'] ?? ('RET-' . str_pad((string)$returnId, 4, '0', STR_PAD_LEFT)))
                );
            }

            $customerId = (int)($return['customer_id'] ?? 0);
            if ($customerId > 0) {
                $this->paymentModel->recalculateCustomerSalesPublic($customerId);
                $this->customerModel->recalculateBalance($customerId);
            }

            $this->db->commit();
            return $this->saleReturnModel->getWithDetails($returnId) ?: $return;
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
