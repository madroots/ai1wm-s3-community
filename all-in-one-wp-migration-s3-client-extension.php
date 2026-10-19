<?php
/**
 * Plugin Name: All-in-One WP Migration S3 Client Extension
 * Plugin URI: https://best-web.sk/
 * Description: Independently maintained GPL fork of the S3 Client extension for All-in-One WP Migration.
 * Author: Simon Fujdala
 * Author URI: https://best-web.sk/
 * Version: 1.52.3
 * Text Domain: all-in-one-wp-migration-s3-client-extension
 * Domain Path: /languages
 * Network: True
 * Update URI: https://github.com/madroots/ai1wm-s3-community
 * Requires Plugins: all-in-one-wp-migration
 *
 * Fork maintained by Simon Fujdala, 2026.
 * Original copyright (C) 2014-2020 ServMask Inc.
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

// Check SSL Mode
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && ( $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) ) {
	$_SERVER['HTTPS'] = 'on';
}

// Plugin Basename
define( 'AI1WMNE_PLUGIN_BASENAME', basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ) );

// Plugin Path
define( 'AI1WMNE_PATH', dirname( __FILE__ ) );

// Plugin URL
define( 'AI1WMNE_URL', plugins_url( '', AI1WMNE_PLUGIN_BASENAME ) );

// Include constants
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'constants.php';

// Include functions
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'functions.php';

// Keep upstream release metadata visible without allowing another package to
// overwrite this independently maintained fork.
add_filter( 'site_transient_update_plugins', 'ai1wmne_filter_managed_update', PHP_INT_MAX );
add_filter( 'pre_set_site_transient_update_plugins', 'ai1wmne_refresh_managed_update', PHP_INT_MAX );
add_filter( 'pre_http_request', 'ai1wmne_block_private_update_check', PHP_INT_MAX, 3 );
add_filter( 'plugins_api', 'ai1wmne_github_plugin_information', PHP_INT_MAX, 3 );

// Include exceptions
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'exceptions.php';

// Include loader
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'loader.php';

// ===========================================================================
// = All app initialization is done in Ai1wmne_Main_Controller __constructor =
// ===========================================================================
$main_controller = new Ai1wmne_Main_Controller( 'AI1WMNE', 's3-client' );
