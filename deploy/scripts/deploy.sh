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
require_cmd curl
require_cmd sudo

lint_php_sources() {
    local files=(
        "index.php"
        "views/errors/404.php"
        "views/errors/500.php"
        "views/errors/maintenance.php"
    )

    for file in "${files[@]}"; do
        if [[ -f "${file}" ]]; then
            echo "[DEPLOY] Linting ${file}"
            "${PHP_BIN}" -l "${file}" >/dev/null
        fi
    done
}

lint_shell_sources() {
    local files=(
        "deploy/scripts/deploy.sh"
        "deploy/setup_prod.sh"
    )

    for file in "${files[@]}"; do
        if [[ -f "${file}" ]]; then
            echo "[DEPLOY] Shell syntax check ${file}"
            bash -n "${file}"
        fi
    done
}

verify_post_deploy() {
    local base_url="${ROOT_URL:-http://127.0.0.1}"
    local app_host="${APP_HOST:-}"
    local curl_opts=(--fail --silent --show-error)
    local home_code missing_code login_code

    if [[ -n "${app_host}" ]]; then
        base_url="https://${app_host}"
        curl_opts+=(--resolve "${app_host}:443:127.0.0.1")
    fi

    echo "[DEPLOY] Running post-deploy smoke tests"
    home_code="$(curl "${curl_opts[@]}" -o /dev/null -w '%{http_code}' "${base_url}/")"
    missing_code="$(curl "${curl_opts[@]}" -o /dev/null -w '%{http_code}' "${base_url}/codex-does-not-exist-404")"
    login_code="$(curl "${curl_opts[@]}" -o /dev/null -w '%{http_code}' "${base_url}/index.php?page=login")"

    if [[ "${home_code}" != "200" ]]; then
        echo "[DEPLOY] Smoke test failed: home returned ${home_code}" >&2
        exit 1
    fi

    if [[ "${missing_code}" != "404" ]]; then
        echo "[DEPLOY] Smoke test failed: missing route returned ${missing_code} (expected 404)" >&2
        exit 1
    fi

    if [[ "${login_code}" != "200" ]]; then
        echo "[DEPLOY] Smoke test failed: login returned ${login_code}" >&2
        exit 1
    fi

    echo "[DEPLOY] Smoke tests passed (home=200, missing=404, login=200)"
}

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

echo "[DEPLOY] Checking shell and PHP syntax"
lint_shell_sources
lint_php_sources

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

verify_post_deploy

echo "[DEPLOY] Recent HTTP headers"
curl --fail --silent --show-error -I "${ROOT_URL:-http://127.0.0.1}/" | sed -n '1,12p' || true

echo "[DEPLOY] Restarting worker + scheduler"
sudo systemctl restart invenbill-worker.service
sudo systemctl restart invenbill-scheduler.timer

echo "[DEPLOY] Done"
