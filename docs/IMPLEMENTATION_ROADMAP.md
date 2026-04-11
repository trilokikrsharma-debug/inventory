# Implementation Roadmap

Current implementation state in this worktree:

- Plan feature cleanup and quota alignment are in place
- Backup/restore flow has been hardened and centralized in `BackupService`
- Product and contact bulk import foundations are present
- Product and customer custom fields are present
- Multi-warehouse foundations, transfers, and reporting are present
- HR foundations now include employee master, attendance, leave, holidays, shifts, leave balances, and payroll
- Public SEO/blog assets and controller scaffolding are present

Current verified status:

- PHPUnit: `78 tests, 192 assertions, OK`
- Main covered areas: backup restore safety, imports, custom fields, API management controller rules, warehouse stock normalization, warehouse controller success/error branches, HR attendance policy, HR controller success branches, HR leave accrual, HR leave transition rules, HR payroll transition rules, HR statutory payroll

## Remaining Priority Order

Focus next on integration hardening rather than new module sprawl:

1. `api_access`
2. `ai_insights`
3. HR workflow polish
4. Warehouse workflow polish
5. Documentation and migration rollout checks

## Why This Order

- `api_access`: roadmap item still not visibly completed end-to-end
- `ai_insights`: premium feature exists in the product surface and should stay explicitly gated, but user-facing copy should describe it as automated or rule-based insights rather than implying an advanced AI system
- HR polish: module breadth is now large enough that approvals, reporting, and UX consistency matter more than more tables
- Warehouse polish: core transfer/reporting exists, but operational edge cases matter before adding more inventory scope
- Documentation and rollout: the migration set is now large and needs careful operator guidance

## Notes For Next Work

### `api_access`

- Verify token CRUD, scope enforcement, last-used tracking, and revoke flow
- Confirm plan gating uses the same feature key in billing, middleware, and UI
- Replace any remaining informational-only API screens

### `ai_insights`

- Verify route, sidebar, and page gates all use the same feature flag
- Keep insights deterministic and analytics-based unless an external dependency is explicitly introduced

### HR polish

- Add controller-level and feature-level integration tests around approval and payroll transitions
- Review permission boundaries between HR managers and finance/report users
- Check payroll payout journal linkage and report exports against real migration state

### Warehouse polish

- Extend controller coverage beyond current guard/error branches into successful approval, rejection, and search-product flows
- Review purchase/sale warehouse defaults and transfer audit visibility
- Add broader coverage for controller flows, not just service normalization

### Test baseline

- Recent hardening added coverage for:
- `ApiController`: tenant-only management access, granular token validation, expiry parsing
- `HrController`: successful payroll approval and manager leave approval actions
- `HrLeaveRequest`: manager/final approval sequencing rules
- `HrPayroll`: approved-to-paid transition and locked-run edit protection
- `WarehouseController`: tenant guard, same-source transfer rejection, approval/rejection success paths, approval/rejection error propagation

### Rollout checks

- Verify `cli/migrate.php` ordering against migrations `021` through `051`
- Remove temporary/debug artifacts immediately when used
- Keep this roadmap synced with shipped work so it reflects the real baseline
