<?php
/**
 * Sale Service
 * 
 * Orchestrates the complex business logic for creating and managing sales.
 */
class SaleService {
    private Database $db;
    private SaleRepository $saleRepo;
    private CustomerRepository $customerRepo;
    private StockService $stockService;

    public function __construct(
        Database $db, 
        SaleRepository $saleRepo, 
        CustomerRepository $customerRepo, 
        StockService $stockService
    ) {
        $this->db = $db;
        $this->saleRepo = $saleRepo;
        $this->customerRepo = $customerRepo;
        $this->stockService = $stockService;
    }

    /**
     * Create a sale transaction
     */
    public function createSale(array $data, array $items, int $userId): int {
        // Validation handled by Controller DTO/Validator before reaching here
        if (empty($items)) {
            throw new Exception("Sale must have at least one item.");
        }

        $this->db->beginTransaction();
        try {
            // 1. Calculate totals dynamically logic if needed, but assuming DTO gave calculated inputs
            // Create the sale
            $saleId = $this->saleRepo->insertSale($data);

            // 2. Insert items and adjust stock
            foreach ($items as &$item) {
                // Ensure array shape matches repository expectations
                if (!isset($item['subtotal'])) {
                    $item['subtotal'] = $item['quantity'] * $item['unit_price'];
                }
                if (!isset($item['total'])) {
                    $item['total'] = $item['subtotal'] + ($item['tax_amount'] ?? 0) - ($item['discount_amount'] ?? 0);
                }

                $this->stockService->deduct(
                    $item['product_id'], 
                    $item['quantity'], 
                    'sale', 
                    $userId, 
                    $saleId
                );
            }
            unset($item);

            $this->saleRepo->insertItems($saleId, $items);

            // 3. Update customer balance if there is a due amount
            if ($data['due_amount'] > 0 && $data['customer_id']) {
                $success = $this->customerRepo->updateBalance($data['customer_id'], $data['due_amount']);
                if (!$success) {
                    throw new Exception("Failed to update customer balance.");
                }
            }

            $this->db->commit();

            // 4. Dispatch Async Webhooks & Audit
            WebhookDispatcher::dispatch('sale.created', [
                'sale_id' => $saleId,
                'invoice' => $data['invoice_number'],
                'total'   => $data['grand_total'],
            ]);

            Logger::audit('sale_created', 'sales', $saleId, [
                'total' => $data['grand_total'], 
                'items' => count($items)
            ]);

            return $saleId;

        } catch (Exception $e) {
            $this->db->rollback();
            Logger::error('sale_creation_failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }
}
