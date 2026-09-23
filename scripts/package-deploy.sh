#!/usr/bin/env bash
set -Eeuo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUTPUT_DIR="${ROOT_DIR}/.deploy"
THEME_DIR="${OUTPUT_DIR}/theme"
PLUGINS_DIR="${OUTPUT_DIR}/plugins"

rm -rf "${OUTPUT_DIR}"
mkdir -p "${THEME_DIR}" "${PLUGINS_DIR}"

find "${ROOT_DIR}" -maxdepth 1 -type f \( -name '*.php' -o -name 'style.css' -o -name 'screenshot.*' \) -exec cp {} "${THEME_DIR}/" \;

for directory in assets inc languages patterns template-parts templates; do
    if [[ -d "${ROOT_DIR}/${directory}" ]]; then
        rsync -a "${ROOT_DIR}/${directory}/" "${THEME_DIR}/${directory}/"
    fi
done

for plugin in boblift-multilingual-sync polytranslate-ai; do
    source_dir="${ROOT_DIR}/.wordpress/wp-content/plugins/${plugin}"
    if [[ ! -d "${source_dir}" ]]; then
        echo "Missing deployable plugin source: ${source_dir}" >&2
        exit 1
    fi
    rsync -a "${source_dir}/" "${PLUGINS_DIR}/${plugin}/"
done

test -f "${THEME_DIR}/style.css"
test -f "${THEME_DIR}/functions.php"
test ! -e "${OUTPUT_DIR}/wp-config.php"
test ! -e "${OUTPUT_DIR}/uploads"

echo "Deployment package prepared at ${OUTPUT_DIR}"
