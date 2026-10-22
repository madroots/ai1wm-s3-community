#!/usr/bin/env bash
# The community update channel end to end: a GitHub-style release (served by the
# local web server) must show up as an update and install over the running plugin.
set -Eeuo pipefail
source /tests/lib.sh

new_version=9.9.9
plugin_dir="${WP_PATH}/wp-content/plugins/${PLUGIN_SLUG}"
mock_dir="${WP_PATH}/mock"
mkdir -p "${mock_dir}" "${WP_PATH}/wp-content/mu-plugins"

# WordPress refuses to download packages from localhost unless told otherwise.
cat > "${WP_PATH}/wp-content/mu-plugins/allow-local-packages.php" <<'PHP'
<?php
add_filter( 'http_request_host_is_external', '__return_true' );
add_filter( 'http_request_args', function ( $args ) { $args['reject_unsafe_urls'] = false; return $args; } );
PHP

# Build the "new release" ZIP from the plugin under test with a higher version.
stage="$(mktemp -d)"
mkdir -p "${stage}/${PLUGIN_SLUG}"
cp -r "${plugin_dir}/." "${stage}/${PLUGIN_SLUG}/"
sed -i "s/^ \* Version: .*/ * Version: ${new_version}/" "${stage}/${PLUGIN_SLUG}/${PLUGIN_SLUG}.php"
sed -i "s/'AI1WMNE_FORK_VERSION', '[^']*'/'AI1WMNE_FORK_VERSION', '${new_version}'/" "${stage}/${PLUGIN_SLUG}/constants.php"
php -r '
	$zip = new ZipArchive();
	$zip->open($argv[2], ZipArchive::CREATE | ZipArchive::OVERWRITE);
	$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($argv[1], FilesystemIterator::SKIP_DOTS));
	foreach ($it as $file) { $zip->addFile($file->getPathname(), substr($file->getPathname(), strlen(dirname($argv[1])) + 1)); }
	$zip->close();
' "${stage}/${PLUGIN_SLUG}" "${mock_dir}/${PLUGIN_SLUG}-${new_version}.zip"

cat > "${mock_dir}/latest.json" <<JSON
{
  "tag_name": "v${new_version}",
  "draft": false,
  "prerelease": false,
  "published_at": "2026-01-01T00:00:00Z",
  "body": "test release",
  "assets": [
    { "name": "${PLUGIN_SLUG}-${new_version}.zip",
      "browser_download_url": "http://localhost/mock/${PLUGIN_SLUG}-${new_version}.zip" }
  ]
}
JSON

wp config set AI1WMNE_GITHUB_RELEASE_API 'http://localhost/mock/latest.json'
wp eval 'delete_site_transient( AI1WMNE_GITHUB_RELEASE_CACHE ); delete_site_transient( "update_plugins" );'

before="$(wp plugin get "${PLUGIN_SLUG}" --field=version)"
available="$(wp plugin list --fields=name,update,update_version --format=csv | grep "^${PLUGIN_SLUG}," || true)"
assert_contains "${available}" "available,${new_version}" "release ${new_version} is offered as an update to ${before}"

wp plugin update "${PLUGIN_SLUG}"
assert_eq "${new_version}" "$(wp plugin get "${PLUGIN_SLUG}" --field=version)" 'plugin updated to the released version'
assert_eq active "$(wp plugin get "${PLUGIN_SLUG}" --field=status)" 'plugin is still active after the update'
[[ -f "${plugin_dir}/${PLUGIN_SLUG}.php" ]] || fail 'update did not leave the plugin in its original directory'
pass 'plugin stayed in its original directory'
