-- Warehouse-to-warehouse stock transfer audit trail.

CREATE TABLE IF NOT EXISTS stock_transfers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    transfer_number VARCHAR(40) NOT NULL,
    source_warehouse_id INT UNSIGNED NOT NULL,
    destination_warehouse_id INT UNSIGNED NOT NULL,
    transfer_date DATE NOT NULL,
    reference_number VARCHAR(100) NULL,
    note TEXT NULL,
    item_count INT UNSIGNED NOT NULL DEFAULT 0,
    total_quantity DECIMAL(15,3) NOT NULL DEFAULT 0.000,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_stock_transfers_number (company_id, transfer_number),
    KEY idx_stock_transfers_company_date (company_id, transfer_date),
    KEY idx_stock_transfers_source (company_id, source_warehouse_id),
    KEY idx_stock_transfers_destination (company_id, destination_warehouse_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_transfer_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    transfer_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity DECIMAL(15,3) NOT NULL,
    unit_cost DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_cost DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_stock_transfer_items_transfer (company_id, transfer_id),
    KEY idx_stock_transfer_items_product (company_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
