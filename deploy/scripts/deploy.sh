#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/inventory"
PHP_BIN="/usr/bin/php"
COMPOSER_BIN="/usr/bin/composer"

echo "[DEPLOY] Starting deployment in ${APP_DIR}"
cd "${APP_DIR}"

echo "[DEPLOY] Fetching latest code"
git fetch --all --prune
git pull --ff-only

echo "[DEPLOY] Installing PHP dependencies"
${COMPOSER_BIN} install --no-dev --optimize-autoloader --no-interaction

echo "[DEPLOY] Building minified assets"
${COMPOSER_BIN} assets:build || true

echo "[DEPLOY] Running migrations"
${PHP_BIN} cli/migrate.php

echo "[DEPLOY] Reloading PHP-FPM and Nginx"
sudo systemctl reload php8.2-fpm
sudo systemctl reload nginx

echo "[DEPLOY] Restarting worker + scheduler"
sudo systemctl restart invenbill-worker.service
sudo systemctl restart invenbill-scheduler.timer

echo "[DEPLOY] Done"
