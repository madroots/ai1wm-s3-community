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

class Ai1wmne_Settings_Controller {

	public static function index() {
		$model = new Ai1wmne_Settings;

		$s3_backup_schedules   = get_option( 'ai1wmne_s3_cron', array() );
		$s3_cron_timestamp     = get_option( 'ai1wmne_s3_cron_timestamp', time() );
		$last_backup_timestamp = get_option( 'ai1wmne_s3_timestamp', false );

		$last_backup_date = $model->get_last_backup_date( $last_backup_timestamp );
		$next_backup_date = $model->get_next_backup_date( $s3_backup_schedules );

		try {
			$buckets = $model->get_buckets();
		} catch ( Ai1wmne_Error_Exception $e ) {
			$buckets = false;
		}

		Ai1wm_Template::render(
			'settings/index',
			array(
				's3_backup_schedules' => $s3_backup_schedules,
				's3_cron_timestamp'   => $s3_cron_timestamp,
				'notify_ok_toggle'    => get_option( 'ai1wmne_s3_notify_toggle', false ),
				'notify_error_toggle' => get_option( 'ai1wmne_s3_notify_error_toggle', false ),
				'notify_email'        => get_option( 'ai1wmne_s3_notify_email', get_option( 'admin_email', false ) ),
				'last_backup_date'    => $last_backup_date,
				'next_backup_date'    => $next_backup_date,
				'api_endpoint'        => get_option( 'ai1wmne_s3_api_endpoint', false ),
				'bucket_template'     => get_option( 'ai1wmne_s3_bucket_template', false ),
				'access_key'          => get_option( 'ai1wmne_s3_access_key', ai1wmne_aws_access_key() ),
				'secret_key'          => get_option( 'ai1wmne_s3_secret_key', ai1wmne_aws_secret_key() ),
				'https_protocol'      => get_option( 'ai1wmne_s3_https_protocol', true ),
				'bucket_name'         => get_option( 'ai1wmne_s3_bucket_name', ai1wm_archive_bucket() ),
				'region_name'         => get_option( 'ai1wmne_s3_region_name', ai1wmne_aws_region_name( AI1WMNE_S3_DEFAULT_REGION_NAME ) ),
				'folder_name'         => get_option( 'ai1wmne_s3_folder_name', '' ),
				'file_chunk_size'     => get_option( 'ai1wmne_s3_file_chunk_size', AI1WMNE_DEFAULT_FILE_CHUNK_SIZE ),
				'storage_class'       => get_option( 'ai1wmne_s3_storage_class', AI1WMNE_S3_DEFAULT_STORAGE_CLASS ),
				'encryption'          => get_option( 'ai1wmne_s3_encryption', false ),
				'backups'             => get_option( 'ai1wmne_s3_backups', false ),
				'total'               => get_option( 'ai1wmne_s3_total', false ),
				'days'                => get_option( 'ai1wmne_s3_days', false ),
				'incremental'         => get_option( 'ai1wmne_s3_incremental', false ),
				'buckets'             => $buckets,
			),
			AI1WMNE_TEMPLATES_PATH
		);
	}

