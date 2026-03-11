<?php
/**
 * Product Repository
 */
class ProductRepository extends BaseRepository {
    protected string $table = 'products';
    
    /**
     * Deduct stock securely
     */
    public function deductStock(int $productId, float $quantity): bool {
        return $this->db->query(
            "UPDATE products SET current_stock = current_stock - ? WHERE id = ? AND company_id = ?",
            [$quantity, $productId, Tenant::id()]
        )->rowCount() > 0;
    }
    
    /**
     * Add stock securely
     */
    public function addStock(int $productId, float $quantity): bool {
        return $this->db->query(
            "UPDATE products SET current_stock = current_stock + ? WHERE id = ? AND company_id = ?",
            [$quantity, $productId, Tenant::id()]
        )->rowCount() > 0;
    }
    
    /**
     * Get product stock details
     */
    public function getStockDetails(int $productId): ?array {
        return $this->db->query(
            "SELECT current_stock, alert_quantity FROM products WHERE id = ? AND company_id = ?",
            [$productId, Tenant::id()]
        )->fetch() ?: null;
    }
    
    /**
     * Log stock history
     */
    public function logStockHistory(int $productId, string $type, float $quantity, float $stockBefore, float $stockAfter, string $note, int $userId): void {
        $this->db->query(
            "INSERT INTO stock_history (company_id, product_id, type, quantity, stock_before, stock_after, note, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [Tenant::id(), $productId, $type, $quantity, $stockBefore, $stockAfter, $note, $userId]
        );
    }
}
