# InvenBill SaaS Production Deployment Guide

This runbook launches the app for real tenants and real payments.

## 1) Server Provisioning (AWS/GCP/DigitalOcean)

Recommended baseline:
- Ubuntu 22.04 LTS
- 2 vCPU / 4 GB RAM minimum
- 80 GB SSD
- Public IP + DNS-managed domain

Provider quick mapping:
- AWS: EC2 + Route53 + optional RDS/ElastiCache
- GCP: Compute Engine + Cloud DNS + optional Cloud SQL/Memorystore
- DigitalOcean: Droplet + DNS + optional Managed DB/Redis

## 2) Install Runtime (Nginx + PHP 8.2 + MySQL + Redis)

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mysql-server redis-server certbot python3-certbot-nginx \
  software-properties-common unzip git curl
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2-fpm php8.2-cli php8.2-mysql php8.2-mbstring php8.2-xml \
  php8.2-curl php8.2-zip php8.2-gd php8.2-intl php8.2-bcmath
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

## 3) Database + App User

```bash
sudo mysql
```

```sql
CREATE DATABASE inventory_billing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'invenbill_user'@'127.0.0.1' IDENTIFIED BY 'CHANGE_ME_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON inventory_billing.* TO 'invenbill_user'@'127.0.0.1';
FLUSH PRIVILEGES;
EXIT;
```

## 4) Deploy Code + Permissions

```bash
sudo mkdir -p /var/www/inventory
sudo chown -R $USER:www-data /var/www/inventory
git clone <YOUR_REPO_URL> /var/www/inventory
cd /var/www/inventory
cp deploy/env/.env.production.example .env
nano .env
composer install --no-dev --optimize-autoloader --classmap-authoritative --prefer-dist
composer run-script assets:build
php cli/migrate.php --status
php cli/migrate.php
sudo chown -R www-data:www-data /var/www/inventory/cache /var/www/inventory/logs /var/www/inventory/uploads
sudo chmod -R 775 /var/www/inventory/cache /var/www/inventory/logs /var/www/inventory/uploads
sudo chmod 640 /var/www/inventory/.env
```

Recommended final permissions:
- App code: owned by your deploy user or root
- Runtime dirs: `www-data:www-data`
- `.env`: readable by web server only if required, otherwise keep `640`
- Never make `database/`, `cli/`, `config/`, or `vendor/` web-writable

## 5) Nginx Virtual Host

```bash
sudo cp deploy/nginx/invenbill.conf /etc/nginx/sites-available/invenbill.conf
sudo ln -s /etc/nginx/sites-available/invenbill.conf /etc/nginx/sites-enabled/invenbill.conf
sudo nginx -t
sudo systemctl reload nginx
```

Production Nginx behavior:
- Blocks direct access to `.env`, `.sql`, logs, cache, vendor, and source directories
- Forces HTTPS
- Returns `403` for the health route by default
- Uses long-lived cache headers for static assets

## 6) Domain + Wildcard Tenant DNS

Set DNS records:
- `A app.example.com -> <SERVER_IP>`
- `A *.example.com -> <SERVER_IP>` (or wildcard CNAME to app host)

For tenant subdomains on HTTPS, use wildcard cert via DNS challenge:

```bash
sudo certbot certonly --manual --preferred-challenges dns \
  -d example.com -d '*.example.com'
```

Then update cert paths in `deploy/nginx/invenbill.conf`, test and reload:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

If you only need `app.example.com` initially:

```bash
sudo certbot --nginx -d app.example.com
```

## 7) Production `.env` Essentials

Minimum required values:
- `APP_ENV=production`
- `APP_URL=https://app.example.com`
- `TENANT_BASE_DOMAIN=example.com`
- `TENANT_HOST_ENFORCEMENT=true`
- `DB_*` credentials
- `RAZORPAY_KEY`, `RAZORPAY_SECRET`, `RAZORPAY_WEBHOOK_SECRET`
- `REDIS_ENABLED=true` + Redis credentials

