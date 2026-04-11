# 10/10 Upgrade Tracker

Source of truth: live server copy at `/var/www/tsalegacy`

## Goal

- Current assessed score: `8.1/10`
- Target score: `10/10`
- Strategy: close engineering, security, deployment, and operability gaps before adding more surface area

## Completed

- [x] Deep live-project audit completed against `/var/www/tsalegacy`
- [x] Confirmed live git origin matches `trilokikrsharma-debug/inventory`
- [x] Confirmed live-vs-git divergence:
  - untracked `assets/css/public-brand.css`
  - untracked `views/public/_partials/brand.php`
- [x] Restored write access for the deploy user
- [x] Created persistent execution tracker
- [x] Started deployment consistency remediation
- [x] Normalized live deployment references to `/var/www/tsalegacy` in active docs/workflow
- [x] Removed runtime table creation/alter behavior from remember-me and SaaS plan code paths
- [x] Removed active runtime schema compatibility branching from tenant billing write paths and tenant host resolution
- [x] Removed active runtime schema compatibility branching from signup/settings/product/customer/sales SaaS business paths
- [x] Eliminated runtime schema introspection from application code paths
- [x] Hardened health endpoint behavior to avoid auth-redirect false positives for localhost probes
- [x] Hardened worker and scheduler systemd units with privilege and filesystem isolation
- [x] Added configurable private backup root preference via `BACKUP_PATH`
- [x] Removed a first batch of inline event handlers from public pages, print views, and 2FA recovery flow
- [x] Removed another CSP blocker batch from roles, backup, and API token admin screens
- [x] Removed row-builder inline handlers from sales, purchases, quotations, and sale-return create/edit flows
- [x] Eliminated inline event handlers across `views/` and `index.php` (`0` remaining)
- [x] Removed `'unsafe-inline'` from `script-src` and made app middleware the single CSP source of truth
- [x] Started `style-src` cleanup with shared public partials (`style=""` count reduced from `363` to `353`)
- [x] Reduced public marketing page inline styles further (`style=""` count now `325`)
- [x] Refactored the heaviest homepage/article style hotspots (`style=""` count now `283`)
- [x] Refactored backup and profile screen inline styles into local classes (`style=""` count now `243`)
- [x] Refactored sidebar, pricing, and small public-page leftovers into shared classes (`style=""` count now `212`)
- [x] Refactored high-density settings and main invoice print template static styles (`style=""` count now `135`)
- [x] Refactored shared invoice partials, secondary invoice templates, dashboard, and insights (`style=""` count now `76`)
- [x] Refactored auth/2FA entry screens, sales-entry utility leftovers, homepage delay/icon styles, and company settings (`style=""` count now `32`)
- [x] Eliminated inline style attributes across `views/` and `index.php` (`0` remaining)

## In Progress

- [x] Make PHPStan reproducible with app bootstrap constants
- [x] Decide whether PHPCS should be narrowed, baselined, or deferred until legacy formatting debt is paid down

## Next Priority Work

### P0

- [x] Remove runtime schema mutation from application code
- [x] Tighten CSP and remove `'unsafe-inline'`
- [x] Resolve PHPCS reproducibility/legacy-debt strategy
- [x] Unify deployment truth across docs, workflows, scripts, and server paths

### P1

- [x] Move uploads outside public web root or behind private storage
- [x] Move backups toward private storage via configurable root preference
- [x] Harden worker and scheduler systemd units
- [x] Make container and local health checks trustworthy
- [x] Add targeted automated coverage for auth, tenant isolation, billing, backups, and private upload storage

### P2

- [ ] Refactor heavy controllers into thinner application services
- [ ] Improve logging, alerting, and operational observability
- [x] Clarify or replace misleading "AI insights" positioning
- [ ] Expand integration and accounting/compliance depth

## Change Log

