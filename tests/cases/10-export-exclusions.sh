#!/usr/bin/env bash
# Exercise database-client compatibility through a real export and inspect the
# archive: selected tables and media must be omitted without losing other data.
set -Eeuo pipefail
source /tests/lib.sh

extract="$(mktemp -d)"
archive=''
post_id=''
cleanup() {
	wp eval 'global $wpdb; $wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}ai1wmne_excluded_test`" );' >/dev/null 2>&1 || true
	[[ -z "${post_id}" ]] || wp post delete "${post_id}" --force >/dev/null 2>&1 || true
	rm -f "${WP_PATH}/wp-content/uploads/ai1wmne-excluded-media.txt" "${WP_PATH}/wp-content/ai1wmne-kept-content.txt"
	[[ -z "${archive}" ]] || rm -f "${WP_PATH}/wp-content/ai1wm-backups/${archive}"
	rm -rf "${extract}"
}
trap cleanup EXIT

wp eval '
	global $wpdb;
	$table = $wpdb->prefix . "ai1wmne_excluded_test";
	if ( false === $wpdb->query( "CREATE TABLE `{$table}` (value varchar(100))" ) ||
		false === $wpdb->insert( $table, array( "value" => "ai1wmne-excluded-table-marker" ) ) ) {
		WP_CLI::error( "Could not create the excluded table fixture" );
	}
'
post_id="$(wp post create --post_title='ai1wmne-kept-post-marker' --post_status=publish --porcelain)"
mkdir -p "${WP_PATH}/wp-content/uploads"
printf 'excluded media\n' > "${WP_PATH}/wp-content/uploads/ai1wmne-excluded-media.txt"
printf 'kept content\n' > "${WP_PATH}/wp-content/ai1wmne-kept-content.txt"

out="$(wp ai1wm backup --exclude-media --exclude-tables="$(wp db prefix)ai1wmne_excluded_test" 2>&1)" || {
	printf '%s\n' "${out}"
	fail 'export with media and table exclusions failed'
}
archive="$(backup_name "${out}")"
[[ -n "${archive}" ]] || fail 'export did not report an archive name'
wp ai1wm extract-backup "${archive}" --extract-path="${extract}"

[[ ! -e "${extract}/uploads/ai1wmne-excluded-media.txt" ]] || fail 'excluded media was included in the archive'
assert_eq 'kept content' "$(< "${extract}/ai1wmne-kept-content.txt")" 'unexcluded content remains in the archive'
dump="$(< "${extract}/database.sql")"
assert_contains "${dump}" 'ai1wmne-kept-post-marker' 'unexcluded database content remains in the archive'
[[ "${dump}" != *ai1wmne-excluded-table-marker* ]] || fail 'excluded table data was included in the archive'
assert_eq 'ai1wmne-excluded-table-marker' "$(wp eval 'global $wpdb; echo $wpdb->get_var( "SELECT value FROM `{$wpdb->prefix}ai1wmne_excluded_test`" );')" 'exclusion does not delete source data'
pass 'media and selected database tables are omitted from the archive'
