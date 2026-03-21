#!/usr/bin/env bash
# Legacy compatibility wrapper.
# Production deployments should use deploy/scripts/deploy.sh instead of this script.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
echo "[SETUP] Legacy script detected."
echo "[SETUP] This wrapper now delegates to deploy/scripts/deploy.sh and no longer stores credentials or seeds demo data."
echo "[SETUP] Running production deploy flow..."
exec "${SCRIPT_DIR}/scripts/deploy.sh"
