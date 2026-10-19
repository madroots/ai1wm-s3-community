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

if ( defined( 'WP_CLI' ) && class_exists( 'Ai1wm_Backup_WP_CLI_Base' ) ) {
	class Ai1wmne_S3_Client_WP_CLI_Command extends Ai1wm_Backup_WP_CLI_Base {
		public function __construct() {
			parent::__construct();

			if ( ! get_option( 'ai1wmne_s3_access_key', ai1wmne_aws_access_key() ) || ! get_option( 'ai1wmne_s3_secret_key', ai1wmne_aws_secret_key() ) ) {
				WP_CLI::error_multi_line(
					array(
						__( 'In order to use All-in-One WP Migration S3 Client extension you need to configure it first.', AI1WMNE_PLUGIN_NAME ),
						__( 'Please navigate to WP Admin > All-in-One WP Migration > S3 Client Settings and specify your S3 connection details.', AI1WMNE_PLUGIN_NAME ),
					)
				);
				exit;
			}
		}

		/**
		 * Creates a new backup and uploads to S3-compatible storage.
		 *
		 * ## OPTIONS
		 *
		 * [--sites[=<comma_separated_ids>]]
		 * : Export sites by id (Multisite only). To list sites use: wp site list --fields=blog_id,url
		 *
		 * [--password[=<password>]]
		 * : Encrypt backup with password
		 *
		 * [--exclude-spam-comments]
		 * : Do not export spam comments
		 *
		 * [--exclude-post-revisions]
		 * : Do not export post revisions
		 *
		 * [--exclude-media]
		 * : Do not export media library (files)
		 *
		 * [--exclude-themes]
		 * : Do not export themes (files)
		 *
		 * [--exclude-inactive-themes]
		 * : Do not export inactive themes (files)
		 *
		 * [--exclude-muplugins]
		 * : Do not export must-use plugins (files)
		 *
		 * [--exclude-plugins]
		 * : Do not export plugins (files)
		 *
		 * [--exclude-inactive-plugins]
		 * : Do not export inactive plugins (files)
		 *
		 * [--exclude-cache]
		 * : Do not export cache (files)
		 *
		 * [--exclude-database]
		 * : Do not export database (sql)
		 *
		 * [--exclude-tables[=<comma_separated_names>]]
		 * : Do not export selected database tables (sql)
		 *
		 * [--exclude-email-replace]
		 * : Do not replace email domain (sql)
		 *
		 * [--replace]
		 * : Find and replace text in the database
		 *
		 * [<find>...]
		 * : A string to search for within the database
		 *
		 * [<replace>...]
		 * : Replace instances of the first string with this new string
		 *
		 * ## EXAMPLES
		 *
		 * $ wp ai1wm s3-client backup --replace "wp" "WordPress"
		 * Backup in progress...
		 * Uploading wordpress-20190509-152915-654.wpress (33 MB) [60% complete]
		 * Uploading wordpress-20190509-152915-654.wpress (33 MB) [100% complete]
		 * Success: Backup complete.
		 * Backup file: wordpress-20190509-152915-654.wpress
		 * Backup location: [region: us-east-1] [bucket: ai1wm-example-com] [path: backups/daily/ai1wm/wordpress-20190509-152915-654.wpress]
		 *
		 * @subcommand backup
		 */
		public function backup( $args = array(), $assoc_args = array() ) {
			$params = $this->run_backup(
				$this->build_export_params( $args, $assoc_args )
			);

			WP_CLI::log( sprintf( __( 'Backup location: %s', AI1WMNE_PLUGIN_NAME ), $this->get_backup_location( $params ) ) );
		}

		/**
		 * Get a list of S3 Client backup files.
		 *
		 * ## OPTIONS
		 *
		 * [--folder-path=<path>]
		 * : List backups in a specific S3 Client subfolder
		 *
		 * ## EXAMPLES
		 *
		 * $ wp ai1wm s3-client list-backups
		 * +------------------------------------------------+--------------+-----------+
		 * | Backup name                                    | Date created | Size      |
		 * +------------------------------------------------+--------------+-----------+
		 * | migration-wp-20170908-152313-435.wpress        | 4 days ago   | 536.77 MB |
		 * | migration-wp-20170908-152103-603.wpress        | 4 days ago   | 536.77 MB |
		 * | migration-wp-20170908-152036-162.wpress        | 4 days ago   | 536.77 MB |
		 * +------------------------------------------------+--------------+-----------+
		 *
		 * $ wp ai1wm s3-client list-backups --folder-path=/backups/daily
		 * +------------------------------------------------+--------------+-----------+
		 * | Backup name                                    | Date created | Size      |
		 * +------------------------------------------------+--------------+-----------+
		 * | migration-wp-20170908-152313-435.wpress        | 4 days ago   | 536.77 MB |
		 * | migration-wp-20170908-152103-603.wpress        | 4 days ago   | 536.77 MB |
		 * +------------------------------------------------+--------------+-----------+
		 *
		 * @subcommand list-backups
		 */
		public function list_backups( $args = array(), $assoc_args = array() ) {
			$backups = new cli\Table;

			$backups->setHeaders(
				array(
					'name' => __( 'Backup name', AI1WMNE_PLUGIN_NAME ),
					'date' => __( 'Date created', AI1WMNE_PLUGIN_NAME ),
					'size' => __( 'Size', AI1WMNE_PLUGIN_NAME ),
				)
			);

			$folder_path = $this->get_folder_path( $assoc_args );
			$items       = $this->list_items( $folder_path );

			// Set folder structure
			$response = array( 'items' => array(), 'num_hidden_files' => 0 );

			foreach ( $items as $item ) {
				if ( pathinfo( $item['name'], PATHINFO_EXTENSION ) === 'wpress' ) {
					$backups->addRow(
						array(
							'name' => $item['name'],
							'date' => sprintf( __( '%s ago', AI1WMNE_PLUGIN_NAME ), human_time_diff( $item['date'] ) ),
							'size' => ai1wm_size_format( $item['bytes'], 2 ),
						)
					);
				}
			}

			$backups->display();
		}

		/**
		 * Restores a backup from S3 Client.
		 *
		 * ## OPTIONS
		 *
		 * <file>
		 * : Name of the backup file
		 *
		 * [--folder-path=<path>]
		 * : Download a backup from a specific S3 Client subfolder
		 *
		 * [--yes]
		 * : Automatically confirm the restore operation
		 *
		 * ## EXAMPLES
		 *
		 * $ wp ai1wm s3-client restore migration-wp-20170913-095743-931.wpress
		 * Restore in progress...
		 * Restore complete.
		 *
		 * $ wp ai1wm s3-client restore migration-wp-20170913-095743-931.wpress --folder-path=/backups/daily
		 * @subcommand restore
		 */
		public function restore( $args = array(), $assoc_args = array() ) {
			if ( ! isset( $args[0] ) ) {
				WP_CLI::error_multi_line(
					array(
						__( 'A backup name must be provided in order to proceed with the restore process.', AI1WMNE_PLUGIN_NAME ),
						__( 'Example: wp ai1wm s3-client restore migration-wp-20170913-095743-931.wpress', AI1WMNE_PLUGIN_NAME ),
					)
				);
				exit;
			}

			$folder_path = $this->get_folder_path( $assoc_args );
			$items       = $this->list_items( $folder_path );
			$bucket_name = get_option( 'ai1wmne_s3_bucket_name', ai1wm_archive_bucket() );

			$file = null;
			foreach ( $items as $item ) {
				if ( $item['name'] === $args[0] ) {
					$file = $item;
					break;
				}
			}

			if ( is_null( $file ) ) {
				WP_CLI::error_multi_line(
					array(
						__( "The backup file could not be located in $folder_path folder.", AI1WMNE_PLUGIN_NAME ),
						__( 'To list available backups use: wp ai1wm s3-client list-backups', AI1WMNE_PLUGIN_NAME ),
					)
				);
				exit;
			}

			$params = array(
				'archive'     => $args[0],
				'storage'     => ai1wm_storage_folder(),
				'file_path'   => $file['path'],
				'file_size'   => $file['bytes'],
				'bucket_name' => $bucket_name,
				'cli_args'    => $assoc_args,
				'secret_key'  => get_option( AI1WM_SECRET_KEY, false ),
			);

			$this->run_restore( $params );
		}

		/**
		 * Get backup items list
		 *
		 * @param  string $folder_path Folder path where backups located
		 * @return array  $items       Backup items
		 */
		protected function list_items( $folder_path ) {
			// Set S3 client
			$s3 = new Ai1wmne_S3_Client(
				get_option( 'ai1wmne_s3_access_key', ai1wmne_aws_access_key() ),
				get_option( 'ai1wmne_s3_secret_key', false ),
				get_option( 'ai1wmne_s3_https_protocol', true )
			);

			$s3->set_api_endpoint( get_option( 'ai1wmne_s3_api_endpoint', false ) );
			$s3->set_bucket_template( get_option( 'ai1wmne_s3_bucket_template', false ) );

			$bucket_name = get_option( 'ai1wmne_s3_bucket_name', ai1wm_archive_bucket() );

			try {
				$region_name = get_option( 'ai1wmne_s3_region_name', ai1wmne_aws_region_name( AI1WMNE_S3_DEFAULT_REGION_NAME ) );
				$items       = $s3->get_objects_by_bucket( $bucket_name, $region_name, array( 'delimiter' => '/', 'prefix' => $folder_path ) );

				Ai1wmve_File_Sorter::sort( $items, Ai1wmve_File_Sorter::by_date_desc() );
			} catch ( Exception $e ) {
				WP_CLI::error( $e->getMessage() );
				exit;
			}

			return $items;
		}

		/**
		 * Get folder path from command-line or WP settings
		 *
		 * @param  array  $assoc_args CLI params
		 * @return string Folder path
		 */
		protected function get_folder_path( $assoc_args ) {
			// Default folder path is read from the extension settings
			$folder_path = get_option( 'ai1wmne_s3_folder_name', '' );

			// If user specified a custom folder it has priority
			if ( isset( $assoc_args['folder-path'] ) ) {
				$folder_path = $assoc_args['folder-path'];
			}

			// Normalizing the folder path
			$folder_path = trim( $folder_path, '/' );
			if ( ! empty( $folder_path ) ) {
				$folder_path = sprintf( '%s/', $folder_path );
			}

			return $folder_path;
		}

		/**
		 * Get backup location string
		 *
		 * @param  array  $params Params
		 * @return string Human-readable backup file location
		 */
		protected function get_backup_location( $params ) {
			$region = $params['region_name'];
			$bucket = $params['bucket_name'];
			$folder = $params['folder_name'];
			$file   = $params['archive'];
			$path   = trim( sprintf( '%s/%s', $folder, $file ), '/' );

			return sprintf( '[region: %s] [bucket: %s] [path: %s]', $region, $bucket, $path );
		}
	}
}
