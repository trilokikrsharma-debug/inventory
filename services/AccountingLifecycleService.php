<?php

class AccountingLifecycleService {
    private $db;
    private $productModel;
    private $customerModel;

    public function __construct($db = null, $productModel = null, $customerModel = null) {
        $this->db = $db ?? Database::getInstance();
        $this->productModel = $productModel ?? new ProductModel();
        $this->customerModel = $customerModel ?? new CustomerModel();
    }

    public function retireOrDeleteProduct(int $id): array {
        $product = $this->productModel->find($id);
        if (!$product) {
            throw new InvalidArgumentException('Product not found.');
        }

        $usage = $this->productUsageCounts($id);
        if (array_sum($usage) > 0) {
            $this->productModel->setActiveState($id, false);
            return [
                'action' => 'archived',
                'record' => $product,
                'usage' => $usage,
            ];
        }

        $this->productModel->delete($id);
        return [
            'action' => 'deleted',
            'record' => $product,
            'usage' => $usage,
        ];
    }

    public function setProductArchived(int $id, bool $archived): array {
        $product = $this->productModel->find($id);
        if (!$product) {
            throw new InvalidArgumentException('Product not found.');
        }

        $this->productModel->setActiveState($id, !$archived);
        return [
            'record' => $product,
            'is_active' => !$archived,
        ];
    }

    public function retireOrDeleteCustomer(int $id): array {
        $customer = $this->customerModel->find($id);
        if (!$customer) {
            throw new InvalidArgumentException('Customer not found.');
        }

        $usage = $this->customerUsageCounts($id);
        if (array_sum($usage) > 0) {
            $this->customerModel->setActiveState($id, false);
            return [
                'action' => 'archived',
                'record' => $customer,
                'usage' => $usage,
            ];
        }

        $this->customerModel->delete($id);
        return [
            'action' => 'deleted',
            'record' => $customer,
            'usage' => $usage,
        ];
    }

    public function setCustomerArchived(int $id, bool $archived): array {
        $customer = $this->customerModel->find($id);
        if (!$customer) {
            throw new InvalidArgumentException('Customer not found.');
        }

        $this->customerModel->setActiveState($id, !$archived);
        return [
            'record' => $customer,
            'is_active' => !$archived,
        ];
    }

    private function productUsageCounts(int $id): array {
        $salesSql = "SELECT COUNT(*)
            FROM sale_items si
            JOIN sales s ON si.sale_id = s.id
            WHERE si.product_id = ? AND s.deleted_at IS NULL";
        $salesParams = [$id];
        if (Tenant::id() !== null) {
            $salesSql .= " AND s.company_id = ?";
            $salesParams[] = Tenant::id();
        }

        $purchaseSql = "SELECT COUNT(*)
            FROM purchase_items pi
            JOIN purchases p ON pi.purchase_id = p.id
            WHERE pi.product_id = ? AND p.deleted_at IS NULL";
        $purchaseParams = [$id];
        if (Tenant::id() !== null) {
            $purchaseSql .= " AND p.company_id = ?";
            $purchaseParams[] = Tenant::id();
        }

        $returnSql = "SELECT COUNT(*)
            FROM sale_return_items sri
            JOIN sale_returns sr ON sri.return_id = sr.id
            WHERE sri.product_id = ? AND sr.deleted_at IS NULL AND sr.status = 'posted'";
        $returnParams = [$id];
        if (Tenant::id() !== null) {
            $returnSql .= " AND sr.company_id = ?";
            $returnParams[] = Tenant::id();
        }

        $quotationSql = "SELECT COUNT(*)
            FROM quotation_items qi
            JOIN quotations q ON qi.quotation_id = q.id
            WHERE qi.product_id = ? AND q.deleted_at IS NULL";
        $quotationParams = [$id];
        if (Tenant::id() !== null) {
            $quotationSql .= " AND q.company_id = ?";
            $quotationParams[] = Tenant::id();
        }

        return [
            'sales' => (int)$this->db->query($salesSql, $salesParams)->fetchColumn(),
            'purchases' => (int)$this->db->query($purchaseSql, $purchaseParams)->fetchColumn(),
            'returns' => (int)$this->db->query($returnSql, $returnParams)->fetchColumn(),
            'quotations' => (int)$this->db->query($quotationSql, $quotationParams)->fetchColumn(),
        ];
    }

    private function customerUsageCounts(int $id): array {
        $salesSql = "SELECT COUNT(*) FROM sales WHERE customer_id = ? AND deleted_at IS NULL";
        $salesParams = [$id];
        if (Tenant::id() !== null) {
            $salesSql .= " AND company_id = ?";
            $salesParams[] = Tenant::id();
        }

        $paymentsSql = "SELECT COUNT(*) FROM payments WHERE customer_id = ? AND deleted_at IS NULL";
        $paymentsParams = [$id];
        if (Tenant::id() !== null) {
            $paymentsSql .= " AND company_id = ?";
            $paymentsParams[] = Tenant::id();
        }

        $returnsSql = "SELECT COUNT(*)
            FROM sale_returns sr
            JOIN sales s ON sr.sale_id = s.id
            WHERE s.customer_id = ? AND sr.deleted_at IS NULL AND sr.status = 'posted'";
        $returnsParams = [$id];
        if (Tenant::id() !== null) {
            $returnsSql .= " AND s.company_id = ?";
            $returnsParams[] = Tenant::id();
        }

        return [
            'sales' => (int)$this->db->query($salesSql, $salesParams)->fetchColumn(),
            'payments' => (int)$this->db->query($paymentsSql, $paymentsParams)->fetchColumn(),
            'returns' => (int)$this->db->query($returnsSql, $returnsParams)->fetchColumn(),
        ];
    }
}
