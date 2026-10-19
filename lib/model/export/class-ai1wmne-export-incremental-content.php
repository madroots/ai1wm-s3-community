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

class Ai1wmne_Export_Incremental_Content {

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
		Ai1wm_Status::info( __( 'Preparing incremental content files...', AI1WMNE_PLUGIN_NAME ) );

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
		if ( ( $incremental_list = ai1wm_open( ai1wm_incremental_content_list_path( $params ), 'wb' ) ) ) {
			try {
				$s3->get_file( $incremental_list, sprintf( '/%s/incremental-backups/incremental.content.list', $params['folder_name'] ), $params['bucket_name'], $params['region_name'] );
			} catch ( Ai1wmne_Error_Exception $e ) {
			}

			ai1wm_close( $incremental_list );
		}

		$incremental_files = array();

		// Get incremental files
		if ( ( $incremental_list = ai1wm_open( ai1wm_incremental_content_list_path( $params ), 'rb' ) ) ) {
			while ( list( $file_abspath, $file_relpath, $file_size, $file_mtime ) = fgetcsv( $incremental_list ) ) {
				$incremental_files[ $file_abspath ][] = array( $file_relpath, $file_size, $file_mtime );
			}

			ai1wm_close( $incremental_list );
		}

		$content_files = array();

		// Get content files
		if ( ( $content_list = ai1wm_open( ai1wm_content_list_path( $params ), 'rb' ) ) ) {
			while ( list( $file_abspath, $file_relpath, $file_size, $file_mtime ) = fgetcsv( $content_list ) ) {
				$content_files[ $file_abspath ][] = array( $file_relpath, $file_size, $file_mtime );
			}

			ai1wm_close( $content_list );
		}

		// Compare incremental files
		foreach ( $incremental_files as $file_abspath => $file_attributes ) {
			if ( ! isset( $content_files[ $file_abspath ] ) ) {
				unset( $incremental_files[ $file_abspath ] );
			}
		}

		// Compare content files
		foreach ( $content_files as $file_abspath => $file_attributes ) {
			if ( isset( $incremental_files[ $file_abspath ] ) ) {
				foreach ( $file_attributes as $file_meta ) {
					if ( in_array( $file_meta, $incremental_files[ $file_abspath ] ) ) {
						unset( $content_files[ $file_abspath ] );
					}
				}
			}
		}

		// Append content files to incremental files
		$incremental_files = array_merge_recursive( $incremental_files, $content_files );

		// Write incremental files
		if ( ( $incremental_list = ai1wm_open( ai1wm_incremental_content_list_path( $params ), 'wb' ) ) ) {
			foreach ( $incremental_files as $file_abspath => $file_attributes ) {
				foreach ( $file_attributes as $file_meta ) {
					ai1wm_putcsv( $incremental_list, array( $file_abspath, $file_meta[0], $file_meta[1], $file_meta[2] ) );
				}
			}

			ai1wm_close( $incremental_list );
		}

		$total_content_files_count = $total_content_files_size = 1;

		// Write content files
		if ( ( $content_list = ai1wm_open( ai1wm_content_list_path( $params ), 'wb' ) ) ) {
			foreach ( $content_files as $file_abspath => $file_attributes ) {
				foreach ( $file_attributes as $file_meta ) {
					if ( ai1wm_putcsv( $content_list, array( $file_abspath, $file_meta[0], $file_meta[1], $file_meta[2] ) ) !== false ) {
						$total_content_files_count++;

						// Add current file size
						$total_content_files_size += $file_meta[1];
					}
				}
			}

			ai1wm_close( $content_list );
		}

		// Set progress
		Ai1wm_Status::info( __( 'Done preparing incremental content files.', AI1WMNE_PLUGIN_NAME ) );

		// Set total content files count
		$params['total_content_files_count'] = $total_content_files_count;

		// Set total content files size
		$params['total_content_files_size'] = $total_content_files_size;

		return $params;
	}
}
