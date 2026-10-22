#!/usr/bin/env bash
# admin-post.php runs handlers for any logged-in user, so the settings handlers
# must enforce capability and nonce themselves.
set -Eeuo pipefail
source /tests/lib.sh

original="$(wp option get ai1wmne_s3_api_endpoint)"
wp user create sub sub@example.com --role=subscriber --user_pass=subpass >/dev/null 2>&1 || true

post_settings() { # jar action [extra curl args]
	local jar="$1" action="$2"
	shift 2
	curl -sS -o /dev/null -w '%{http_code}' -b "${jar}" \
		--data 'ai1wmne_s3_update=1' --data 'ai1wmne_s3_api_endpoint=attacker.example' \
		--data 'ai1wmne_s3_access_key=evil' --data 'ai1wmne_s3_secret_key=evil' \
		"$@" "http://localhost/wp-admin/admin-post.php?action=${action}"
}

rm -f /tmp/sub.jar /tmp/admin.jar
wp_login /tmp/sub.jar sub subpass
wp_login /tmp/admin.jar admin admin

for action in ai1wmne_s3_connection ai1wmne_s3_settings; do
	assert_eq 403 "$(post_settings /tmp/sub.jar "${action}")" "subscriber is refused on ${action}"
	assert_eq "${original}" "$(wp option get ai1wmne_s3_api_endpoint)" "subscriber could not change the endpoint via ${action}"

	status="$(post_settings /tmp/admin.jar "${action}")"
	[[ "${status}" == 403 || "${status}" == 302 ]] || true
	assert_eq "${original}" "$(wp option get ai1wmne_s3_api_endpoint)" "administrator without nonce could not change the endpoint via ${action} (CSRF)"
done

# The legitimate path must keep working: the real form carries valid nonces.
page="$(curl -sS -b /tmp/admin.jar 'http://localhost/wp-admin/admin.php?page=ai1wmne_settings')"
nonce="$(grep -o 'name="ai1wmne_s3_connection_nonce" value="[^"]*"' <<<"${page}" | head -n 1 | sed 's/.*value="//; s/"$//')"
[[ -n "${nonce}" ]] || fail 'settings page does not render the connection nonce'
pass 'settings page renders the connection nonce'

status="$(curl -sS -o /dev/null -w '%{http_code}' -b /tmp/admin.jar \
	--data 'ai1wmne_s3_update=1' --data "ai1wmne_s3_connection_nonce=${nonce}" \
	--data-urlencode "ai1wmne_s3_api_endpoint=${original}" --data 'ai1wmne_s3_region_name=us-west-2' \
	--data-urlencode 'ai1wmne_s3_access_key=testaccess' --data-urlencode 'ai1wmne_s3_secret_key=testsecret1234' \
	'http://localhost/wp-admin/admin-post.php?action=ai1wmne_s3_connection')"
assert_eq 302 "${status}" 'administrator with a valid nonce is accepted'
assert_eq us-west-2 "$(wp option get ai1wmne_s3_region_name)" 'administrator change was applied'
wp option update ai1wmne_s3_region_name us-east-1 >/dev/null
