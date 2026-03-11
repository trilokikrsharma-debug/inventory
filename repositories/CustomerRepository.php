<?php
/**
 * Customer Repository
 */
class CustomerRepository extends BaseRepository {
    protected string $table = 'customers';
    
    public function updateBalance(int $customerId, float $amount): bool {
        // Safe atomic update using DB locking if necessary, or numeric addition
        return $this->db->query(
            "UPDATE customers SET 
                current_balance = current_balance + ? 
             WHERE id = ? AND company_id = ?",
            [$amount, $customerId, Tenant::id()]
        )->rowCount() > 0;
    }
}
