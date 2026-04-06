#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/tsalegacy}"
APP_USER="${APP_USER:-$USER}"
APP_GROUP="${APP_GROUP:-www-data}"
PHP_VERSION="${PHP_VERSION:-8.2}"
APP_HOST="${APP_HOST:-}"
TENANT_BASE_DOMAIN="${TENANT_BASE_DOMAIN:-}"
REPO_URL="${REPO_URL:-}"
DB_MODE="${DB_MODE:-cloudsql}"
REDIS_MODE="${REDIS_MODE:-memorystore}"

if [[ -z "${REPO_URL}" ]]; then
    echo "[BOOTSTRAP] REPO_URL is required"
    exit 1
fi

export DEBIAN_FRONTEND=noninteractive

echo "[BOOTSTRAP] Updating apt packages"
sudo apt update
sudo apt upgrade -y

echo "[BOOTSTRAP] Installing runtime packages"
sudo apt install -y software-properties-common curl unzip git ca-certificates lsb-release apt-transport-https
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y \
    nginx certbot python3-certbot-nginx \
    "php${PHP_VERSION}-fpm" "php${PHP_VERSION}-cli" "php${PHP_VERSION}-mysql" \
    "php${PHP_VERSION}-curl" "php${PHP_VERSION}-gd" "php${PHP_VERSION}-mbstring" \
    "php${PHP_VERSION}-xml" "php${PHP_VERSION}-zip" "php${PHP_VERSION}-intl" \
    "php${PHP_VERSION}-bcmath" "php${PHP_VERSION}-opcache" "php${PHP_VERSION}-redis" \
    composer mysql-client

if [[ "${DB_MODE}" == "local" ]]; then
    echo "[BOOTSTRAP] Installing local MySQL server"
    sudo apt install -y mysql-server
fi

if [[ "${REDIS_MODE}" == "local" ]]; then
    echo "[BOOTSTRAP] Installing local Redis server"
    sudo apt install -y redis-server
fi

if ! dpkg -s postfix >/dev/null 2>&1 && ! dpkg -s msmtp-mta >/dev/null 2>&1; then
    echo "[BOOTSTRAP] Installing postfix for PHP mail() delivery"
    sudo apt install -y postfix
fi

echo "[BOOTSTRAP] Preparing application directory"
sudo mkdir -p "${APP_DIR}"
sudo chown -R "${APP_USER}:${APP_GROUP}" "${APP_DIR}"

if [[ ! -d "${APP_DIR}/.git" ]]; then
    echo "[BOOTSTRAP] Cloning repository"
    git clone "${REPO_URL}" "${APP_DIR}"
fi

cd "${APP_DIR}"

if [[ ! -f .env ]]; then
    echo "[BOOTSTRAP] Creating .env from production template"
    cp deploy/env/.env.production.example .env
fi

echo "[BOOTSTRAP] Ensuring runtime directories"
sudo install -d -o www-data -g www-data -m 0775 \
    "${APP_DIR}/cache" \
    "${APP_DIR}/logs" \
    "${APP_DIR}/uploads" \
    "${APP_DIR}/uploads/backups"

echo "[BOOTSTRAP] Installing PHP dependencies"
composer install --no-dev --optimize-autoloader --classmap-authoritative --no-interaction --prefer-dist

echo "[BOOTSTRAP] Building assets"
composer run-script assets:build --no-interaction

echo "[BOOTSTRAP] Installing systemd units"
sudo cp deploy/systemd/invenbill-worker.service /etc/systemd/system/
sudo cp deploy/systemd/invenbill-scheduler.service /etc/systemd/system/
sudo cp deploy/systemd/invenbill-scheduler.timer /etc/systemd/system/
sudo cp deploy/systemd/invenbill-webhooks.service /etc/systemd/system/

echo "[BOOTSTRAP] Installing nginx site"
sudo cp deploy/nginx/invenbill.conf /etc/nginx/sites-available/invenbill.conf
sudo ln -sf /etc/nginx/sites-available/invenbill.conf /etc/nginx/sites-enabled/invenbill.conf
if [[ -f /etc/nginx/sites-enabled/default ]]; then
    sudo rm -f /etc/nginx/sites-enabled/default
fi

echo "[BOOTSTRAP] Validating syntax"
bash -n deploy/scripts/deploy.sh
php -l index.php >/dev/null
php -l cli/worker.php >/dev/null
php -l cli/scheduler.php >/dev/null
php -l cli/process_webhooks.php >/dev/null
sudo nginx -t

echo "[BOOTSTRAP] Reloading services"
sudo systemctl daemon-reload
sudo systemctl enable --now "php${PHP_VERSION}-fpm"
sudo systemctl enable --now nginx
sudo systemctl enable --now invenbill-worker.service
sudo systemctl enable --now invenbill-scheduler.timer
sudo systemctl enable --now invenbill-webhooks.service

cat <<EOF
[BOOTSTRAP] Completed.

Next manual steps:
1. Edit ${APP_DIR}/.env with real APP_URL, DB, Redis, Razorpay, and tenant domain values.
2. If using Cloud SQL or Memorystore, point DB_HOST and REDIS_HOST to their private IPs.
3. Run: php ${APP_DIR}/cli/migrate.php --status && php ${APP_DIR}/cli/migrate.php
4. Review nginx vhost in deploy/nginx/invenbill.conf and replace example.com/app.example.com placeholders.
5. Issue SSL certificates after DNS is live.

Context:
- APP_HOST=${APP_HOST}
- TENANT_BASE_DOMAIN=${TENANT_BASE_DOMAIN}
- DB_MODE=${DB_MODE}
- REDIS_MODE=${REDIS_MODE}
EOF
