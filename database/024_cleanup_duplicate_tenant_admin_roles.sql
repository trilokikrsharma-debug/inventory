-- ============================================================================
-- Cleanup duplicate tenant administrator roles
-- Migration: 024_cleanup_duplicate_tenant_admin_roles.sql
--
-- PURPOSE:
--   - Keep one tenant-local administrator-style role per company
--   - Move users + permissions from duplicate admin roles to the canonical role
--   - Remove duplicate tenant admin roles created by older signup/onboarding flows
-- ============================================================================

CREATE TEMPORARY TABLE tmp_canonical_tenant_admin_roles AS
SELECT
    company_id,
    MIN(id) AS canonical_id
FROM roles
WHERE company_id IS NOT NULL
  AND IFNULL(is_super_admin, 0) = 0
  AND (
      LOWER(display_name) = 'administrator'
      OR LOWER(name) IN ('admin', 'administrator', 'owner')
      OR LOWER(name) LIKE 'tenant_admin_%'
  )
GROUP BY company_id;

CREATE TEMPORARY TABLE tmp_duplicate_tenant_admin_roles AS
SELECT
    r.id AS duplicate_id,
    c.canonical_id
FROM roles r
JOIN tmp_canonical_tenant_admin_roles c
  ON c.company_id = r.company_id
WHERE r.company_id IS NOT NULL
  AND IFNULL(r.is_super_admin, 0) = 0
  AND r.id <> c.canonical_id
  AND (
      LOWER(r.display_name) = 'administrator'
      OR LOWER(r.name) IN ('admin', 'administrator', 'owner')
      OR LOWER(r.name) LIKE 'tenant_admin_%'
  );

UPDATE users u
JOIN tmp_duplicate_tenant_admin_roles d
  ON d.duplicate_id = u.role_id
SET u.role_id = d.canonical_id;

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT d.canonical_id, rp.permission_id
FROM role_permissions rp
JOIN tmp_duplicate_tenant_admin_roles d
  ON d.duplicate_id = rp.role_id;

DELETE rp
FROM role_permissions rp
JOIN tmp_duplicate_tenant_admin_roles d
  ON d.duplicate_id = rp.role_id;

DELETE r
FROM roles r
JOIN tmp_duplicate_tenant_admin_roles d
  ON d.duplicate_id = r.id;

DROP TEMPORARY TABLE IF EXISTS tmp_duplicate_tenant_admin_roles;
DROP TEMPORARY TABLE IF EXISTS tmp_canonical_tenant_admin_roles;