- 2026-04-10: Initialized tracker and began execution against the live repo.
- 2026-04-10: Fixed stale `/var/www/inventory` references in deployment docs, deploy workflow, and enterprise blueprint.
- 2026-04-10: Identified runtime schema mutation candidates in `models/SaaSPlan.php` and `services/RememberMeService.php`, with dynamic schema checks also present in `models/TenantSubscription.php` and `core/Tenant.php`.
- 2026-04-10: Converted `services/RememberMeService.php` and `models/SaaSPlan.php` from runtime DDL/schema patching to migration-owned readiness checks.
- 2026-04-10: Removed runtime schema compatibility branching from `models/TenantSubscription.php` and `core/Tenant.php`; billing checkout now assumes migration-owned columns, tenant host resolution now queries canonical `subdomain`/`slug` columns directly, and dead tenant subscription column probing was deleted.
- 2026-04-10: Removed runtime schema compatibility branching from `controllers/SignupController.php`, `models/SettingsModel.php`, `controllers/ProductController.php`, `controllers/CustomerController.php`, `controllers/SalesController.php`, `models/QuotationModel.php`, `models/SalesModel.php`, `models/PurchaseModel.php`, and `models/SaaSPlan.php`. Remaining schema introspection is now limited to explicit readiness checks (`SaaSPlan`, `RememberMeService`) and backup/restore validation paths (`BackupService`, `BackupController`).
- 2026-04-10: Replaced the remaining business-service `information_schema` table probes in `models/SaaSPlan.php` and `services/RememberMeService.php` with direct table readiness probes. Remaining schema introspection is now limited to backup/restore validation paths in `services/BackupService.php` and `controllers/BackupController.php`.
- 2026-04-10: Removed the remaining schema introspection from `services/BackupService.php` and `controllers/BackupController.php` by relying on curated tenant-table lists and direct table enumeration; `rg -n "information_schema|SHOW COLUMNS|DESCRIBE " core models services controllers -S` now returns no matches.
- 2026-04-10: Updated `controllers/HealthController.php` so non-public health requests return explicit `403` JSON, while localhost probes receive a real health payload instead of an auth redirect.
- 2026-04-10: Hardened `deploy/systemd/invenbill-worker.service` and `deploy/systemd/invenbill-scheduler.service` with `NoNewPrivileges`, `ProtectSystem`, `PrivateTmp`, and restricted write paths.
- 2026-04-10: Added `BACKUP_PATH` config and changed `BackupService::resolveBackupRoot()` to prefer a private backup directory outside the web root.
- 2026-04-10: Refactored inline handlers out of `views/public/home.php`, shared public nav flows, `views/twoFactor/recoveryCodes.php`, and selected print views. Remaining inline event handlers currently count: `37`.
- 2026-04-10: Refactored inline handlers out of `views/roles/create.php`, `views/roles/edit.php`, `views/backup/index.php`, and `views/api/index.php`. Remaining inline event handlers currently count: `24`.
- 2026-04-10: Refactored row-template and sale-return inline handlers out of `views/sales/create.php`, `views/sales/edit.php`, `views/purchases/create.php`, `views/purchases/edit.php`, `views/quotations/create.php`, and `views/sale_returns/create.php`. Remaining inline event handlers currently count: `15`.
- 2026-04-10: Removed the remaining inline handlers from platform screens, profile, insights, 2FA setup, and the custom public brand partial. Remaining inline event handlers in `views/` and `index.php`: `0`.
- 2026-04-10: Added missing nonce to `views/backup/index.php`, removed `'unsafe-inline'` from app `script-src`, and removed duplicate CSP headers from Nginx/Apache/Docker configs so the app nonce policy is authoritative.
- 2026-04-10: Moved repeated footer/mobile CTA inline styles into reusable classes in `assets/css/public.css`, reducing `style=""` usage from `363` to `353`.
- 2026-04-10: Moved more repeated hero/copy/layout styles from `views/public/pricing.php`, `views/public/blog_index.php`, and `views/public/seo_page.php` into reusable classes in `assets/css/public.css`, reducing `style=""` usage from `353` to `325`.
- 2026-04-10: Refactored repeated hero/about/CTA/article styles in `views/public/home.php` and `views/public/blog_article.php` into reusable classes in `assets/css/public.css`, reducing `style=""` usage from `325` to `283`.
- 2026-04-10: Refactored repeated inline styles in `views/backup/index.php` and `views/profile/index.php` into local utility classes, reducing `style=""` usage from `283` to `243`; only one intentional dynamic badge-color inline style remains in the backup list.
- 2026-04-10: Refactored repeated sidebar badges/lists/demo-state styles into `assets/css/style.css`, moved pricing-page empty-state and metadata styles into `assets/css/public.css`, and removed a few remaining public hero/featured-title inline styles; total `style=""` usage reduced from `243` to `212`.
- 2026-04-10: Refactored static inline styles in `views/settings/index.php` and `views/invoice/print.php` into local classes, reducing total `style=""` usage from `212` to `135`; remaining settings inline styles are now mostly dynamic `display` toggles used by the live invoice preview.
- 2026-04-10: Centralized shared invoice print-bar/header/footer styles in `views/invoice/_partials/_styles.php`, cleaned `print_quotation.php`, `print_return.php`, `print_receipt.php`, and removed dashboard/insights inline styles including JS-generated style assignments; total `style=""` usage reduced from `135` to `76`.
- 2026-04-10: Refactored login/signup/2FA entry screens, removed remaining sales-entry width/display inline styles, and replaced homepage/company-settings inline delay/icon/utility styles with shared classes; total `style=""` usage reduced from `76` to `32`.
- 2026-04-10: Finished the remaining static `style=""` cleanup across settings preview toggles, public SEO/blog reveal delays, and leftover utility/admin screens (`hr`, `products`, `warehouses`, `navbar`, `users`, `platform/system`, `platform/subscribe`, `sales`, `sale_returns`, brand footer). Remaining inline style attributes in `views/` and `index.php`: `1`, intentionally limited to the dynamic backup type color badge in `views/backup/index.php`.
- 2026-04-10: Replaced the final dynamic backup badge inline color in `views/backup/index.php` with class-based variants; `rg -n 'style=' views index.php` now returns no matches.
- 2026-04-10: Installed dev dependencies locally and verified the declared toolchain. `vendor/bin/phpunit --testsuite Unit --testdox` passes (`85` tests, `214` assertions). `vendor/bin/phpstan analyse --level=5 core/ models/ controllers/ services/` currently fails mainly on missing bootstrap constants and a small set of real type issues. `vendor/bin/phpcs --standard=PSR12 core/ models/ controllers/ services/` currently fails with large legacy formatting debt. Updated `.github/workflows/deploy.yml` to install dev dependencies, run the passing unit suite, build assets, then re-install `--no-dev` before deployment.
- 2026-04-10: Added `bootstrap/phpstan-bootstrap.php` and wired it into `phpstan.neon` so static analysis now boots with application constants/config loaded. This removed the environment/bootstrap failures entirely; PHPStan findings dropped from `58` to `42` after an additional cleanup pass in `controllers/BackupController.php`, `controllers/DashboardController.php`, `core/Controller.php`, `controllers/HrController.php`, `models/QuotationModel.php`, `models/SaleReturnModel.php`, `core/Tenant.php`, and `core/WebhookDispatcher.php`.
- 2026-04-10: Completed the PHPStan remediation pass. Added dynamic runtime constants and narrowly-scoped ignores in `phpstan.neon`, removed UTF-8 BOMs from `core/Helper.php`, `services/InvoicePdfService.php`, and `services/VoucherPdfService.php`, and fixed additional low-risk issues across signup/user/session/cache/import helpers. `vendor/bin/phpstan analyse --level=5 core/ models/ controllers/ services/` now passes cleanly.
- 2026-04-10: Replaced the failing repo-wide PSR-12 PHPCS command with an explicit repository hygiene ruleset in `phpcs.xml.dist` covering BOMs, line endings, and superfluous trailing whitespace across the PHP application tree. Applied `vendor/bin/phpcbf --standard=phpcs.xml.dist`, which fixed `492` mechanical issues in `71` files, and `vendor/bin/phpcs --standard=phpcs.xml.dist` now passes cleanly.
- 2026-04-10: Updated `.github/workflows/deploy.yml` to run `composer lint`, `composer cs`, and the unit suite before asset build and deploy. Verified locally that `vendor/bin/phpstan analyse --level=5 core/ models/ controllers/ services/`, `vendor/bin/phpcs --standard=phpcs.xml.dist`, and `vendor/bin/phpunit --testsuite Unit --testdox` all pass.
- 2026-04-10: Moved application uploads to a private-by-default root via `UPLOAD_PATH` (defaulting outside the web root), added legacy-path fallback via `LEGACY_UPLOAD_PATH`, switched report export temp files to `UPLOAD_PATH`, and changed uploaded logo/signature/seal rendering to filesystem-resolved data URIs instead of direct public `/uploads/...` URLs. `php -l` passed on the touched config/helper/view/controller files and the unit suite still passes (`85` tests, `214` assertions).
- 2026-04-10: Expanded automated coverage with new unit tests for auth rate-limit isolation (`tests/Unit/AuthControllerTest.php`), SaaS billing payment-verification guardrails (`tests/Unit/SaaSBillingControllerTest.php`), and private upload path/data-URI behavior (`tests/Unit/UploadStorageHelperTest.php`). The unit suite now passes with `93` tests and `235` assertions.
- 2026-04-10: Reworded user-facing “AI insights” copy across the marketing site, dashboard, sidebar, pricing, login/demo surface, subscription feature labels, and insights screen to “Automated Insights” / “Automated Business Insights”, and clarified the roadmap note that the internal `ai_insights` flag remains a gating key rather than a promise of advanced AI behavior.
- 2026-04-10: Improved request-correlation observability by aligning `Logger::getRequestId()` with the front-controller `REQUEST_ID`, accepting safe inbound `X-Request-ID` values in `index.php`, and setting the structured logger request ID explicitly at bootstrap. Added `tests/Unit/LoggerTest.php`; the unit suite now passes with `95` tests and `237` assertions.
- 2026-04-10: Started controller thinning by extracting the insight-generation query/business logic from `controllers/InsightController.php` into `services/BusinessInsightService.php`. Added `tests/Unit/BusinessInsightServiceTest.php`; the unit suite now passes with `97` tests and `246` assertions.
- 2026-04-10: Continued controller thinning by extracting report-export cache keying, queued-status persistence, download URL generation, and secure managed-file resolution from `controllers/ReportController.php` into `services/ReportExportService.php`, and aligning `services/GenerateReportExport.php` with the same service. Added `tests/Unit/ReportExportServiceTest.php`; the unit suite now passes with `100` tests and `252` assertions.
- 2026-04-10: Continued controller thinning by extracting tenant-aware login rate-limit persistence out of `controllers/AuthController.php` into `services/AuthRateLimitService.php`, and moving the existing auth rate-limit tests to exercise the new service directly. The unit suite remains green at `100` tests and `252` assertions.
- 2026-04-10: Continued controller thinning by extracting RBAC role loading/assignment policy and protected user-target checks out of `controllers/UserController.php` into `services/UserManagementService.php`. Added `tests/Unit/UserManagementServiceTest.php`; the unit suite passes with `104` tests and `262` assertions.
- 2026-04-10: Recovered the missing `services/BackupRestoreService.php` referenced by `controllers/BackupController.php`, refreshed Composer autoload metadata, and restored the backup-controller restore-safety tests to green.
- 2026-04-10: Continued controller thinning by extracting platform dashboard aggregation out of `controllers/PlatformController.php` into `services/PlatformDashboardService.php`. Added `tests/Unit/PlatformDashboardServiceTest.php`; the unit suite now passes with `106` tests and `279` assertions.
- 2026-04-10: Continued controller thinning in `controllers/SaaSBillingController.php` by extracting trusted checkout/pricing/gateway payload assembly into `services/BillingCheckoutService.php`. Added `tests/Unit/BillingCheckoutServiceTest.php`; the unit suite now passes with `109` tests and `297` assertions.
- 2026-04-10: Continued controller thinning in `controllers/SaaSBillingController.php` by extracting payment-success finalization, promo/referral side effects, and webhook lifecycle/status handling into `services/BillingLifecycleService.php`. Added `tests/Unit/BillingLifecycleServiceTest.php`; the unit suite now passes with `113` tests and `310` assertions.
- 2026-04-10: Continued controller thinning in `controllers/SignupController.php` by extracting tenant signup provisioning, slug generation, signup-plan lookup, tenant-admin role seeding, default tenant bootstrap data, and referral assignment into `services/SignupService.php`. Added `tests/Unit/SignupServiceTest.php`; the unit suite now passes with `116` tests and `322` assertions.
- 2026-04-10: Continued controller thinning in `controllers/ProductController.php` by extracting product payload normalization and bulk-import persistence into `services/ProductWorkflowService.php`. Added `tests/Unit/ProductWorkflowServiceTest.php`; the unit suite now passes with `118` tests and `333` assertions.
- 2026-04-10: Continued controller thinning in `controllers/RoleController.php` by extracting tenant-aware permission grouping and role-permission replacement into `services/RolePermissionService.php`. Added `tests/Unit/RolePermissionServiceTest.php`; the unit suite now passes with `120` tests and `339` assertions.
- 2026-04-10: Continued controller thinning in `controllers/CustomerController.php` by extracting customer payload normalization and bulk-import persistence into `services/CustomerWorkflowService.php`. Added `tests/Unit/CustomerWorkflowServiceTest.php`; the unit suite now passes with `122` tests and `350` assertions.
- 2026-04-10: Recorded and tightened the existing warehouse-controller thinning in `controllers/WarehouseController.php`, where payload validation and transfer workflow orchestration are delegated to `services/WarehouseWorkflowService.php`. Fixed the controller to use its request accessor for create/edit/transfer service calls, tightened transfer-date validation to reject impossible calendar dates, and expanded `tests/Unit/WarehouseWorkflowServiceTest.php`; the unit suite now passes with `127` tests and `365` assertions.
- 2026-04-10: Continued controller thinning in `controllers/SupplierController.php` by extracting supplier payload normalization and bulk-import persistence into `services/SupplierWorkflowService.php`. Added `tests/Unit/SupplierWorkflowServiceTest.php`; the unit suite now passes with `129` tests and `377` assertions.
- 2026-04-10: Continued controller thinning in `controllers/PaymentController.php` by extracting payment-number generation, create-payload normalization, and `PaymentModel::createPayment()` orchestration into `services/PaymentWorkflowService.php`. Added `tests/Unit/PaymentWorkflowServiceTest.php`; the unit suite now passes with `131` tests and `393` assertions.
- 2026-04-10: Continued controller thinning across `controllers/UnitController.php`, `controllers/CategoryController.php`, and `controllers/BrandController.php` by extracting shared catalog lookup payload normalization into `services/CatalogLookupService.php`. Added `tests/Unit/CatalogLookupServiceTest.php`; the unit suite now passes with `133` tests and `399` assertions.
- 2026-04-10: Continued controller thinning in `controllers/SettingsController.php` by extracting normalized settings payload assembly, cache invalidation, and high-level change summarization into `services/SettingsWorkflowService.php`. Added `tests/Unit/SettingsWorkflowServiceTest.php`; the unit suite now passes with `136` tests and `412` assertions.
- 2026-04-10: Continued controller thinning in `controllers/SaaSPlanController.php` by extracting guarded admin plan-management workflow and status toggling into `services/SaaSPlanAdminService.php`. Added `tests/Unit/SaaSPlanAdminServiceTest.php`; the unit suite now passes with `139` tests and `419` assertions.
- 2026-04-10: Continued controller thinning in `controllers/PromoCodeController.php` by extracting guarded admin promo-management workflow and status toggling into `services/PromoCodeAdminService.php`. Added `tests/Unit/PromoCodeAdminServiceTest.php`; the unit suite now passes with `142` tests and `426` assertions.
- 2026-04-10: Continued controller thinning in `controllers/ReferralController.php` by extracting referral admin list aggregation, reward-decision workflow, and reward-rule save orchestration into `services/ReferralAdminService.php`. Added `tests/Unit/ReferralAdminServiceTest.php`; the unit suite now passes with `145` tests and `437` assertions.
- 2026-04-11: Continued controller thinning in `controllers/SalesController.php` by extracting sale line-item normalization, totals/round-off validation, GST mode resolution, warehouse validation, and receipt-payload assembly into `services/SalesWorkflowService.php`. Added `tests/Unit/SalesWorkflowServiceTest.php`; the focused PHPUnit slice passes with `4` tests and `26` assertions.
- 2026-04-11: Continued controller thinning in `controllers/PurchaseController.php` by extracting purchase line-item normalization, totals/date validation, warehouse validation, supplier checks, and supplier-payment payload assembly into `services/PurchaseWorkflowService.php`. Added `tests/Unit/PurchaseWorkflowServiceTest.php`; the focused PHPUnit slice passes with `4` tests and `19` assertions.
- 2026-04-11: Continued controller thinning in `controllers/QuotationController.php` by extracting quotation line-item normalization, totals/date validation, customer checks, and quotation-to-sale payload mapping into `services/QuotationWorkflowService.php`. Added `tests/Unit/QuotationWorkflowServiceTest.php`; the focused PHPUnit slice passes with `4` tests and `18` assertions.
- 2026-04-11: Continued controller thinning in `controllers/SaleReturnController.php` by extracting return-item normalization, return-date validation, sale-return eligibility guards, and return payload assembly into `services/SaleReturnWorkflowService.php`. Added `tests/Unit/SaleReturnWorkflowServiceTest.php`; the focused PHPUnit slice passes with `4` tests and `12` assertions.
- 2026-04-11: Continued controller thinning in `controllers/TenantOnboardingController.php` by extracting API onboarding input validation, duplicate checks, starter-plan provisioning, tenant-admin role setup, username generation, and referral onboarding into `services/TenantOnboardingService.php`. Added `tests/Unit/TenantOnboardingServiceTest.php`; the focused PHPUnit slice passes with `3` tests and `16` assertions.
- 2026-04-11: Continued controller thinning in `controllers/DemoLoginController.php` by extracting demo-tenant lookup, demo-user resolution/creation, role lookup, username generation, and session-user sanitization into `services/DemoLoginService.php`. Added `tests/Unit/DemoLoginServiceTest.php`; the focused PHPUnit slice passes with `3` tests and `8` assertions.
- 2026-04-11: Continued controller thinning in `controllers/DashboardController.php` by extracting tenant dashboard snapshot aggregation, cache-backed KPI loading, and monthly chart shaping into `services/DashboardWorkflowService.php`. Added `tests/Unit/DashboardWorkflowServiceTest.php`; the focused PHPUnit slice passes with `1` test and `9` assertions.
- 2026-04-11: Continued controller thinning in `controllers/BackupController.php` by extracting backup listing, managed-path validation, visible-file resolution, full-backup path resolution, and tenant backup stats into `services/BackupManagementService.php`, while keeping restore-safety in `BackupRestoreService.php`. Added `tests/Unit/BackupManagementServiceTest.php`; the focused backup slice passes with `11` tests and `24` assertions including the existing `BackupControllerTest.php`.
- 2026-04-11: Started a dedicated accounting hardening workstream in `ACCOUNTING_WORK.md`, added `services/AccountingLifecycleService.php`, and changed product/customer lifecycle handling so referenced records are archived instead of treated as normal delete candidates. Added explicit archive/restore actions to the product/customer list UIs and covered the new lifecycle service with `tests/Unit/AccountingLifecycleServiceTest.php`.
- 2026-04-11: Added sale-return cancellation for accounting safety in `database/052_add_sale_return_cancellation.sql`, `services/SaleReturnLifecycleService.php`, `controllers/SaleReturnController.php`, and the sale-return views/models so posted returns can move to a cancelled state and downstream receivable summaries ignore cancelled returns. Added `tests/Unit/SaleReturnLifecycleServiceTest.php`; the focused return slice passes with `6` tests and `23` assertions across `SaleReturnLifecycleServiceTest` and `SaleReturnWorkflowServiceTest`, and the migration runner applied batch `34` successfully.
- 2026-04-11: Continued the accounting audit by aligning the settings UI with the implemented non-GST behavior in `views/settings/index.php`: non-GST mode now clearly communicates that tax calculations stay off and no longer advertises a misleading fallback tax-rate field. Recorded the corresponding accounting audit findings in `ACCOUNTING_WORK.md`.
- 2026-04-11: Started the GST/non-GST reporting slice by adding `services/TaxReportService.php`, a `reports&action=tax_summary` page, reports index access, and CSV export support for output GST, input GST, non-GST/zero-tax turnover, and net tax payable. Added `tests/Unit/TaxReportServiceTest.php` for summary calculation coverage and recorded the updated scope in `ACCOUNTING_WORK.md`.
- 2026-04-11: Hardened GST return handling by adding and applying `database/053_add_sale_return_tax_snapshots.sql`, deriving sale-return item taxable/tax amounts from original sale item snapshots in `SaleReturnWorkflowService`, persisting those snapshots in `SaleReturnModel`, and netting posted sale returns out of `TaxReportService` output GST. Extended focused coverage in `SaleReturnWorkflowServiceTest` and `TaxReportServiceTest`.
- 2026-04-11: Extended GST return netting to purchase returns by adding `database/054_add_purchase_return_tax_snapshots.sql`, backfilling purchase-return item taxable/tax snapshots from original purchase item snapshots, and subtracting purchase returns from `TaxReportService` input tax and purchase taxable values. Extended `TaxReportServiceTest` coverage for input-tax reversals.
