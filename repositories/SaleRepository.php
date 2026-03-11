<?php
/**
 * Sale Repository
 * 
 * Data access logic for Sales and related tables.
 */
class SaleRepository extends BaseRepository {
    protected string $table = 'sales';

    /**
     * Insert a new sale record
     */
    public function insertSale(array $data): int {
        return (int)$this->db->query(
            "INSERT INTO sales (company_id, customer_id, invoice_number, sale_date, subtotal, tax_amount, discount_amount, shipping_cost, grand_total, paid_amount, due_amount, payment_status, payment_method, note, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                $data['company_id'],
                $data['customer_id'],
                $data['invoice_number'],
                $data['sale_date'],
                $data['subtotal'],
                $data['tax_amount'],
                $data['discount_amount'],
                $data['shipping_cost'],
                $data['grand_total'],
                $data['paid_amount'],
                $data['due_amount'],
                $data['payment_status'],
                $data['payment_method'],
                $data['note'],
                $data['created_by']
            ]
        );
    }

    /**
     * Insert related sale items
     */
    public function insertItems(int $saleId, array $items): void {
        foreach ($items as $item) {
            $this->db->query(
                "INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, subtotal, tax_amount, discount_amount, total, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $saleId,
                    $item['product_id'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['subtotal'],
                    $item['tax_amount'],
                    $item['discount_amount'],
                    $item['total']
                ]
            );
        }
    }
    
    /**
     * Find a sale with its customer details
     */
    public function findWithCustomer(int $id): ?array {
        $query = "SELECT s.*, c.name as customer_name 
             FROM sales s 
             LEFT JOIN customers c ON s.customer_id = c.id
             WHERE s.id = ? AND s.deleted_at IS NULL";
        
        $params = [$id];
             
        if(Tenant::id() !== null) {
            $query .= " AND s.company_id = ?";
            $params[] = Tenant::id();
        }
        
        return $this->db->query($query, $params)->fetch() ?: null;
    }
    
    /**
     * Find items for a sale
     */
    public function findItems(int $saleId): array {
        return $this->db->query(
            "SELECT si.*, p.name as product_name, p.sku 
             FROM sale_items si
             JOIN products p ON si.product_id = p.id
             WHERE si.sale_id = ?",
            [$saleId]
        )->fetchAll();
    }
    
    /**
     * Fetch paginated sales list eager loading customer
     */
    public function paginatedList(array $filters, int $page, int $perPage = 20): array {
        $where = ["s.deleted_at IS NULL"];
        $params = [];
        
        if (Tenant::id() !== null) {
            $where[] = "s.company_id = ?";
            $params[] = Tenant::id();
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(s.invoice_number LIKE ? OR c.name LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }
        
        if (!empty($filters['customer_id'])) {
            $where[] = "s.customer_id = ?";
            $params[] = (int)$filters['customer_id'];
        }
        
        if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
            $where[] = "s.sale_date BETWEEN ? AND ?";
            $params[] = $filters['from_date'];
            $params[] = $filters['to_date'];
        }
        
        $whereStr = implode(' AND ', $where);
        
        $countQuery = "SELECT COUNT(*) FROM sales s LEFT JOIN customers c ON s.customer_id = c.id WHERE {$whereStr}";
        $total = $this->db->query($countQuery, $params)->fetchColumn();
        
        $totalPages = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        
        $dataQuery = "SELECT s.*, c.name as customer_name FROM sales s LEFT JOIN customers c ON s.customer_id = c.id WHERE {$whereStr} ORDER BY s.sale_date DESC, s.id DESC LIMIT {$perPage} OFFSET {$offset}";
        $data = $this->db->query($dataQuery, $params)->fetchAll();
        
        return [
            'data'         => $data,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => $totalPages,
        ];
    }
}
