#!/usr/bin/env bash
set -Eeuo pipefail

: "${BAOTA_HOST:?BAOTA_HOST is required}"
: "${BAOTA_USER:?BAOTA_USER is required}"
: "${BAOTA_SSH_KEY_PATH:?BAOTA_SSH_KEY_PATH is required}"

DEPLOY_ROOT="${BAOTA_DEPLOY_ROOT:-/www/wwwroot/bobliftcrane}"
BACKUP_ROOT="${BAOTA_BACKUP_ROOT:-/www/backup/bobliftcrane-code}"
PACKAGE_ROOT="${BAOTA_PACKAGE_ROOT:-.deploy}"
RELEASE_ID="${GITHUB_SHA:-manual-$(date -u +%Y%m%d%H%M%S)}"
REMOTE="${BAOTA_USER}@${BAOTA_HOST}"

if [[ ! "${DEPLOY_ROOT}" =~ ^/www/wwwroot/[A-Za-z0-9._/-]+$ ]]; then
    echo "Unsafe deploy root: ${DEPLOY_ROOT}" >&2
    exit 1
fi

for required in "${PACKAGE_ROOT}/theme" "${PACKAGE_ROOT}/plugins/boblift-multilingual-sync" "${PACKAGE_ROOT}/plugins/polytranslate-ai"; do
    if [[ ! -d "${required}" ]]; then
        echo "Missing deployment package directory: ${required}" >&2
        exit 1
    fi
done

SSH_OPTIONS=(
    -i "${BAOTA_SSH_KEY_PATH}"
    -o IdentitiesOnly=yes
    -o BatchMode=yes
    -o StrictHostKeyChecking=yes
)

backup_output="$(ssh "${SSH_OPTIONS[@]}" "${REMOTE}" bash -s -- "${DEPLOY_ROOT}" "${BACKUP_ROOT}" "${RELEASE_ID}" <<'REMOTE_BACKUP'
set -Eeuo pipefail
deploy_root="$1"
backup_root="$2"
release_id="$3"
mkdir -p "${backup_root}"

items=()
for path in \
    wp-content/themes/bobliftcrane \
    wp-content/plugins/boblift-multilingual-sync \
    wp-content/plugins/polytranslate-ai; do
    [[ -e "${deploy_root}/${path}" ]] && items+=("${path}")
done

if (( ${#items[@]} > 0 )); then
    backup_file="${backup_root}/${release_id}.tar.gz"
    tar -czf "${backup_file}" -C "${deploy_root}" "${items[@]}"
    echo "BACKUP_FILE=${backup_file}"
fi

find "${backup_root}" -maxdepth 1 -type f -name '*.tar.gz' -printf '%T@ %p\n' \
    | sort -nr \
    | awk 'NR > 10 { sub(/^[^ ]+ /, ""); print }' \
    | xargs -r rm -f
REMOTE_BACKUP
)"
backup_file="$(printf '%s\n' "${backup_output}" | sed -n 's/^BACKUP_FILE=//p' | tail -1)"

rollback() {
    if [[ -z "${backup_file}" ]]; then
        echo "No previous deployment backup is available for rollback." >&2
        return
    fi
    echo "Deployment failed; restoring ${backup_file}" >&2
    ssh "${SSH_OPTIONS[@]}" "${REMOTE}" bash -s -- "${DEPLOY_ROOT}" "${backup_file}" <<'REMOTE_ROLLBACK'
set -Eeuo pipefail
deploy_root="$1"
backup_file="$2"
rm -rf \
    "${deploy_root}/wp-content/themes/bobliftcrane" \
    "${deploy_root}/wp-content/plugins/boblift-multilingual-sync" \
    "${deploy_root}/wp-content/plugins/polytranslate-ai"
tar -xzf "${backup_file}" -C "${deploy_root}"
REMOTE_ROLLBACK
}

trap rollback ERR

RSYNC_OPTIONS=(
    -az
    --delete-delay
    --delay-updates
    --chmod=Dug=rwx,Do=rx,Fug=rw,Fo=r
    -e "ssh -i ${BAOTA_SSH_KEY_PATH} -o IdentitiesOnly=yes -o BatchMode=yes -o StrictHostKeyChecking=yes"
)

rsync "${RSYNC_OPTIONS[@]}" "${PACKAGE_ROOT}/theme/" "${REMOTE}:${DEPLOY_ROOT}/wp-content/themes/bobliftcrane/"
for plugin in boblift-multilingual-sync polytranslate-ai; do
    rsync "${RSYNC_OPTIONS[@]}" "${PACKAGE_ROOT}/plugins/${plugin}/" "${REMOTE}:${DEPLOY_ROOT}/wp-content/plugins/${plugin}/"
done

ssh "${SSH_OPTIONS[@]}" "${REMOTE}" bash -s -- "${DEPLOY_ROOT}" <<'REMOTE_VERIFY'
set -Eeuo pipefail
deploy_root="$1"
php_bin="/www/server/php/83/bin/php"

test -f "${deploy_root}/wp-content/themes/bobliftcrane/style.css"
test -f "${deploy_root}/wp-content/themes/bobliftcrane/functions.php"
test ! -e "${deploy_root}/wp-content/themes/bobliftcrane/wp-config.php"

if [[ -x "${php_bin}" ]]; then
    while IFS= read -r -d '' file; do
        "${php_bin}" -l "${file}" >/dev/null
    done < <(find \
        "${deploy_root}/wp-content/themes/bobliftcrane" \
        "${deploy_root}/wp-content/plugins/boblift-multilingual-sync" \
        "${deploy_root}/wp-content/plugins/polytranslate-ai" \
        -type f -name '*.php' -print0)
fi
REMOTE_VERIFY

if [[ -n "${BAOTA_HEALTH_URL:-}" ]]; then
    curl_options=(-fsS --max-time 30)
    if [[ -n "${BAOTA_BASIC_AUTH_USER:-}" && -n "${BAOTA_BASIC_AUTH_PASSWORD:-}" ]]; then
        curl_options+=(-u "${BAOTA_BASIC_AUTH_USER}:${BAOTA_BASIC_AUTH_PASSWORD}")
    fi
    curl "${curl_options[@]}" "${BAOTA_HEALTH_URL}" >/dev/null
fi

trap - ERR
echo "Deployment completed: ${RELEASE_ID}"
