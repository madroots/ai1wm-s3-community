#!/usr/bin/env bash
# Retention: with "keep 2 backups" set, a third backup prunes the oldest.
set -Eeuo pipefail
source /tests/lib.sh

trap 'wp option delete ai1wmne_s3_backups >/dev/null 2>&1 || true' EXIT

# Start from a known bucket: no archives at the top level.
s3_objects | cut -f1 | grep '^[^/]*\.wpress$' | while read -r key; do
	s3_php '$s3->remove_file( "'"${key}"'", $bucket, $region );'
done

wp option update ai1wmne_s3_backups 2 >/dev/null

names=()
for _ in 1 2 3; do
	out="$(wp ai1wm s3-client backup 2>&1)" || { printf '%s\n' "${out}" >&2; fail 'backup failed'; }
	names+=("$(backup_name "${out}")")
	sleep 1
done

remaining="$(s3_objects | cut -f1 | grep '^[^/]*\.wpress$' | sort)"
assert_eq 2 "$(grep -c . <<<"${remaining}")" 'only the configured number of backups remain'
assert_contains "${remaining}" "${names[2]}" 'newest backup is kept'
assert_contains "${remaining}" "${names[1]}" 'second newest backup is kept'
if grep -q "${names[0]}" <<<"${remaining}"; then fail 'oldest backup was not pruned'; fi
pass 'oldest backup was pruned'
