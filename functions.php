<?php
/**
 * Copyright (C) 2014-2020 ServMask Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Kangaroos cannot jump here' );
}

/**
 * Check whether export/import is running
 *
 * @return boolean
 */
function ai1wmne_is_running() {
	if ( isset( $_GET['s3-client'] ) || isset( $_POST['s3-client'] ) ) {
		return true;
	}

	return false;
}

/**
 * Check whether export/import is incremental
 *
 * @return boolean
 */
function ai1wmne_is_incremental() {
	if ( isset( $_GET['s3-client'], $_GET['incremental'] ) || isset( $_POST['s3-client'], $_POST['incremental'] ) ) {
		return true;
	}

	return false;
}

/**
 * Get AWS access key environment variable
 *
 * @return string
 */
function ai1wmne_aws_access_key() {
	return getenv( 'AWS_ACCESS_KEY_ID' );
}

/**
 * Get AWS secret key environment variable
 *
 * @return string
 */
function ai1wmne_aws_secret_key() {
	return getenv( 'AWS_SECRET_ACCESS_KEY' );
}

/**
 * Get AWS region name environment variable
 *
 * @param  string $default Region name
 * @return string
 */
function ai1wmne_aws_region_name( $default = null ) {
	if ( getenv( 'AWS_DEFAULT_REGION' ) ) {
		return getenv( 'AWS_DEFAULT_REGION' );
	}

	return $default;
}

/**
 * Remove third-party metadata while preserving this fork's cached update.
 *
 * This read filter never performs network I/O. The base plugin advertises a
 * different package at this plugin's basename, so unknown metadata is removed
 * before WordPress displays or installs it.
 *
 * @param  object $transient WordPress plugin update transient
 * @return object
 */
function ai1wmne_filter_managed_update( $transient ) {
	if ( ! is_object( $transient ) ) {
		return $transient;
	}

	if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
		$transient->response = array();
	}
	if ( ! isset( $transient->no_update ) || ! is_array( $transient->no_update ) ) {
		$transient->no_update = array();
	}

	$details = null;
	if ( isset( $transient->response[ AI1WMNE_PLUGIN_BASENAME ] ) && ai1wmne_is_community_update( $transient->response[ AI1WMNE_PLUGIN_BASENAME ] ) ) {
		$details = $transient->response[ AI1WMNE_PLUGIN_BASENAME ];
	} elseif ( isset( $transient->no_update[ AI1WMNE_PLUGIN_BASENAME ] ) && ai1wmne_is_community_update( $transient->no_update[ AI1WMNE_PLUGIN_BASENAME ] ) ) {
		$details = $transient->no_update[ AI1WMNE_PLUGIN_BASENAME ];
	}

	unset( $transient->response[ AI1WMNE_PLUGIN_BASENAME ] );
	unset( $transient->no_update[ AI1WMNE_PLUGIN_BASENAME ] );

	if ( $details ) {
		$new_version = is_object( $details ) ? $details->new_version : $details['new_version'];
		if ( version_compare( AI1WMNE_FORK_VERSION, $new_version, '<' ) ) {
			$transient->response[ AI1WMNE_PLUGIN_BASENAME ] = $details;
		} else {
			$transient->no_update[ AI1WMNE_PLUGIN_BASENAME ] = $details;
		}
	}

	return $transient;
}

/**
 * Check whether update metadata belongs to this community fork.
 *
 * @param  array|object $details Plugin update details
 * @return boolean
 */
function ai1wmne_is_community_update( $details ) {
	if ( is_object( $details ) ) {
		return isset( $details->id, $details->new_version ) && $details->id === AI1WMNE_GITHUB_REPOSITORY_URL;
	}

	return is_array( $details ) &&
		isset( $details['id'], $details['new_version'] ) &&
		$details['id'] === AI1WMNE_GITHUB_REPOSITORY_URL;
}

/**
 * Fetch release metadata while WordPress refreshes its plugin update cache.
 *
 * @param  object $transient WordPress plugin update transient
 * @return object
 */
function ai1wmne_refresh_managed_update( $transient ) {
	$transient = ai1wmne_filter_managed_update( $transient );
	if ( ! is_object( $transient ) ) {
		return $transient;
	}

	if ( $release = ai1wmne_github_release() ) {
		unset( $transient->response[ AI1WMNE_PLUGIN_BASENAME ] );
		unset( $transient->no_update[ AI1WMNE_PLUGIN_BASENAME ] );

		$details = ai1wmne_github_update_details( $release );
		if ( version_compare( AI1WMNE_FORK_VERSION, $release['version'], '<' ) ) {
			$transient->response[ AI1WMNE_PLUGIN_BASENAME ] = $details;
		} else {
			$transient->no_update[ AI1WMNE_PLUGIN_BASENAME ] = $details;
		}
	}

	return $transient;
}

/**
 * Prevent the base plugin from sending site and administrator details to its
 * private extension-update check. Public release metadata remains available.
 *
 * @param  mixed  $response Short-circuit response
 * @param  array  $args     HTTP request arguments
 * @param  string $url      Request URL
 * @return mixed
 */
function ai1wmne_block_private_update_check( $response, $args, $url ) {
	if ( ! defined( 'AI1WMNE_PLUGIN_CHECK' ) || strpos( $url, AI1WMNE_PLUGIN_CHECK ) !== 0 ) {
		return $response;
	}

	return array(
		'headers'  => array(),
		'body'     => '{}',
		'response' => array(
			'code'    => 200,
			'message' => 'OK',
		),
		'cookies'  => array(),
		'filename' => null,
	);
}

