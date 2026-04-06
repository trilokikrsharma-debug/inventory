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
                    'date_format' => 'd-m-Y',
                    'timezone' => 'Asia/Kolkata',
                    'enable_gst' => 0,
                    'enable_tax' => 0,
                    'tax_rate' => 0,
                    'low_stock_threshold' => 10,
                    'invoice_prefix' => 'INV-',
                    'purchase_prefix' => 'PUR-',
                    'payment_prefix' => 'PAY-',
                    'receipt_prefix' => 'REC-',
                    'invoice_title' => 'Invoice',
                    'purchase_invoice_title' => 'Purchase Bill',
                    'invoice_signature_image' => null,
                    'invoice_seal_image' => null,
                    'invoice_show_signature' => 1,
                    'invoice_show_seal' => 1,
                    'invoice_signature_label' => 'Authorised Signatory',
                    'invoice_notes_label' => 'Notes',
                    'invoice_show_logo' => 1,
                    'invoice_show_payment_status' => 1,
                    'show_paid_due_on_invoice' => 0,
                    'show_unit_on_invoice' => 1,
                    'show_discount_on_invoice' => 0,
                    'show_hsn_on_invoice' => 0,
                    'auto_round_off_rupee' => 0,
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
        if (empty($data)) {
            return 0;
        }

        $cid = Tenant::id();
        // Fetch the persisted row directly. getSettings() can return defaults without DB id.
        $settingsRow = null;
        if ($cid !== null) {
            $settingsRow = $this->db->query(
                "SELECT id FROM {$this->table} WHERE company_id = ? LIMIT 1",
                [$cid]
            )->fetch();
        } else {
            $settingsRow = $this->db->query(
                "SELECT id FROM {$this->table} ORDER BY id ASC LIMIT 1"
            )->fetch();
        }

        if ($settingsRow && !empty($settingsRow['id'])) {
            $set = implode(' = ?, ', array_keys($data)) . ' = ?';
            $values = array_values($data);
            $values[] = (int)$settingsRow['id'];

            if ($cid !== null) {
                $values[] = $cid;
                return $this->db->query(
                    "UPDATE {$this->table} SET {$set} WHERE id = ? AND company_id = ?",
                    $values
                )->rowCount();
            }

            return $this->db->query(
                "UPDATE {$this->table} SET {$set} WHERE id = ?",
                $values
            )->rowCount();
        }

        // First-time tenant bootstrap: create settings row.
        if ($cid !== null) {
            $data['company_id'] = $cid;
        }
        return $this->create($data);
    }

    /**
     * Create default settings for a new company
     */
    public function createDefaultSettings($companyId, $companyName) {
        return $this->db->query(
            "INSERT INTO {$this->table} (
                company_id, company_name, company_email, company_phone, company_address, company_city, company_state,
                company_country, currency_symbol, currency_code, date_format, timezone, enable_tax, enable_gst, tax_rate,
                invoice_title, purchase_invoice_title, invoice_signature_image, invoice_seal_image, invoice_show_signature, invoice_show_seal, invoice_signature_label, invoice_notes_label, invoice_show_logo,
                invoice_show_payment_status, show_paid_due_on_invoice, show_unit_on_invoice, show_discount_on_invoice,
                show_hsn_on_invoice, auto_round_off_rupee
            ) VALUES (?, ?, '', '', '', '', '', 'India', '₹', 'INR', 'd-m-Y', 'Asia/Kolkata', 0, 0, 0, 'Invoice', 'Purchase Bill', NULL, NULL, 1, 1, 'Authorised Signatory', 'Notes', 1, 1, 0, 1, 0, 0, 0)",
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
