#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/inventory"
PHP_BIN="/usr/bin/php"
COMPOSER_BIN="/usr/bin/composer"
BRANCH="${BRANCH:-$(git -C "${APP_DIR}" rev-parse --abbrev-ref HEAD 2>/dev/null || echo main)}"

trap 'echo "[DEPLOY] Failed at line ${LINENO}" >&2' ERR

require_cmd() {
    command -v "$1" >/dev/null 2>&1 || {
        echo "[DEPLOY] Missing required command: $1" >&2
        exit 1
    }
}

ensure_runtime_dirs() {
    sudo install -d -o www-data -g www-data -m 0775 "${APP_DIR}/cache" "${APP_DIR}/logs" "${APP_DIR}/uploads"
}

require_cmd git
require_cmd "${PHP_BIN}"
require_cmd "${COMPOSER_BIN}"
require_cmd sudo

if [[ ! -d "${APP_DIR}/.git" ]]; then
    echo "[DEPLOY] ${APP_DIR} is not a git repository" >&2
    exit 1
fi

if [[ ! -f "${APP_DIR}/.env" ]]; then
    echo "[DEPLOY] Missing ${APP_DIR}/.env. Copy deploy/env/.env.production.example first." >&2
    exit 1
fi

if [[ ! -f "${APP_DIR}/database/schema.sql" ]]; then
    echo "[DEPLOY] Missing database/schema.sql. Deployment cannot continue." >&2
    exit 1
fi

echo "[DEPLOY] Starting deployment in ${APP_DIR}"
cd "${APP_DIR}"
ensure_runtime_dirs

echo "[DEPLOY] Fetching latest code"
git fetch --all --prune
git pull --ff-only origin "${BRANCH}"

echo "[DEPLOY] Installing PHP dependencies"
${COMPOSER_BIN} install --no-dev --optimize-autoloader --classmap-authoritative --no-interaction --prefer-dist

echo "[DEPLOY] Validating composer scripts"
${COMPOSER_BIN} validate --no-check-all --no-interaction

echo "[DEPLOY] Building minified assets"
${COMPOSER_BIN} run-script assets:build --no-interaction

echo "[DEPLOY] Running migrations"
${PHP_BIN} cli/migrate.php --status
${PHP_BIN} cli/migrate.php

echo "[DEPLOY] Normalizing runtime permissions"
ensure_runtime_dirs
sudo chown -R www-data:www-data "${APP_DIR}/cache" "${APP_DIR}/logs" "${APP_DIR}/uploads"
sudo chmod 640 "${APP_DIR}/.env"

echo "[DEPLOY] Reloading PHP-FPM and Nginx"
sudo systemctl reload php8.2-fpm
sudo systemctl reload nginx

echo "[DEPLOY] Restarting worker + scheduler"
sudo systemctl restart invenbill-worker.service
sudo systemctl restart invenbill-scheduler.timer

echo "[DEPLOY] Done"