Template file:
- [deploy/env/.env.production.example](/var/www/inventory/deploy/env/.env.production.example)

## 8) Razorpay LIVE Setup

1. In Razorpay Dashboard, switch to LIVE mode.
2. Create LIVE plans matching `saas_plans`.
3. Update `saas_plans.razorpay_plan_id` with live plan IDs.
4. Add webhook endpoint:
   - `https://app.example.com/api/v1/saas/webhook`
   - Also supported: `https://app.example.com/api/v1/webhook/razorpay`
5. Copy webhook secret into `RAZORPAY_WEBHOOK_SECRET` in `.env`.
6. Fire a test webhook from Razorpay and verify 200 response.

## 9) Queue Worker + Scheduler (systemd)

Install units:

```bash
sudo cp deploy/systemd/invenbill-worker.service /etc/systemd/system/
sudo cp deploy/systemd/invenbill-scheduler.service /etc/systemd/system/
sudo cp deploy/systemd/invenbill-scheduler.timer /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now invenbill-worker.service
sudo systemctl enable --now invenbill-scheduler.timer
sudo systemctl status invenbill-worker.service --no-pager
sudo systemctl status invenbill-scheduler.timer --no-pager
```

Fallback cron profile:
- [deploy/cron/invenbill.cron](/var/www/inventory/deploy/cron/invenbill.cron)

Scheduler CLI:
- [cli/scheduler.php](/var/www/inventory/cli/scheduler.php)

## 10) Backups + Storage

Current behavior:
- Backups are queued via `ProcessBackup` jobs.
- Scheduler queues daily tenant backups.
- Backup files are stored outside/away from direct web serving when possible.

Recommended production hardening:
- Move `uploads` and backup directories to mounted block storage or object storage sync target.
- Nightly DB dump to offsite:

```bash
mysqldump -u invenbill_user -p inventory_billing | gzip > /var/backups/invenbill/db_$(date +%F).sql.gz
```

- Retention policy: 14 daily + 8 weekly + 6 monthly.

## 11) Monitoring + Logs

App logs:
- `/var/www/inventory/logs/`
- queue worker: `queue-worker.log`, `queue-worker-error.log`
- scheduler: `scheduler.log`

Health endpoint:
- `GET /index.php?page=health`
- Keep public mode OFF in production (`HEALTH_PUBLIC_MODE=false`)
- Current Nginx/Apache configs block public health access; use CLI/server-local checks instead unless you intentionally open an internal probe route

Useful server-local checks:
- `php cli/verify_hardening.php`
- `php cli/diag_db.php`

Recommended monitors:
- Uptime checks for app + webhook endpoint
- MySQL CPU/connections/slow queries
- Redis memory + evictions
- Disk usage alerts at 80% and 90%

## 12) Go-Live Deployment Command

```bash
sudo bash /var/www/inventory/deploy/scripts/deploy.sh
```

Deployment script behavior:
- Fails fast if `.env` or `database/schema.sql` is missing
- Validates composer config before migration
- Runs `php cli/migrate.php --status` before migrating
- Normalizes ownership on `cache/`, `logs/`, and `uploads/`

Script file:
- [deploy/scripts/deploy.sh](/var/www/inventory/deploy/scripts/deploy.sh)

## 13) Final Revenue-Ready Checklist

- [ ] Domain and wildcard DNS are correct (`app.example.com`, `*.example.com`)
- [ ] SSL certificate active and auto-renew tested
- [ ] `.env` uses LIVE keys/secrets only
- [ ] `TENANT_BASE_DOMAIN` and host enforcement enabled
- [ ] Database migrated and `performance_indexes.sql` applied
- [ ] Worker service running and stable
- [ ] Scheduler timer active
- [ ] Razorpay webhook returns success and signatures verify
- [ ] Trial/subscription flows tested on LIVE (small amount)
- [ ] Daily backup job verified and restore dry-run completed
- [ ] Health checks and alerting connected
- [ ] Rollback plan documented (previous release + DB backup)
