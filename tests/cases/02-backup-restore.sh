#!/usr/bin/env bash
# Full round trip through S3: backup, remove content, restore, verify.
set -Eeuo pipefail
source /tests/lib.sh

marker="roundtrip-$(date +%s)"
post_id="$(wp post create --post_title="${marker}" --post_status=publish --porcelain)"
printf 'media-%s' "${marker}" > "/var/www/html/wp-content/uploads/${marker}.txt"

out="$(wp ai1wm s3-client backup 2>&1)" || { printf '%s\n' "${out}"; fail 'backup command failed'; }
printf '%s\n' "${out}"
archive="$(sed -n 's/^Backup file: //p' <<<"${out}" | tail -n 1)"
[[ -n "${archive}" ]] || fail 'backup did not report an archive name'
pass "backup created ${archive}"

assert_contains "$(s3_list)" "${archive}" 'archive object exists in the S3 bucket'

list_out="$(wp ai1wm s3-client list-backups 2>&1)"
assert_contains "${list_out}" "${archive}" 'list-backups shows the archive'

wp post delete "${post_id}" --force >/dev/null
rm -f "/var/www/html/wp-content/uploads/${marker}.txt"
assert_eq 0 "$(wp post list --post_type=post --title="${marker}" --format=count)" 'content removed before restore'

wp ai1wm s3-client restore "${archive}" --yes 2>&1 | tail -n 5

assert_eq 1 "$(wp post list --post_type=post --title="${marker}" --format=count)" 'post restored from S3 backup'
[[ -f "/var/www/html/wp-content/uploads/${marker}.txt" ]] || fail 'uploaded file not restored'
pass 'uploaded file restored from S3 backup'
