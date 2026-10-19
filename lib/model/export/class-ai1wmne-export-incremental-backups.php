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

class Ai1wmne_Export_Incremental_Backups {

	public static function execute( $params, Ai1wmne_S3_Client $s3 = null ) {

		// Get bucket name
		if ( ! isset( $params['bucket_name'] ) ) {
			$params['bucket_name'] = get_option( 'ai1wmne_s3_bucket_name', ai1wm_archive_bucket() );
		}

		// Get region name
		if ( ! isset( $params['region_name'] ) ) {
			$params['region_name'] = get_option( 'ai1wmne_s3_region_name', ai1wmne_aws_region_name( AI1WMNE_S3_DEFAULT_REGION_NAME ) );
		}

		// Get folder name
		if ( ! isset( $params['folder_name'] ) ) {
			$params['folder_name'] = get_option( 'ai1wmne_s3_folder_name', '' );
		}

		// Set progress
		Ai1wm_Status::info( __( 'Preparing incremental backup files...', AI1WMNE_PLUGIN_NAME ) );

		// Set S3 client
		if ( is_null( $s3 ) ) {
			$s3 = new Ai1wmne_S3_Client(
				get_option( 'ai1wmne_s3_access_key', ai1wmne_aws_access_key() ),
				get_option( 'ai1wmne_s3_secret_key', ai1wmne_aws_secret_key() ),
				get_option( 'ai1wmne_s3_https_protocol', true )
			);
		}

		$s3->set_api_endpoint( get_option( 'ai1wmne_s3_api_endpoint', false ) );
		$s3->set_bucket_template( get_option( 'ai1wmne_s3_bucket_template', false ) );

		// Download incremental files
		if ( ( $incremental_list = ai1wm_open( ai1wm_incremental_backups_list_path( $params ), 'wb' ) ) ) {
			try {
				$s3->get_file( $incremental_list, sprintf( '/%s/incremental-backups/incremental.backups.list', $params['folder_name'] ), $params['bucket_name'], $params['region_name'] );
			} catch ( Ai1wmne_Error_Exception $e ) {
			}

			ai1wm_close( $incremental_list );
		}

		$incremental_files = array();

		// Get incremental files
		if ( ( $incremental_list = ai1wm_open( ai1wm_incremental_backups_list_path( $params ), 'rb' ) ) ) {
			while ( list( $file_index, $file_path, $file_size, $file_mtime ) = fgetcsv( $incremental_list ) ) {
				$incremental_files[ $file_index ] = array( $file_path, $file_size, $file_mtime );
			}

			ai1wm_close( $incremental_list );
		}

		// Append backup file to incremental files
		$incremental_files[] = array( ai1wm_archive_name( $params ), ai1wm_archive_bytes( $params ), ai1wm_archive_mtime( $params ) );

		// Write incremental files
		if ( ( $incremental_list = ai1wm_open( ai1wm_incremental_backups_list_path( $params ), 'wb' ) ) ) {
			foreach ( $incremental_files as $file_index => $file_meta ) {
				ai1wm_putcsv( $incremental_list, array( $file_index, $file_meta[0], $file_meta[1], $file_meta[2] ) );
			}

			ai1wm_close( $incremental_list );
		}

		// Upload incremental content files
		if ( ( $incremental_content_list = ai1wm_open( ai1wm_incremental_content_list_path( $params ), 'rb' ) ) ) {
			if ( ( $incremental_content_files = ai1wm_read( $incremental_content_list, filesize( ai1wm_incremental_content_list_path( $params ) ) ) ) !== false ) {
				$s3->upload_file( $incremental_content_files, sprintf( '/%s/incremental-backups/incremental.content.list', $params['folder_name'] ), $params['bucket_name'], $params['region_name'] );
			}

			ai1wm_close( $incremental_content_list );
		}

		// Upload incremental media files
		if ( ( $incremental_media_list = ai1wm_open( ai1wm_incremental_media_list_path( $params ), 'rb' ) ) ) {
			if ( ( $incremental_media_files = ai1wm_read( $incremental_media_list, filesize( ai1wm_incremental_media_list_path( $params ) ) ) ) !== false ) {
				$s3->upload_file( $incremental_media_files, sprintf( '/%s/incremental-backups/incremental.media.list', $params['folder_name'] ), $params['bucket_name'], $params['region_name'] );
			}

			ai1wm_close( $incremental_media_list );
		}

		// Upload incremental plugins files
		if ( ( $incremental_plugins_list = ai1wm_open( ai1wm_incremental_plugins_list_path( $params ), 'rb' ) ) ) {
			if ( ( $incremental_plugins_files = ai1wm_read( $incremental_plugins_list, filesize( ai1wm_incremental_plugins_list_path( $params ) ) ) ) !== false ) {
				$s3->upload_file( $incremental_plugins_files, sprintf( '/%s/incremental-backups/incremental.plugins.list', $params['folder_name'] ), $params['bucket_name'], $params['region_name'] );
			}

			ai1wm_close( $incremental_plugins_list );
		}

		// Upload incremental themes files
		if ( ( $incremental_themes_list = ai1wm_open( ai1wm_incremental_themes_list_path( $params ), 'rb' ) ) ) {
			if ( ( $incremental_themes_files = ai1wm_read( $incremental_themes_list, filesize( ai1wm_incremental_themes_list_path( $params ) ) ) ) !== false ) {
				$s3->upload_file( $incremental_themes_files, sprintf( '/%s/incremental-backups/incremental.themes.list', $params['folder_name'] ), $params['bucket_name'], $params['region_name'] );
			}

			ai1wm_close( $incremental_themes_list );
		}

		// Upload incremental backups files
		if ( ( $incremental_backups_list = ai1wm_open( ai1wm_incremental_backups_list_path( $params ), 'rb' ) ) ) {
			if ( ( $incremental_backups_files = ai1wm_read( $incremental_backups_list, filesize( ai1wm_incremental_backups_list_path( $params ) ) ) ) !== false ) {
				$s3->upload_file( $incremental_backups_files, sprintf( '/%s/incremental-backups/incremental.backups.list', $params['folder_name'] ), $params['bucket_name'], $params['region_name'] );
			}

			ai1wm_close( $incremental_backups_list );
		}

		// Set progress
		Ai1wm_Status::info( __( 'Done preparing incremental backup files.', AI1WMNE_PLUGIN_NAME ) );

		return $params;
	}
}
