#!/usr/bin/env bash
# The export and schedule screens render the extension's controls through base
# plugin hooks; a fatal there truncates the page and hides the base options.
set -Eeuo pipefail
source /tests/lib.sh

jar="$(mktemp)"
trap 'rm -f "${jar}"' EXIT
wp_login "${jar}" admin admin

page() { curl -sS -b "${jar}" "http://localhost/wp-admin/admin.php?page=$1"; }

export_page="$(page ai1wm_export)"
[[ "${export_page}" != *wp-die-message* ]] || fail 'export screen hit a fatal error'
assert_contains "${export_page}" 'name="options[exclude_db_tables]"' 'export screen renders the table exclusion control'
assert_contains "${export_page}" 'name="options[no_media]"' 'export screen renders the media exclusion option'
assert_contains "${export_page}" 'id="ai1wm-export-s3-client"' 'export screen renders the S3 Client destination'

schedule_page="$(page 'ai1wmve_schedules&action=create-event')"
[[ "${schedule_page}" != *wp-die-message* ]] || fail 'schedule editor hit a fatal error'
assert_contains "${schedule_page}" 'id="ai1wmve-schedule-event-form"' 'schedule editor renders its form'