	/**
	 * Reject admin-post requests from users who may not manage S3 settings or
	 * that lack a valid nonce. admin-post.php runs handlers for any logged-in
	 * user, so this check is the only access control.
	 *
	 * @param  string $action Nonce action, also the admin-post action name
	 * @return void
	 */
	protected static function authorize( $action ) {
		if ( ! current_user_can( 'export' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to manage S3 Client settings.', AI1WMNE_PLUGIN_NAME ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( $action, $action . '_nonce' );
	}

	public static function connection( $params = array() ) {
		self::authorize( 'ai1wmne_s3_connection' );
		ai1wm_setup_environment();

		// Set params
		if ( empty( $params ) ) {
			$params = stripslashes_deep( $_POST );
		}

		// S3 Client update
		if ( isset( $params['ai1wmne_s3_update'] ) ) {
			try {

				$model = new Ai1wmne_Settings;

				// Set API endpoint
				if ( isset( $params['ai1wmne_s3_api_endpoint'] ) ) {
					$model->set_api_endpoint( trim( $params['ai1wmne_s3_api_endpoint'] ) );
				}

				// Set bucket template
				if ( isset( $params['ai1wmne_s3_bucket_template'] ) ) {
					$model->set_bucket_template( trim( $params['ai1wmne_s3_bucket_template'] ) );
				}

				// Set region name
				if ( isset( $params['ai1wmne_s3_region_name'] ) ) {
					$model->set_region_name( trim( $params['ai1wmne_s3_region_name'] ) );
				}

				// Access key
				if ( isset( $params['ai1wmne_s3_access_key'] ) ) {
					$model->set_access_key( trim( $params['ai1wmne_s3_access_key'] ) );
				}

				// Secret key
				if ( ! empty( $params['ai1wmne_s3_secret_key'] ) ) {
					$model->set_secret_key( trim( $params['ai1wmne_s3_secret_key'] ) );
				}

				// HTTPS protocol
				if ( ! empty( $params['ai1wmne_s3_https_protocol'] ) ) {
					$model->set_https_protocol( 1 );
				} else {
					$model->set_https_protocol( 0 );
				}

				// Get buckets
				$model->get_buckets();

				// Set message
				Ai1wm_Message::flash( 'success', __( 'S3 connection is successfully established.', AI1WMNE_PLUGIN_NAME ) );

			} catch ( Ai1wmne_Error_Exception $e ) {
				Ai1wm_Message::flash( 'error', $e->getMessage() );
			}
		}

		// Redirect to settings page
		wp_redirect( network_admin_url( 'admin.php?page=ai1wmne_settings' ) );
		exit;
	}

	public static function settings( $params = array() ) {
		self::authorize( 'ai1wmne_s3_settings' );
		ai1wm_setup_environment();

		// Set params
		if ( empty( $params ) ) {
			$params = stripslashes_deep( $_POST );
		}

		// S3 Client update
		if ( isset( $params['ai1wmne_s3_update'] ) ) {
			try {

				$model = new Ai1wmne_Settings;

				// Set incremental
				if ( ! empty( $params['ai1wmne_s3_incremental'] ) ) {
					$model->set_incremental( 1 );
				} else {
					$model->set_incremental( 0 );
				}

				// Cron timestamp update
				if ( ! empty( $params['ai1wmne_s3_cron_timestamp'] ) && ( $cron_timestamp = strtotime( $params['ai1wmne_s3_cron_timestamp'], current_time( 'timestamp' ) ) ) ) {
					$model->set_cron_timestamp( strtotime( get_gmt_from_date( date( 'Y-m-d H:i:s', $cron_timestamp ) ) ) );
				} else {
					$model->set_cron_timestamp( time() );
				}

				// Bucket name
				if ( isset( $params['ai1wmne_s3_bucket_name'] ) ) {
					$model->create_bucket( strtolower( trim( $params['ai1wmne_s3_bucket_name'] ) ) );
					$model->set_bucket_name( strtolower( trim( $params['ai1wmne_s3_bucket_name'] ) ) );
				}

				// Folder name
				if ( isset( $params['ai1wmne_s3_folder_name'] ) ) {
					$model->set_folder_name( trim( $params['ai1wmne_s3_folder_name'] ) );
				}

				// Storage class
				if ( isset( $params['ai1wmne_s3_storage_class'] ) ) {
					$model->set_storage_class( trim( $params['ai1wmne_s3_storage_class'] ) );
				}

				// Bucket encryption
				if ( isset( $params['ai1wmne_s3_encryption'] ) ) {
					$model->set_encryption( trim( $params['ai1wmne_s3_encryption'] ) );
				} else {
					$model->set_encryption( false );
				}

				// Cron update
				if ( ! empty( $params['ai1wmne_s3_cron'] ) ) {
					$model->set_cron( (array) $params['ai1wmne_s3_cron'] );
				} else {
					$model->set_cron( array() );
				}

				// Set number of backups
				if ( ! empty( $params['ai1wmne_s3_backups'] ) ) {
					$model->set_backups( (int) $params['ai1wmne_s3_backups'] );
				} else {
					$model->set_backups( 0 );
				}

				// Set size of backups
				if ( ! empty( $params['ai1wmne_s3_total'] ) && ! empty( $params['ai1wmne_s3_total_unit'] ) ) {
					$model->set_total( (int) $params['ai1wmne_s3_total'] . trim( $params['ai1wmne_s3_total_unit'] ) );
				} else {
					$model->set_total( 0 );
				}

				// Set age of backups
				if ( ! empty( $params['ai1wmne_s3_days'] ) ) {
					$model->set_days( (int) $params['ai1wmne_s3_days'] );
				} else {
					$model->set_days( 0 );
				}

				// Set file chunk size
				if ( ! empty( $params['ai1wmne_s3_file_chunk_size'] ) ) {
					$model->set_file_chunk_size( $params['ai1wmne_s3_file_chunk_size'] );
				} else {
					$model->set_file_chunk_size( AI1WMNE_DEFAULT_FILE_CHUNK_SIZE );
				}

				// Set notify ok toggle
				$model->set_notify_ok_toggle( isset( $params['ai1wmne_s3_notify_toggle'] ) );

				// Set notify error toggle
				$model->set_notify_error_toggle( isset( $params['ai1wmne_s3_notify_error_toggle'] ) );

				// Set notify email
				$model->set_notify_email( trim( $params['ai1wmne_s3_notify_email'] ) );

				// Set message
				Ai1wm_Message::flash( 'settings', __( 'Your changes have been saved.', AI1WMNE_PLUGIN_NAME ) );

			} catch ( Ai1wmne_Error_Exception $e ) {
				Ai1wm_Message::flash( 'bucket', $e->getMessage() );
			}
		}

		// Redirect to settings page
		wp_redirect( network_admin_url( 'admin.php?page=ai1wmne_settings' ) );
		exit;
	}

	public static function init_cron() {
		$model = new Ai1wmne_Settings;
		return $model->init_cron();
	}

	public static function notify_ok_toggle() {
		$model = new Ai1wmne_Settings;
		return $model->get_notify_ok_toggle();
	}

	public static function notify_error_toggle() {
		$model = new Ai1wmne_Settings;
		return $model->get_notify_error_toggle();
	}

	public static function notify_error_subject() {
		$model = new Ai1wmne_Settings;
		return $model->get_notify_error_subject();
	}

	public static function notify_email() {
		$model = new Ai1wmne_Settings;
		return $model->get_notify_email();
	}
}
