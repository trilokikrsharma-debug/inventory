-- Multi-warehouse foundation:
-- 1. Tenant-scoped warehouse master
-- 2. Per-product warehouse stock buckets
-- 3. Backfill existing stock into each tenant's default warehouse
-- 4. Enable the feature for enterprise plans

CREATE TABLE IF NOT EXISTS warehouses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    code VARCHAR(40) DEFAULT NULL,
    location VARCHAR(190) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uniq_warehouse_company_code (company_id, code),
    KEY idx_warehouses_company_active (company_id, is_active, deleted_at),
    KEY idx_warehouses_company_default (company_id, is_default, deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_warehouse_stock (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    product_id INT NOT NULL,
    warehouse_id INT UNSIGNED NOT NULL,
    quantity DECIMAL(15,3) NOT NULL DEFAULT 0.000,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_product_warehouse_stock (company_id, product_id, warehouse_id),
    KEY idx_pws_company_product (company_id, product_id),
    KEY idx_pws_company_warehouse (company_id, warehouse_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO warehouses (company_id, name, code, location, description, is_default, is_active)
SELECT c.id, 'Main Warehouse', 'MAIN', NULL, 'Default stock bucket created during multi-warehouse rollout.', 1, 1
FROM companies c
LEFT JOIN warehouses w
  ON w.company_id = c.id
 AND w.deleted_at IS NULL
WHERE c.id IS NOT NULL
  AND w.id IS NULL;

UPDATE warehouses w
JOIN (
    SELECT company_id, MIN(id) AS warehouse_id
    FROM warehouses
    WHERE deleted_at IS NULL
    GROUP BY company_id
) picked
  ON picked.company_id = w.company_id
SET w.is_default = CASE WHEN w.id = picked.warehouse_id THEN 1 ELSE 0 END
WHERE w.deleted_at IS NULL;

INSERT INTO product_warehouse_stock (company_id, product_id, warehouse_id, quantity)
SELECT p.company_id, p.id, w.id, p.current_stock
FROM products p
JOIN warehouses w
  ON w.company_id = p.company_id
 AND w.is_default = 1
 AND w.deleted_at IS NULL
LEFT JOIN product_warehouse_stock pws
  ON pws.company_id = p.company_id
 AND pws.product_id = p.id
 AND pws.warehouse_id = w.id
WHERE p.deleted_at IS NULL
  AND p.company_id IS NOT NULL
  AND pws.id IS NULL;

UPDATE saas_plans
SET features = JSON_MERGE_PATCH(
    COALESCE(features, JSON_OBJECT()),
    JSON_OBJECT('multi_warehouse', TRUE)
)
WHERE slug = 'enterprise';
