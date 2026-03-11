<?php
/**
 * Base Repository
 * 
 * Provides foundational data access methods.
 * Specific repositories extend this class.
 */
abstract class BaseRepository {
    protected Database $db;
    protected string $table;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Get a record by ID, scoped to the current tenant if applicable
     */
    public function findById(int $id): ?array {
        $query = "SELECT * FROM {$this->table} WHERE id = ?";
        $params = [$id];

        if (Tenant::id() !== null) {
            $query .= " AND company_id = ?";
            $params[] = Tenant::id();
        }
        
        // Add soft delete check if column exists (assuming all tables have deleted_at for now, adjust as needed per repository)
        // $query .= " AND deleted_at IS NULL"; 

        $result = $this->db->query($query, $params)->fetch();
        return $result ?: null;
    }

    /**
     * Delete a record by ID (Hard delete), scoped to tenant
     */
    public function delete(int $id): bool {
        $query = "DELETE FROM {$this->table} WHERE id = ?";
        $params = [$id];

        if (Tenant::id() !== null) {
            $query .= " AND company_id = ?";
            $params[] = Tenant::id();
        }

        return $this->db->query($query, $params)->rowCount() > 0;
    }
}
