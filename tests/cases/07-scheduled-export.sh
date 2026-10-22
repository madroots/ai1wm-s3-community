#!/usr/bin/env bash
# A scheduled S3 export: enabling a schedule registers the cron event, and running
# that event produces a backup in the bucket.
set -Eeuo pipefail
source /tests/lib.sh

trap 'wp eval "(new Ai1wmne_Settings)->set_cron( array() );" >/dev/null 2>&1 || true' EXIT

count() { s3_objects | cut -f1 | grep -c '^[^/]*\.wpress$' || true; }

before="$(count)"
wp eval '(new Ai1wmne_Settings)->set_cron( array( "daily" ) );'
assert_contains "$(wp cron event list --fields=hook,recurrence --format=csv)" 'ai1wmne_s3_daily_export,"1 day"' 'daily export event is scheduled'

wp cron event run ai1wmne_s3_daily_export >/dev/null 2>&1

for _ in $(seq 1 30); do
	[[ "$(count)" -gt "${before}" ]] && break
	sleep 1
done
after="$(count)"
[[ "${after}" -gt "${before}" ]] || fail "scheduled export produced no backup (before ${before}, after ${after})"
pass "scheduled export produced a backup (${before} -> ${after})"

wp eval '(new Ai1wmne_Settings)->set_cron( array() );'
if wp cron event list --fields=hook --format=csv | grep -q '^ai1wmne_s3_daily_export$'; then
	fail 'clearing the schedule left the cron event behind'
fi
pass 'clearing the schedule removes the event'
