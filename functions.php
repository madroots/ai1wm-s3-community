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
 * Read this fork's stored update version from the plugin update transient.
 *
 * @param  object $transient WordPress plugin update transient
 * @return string|null
 */
function ai1wmne_stored_update_version( $transient ) {
	foreach ( array( 'response', 'no_update' ) as $key ) {
		if ( empty( $transient->{$key}[ AI1WMNE_PLUGIN_BASENAME ] ) ) {
			continue;
		}

		$details = $transient->{$key}[ AI1WMNE_PLUGIN_BASENAME ];
		if ( ! ai1wmne_is_community_update( $details ) ) {
			continue;
		}

		return is_object( $details ) ? $details->new_version : $details['new_version'];
	}

	return null;
}

/**
 * Clear cached update data when an administrator requests a fresh check.
 *
 * WordPress forces core checks with `?force-check=1`, but a stored
 * `update_plugins` transient and this plugin's release cache both suppress a
 * newly published release. Removing them lets the pending plugin check run
 * immediately and refetch the latest GitHub release.
 *
 * @return void
 */
function ai1wmne_force_update_refresh() {
	if ( empty( $_GET['force-check'] ) || ! current_user_can( 'update_plugins' ) ) {
		return;
	}

	delete_site_transient( 'update_plugins' );
	delete_site_transient( AI1WMNE_GITHUB_RELEASE_CACHE );
}

/**
 * Reconcile the Plugins screen with the latest cached GitHub release.
 *
 * WordPress refreshes plugin updates at most once an hour on this screen, so a
 * newly published release would otherwise stay hidden until that check runs.
 * This keeps the stored transient current, and the release lookup itself is
 * cached to avoid repeated GitHub requests.
 *
 * @return void
 */
function ai1wmne_sync_plugins_screen() {
	if ( ! current_user_can( 'update_plugins' ) ) {
		return;
	}

	$transient = get_site_transient( 'update_plugins' );
	if ( ! is_object( $transient ) ) {
		$transient = (object) array(
			'response'     => array(),
			'no_update'    => array(),
			'last_checked' => time(),
		);
	}

	$release = ai1wmne_github_release();
	if ( ! $release ) {
		return;
	}

	if ( ai1wmne_stored_update_version( $transient ) === $release['version'] ) {
		return;
	}

	set_site_transient( 'update_plugins', ai1wmne_refresh_managed_update( $transient ) );
}

/**
 * Explain a failed or unregistered update check on update screens.
 *
 * Silent failures are otherwise indistinguishable from "up to date": a site
 * whose server cannot reach GitHub, or a transient another plugin removed,
 * would never show the published release.
 *
 * @return void
 */
function ai1wmne_update_diagnostics_notice() {
	if ( ! current_user_can( 'update_plugins' ) ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || ! in_array( $screen->id, array( 'plugins', 'update-core', 'plugins-network', 'update-core-network' ), true ) ) {
		return;
	}

	$state = ai1wmne_github_release_state();
	if ( ! is_array( $state ) ) {
		return;
	}

	$retry_url = function_exists( 'is_network_admin' ) && is_network_admin()
		? network_admin_url( 'update-core.php?force-check=1' )
		: admin_url( 'update-core.php?force-check=1' );

	$retry = '<a href="' . esc_url( $retry_url ) . '">' .
		esc_html__( 'Retry', AI1WMNE_PLUGIN_NAME ) .
		'</a>';

	if ( empty( $state['release'] ) ) {
		$error = ! empty( $state['error'] ) ? $state['error'] : __( 'unknown error', AI1WMNE_PLUGIN_NAME );

		printf(
			'<div class="notice notice-warning"><p>%s %s</p></div>',
			sprintf(
				esc_html__( 'S3 Client update check failed: %s', AI1WMNE_PLUGIN_NAME ),
				esc_html( $error )
			),
			$retry
		);
		return;
	}

	if ( version_compare( AI1WMNE_FORK_VERSION, $state['release']['version'], '>=' ) ) {
		return;
	}

	$transient = get_site_transient( 'update_plugins' );
	if ( is_object( $transient ) && isset( $transient->response[ AI1WMNE_PLUGIN_BASENAME ] ) ) {
		return;
	}

	printf(
		'<div class="notice notice-warning"><p>%s %s</p></div>',
		sprintf(
			esc_html__( 'S3 Client %1$s is published, but WordPress did not register the update. Installed version: %2$s.', AI1WMNE_PLUGIN_NAME ),
			esc_html( $state['release']['version'] ),
			esc_html( AI1WMNE_FORK_VERSION )
		),
		$retry
	);
}

