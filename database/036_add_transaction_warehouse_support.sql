-- Add transaction-level warehouse selection for purchases and sales.

ALTER TABLE purchases
    ADD COLUMN warehouse_id INT UNSIGNED NULL AFTER supplier_id,
    ADD INDEX idx_purchases_warehouse_id (warehouse_id);

ALTER TABLE sales
    ADD COLUMN warehouse_id INT UNSIGNED NULL AFTER customer_id,
    ADD INDEX idx_sales_warehouse_id (warehouse_id);

ALTER TABLE purchase_items
    ADD COLUMN warehouse_id INT UNSIGNED NULL AFTER product_id,
    ADD INDEX idx_purchase_items_warehouse_id (warehouse_id),
    ADD INDEX idx_purchase_items_purchase_warehouse (purchase_id, warehouse_id);

ALTER TABLE sale_items
    ADD COLUMN warehouse_id INT UNSIGNED NULL AFTER product_id,
    ADD INDEX idx_sale_items_warehouse_id (warehouse_id),
    ADD INDEX idx_sale_items_sale_warehouse (sale_id, warehouse_id);

UPDATE purchases p
JOIN warehouses w
  ON w.company_id = p.company_id
 AND w.is_default = 1
 AND w.deleted_at IS NULL
SET p.warehouse_id = w.id
WHERE p.warehouse_id IS NULL
  AND p.deleted_at IS NULL;

UPDATE sales s
JOIN warehouses w
  ON w.company_id = s.company_id
 AND w.is_default = 1
 AND w.deleted_at IS NULL
SET s.warehouse_id = w.id
WHERE s.warehouse_id IS NULL
  AND s.deleted_at IS NULL;

UPDATE purchase_items pi
JOIN purchases p
  ON p.id = pi.purchase_id
 AND p.company_id = pi.company_id
SET pi.warehouse_id = p.warehouse_id
WHERE pi.warehouse_id IS NULL;

UPDATE sale_items si
JOIN sales s
  ON s.id = si.sale_id
 AND s.company_id = si.company_id
SET si.warehouse_id = s.warehouse_id
WHERE si.warehouse_id IS NULL;
