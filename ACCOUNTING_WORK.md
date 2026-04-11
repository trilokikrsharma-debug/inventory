# Accounting Work Tracker

Last updated: 2026-04-11

## Objective

Raise TSA Legacy's accounting and billing surface to production-grade SaaS standards for high-scale tenants, with safe lifecycle rules, GST/non-GST correctness, auditability, and fast operator workflows.

## Audit Status

Status: In progress

## Current Findings

### Confirmed strengths

- Sales, purchases, quotations, payments, ledgers, and sale returns are tenant-scoped and store transactional totals rather than recomputing everything from mutable master data.
- GST invoice rendering supports CGST/SGST vs IGST display, HSN visibility, and round-off presentation.
- Sale return cancellation is now first-class and downstream receivable summaries exclude cancelled returns.

### Confirmed risks or gaps

- The product is not yet a full double-entry accounting system. There is no general chart of accounts, trial balance, balance sheet, or universal journal across billing flows.
- GST/non-GST operational billing now has a dedicated tax summary report for date-wise output GST, input GST, non-GST/zero-tax turnover, and net tax payable. It is an operational summary, not a full GST return filing module.
- Non-GST settings previously exposed a fallback tax-rate field even though backend billing intentionally disables tax when GST is off. The UI has been aligned so this no longer suggests unsupported behavior.
- `returns.cancel` has been seeded, but runtime still falls back to `returns.create` for backward compatibility until all tenant roles are refreshed.

### What is already strong

- Sales, purchases, quotations, payments, customer ledgers, supplier ledgers, and sale returns are present and tenant-scoped.
- GST/non-GST controls already exist in settings and invoice rendering, including HSN visibility and CGST/SGST/IGST breakup.
- Sales and purchase line items persist tax snapshots (`tax_rate`, `tax_amount`) instead of relying only on mutable master data.
- Round-off support already exists for billing flows.
- Core transaction tables use soft-delete semantics, which is safer than physical deletion for historical reporting.

### High-priority gaps for 1M-user SaaS readiness

- Product and customer lifecycle needed archive-first behavior instead of treating delete as the normal user path.
- Sale returns currently lack a first-class cancel/void status model; they are posted records without explicit reversal state.
- The app exposes ledgers and operational reporting, but it is not yet a full double-entry accounting system with chart of accounts, trial balance, balance sheet, and general journal across sales/purchase flows.
- GST/non-GST behavior exists, but deeper compliance audit is still pending for edge cases like amendment flows, credit-note conventions, and export/report parity.

## Decisions

- Master data with historical references should be archived, not deleted.
- Transaction data that affects stock or receivables should be cancelled/reversed, not deleted.
- Hard delete remains acceptable only for unused setup/master records and should stay secondary to archive.

## Completed

### 2026-04-11

- Added an accounting audit/work tracker in this file.
- Implemented archive-first lifecycle behavior for products and customers.
- Added explicit archive/restore actions in product and customer screens.
- Changed delete behavior so referenced products/customers are archived instead of failing hard or disappearing from history-sensitive flows.
- Verified the lifecycle slice with `php -l` on touched PHP files, `vendor/bin/phpunit --filter AccountingLifecycleServiceTest --testdox`, and `composer dump-autoload`.
- Implemented first-class sale return cancellation with status, cancellation metadata, reversal-safe stock/customer recalculation, and a dedicated migration.
- Added `returns.cancel` permission seeding with backward-compatible runtime fallback to `returns.create` until all roles are refreshed.
- Applied the schema change with `php cli/migrate.php` and verified sale-return coverage with `vendor/bin/phpunit --filter "SaleReturnLifecycleServiceTest|SaleReturnWorkflowServiceTest" --testdox`.
- Added a GST / Tax Summary report with CSV export support for date-wise sales tax breakup, purchase input tax, non-GST/zero-tax turnover, and net tax payable.
- Added sale-return item tax snapshots, applied `database/053_add_sale_return_tax_snapshots.sql`, and netted posted sale returns out of the GST / Tax Summary output-tax calculation.
- Added purchase-return item tax snapshots, applied `database/054_add_purchase_return_tax_snapshots.sql`, and netted purchase returns out of GST / Tax Summary input-tax calculation.

## In Progress

- Full accounting audit across billing, GST/non-GST, lifecycle, reporting, and reversals.

## Next

- Extend audit into tax edge cases, credit-note/amendment conventions, and high-volume operational concerns.
- Decide whether the product should stay positioned as billing/inventory with ledgers or grow into full double-entry accounting.
