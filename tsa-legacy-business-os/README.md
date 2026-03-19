# TSA Legacy Business OS

Production-grade multi-tenant SaaS ERP platform for `https://tsalegacy.shop`, built on:
- Laravel 12
- PHP 8.3 (runtime container)
- MySQL + Redis
- Tailwind CSS + Alpine.js + React (marketing/auth UX layer)
- REST API + Sanctum

## Implemented Highlights
- True multi-tenancy (subdomain/domain based) with `stancl/tenancy`
- Central SaaS control plane + per-tenant data isolation
- ERP schema for Inventory, Sales, Purchase, CRM, Accounting, HR, Reports
- Plan/Subscription/Payment/Feature-Flag system
- Razorpay checkout + webhook signature verification
- RBAC with Spatie permissions
- Security headers, login monitoring, audit logging, API/login rate limiting
- Google Cloud deployment artifacts (Cloud Run + Cloud Build + GitHub Actions)

## Quick Start
```bash
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm install
npm run build
php artisan serve
```

If auth or pricing pages look unstyled, frontend assets are not built/running. Use either:

```bash
npm run dev
```

or rebuild static assets:

```bash
npm run build
```

## Tenant Setup (example)
1. Create tenant from `/admin/tenants`.
2. Map subdomain (example: `acme.tsalegacy.shop`).
3. Register on the central domain (`/register`) and assign the user to the tenant.
4. Login on the tenant domain and open `/billing` to choose a plan.

## API
- Central:
  - `POST /api/v1/auth/token`
  - `POST /api/v1/webhooks/razorpay`
- Tenant:
  - `GET /api/v1/inventory/products`
  - `POST /api/v1/sales/invoices`
  - `GET /api/v1/reports/sales-analytics`
  - and more under `routes/tenant.php`

## Production Docs
- [Architecture](docs/ARCHITECTURE.md)
- [Google Cloud Deployment](docs/GOOGLE_CLOUD_DEPLOYMENT.md)
- [Security Checklist](docs/SECURITY_CHECKLIST.md)
- [Scaling to 10,000+ Tenants](docs/SCALING_STRATEGY_10000_TENANTS.md)