/**
 * Run an immediate update check from the plugin row.
 *
 * WordPress throttles its own checks, and a broken outbound request is
 * otherwise invisible. This clears both caches, records a fresh result, and
 * reloads the Plugins screen so the row status shows what happened.
 *
 * @return void
 */
function ai1wmne_manual_update_check() {
	if ( empty( $_GET['ai1wmne-check'] ) || ! current_user_can( 'update_plugins' ) ) {
		return;
	}

	check_admin_referer( 'ai1wmne_check' );

	delete_site_transient( 'update_plugins' );
	delete_site_transient( AI1WMNE_GITHUB_RELEASE_CACHE );
	ai1wmne_github_release();

	wp_safe_redirect(
		function_exists( 'is_network_admin' ) && is_network_admin()
			? network_admin_url( 'plugins.php' )
			: admin_url( 'plugins.php' )
	);
	exit;
}

/**
 * Add an immediate update check to the plugin action links.
 *
 * @param  array $links Plugin action links
 * @return array
 */
function ai1wmne_plugin_action_links( $links ) {
	if ( ! current_user_can( 'update_plugins' ) ) {
		return $links;
	}

	$url = function_exists( 'is_network_admin' ) && is_network_admin()
		? network_admin_url( 'plugins.php?ai1wmne-check=1' )
		: admin_url( 'plugins.php?ai1wmne-check=1' );

	$links[] = sprintf(
		'<a href="%s">%s</a>',
		esc_url( wp_nonce_url( $url, 'ai1wmne_check' ) ),
		esc_html__( 'Check for updates', AI1WMNE_PLUGIN_NAME )
	);

	return $links;
}

/**
 * Print the latest GitHub check result in the plugin row.
 *
 * @param  array  $links Plugin row links
 * @param  string $file  Plugin basename
 * @return array
 */
function ai1wmne_plugin_row_update_status( $links, $file ) {
	if ( $file !== AI1WMNE_PLUGIN_BASENAME ) {
		return $links;
	}

	$state = ai1wmne_github_release_state();
	if ( ! is_array( $state ) ) {
		return $links;
	}

	if ( empty( $state['release'] ) ) {
		$status = sprintf(
			__( 'GitHub update check failed: %s', AI1WMNE_PLUGIN_NAME ),
			! empty( $state['error'] ) ? $state['error'] : __( 'unknown error', AI1WMNE_PLUGIN_NAME )
		);
	} else {
		$status = sprintf(
			__( 'GitHub release: %s', AI1WMNE_PLUGIN_NAME ),
			$state['release']['version']
		);
	}

	if ( ! empty( $state['checked'] ) ) {
		$status .= ' (' . sprintf(
			__( 'checked %s ago', AI1WMNE_PLUGIN_NAME ),
			human_time_diff( $state['checked'] )
		) . ')';
	}

	$links[] = esc_html( $status );

	return $links;
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
 * The GitHub API is queried first. When it is rate-limited, blocked, or
 * otherwise unavailable, the public release page redirect is used to resolve
 * the tag and build the deterministic asset URL. Successful and failed
 * lookups are cached for ten minutes.
 *
 * @return array|null
 */
function ai1wmne_github_release() {
	$cached = get_site_transient( AI1WMNE_GITHUB_RELEASE_CACHE );
	if ( is_array( $cached ) && array_key_exists( 'release', $cached ) ) {
		return $cached['release'];
	}

	$error      = '';
	$release    = null;
	$api_usable = false;

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

	if ( is_wp_error( $response ) ) {
		$error = $response->get_error_message();
	} elseif ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
		$error = sprintf( 'GitHub API returned HTTP %d', (int) wp_remote_retrieve_response_code( $response ) );
	} else {
		$release = ai1wmne_github_release_from_api( wp_remote_retrieve_body( $response ), $error, $api_usable );
	}

	/*
	 * Only use the release page when the API itself was unusable. A readable
	 * API response that lacks the expected asset must not be replaced with a
	 * synthesized download URL that does not exist.
	 */
	if ( ! $release && ! $api_usable ) {
		$fallback_error = '';
		$release        = ai1wmne_github_release_from_redirect( $fallback_error );
		if ( ! $release ) {
			ai1wmne_github_release_failed( $error ? $error . '; ' . $fallback_error : $fallback_error );
			return null;
		}
	}

	if ( ! $release ) {
		ai1wmne_github_release_failed( $error );
		return null;
	}

	set_site_transient(
		AI1WMNE_GITHUB_RELEASE_CACHE,
		array(
			'release' => $release,
			'checked' => time(),
		),
		10 * 60
	);

	return $release;
}

