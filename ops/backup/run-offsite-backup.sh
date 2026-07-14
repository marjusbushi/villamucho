#!/usr/bin/env bash

set -Eeuo pipefail
umask 077

readonly CONFIG_FILE="${LORA_BACKUP_CONFIG:-/etc/lora-backup/restic.env}"
readonly APP_PATH="${LORA_APP_PATH:-/var/www/villamucho}"
readonly STATE_PATH="${LORA_BACKUP_STATE_PATH:-/var/lib/lora-backup}"

fail() {
    printf 'Lora backup failed: %s\n' "$*" >&2
    exit 1
}

require_root_only_file() {
    local path="$1"
    local label="$2"
    local owner
    local mode

    [[ -r "${path}" ]] || fail "${label} is not readable: ${path}"
    owner="$(stat -c '%U' "${path}")"
    mode="$(stat -c '%a' "${path}")"
    [[ "${owner}" == "root" ]] || fail "${label} must be owned by root"
    (( (8#${mode} & 8#077) == 0 )) || fail "${label} must not be accessible by group/others"
}

[[ "${EUID}" -eq 0 ]] || fail "must run as root"
require_root_only_file "${CONFIG_FILE}" "config"

# shellcheck source=/dev/null
set -a
source "${CONFIG_FILE}"
set +a

: "${RESTIC_REPOSITORY:?RESTIC_REPOSITORY is required}"
: "${RESTIC_PASSWORD_FILE:?RESTIC_PASSWORD_FILE is required}"
: "${AWS_ACCESS_KEY_ID:?AWS_ACCESS_KEY_ID is required}"
: "${AWS_SECRET_ACCESS_KEY:?AWS_SECRET_ACCESS_KEY is required}"

[[ -d "${APP_PATH}" ]] || fail "application path is missing: ${APP_PATH}"
require_root_only_file "${RESTIC_PASSWORD_FILE}" "Restic password file"

for binary in flock php restic sha256sum; do
    command -v "${binary}" >/dev/null 2>&1 || fail "required binary is missing: ${binary}"
done

mkdir -p "${STATE_PATH}"
chmod 0700 "${STATE_PATH}"

exec 9>"${STATE_PATH}/backup.lock"
flock -n 9 || fail "another backup is already running"

run_dir="$(mktemp -d "${STATE_PATH}/run.XXXXXXXX")"
cleanup() {
    rm -rf -- "${run_dir}"
}
trap cleanup EXIT

cd "${APP_PATH}"

php artisan tenants:verify-integrity --snapshot="${run_dir}/integrity.json"
php artisan backups:export-database "${run_dir}/database.sql"

(
    cd "${run_dir}"
    sha256sum database.sql integrity.json > SHA256SUMS
)

backup_paths=("${run_dir}")
for storage_path in "${APP_PATH}/storage/app/private" "${APP_PATH}/storage/app/public"; do
    [[ -d "${storage_path}" ]] && backup_paths+=("${storage_path}")
done

restic backup \
    --host "$(hostname -f 2>/dev/null || hostname)" \
    --tag lora-production \
    --tag database-and-storage \
    "${backup_paths[@]}"

# Metadata verification is quick enough for every run. Full data reads belong
# to the scheduled restore drill, not the production backup window.
restic check

date --utc +'%Y-%m-%dT%H:%M:%SZ' > "${STATE_PATH}/last-success"
printf 'Lora offsite backup completed successfully.\n'
