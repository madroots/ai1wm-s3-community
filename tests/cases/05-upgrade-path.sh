#!/usr/bin/env bash
# What an existing user experiences: the last published release is installed and
# configured, has produced a backup, then the plugin under test is installed over
# it exactly as WordPress's upgrader does (overwrite in place, no uninstall).
set -Eeuo pipefail
source /tests/lib.sh

if [[ ! -s /tmp/previous.zip ]]; then
	echo '  skipped: no previous release ZIP was provided'
	exit 0
fi

# Version of the ZIP under test, read from its plugin header.
new_version="$(php -r '
	$zip = new ZipArchive();
	$zip->open("/tmp/plugin.zip");
	$source = $zip->getFromName($argv[1] . "/" . $argv[1] . ".php");
	preg_match("/^ \\* Version: (.+)$/m", $source, $m);
	echo trim($m[1]);
' "${PLUGIN_SLUG}")"
[[ -n "${new_version}" ]] || fail 'could not read the version of the ZIP under test'

wp plugin install /tmp/previous.zip --force --activate >/dev/null
old_version="$(wp plugin get "${PLUGIN_SLUG}" --field=version)"
pass "previous release ${old_version} installed over the build under test"

endpoint="$(wp option get ai1wmne_s3_api_endpoint)"
bucket="$(wp option get ai1wmne_s3_bucket_name)"
access_key="$(wp option get ai1wmne_s3_access_key)"

marker="upgrade-$(date +%s)"
post_id="$(wp post create --post_title="${marker}" --post_status=publish --porcelain)"
out="$(wp ai1wm s3-client backup 2>&1)" || { printf '%s\n' "${out}"; fail "backup with ${old_version} failed"; }
old_archive="$(sed -n 's/^Backup file: //p' <<<"${out}" | tail -n 1)"
[[ -n "${old_archive}" ]] || fail 'previous release did not report an archive name'
pass "previous release produced ${old_archive}"

wp plugin install /tmp/plugin.zip --force >/dev/null
assert_eq "${new_version}" "$(wp plugin get "${PLUGIN_SLUG}" --field=version)" 'upgraded to the version under test'
assert_eq active "$(wp plugin get "${PLUGIN_SLUG}" --field=status)" 'plugin stays active through the upgrade'
assert_eq "${endpoint}" "$(wp option get ai1wmne_s3_api_endpoint)" 'S3 endpoint setting survives'
assert_eq "${bucket}" "$(wp option get ai1wmne_s3_bucket_name)" 'bucket setting survives'
assert_eq "${access_key}" "$(wp option get ai1wmne_s3_access_key)" 'access key setting survives'

compat="$(wp eval '$e = Ai1wm_Extensions::get(); echo Ai1wm_Compatibility::check($e[AI1WMNE_PLUGIN_NAME]) ? "compatible" : "INCOMPATIBLE";')"
assert_eq compatible "${compat}" 'base plugin still accepts the upgraded extension'

assert_contains "$(wp ai1wm s3-client list-backups 2>&1)" "${old_archive}" 'backup made by the previous release is still listed'

wp post delete "${post_id}" --force >/dev/null
wp ai1wm s3-client restore "${old_archive}" --yes >/dev/null 2>&1
assert_eq 1 "$(wp post list --post_type=post --title="${marker}" --format=count)" 'backup made by the previous release restores with the new version'
assert_eq "${new_version}" "$(wp plugin get "${PLUGIN_SLUG}" --field=version)" 'restoring an old backup does not downgrade the plugin'

out="$(wp ai1wm s3-client backup 2>&1)" || { printf '%s\n' "${out}"; fail 'backup with the upgraded version failed'; }
new_archive="$(sed -n 's/^Backup file: //p' <<<"${out}" | tail -n 1)"
assert_contains "$(s3_list)" "${new_archive}" 'upgraded version uploads a new backup'