/**
 * Parse a GitHub API release payload.
 *
 * @param  string  $body   Response body
 * @param  string  $error  Failure reason
 * @param  boolean $usable Whether the API returned a readable release
 * @return array|null
 */
function ai1wmne_github_release_from_api( $body, &$error, &$usable ) {
	$data = json_decode( $body, true );
	if ( ! is_array( $data ) || empty( $data['tag_name'] ) ) {
		$error = 'GitHub API returned an unreadable payload';
		return null;
	}

	$usable = true;

	if ( ! empty( $data['draft'] ) || ! empty( $data['prerelease'] ) ) {
		$error = 'the latest GitHub release is a draft or prerelease';
		return null;
	}

	$version = preg_replace( '/^[vV]/', '', trim( $data['tag_name'] ) );
	if ( ! preg_match( '/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version ) ) {
		$error = sprintf( 'unsupported release tag %s', $data['tag_name'] );
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
		$error = sprintf( 'release asset %s is missing', $asset_name );
		return null;
	}

	return array(
		'version'      => $version,
		'package'      => $package,
		'published_at' => isset( $data['published_at'] ) ? $data['published_at'] : null,
		'body'         => isset( $data['body'] ) ? $data['body'] : '',
	);
}

/**
 * Resolve the latest tag from the public release page redirect.
 *
 * @param  string $error Failure reason
 * @return array|null
 */
function ai1wmne_github_release_from_redirect( &$error ) {
	$response = wp_remote_get(
		AI1WMNE_GITHUB_REPOSITORY_URL . '/releases/latest',
		array(
			'headers'     => array(
				'User-Agent' => 'ai1wm-s3-community/' . AI1WMNE_FORK_VERSION,
			),
			'redirection' => 0,
			'timeout'     => 10,
		)
	);

	if ( is_wp_error( $response ) ) {
		$error = $response->get_error_message();
		return null;
	}

	$code = wp_remote_retrieve_response_code( $response );
	if ( $code < 300 || $code >= 400 ) {
		$error = sprintf( 'GitHub release page returned HTTP %d', (int) $code );
		return null;
	}

	$location = wp_remote_retrieve_header( $response, 'location' );
	if ( ! preg_match( '#/tag/(v?\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?)$#', (string) $location, $matches ) ) {
		$error = 'GitHub release redirect did not contain a version tag';
		return null;
	}

	$tag     = $matches[1];
	$version = preg_replace( '/^[vV]/', '', $tag );

	return array(
		'version'      => $version,
		'package'      => sprintf(
			'%s/releases/download/%s/%s-%s.zip',
			AI1WMNE_GITHUB_REPOSITORY_URL,
			$tag,
			AI1WMNE_PLUGIN_NAME,
			$version
		),
		'published_at' => null,
		'body'         => '',
	);
}

/**
 * Cache a failed release lookup.
 *
 * @param  string $error Failure reason
 * @return void
 */
function ai1wmne_github_release_failed( $error ) {
	set_site_transient(
		AI1WMNE_GITHUB_RELEASE_CACHE,
		array(
			'release' => null,
			'error'   => $error,
			'checked' => time(),
		),
		10 * 60
	);
}

/**
 * Return the cached release lookup state.
 *
 * @return array|null
 */
function ai1wmne_github_release_state() {
	$cached = get_site_transient( AI1WMNE_GITHUB_RELEASE_CACHE );
	return is_array( $cached ) ? $cached : null;
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
