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
 * Remove third-party update metadata for this independently maintained fork.
 *
 * The base plugin advertises a different package at this plugin's basename.
 * Leaving that response in place can overwrite the fork.
 *
 * @param  object $transient WordPress plugin update transient
 * @return object
 */
function ai1wmne_filter_managed_update( $transient ) {
	if ( ! is_object( $transient ) ) {
		return $transient;
	}

	if ( isset( $transient->response ) && is_array( $transient->response ) ) {
		unset( $transient->response[ AI1WMNE_PLUGIN_BASENAME ] );
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
