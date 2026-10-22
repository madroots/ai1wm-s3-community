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

// ===================
// = Plugin Versions =
// ===================
// "develop" is the base plugin's supported marker for independently
// maintained development builds; the public fork version remains semantic.
define( 'AI1WMNE_VERSION', 'develop' );
define( 'AI1WMNE_FORK_VERSION', '1.52.12' );
define( 'AI1WMNE_UPSTREAM_VERSION', '1.51' );
define( 'AI1WMNE_MAX_REVIEWED_AI1WM_VERSION', '7.112' );

// ===============
// = Plugin Name =
// ===============
define( 'AI1WMNE_PLUGIN_NAME', 'all-in-one-wp-migration-s3-client-extension' );

// =========================
// = GitHub Release Updates =
// =========================
// Overridable (e.g. by the test harness) before this file is loaded.
defined( 'AI1WMNE_GITHUB_REPOSITORY_URL' ) || define( 'AI1WMNE_GITHUB_REPOSITORY_URL', 'https://github.com/madroots/ai1wm-s3-community' );
defined( 'AI1WMNE_GITHUB_RELEASE_API' ) || define( 'AI1WMNE_GITHUB_RELEASE_API', 'https://api.github.com/repos/madroots/ai1wm-s3-community/releases/latest' );
defined( 'AI1WMNE_GITHUB_RELEASE_CACHE' ) || define( 'AI1WMNE_GITHUB_RELEASE_CACHE', 'ai1wmne_github_release' );

// ============
// = Lib Path =
// ============
define( 'AI1WMNE_LIB_PATH', AI1WMNE_PATH . DIRECTORY_SEPARATOR . 'lib' );

// ===================
// = Controller Path =
// ===================
define( 'AI1WMNE_CONTROLLER_PATH', AI1WMNE_LIB_PATH . DIRECTORY_SEPARATOR . 'controller' );

// ==============
// = Model Path =
// ==============
define( 'AI1WMNE_MODEL_PATH', AI1WMNE_LIB_PATH . DIRECTORY_SEPARATOR . 'model' );

// ===============
// = Export Path =
// ===============
define( 'AI1WMNE_EXPORT_PATH', AI1WMNE_MODEL_PATH . DIRECTORY_SEPARATOR . 'export' );

// ===============
// = Import Path =
// ===============
define( 'AI1WMNE_IMPORT_PATH', AI1WMNE_MODEL_PATH . DIRECTORY_SEPARATOR . 'import' );

// =============
// = View Path =
// =============
define( 'AI1WMNE_TEMPLATES_PATH', AI1WMNE_LIB_PATH . DIRECTORY_SEPARATOR . 'view' );

// ===============
// = Vendor Path =
// ===============
define( 'AI1WMNE_VENDOR_PATH', AI1WMNE_LIB_PATH . DIRECTORY_SEPARATOR . 'vendor' );

// =================================
// = S3 Client Default Region Name =
// =================================
define( 'AI1WMNE_S3_DEFAULT_REGION_NAME', 'us-east-1' );

// ===================================
// = S3 Client Default Storage Class =
// ===================================
define( 'AI1WMNE_S3_DEFAULT_STORAGE_CLASS', 'STANDARD' );

// ===========================
// = Default File Chunk Size =
// ===========================
define( 'AI1WMNE_DEFAULT_FILE_CHUNK_SIZE', 5 * 1024 * 1024 );

// ===============================
// = Minimal Base Plugin Version =
// ===============================
define( 'AI1WMNE_MIN_AI1WM_VERSION', '7.84' );

// ================================
// = Add incremental to scheduler =
// ================================
define( 'AI1WMNE_PRO_INCREMENTAL', 1 );

// Required by the base plugin's extension registry; contains no credential.
define( 'AI1WMNE_PURCHASE_ID', '' );