/**
 * Return the latest upstream version cached by the base plugin.
 *
 * @return string|null
 */
function ai1wmne_upstream_version() {
	if ( ! defined( 'AI1WM_UPDATER' ) ) {
		return null;
	}

	$updates = get_option( AI1WM_UPDATER, array() );
	if ( isset( $updates[ AI1WMNE_PLUGIN_NAME ]['version'] ) ) {
		return (string) $updates[ AI1WMNE_PLUGIN_NAME ]['version'];
	}

	return null;
}

/**
 * Read the latest stable GitHub release and locate its installable ZIP asset.
 *
 * Successful responses are cached for one hour. Failures are cached for ten
 * minutes to avoid repeatedly hitting the unauthenticated GitHub API limit.
 *
 * @return array|null
 */
function ai1wmne_github_release() {
	$cached = get_site_transient( AI1WMNE_GITHUB_RELEASE_CACHE );
	if ( is_array( $cached ) && array_key_exists( 'release', $cached ) ) {
		return $cached['release'];
	}

	$response = wp_remote_get(
		AI1WMNE_GITHUB_RELEASE_API,
		array(
			'headers' => array(
				'Accept'               => 'application/vnd.github+json',
				'User-Agent'           => 'ai1wm-s3-community/' . AI1WMNE_FORK_VERSION,
				'X-GitHub-Api-Version' => '2022-11-28',
			),
			'timeout' => 10,
		)
	);

	if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
		set_site_transient( AI1WMNE_GITHUB_RELEASE_CACHE, array( 'release' => null ), 10 * 60 );
		return null;
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $data ) || empty( $data['tag_name'] ) || ! empty( $data['draft'] ) || ! empty( $data['prerelease'] ) ) {
		set_site_transient( AI1WMNE_GITHUB_RELEASE_CACHE, array( 'release' => null ), 10 * 60 );
		return null;
	}

	$version = preg_replace( '/^[vV]/', '', trim( $data['tag_name'] ) );
	if ( ! preg_match( '/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version ) ) {
		set_site_transient( AI1WMNE_GITHUB_RELEASE_CACHE, array( 'release' => null ), 10 * 60 );
		return null;
	}

	$asset_name = sprintf( '%s-%s.zip', AI1WMNE_PLUGIN_NAME, $version );
	$package    = null;
	if ( ! empty( $data['assets'] ) && is_array( $data['assets'] ) ) {
		foreach ( $data['assets'] as $asset ) {
			if ( isset( $asset['name'], $asset['browser_download_url'] ) && $asset['name'] === $asset_name ) {
				$package = $asset['browser_download_url'];
				break;
			}
		}
	}

	if ( ! $package ) {
		set_site_transient( AI1WMNE_GITHUB_RELEASE_CACHE, array( 'release' => null ), 10 * 60 );
		return null;
	}

	$release = array(
		'version'      => $version,
		'package'      => $package,
		'published_at' => isset( $data['published_at'] ) ? $data['published_at'] : null,
		'body'         => isset( $data['body'] ) ? $data['body'] : '',
	);

	set_site_transient( AI1WMNE_GITHUB_RELEASE_CACHE, array( 'release' => $release ), 60 * 60 );
	return $release;
}

/**
 * Build the update object consumed by WordPress core.
 *
 * @param  array $release Parsed GitHub release
 * @return object
 */
function ai1wmne_github_update_details( $release ) {
	global $wp_version;

	return (object) array(
		'id'           => AI1WMNE_GITHUB_REPOSITORY_URL,
		'slug'         => AI1WMNE_PLUGIN_NAME,
		'plugin'       => AI1WMNE_PLUGIN_BASENAME,
		'new_version'  => $release['version'],
		'url'          => AI1WMNE_GITHUB_REPOSITORY_URL,
		'package'      => $release['package'],
		'tested'       => $wp_version,
		'requires_php' => '5.3',
	);
}

/**
 * Supply the details modal for updates distributed through GitHub Releases.
 *
 * @param  mixed        $result Existing Plugins API result
 * @param  string       $action Requested API action
 * @param  array|object $args   API arguments
 * @return mixed
 */
function ai1wmne_github_plugin_information( $result, $action, $args ) {
	if ( $action !== 'plugin_information' || ! is_object( $args ) || empty( $args->slug ) || $args->slug !== AI1WMNE_PLUGIN_NAME ) {
		return $result;
	}

	if ( ! ( $release = ai1wmne_github_release() ) ) {
		return $result;
	}

	return (object) array(
		'name'          => 'All-in-One WP Migration S3 Client Extension',
		'slug'          => AI1WMNE_PLUGIN_NAME,
		'version'       => $release['version'],
		'author'        => '<a href="https://best-web.sk/">Simon Fujdala</a>',
		'author_profile' => 'https://best-web.sk/',
		'homepage'      => AI1WMNE_GITHUB_REPOSITORY_URL,
		'external'      => true,
		'requires'      => '3.3',
		'tested'        => isset( $GLOBALS['wp_version'] ) ? $GLOBALS['wp_version'] : '',
		'requires_php'  => '5.3',
		'last_updated'  => $release['published_at'],
		'download_link' => $release['package'],
		'sections'      => array(
			'description' => 'Community-maintained S3-compatible storage extension for All-in-One WP Migration.',
			'changelog'   => nl2br( esc_html( $release['body'] ) ),
		),
	);
}
