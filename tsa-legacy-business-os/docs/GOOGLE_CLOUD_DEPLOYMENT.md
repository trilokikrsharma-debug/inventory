# Google Cloud Deployment Guide

## 1. Prerequisites
- Enable APIs:
  - Cloud Run
  - Cloud Build
  - Artifact Registry
  - Cloud SQL Admin
  - Secret Manager
  - Memorystore for Redis
- Configure billing and project quotas.

## 2. Provision Infrastructure
1. Create Cloud SQL MySQL instance (`tsa-platform-sql`).
2. Create MemoryStore Redis (`tsa-redis`).
3. Create Artifact Registry repo (`tsa-legacy`).
4. Create GCS bucket for files and exports.
5. Configure wildcard DNS for `*.tsalegacy.shop` to Load Balancer.

## 3. Secrets
Store these in Secret Manager and inject into Cloud Run:
- `APP_KEY`
- `DB_*` + `DB_TENANT_*`
- `RAZORPAY_KEY_ID`
- `RAZORPAY_KEY_SECRET`
- `RAZORPAY_WEBHOOK_SECRET`
- `REDIS_*`

## 4. Build and Deploy
```bash
gcloud builds submit --config cloudbuild.yaml
```

## 5. Post-Deploy Commands
Run one-off jobs (Cloud Run Job / CI step):
```bash
php artisan migrate --force
php artisan db:seed --force
php artisan tenancy:run "php artisan tenants:migrate --force"
```

## 6. Runtime Recommended Settings
- Min instances: `2`
- Max instances: `200`
- Concurrency: `80`
- CPU: `2`
- Memory: `2Gi`
- Queue worker autoscaling through Cloud Run jobs/worker service.

