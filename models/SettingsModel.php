<?php
/**
 * Settings Model — Multi-Tenant Aware
 * 
 * Manages per-company settings and application configuration.
 * Each company has its own row in company_settings.
 */
class SettingsModel extends Model {
    protected $table = 'company_settings';
    protected $softDelete = false;
    
    private static $cachedSettings = null;

    /**
     * Get application settings for current tenant (cached per request)
     */
    public function getSettings() {
        if (self::$cachedSettings === null) {
            $cid = Tenant::id();
            if ($cid !== null) {
                self::$cachedSettings = $this->db->query(
                    "SELECT * FROM {$this->table} WHERE company_id = ? LIMIT 1", [$cid]
                )->fetch();
            }
            // SECURITY: Do NOT fall back to another tenant's settings.
            // Return safe defaults if no tenant-specific row exists.
            if (!self::$cachedSettings) {
                self::$cachedSettings = [
                    'company_name' => defined('APP_NAME') ? APP_NAME : 'InvenBill',
                    'currency_symbol' => '₹',
                    'currency_code' => 'INR',
                    'enable_gst' => 1,
                    'enable_tax' => 1,
                    'tax_rate' => 18,
                    'low_stock_threshold' => 10,
                    'invoice_prefix' => 'INV-',
                    'purchase_prefix' => 'PUR-',
                    'payment_prefix' => 'PAY-',
                    'receipt_prefix' => 'REC-',
                ];
            }
        }
        return self::$cachedSettings;
    }

    /**
     * Update settings for current tenant
     */
    public function updateSettings($data) {
        self::$cachedSettings = null;

        $result = $this->db->query("SHOW COLUMNS FROM {$this->table}")->fetchAll();
        $columns = array_column($result, 'Field');
        $data = array_intersect_key($data, array_flip($columns));

        $cid = Tenant::id();
        $settings = $this->getSettings();

        if ($settings && $cid !== null) {
            // Update only own company's settings
            $set = implode(' = ?, ', array_keys($data)) . ' = ?';
            $values = array_values($data);
            $values[] = $settings['id'];
            $values[] = $cid;
            return $this->db->query(
                "UPDATE {$this->table} SET {$set} WHERE id = ? AND company_id = ?", $values
            )->rowCount();
        } elseif ($settings) {
            return $this->update($settings['id'], $data);
        }
        // Create new settings row for this tenant
        if ($cid !== null) $data['company_id'] = $cid;
        return $this->create($data);
    }

    /**
     * Create default settings for a new company
     */
    public function createDefaultSettings($companyId, $companyName) {
        return $this->db->query(
            "INSERT INTO {$this->table} (company_id, company_name, company_email, company_phone, company_address, company_city, company_state, company_country, currency_symbol, currency_code)
             VALUES (?, ?, '', '', '', '', '', 'India', '₹', 'INR')",
            [$companyId, $companyName]
        );
    }

    /**
     * Allowed number types — prevents SQL injection via column name interpolation.
     */
    private static $allowedTypes = ['invoice', 'purchase', 'payment', 'receipt'];

    /**
     * Get next invoice/document number and atomically increment (tenant-scoped)
     */
    public function getNextNumber($type) {
        if (!in_array($type, self::$allowedTypes, true)) {
            throw new Exception("Invalid number type: '{$type}'.");
        }
        $field       = $type . '_next_number';
        $prefixField = $type . '_prefix';

        $maxRetries = 2;
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                return $this->getNextNumberLocked($field, $prefixField);
            } catch (PDOException $e) {
                if ($attempt < $maxRetries && $e->getCode() == '40001') {
                    usleep(50000);
                    continue;
                }
                throw $e;
            }
        }
    }

    private function getNextNumberLocked($field, $prefixField) {
        $db = $this->db;
        $ownTransaction = !$db->getConnection()->inTransaction();

        if ($ownTransaction) {
            $db->beginTransaction();
        }

        try {
            $cid = Tenant::id();
            $where = $cid !== null ? "WHERE company_id = ?" : "";
            $params = $cid !== null ? [$cid] : [];

            $row = $db->query(
                "SELECT id, {$field} AS next_number, {$prefixField} AS prefix FROM {$this->table} {$where} LIMIT 1 FOR UPDATE",
                $params
            )->fetch();

            if (!$row) {
                throw new Exception('Settings row not found.');
            }

            $prefix    = $row['prefix'] ?? '';
            $number    = (int)($row['next_number'] ?? 1);
            $formatted = Helper::generateNumber($prefix, $number);

            $updateParams = [$number + 1, $row['id']];
            $updateSql = "UPDATE {$this->table} SET {$field} = ? WHERE id = ?";
            if ($cid !== null) {
                $updateSql .= " AND company_id = ?";
                $updateParams[] = $cid;
            }
            $db->query($updateSql, $updateParams);

            if ($ownTransaction) {
                $db->commit();
            }
            self::$cachedSettings = null;
            return $formatted;
        } catch (Exception $e) {
            if ($ownTransaction && $db->getConnection()->inTransaction()) {
                $db->rollback();
            }
            throw $e;
        }
    }
}
