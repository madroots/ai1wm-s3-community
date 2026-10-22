#!/usr/bin/env bash
# Incremental backups: the first run is a full archive, later runs upload only what
# changed and keep the bookkeeping lists in the bucket current.
set -Eeuo pipefail
source /tests/lib.sh

s3_clear_prefix incremental-backups/

run_backup() {
	local out
	out="$(wp ai1wm s3-client incremental backup 2>&1)" || { printf '%s\n' "${out}" >&2; fail 'incremental backup failed'; }
	backup_name "${out}"
}

first="$(run_backup)"
size1="$(s3_size "incremental-backups/${first}")"
[[ -n "${size1}" && "${size1}" -gt 1000000 ]] || fail "first incremental backup should be a full archive (got '${size1}' bytes)"
pass "first run uploaded a full archive (${size1} bytes)"
assert_contains "$(s3_objects)" 'incremental-backups/incremental.themes.list' 'bookkeeping lists are stored in the bucket'

head -c 4096 /dev/urandom > "${WP_PATH}/wp-content/uploads/incremental-$(date +%s).bin"
second="$(run_backup)"
size2="$(s3_size "incremental-backups/${second}")"
[[ -n "${size2}" && "${size2}" -lt $((size1 / 4)) ]] || fail "second run should upload only the delta (first ${size1}, second '${size2}')"
pass "second run uploaded only the delta (${size2} bytes)"
assert_contains "$(s3_objects)" 'incremental-backups/incremental.media.list' 'new media file was tracked in the media list'

third="$(run_backup)"
size3="$(s3_size "incremental-backups/${third}")"
[[ -n "${size3}" && "${size3}" -lt $((size1 / 4)) ]] || fail "unchanged run should stay small (first ${size1}, third '${size3}')"
pass "run without file changes stayed small (${size3} bytes)"

[[ "${first}" != "${second}" && "${second}" != "${third}" ]] || fail 'runs reused an archive name'
