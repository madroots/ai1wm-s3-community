#!/usr/bin/env bash
# The import page's S3 file browser (admin-ajax): administrators see the bucket's
# backups; anyone without the import capability gets nothing.
set -Eeuo pipefail
source /tests/lib.sh

out="$(wp ai1wm s3-client backup 2>&1)" || { printf '%s\n' "${out}" >&2; fail 'backup failed'; }
archive="$(backup_name "${out}")"

url="http://localhost/wp-admin/admin-ajax.php?action=ai1wmne_s3_browser&bucket_name=${S3_BUCKET}"
wp user create sub sub@example.com --role=subscriber --user_pass=subpass >/dev/null 2>&1 || true
rm -f /tmp/b-admin.jar /tmp/b-sub.jar
wp_login /tmp/b-admin.jar admin admin
wp_login /tmp/b-sub.jar sub subpass

body="$(curl -sS -b /tmp/b-admin.jar "${url}")"
assert_contains "${body}" "\"name\":\"${archive}\"" 'administrator sees the new backup in the browser'
assert_contains "${body}" "\"bucket_name\":\"${S3_BUCKET}\"" 'browser reports the bucket'

sub_status="$(curl -sS -o /tmp/b-sub.body -w '%{http_code}' -b /tmp/b-sub.jar "${url}")"
[[ "${sub_status}" != 200 ]] || fail 'subscriber was served the S3 browser'
if grep -q "${archive}" /tmp/b-sub.body; then fail 'subscriber can see backup names'; fi
pass "subscriber is refused (HTTP ${sub_status})"

anon_status="$(curl -sS -o /tmp/b-anon.body -w '%{http_code}' "${url}")"
[[ "${anon_status}" != 200 ]] || fail 'anonymous visitor was served the S3 browser'
if grep -q "${archive}" /tmp/b-anon.body; then fail 'anonymous visitor can see backup names'; fi
pass "anonymous visitor is refused (HTTP ${anon_status})"
