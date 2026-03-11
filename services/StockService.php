<?php
/**
 * Stock Service
 * 
 * Handles business logic related to inventory stock levels.
 */
class StockService {
    private ProductRepository $productRepo;

    public function __construct(ProductRepository $productRepo) {
        $this->productRepo = $productRepo;
    }

    /**
     * Deduct stock securely
     * 
     * @throws Exception if insufficient stock
     */
    public function deduct(int $productId, float $quantity, string $reason, int $userId, ?int $referenceId = null): void {
        if ($quantity <= 0) {
            throw new Exception("Quantity to deduct must be greater than zero.");
        }

        // Get current stock (lock row if using transactions)
        $stockInfo = $this->productRepo->getStockDetails($productId);
        if (!$stockInfo) {
            throw new Exception("Product not found.");
        }

        if ($stockInfo['current_stock'] < $quantity) {
            throw new Exception("Insufficient stock for product. Available: {$stockInfo['current_stock']}, Requested: {$quantity}");
        }

        // Deduct stock
        $success = $this->productRepo->deductStock($productId, $quantity);
        if (!$success) {
            throw new Exception("Failed to deduct stock. The record may have been modified concurrently.");
        }

        $stockAfter = $stockInfo['current_stock'] - $quantity;

        // Log history
        $note = "Stock deducted for {$reason}" . ($referenceId ? " (Ref: {$referenceId})" : "");
        $this->productRepo->logStockHistory(
            $productId, 
            $reason, 
            -$quantity, 
            $stockInfo['current_stock'], 
            $stockAfter, 
            $note,
            $userId
        );

        // Check for low stock alert
        if ($stockAfter <= $stockInfo['alert_quantity']) {
            Logger::warning('low_stock', ['product_id' => $productId, 'stock' => $stockAfter, 'alert_level' => $stockInfo['alert_quantity']]);
            // Dispatch webhook or email notification asynchronously
            WebhookDispatcher::dispatch('product.low_stock', [
                'product_id' => $productId,
                'stock' => $stockAfter,
                'alert_quantity' => $stockInfo['alert_quantity']
            ]);
        }
    }

    /**
     * Add stock securely
     */
    public function add(int $productId, float $quantity, string $reason, int $userId, ?int $referenceId = null): void {
        if ($quantity <= 0) {
            throw new Exception("Quantity to add must be greater than zero.");
        }

        $stockInfo = $this->productRepo->getStockDetails($productId);
        if (!$stockInfo) {
            throw new Exception("Product not found.");
        }

        $success = $this->productRepo->addStock($productId, $quantity);
        if (!$success) {
            throw new Exception("Failed to add stock.");
        }

        $stockAfter = $stockInfo['current_stock'] + $quantity;

        // Log history
        $note = "Stock added via {$reason}" . ($referenceId ? " (Ref: {$referenceId})" : "");
        $this->productRepo->logStockHistory(
            $productId, 
            $reason, 
            $quantity, 
            $stockInfo['current_stock'], 
            $stockAfter, 
            $note,
            $userId
        );
    }
}
