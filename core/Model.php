<?php
/**
 * Base Model Class — Multi-Tenant Aware
 * 
 * All models extend this class for common database operations.
 * Supports soft deletes, CRUD operations, and AUTOMATIC tenant scoping.
 * 
 * TENANT ISOLATION:
 *   - All queries are automatically scoped by company_id via Tenant::id()
 *   - All inserts automatically include company_id
 *   - Set $tenantScoped = false in models that should NOT be tenant-scoped
 *     (e.g., the Company model itself, or system-level tables)
 * 
 * This is the PRIMARY security boundary for multi-tenant data isolation.
 */
class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $softDelete = true;

    /**
     * Whether this model's queries should be automatically scoped by company_id.
     * Override to false in models like CompanyModel that operate across tenants.
     * 
     * @var bool
     */
    protected $tenantScoped = true;

    /**
     * Columns that can NEVER be set via create() or update().
     * These must be set through direct SQL only.
     * Prevents mass-assignment of security-sensitive fields.
     *
     * @var array
     */
    protected $guarded = ['is_super_admin'];

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // =========================================================
    // TENANT HELPERS
    // =========================================================

    /**
     * Get the current tenant company_id.
     * Returns null if tenant is not resolved or model is not tenant-scoped.
     * 
     * @return int|null
     */
    protected function getCompanyId() {
        if (!$this->tenantScoped) return null;
        return Tenant::id();
    }

    /**
     * Build WHERE clause fragment for tenant scoping.
     * Returns empty string if not tenant-scoped or no tenant.
     * 
     * @param string $alias Optional table alias (e.g., 'p' for products)
     * @return string SQL fragment like "p.company_id = ?" or ""
     */
    protected function tenantWhere($alias = '') {
        $companyId = $this->getCompanyId();
        if ($companyId === null) return '';
        $prefix = $alias ? "{$alias}." : '';
        return "{$prefix}company_id = ?";
    }

    /**
     * Get tenant param array for binding.
     * Returns empty array if not tenant-scoped.
     * 
     * @return array [company_id] or []
     */
    protected function tenantParams() {
        $companyId = $this->getCompanyId();
        if ($companyId === null) return [];
        return [$companyId];
    }

    /**
     * Add company_id to data array if tenant-scoped.
     * Used before INSERT to automatically inject company_id.
     * 
     * @param array $data
     * @return array
     */
    protected function injectCompanyId($data) {
        if ($this->tenantScoped && Tenant::id() !== null) {
            $data['company_id'] = Tenant::id();
        }
        return $data;
    }

    // =========================================================
    // TENANT SAFETY GUARD: Raw Query Wrapper
    // =========================================================

    /**
     * Tenant Safety Guard: Automatically inject company_id into raw queries.
     * Prevents developers from accidentally exposing cross-tenant data.
     * 
     * Usage: $this->safeQuery("SELECT * FROM invoices");
     */
    protected function safeQuery(string $sql, array $params = []) {
        if ($this->tenantScoped && Tenant::id() !== null) {
            $sqlUpper = strtoupper(trim($sql));
            if (str_starts_with($sqlUpper, 'SELECT') || str_starts_with($sqlUpper, 'UPDATE') || str_starts_with($sqlUpper, 'DELETE')) {
                // Only wrap if company_id is not already in the query
                if (strpos($sqlUpper, 'COMPANY_ID') === false) {
                    $tenantId = Tenant::id();
                    
                    if (strpos($sqlUpper, 'WHERE') !== false) {
                        $sql = preg_replace('/WHERE/i', 'WHERE company_id = ? AND ', $sql, 1);
                    } else {
                        // Place WHERE before any grouping or ordering
                        if (preg_match('/(ORDER BY|GROUP BY|LIMIT)/i', $sql, $matches, PREG_OFFSET_CAPTURE)) {
                            $pos = $matches[0][1];
                            $sql = substr_replace($sql, " WHERE company_id = ? ", $pos, 0);
                        } else {
                            $sql .= " WHERE company_id = ?";
                        }
                    }
                    array_unshift($params, $tenantId);
                }
            }
        }
        return $this->db->query($sql, $params);
    }

    // =========================================================
    // SECURITY: ORDER BY Sanitization
    // =========================================================

    /**
     * Sanitize a column name for use in ORDER BY.
     * Only allows alphanumeric, underscores, dots (for table.column).
     * Falls back to primary key if input is suspicious.
     *
     * @param string $orderBy  Raw order column(s)
     * @return string  Sanitized column name
     */
    protected function sanitizeOrderBy($orderBy) {
        // Handle compound like 'id DESC' — extract just the column
        $parts = preg_split('/\s+/', trim($orderBy), 2);
        $column = $parts[0];
        // Whitelist: alphanumeric, underscore, dot only
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_.]*$/', $column)) {
            return $this->primaryKey;
        }
        return $column;
    }

    /**
     * Sanitize ORDER BY direction.
     * Only allows ASC or DESC (case-insensitive).
     *
     * @param string $direction
     * @return string 'ASC' or 'DESC'
     */
    protected function sanitizeDirection($direction) {
        return strtoupper(trim($direction)) === 'ASC' ? 'ASC' : 'DESC';
    }

    // =========================================================
    // CRUD OPERATIONS (Tenant-Scoped)
    // =========================================================

    /**
     * Find record by ID (tenant-scoped)
     */
    public function find($id) {
        $where = ["{$this->primaryKey} = ?"];
        $params = [$id];

        if ($this->tenantScoped && Tenant::id() !== null) {
            $where[] = "company_id = ?";
            $params[] = Tenant::id();
        }
        if ($this->softDelete) {
            $where[] = "deleted_at IS NULL";
        }

        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $where);
        return $this->db->query($sql, $params)->fetch();
    }

    /**
     * Get all records (tenant-scoped)
     */
    public function all($orderBy = 'id', $direction = 'DESC') {
        $where = [];
        $params = [];

        if ($this->tenantScoped && Tenant::id() !== null) {
            $where[] = "company_id = ?";
            $params[] = Tenant::id();
        }
        if ($this->softDelete) {
            $where[] = "deleted_at IS NULL";
        }

        $orderBy = $this->sanitizeOrderBy($orderBy);
        $direction = $this->sanitizeDirection($direction);

        $sql = "SELECT * FROM {$this->table}";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY {$orderBy} {$direction}";
        return $this->db->query($sql, $params)->fetchAll();
    }

    /**
     * Get all active records (tenant-scoped)
     */
    public function allActive($orderBy = 'name', $direction = 'ASC') {
        $where = ["is_active = 1"];
        $params = [];

        if ($this->tenantScoped && Tenant::id() !== null) {
            $where[] = "company_id = ?";
            $params[] = Tenant::id();
        }
        if ($this->softDelete) {
            $where[] = "deleted_at IS NULL";
        }

        $orderBy = $this->sanitizeOrderBy($orderBy);
        $direction = $this->sanitizeDirection($direction);

        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $where);
        $sql .= " ORDER BY {$orderBy} {$direction}";
        return $this->db->query($sql, $params)->fetchAll();
    }

    /**
     * Insert a new record (auto-injects company_id)
     */
    public function create($data) {
        $data = $this->stripGuarded($data);
        $data = $this->injectCompanyId($data);
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $this->db->query($sql, array_values($data));
        return $this->db->lastInsertId();
    }

    /**
     * Update a record (tenant-scoped for safety)
     */
    public function update($id, $data) {
        $data = $this->stripGuarded($data);
        $set = implode(' = ?, ', array_keys($data)) . ' = ?';
        $where = ["{$this->primaryKey} = ?"];
        $values = array_values($data);
        $values[] = $id;

        if ($this->tenantScoped && Tenant::id() !== null) {
            $where[] = "company_id = ?";
            $values[] = Tenant::id();
        }

        $sql = "UPDATE {$this->table} SET {$set} WHERE " . implode(' AND ', $where);
        return $this->db->query($sql, $values)->rowCount();
    }

    /**
     * Soft delete a record (tenant-scoped)
     */
    public function delete($id) {
        if ($this->softDelete) {
            return $this->update($id, ['deleted_at' => date(DATETIME_FORMAT_DB)]);
        }
        return $this->hardDelete($id);
    }

    /**
     * Permanently delete a record (tenant-scoped)
     */
    public function hardDelete($id) {
        $where = ["{$this->primaryKey} = ?"];
        $params = [$id];

        if ($this->tenantScoped && Tenant::id() !== null) {
            $where[] = "company_id = ?";
            $params[] = Tenant::id();
        }

        $sql = "DELETE FROM {$this->table} WHERE " . implode(' AND ', $where);
        return $this->db->query($sql, $params)->rowCount();
    }

    /**
     * Count records (tenant-scoped)
     */
    public function count($conditions = '', $conditionParams = []) {
        $where = [];
        $params = [];

        if ($this->tenantScoped && Tenant::id() !== null) {
            $where[] = "company_id = ?";
            $params[] = Tenant::id();
        }
        if ($this->softDelete) {
            $where[] = "deleted_at IS NULL";
        }
        if ($conditions) {
            $where[] = $conditions;
            $params = array_merge($params, $conditionParams);
        }

        $sql = "SELECT COUNT(*) FROM {$this->table}";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        return $this->db->query($sql, $params)->fetchColumn();
    }

    /**
     * Get records with WHERE clause (tenant-scoped)
     */
    public function where($conditions, $params = [], $orderBy = 'id DESC') {
        $where = [$conditions];

        if ($this->tenantScoped && Tenant::id() !== null) {
            $where[] = "company_id = ?";
            $params[] = Tenant::id();
        }
        if ($this->softDelete) {
            $where[] = "deleted_at IS NULL";
        }

        // Sanitize ORDER BY
        $orderParts = preg_split('/\s+/', trim($orderBy), 2);
        $col = $this->sanitizeOrderBy($orderParts[0] ?? 'id');
        $dir = $this->sanitizeDirection($orderParts[1] ?? 'DESC');

        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $where);
        $sql .= " ORDER BY {$col} {$dir}";
        return $this->db->query($sql, $params)->fetchAll();
    }

    /**
     * Get single record with WHERE clause (tenant-scoped)
     */
    public function findWhere($conditions, $params = []) {
        $where = [$conditions];

        if ($this->tenantScoped && Tenant::id() !== null) {
            $where[] = "company_id = ?";
            $params[] = Tenant::id();
        }
        if ($this->softDelete) {
            $where[] = "deleted_at IS NULL";
        }

        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $where);
        $sql .= " LIMIT 1";
        return $this->db->query($sql, $params)->fetch();
    }

    /**
     * Paginate results (tenant-scoped)
     */
    public function paginate($page = 1, $perPage = RECORDS_PER_PAGE, $conditions = '', $params = [], $orderBy = 'id DESC') {
        $offset = max(0, ($page - 1) * $perPage);
        $perPage = max(1, min(100, (int)$perPage)); // Cap per-page to 100
        
        // Build WHERE
        $whereClauses = [];
        $countParams = [];

        if ($this->tenantScoped && Tenant::id() !== null) {
            $whereClauses[] = "company_id = ?";
            $countParams[] = Tenant::id();
        }
        if ($this->softDelete) {
            $whereClauses[] = "deleted_at IS NULL";
        }
        if ($conditions) {
            $whereClauses[] = $conditions;
        }

        // Merge tenant params before user-provided params
        $allParams = array_merge($countParams, $params);

        // Sanitize ORDER BY
        $orderParts = preg_split('/\s+/', trim($orderBy), 2);
        $col = $this->sanitizeOrderBy($orderParts[0] ?? 'id');
        $dir = $this->sanitizeDirection($orderParts[1] ?? 'DESC');

        // Count
        $countSql = "SELECT COUNT(*) FROM {$this->table}";
        if (!empty($whereClauses)) {
            $countSql .= " WHERE " . implode(' AND ', $whereClauses);
        }
        $total = $this->db->query($countSql, $allParams)->fetchColumn();

        // Data
        $sql = "SELECT * FROM {$this->table}";
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }
        $sql .= " ORDER BY {$col} {$dir} LIMIT {$perPage} OFFSET {$offset}";
        $data = $this->db->query($sql, $allParams)->fetchAll();

        return [
            'data'       => $data,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => ceil($total / $perPage),
        ];
    }

    // =========================================================
    // Security Helpers
    // =========================================================

    /**
     * Remove guarded columns from a data array.
     * Called automatically by create() and update().
     *
     * @param array $data
     * @return array  Cleaned data with guarded keys removed
     */
    protected function stripGuarded($data) {
        if (empty($this->guarded)) return $data;
        return array_diff_key($data, array_flip($this->guarded));
    }
}
