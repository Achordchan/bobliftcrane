#!/usr/bin/env bash
set -Eeuo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WORDPRESS_ROOT="${PROJECT_ROOT}/.wordpress"
ROUTER_SCRIPT="${PROJECT_ROOT}/scripts/local-wordpress-router.php"
RUNTIME_HOST="${HOST:-0.0.0.0}"
RUNTIME_PORT="${1:-${PORT:-8080}}"
PHP_WORKERS="${PHP_CLI_SERVER_WORKERS:-4}"

fail() {
    printf 'Error: %s\n' "$1" >&2
    exit 1
}

if (( $# > 1 )); then
    fail "Usage: ./start.sh [port]"
fi

if [[ ! "${RUNTIME_PORT}" =~ ^[0-9]+$ ]] ||
    (( RUNTIME_PORT < 1 || RUNTIME_PORT > 65535 )); then
    fail "Port must be an integer between 1 and 65535."
fi

[[ -d "${WORDPRESS_ROOT}" ]] || fail "WordPress directory not found: ${WORDPRESS_ROOT}"
[[ -f "${WORDPRESS_ROOT}/wp-config.php" ]] || fail "Missing ${WORDPRESS_ROOT}/wp-config.php"
[[ -f "${ROUTER_SCRIPT}" ]] || fail "Missing local router: ${ROUTER_SCRIPT}"
command -v php >/dev/null 2>&1 || fail "PHP is unavailable. Install it with: brew install php"
command -v lsof >/dev/null 2>&1 || fail "lsof is required to check the local port."

if lsof -nP -iTCP:"${RUNTIME_PORT}" -sTCP:LISTEN >/dev/null 2>&1; then
    if (( RUNTIME_PORT < 65535 )); then
        SUGGESTED_PORT=$((RUNTIME_PORT + 1))
    else
        SUGGESTED_PORT=8080
    fi
    printf 'Port %s is already in use:\n' "${RUNTIME_PORT}" >&2
    lsof -nP -iTCP:"${RUNTIME_PORT}" -sTCP:LISTEN >&2 || true
    fail "Choose another port, for example: ./start.sh ${SUGGESTED_PORT}"
fi

if command -v wp >/dev/null 2>&1; then
    if ! wp db check --path="${WORDPRESS_ROOT}" --quiet >/dev/null 2>&1; then
        fail "The local WordPress database is unavailable. Start MySQL with: brew services start mysql"
    fi
elif ! lsof -nP -iTCP:3306 -sTCP:LISTEN >/dev/null 2>&1; then
    fail "MySQL is unavailable. Start it with: brew services start mysql"
fi

if [[ "${RUNTIME_HOST}" == "0.0.0.0" ]]; then
    DISPLAY_HOST="localhost"
else
    DISPLAY_HOST="${RUNTIME_HOST}"
fi

printf 'BOB-LIFT local site: http://%s:%s\n' "${DISPLAY_HOST}" "${RUNTIME_PORT}"
printf 'Logs are shown in this terminal. Press Ctrl+C to stop.\n'

cd "${PROJECT_ROOT}"
exec env PHP_CLI_SERVER_WORKERS="${PHP_WORKERS}" php \
    -S "${RUNTIME_HOST}:${RUNTIME_PORT}" \
    -t "${WORDPRESS_ROOT}" \
    "${ROUTER_SCRIPT}"
