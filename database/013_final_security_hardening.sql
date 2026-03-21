-- ==========================================================
-- 013_final_security_hardening.sql
-- InvenBill Pro — Final Security & Performance Migration
-- Date: 2026-03-08
-- ==========================================================

-- ─── Performance Indexes ──────────────────────────────────
-- These indexes support the most common query patterns at scale
-- (1000+ tenants, 100k+ invoices/day)

-- Sales: company + date is the most common dashboard/report query
CREATE INDEX idx_sales_company_date 
    ON sales(company_id, sale_date);

-- Products: filtered by active status in almost every listing
CREATE INDEX idx_products_company_active 
    ON products(company_id, is_active, deleted_at);

-- Payments: filtered by type (payment/receipt) in listings
CREATE INDEX idx_payments_company_type 
    ON payments(company_id, type);

-- Audit trail: queried by company + table + record
CREATE INDEX idx_audit_trail_lookup 
    ON audit_trail(company_id, table_name, record_id);

-- Jobs: worker polls by status + scheduled_at + priority
CREATE INDEX idx_jobs_worker_poll 
    ON jobs(status, scheduled_at, priority);

-- Quotations: company + status for list views
CREATE INDEX idx_quotations_company_status 
    ON quotations(company_id, status);

-- Sale returns: company + date for reports
CREATE INDEX idx_sale_returns_company 
    ON sale_returns(company_id, return_date);


-- ─── Foreign Key Constraints ──────────────────────────────
-- These enforce referential integrity at the database level.
-- Use SET NULL or CASCADE depending on the relationship.
-- Skip if constraints already exist (idempotent).

-- users.company_id → companies.id (SET NULL for super-admins)
ALTER TABLE users
    ADD CONSTRAINT fk_users_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT ON UPDATE CASCADE;

-- sale_items.sale_id → sales.id (CASCADE delete)
ALTER TABLE sale_items
    ADD CONSTRAINT fk_sale_items_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE;

-- purchase_items.purchase_id → purchases.id (CASCADE delete)
ALTER TABLE purchase_items
    ADD CONSTRAINT fk_purchase_items_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE;

-- company_settings.company_id → companies.id (CASCADE delete)
ALTER TABLE company_settings
    ADD CONSTRAINT fk_company_settings_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE;


-- ─── End of Migration ─────────────────────────────────────
