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
 * ███████╗███████╗██████╗ ██╗   ██╗███╗   ███╗ █████╗ ███████╗██╗  ██╗
 * ██╔════╝██╔════╝██╔══██╗██║   ██║████╗ ████║██╔══██╗██╔════╝██║ ██╔╝
 * ███████╗█████╗  ██████╔╝██║   ██║██╔████╔██║███████║███████╗█████╔╝
 * ╚════██║██╔══╝  ██╔══██╗╚██╗ ██╔╝██║╚██╔╝██║██╔══██║╚════██║██╔═██╗
 * ███████║███████╗██║  ██║ ╚████╔╝ ██║ ╚═╝ ██║██║  ██║███████║██║  ██╗
 * ╚══════╝╚══════╝╚═╝  ╚═╝  ╚═══╝  ╚═╝     ╚═╝╚═╝  ╚═╝╚══════╝╚═╝  ╚═╝
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Kangaroos cannot jump here' );
}

class Ai1wmne_Import_Controller {

	public static function button() {
		return Ai1wm_Template::get_content(
			'import/button',
			array( 'access_key' => get_option( 'ai1wmne_s3_access_key', ai1wmne_aws_access_key() ) ),
			AI1WMNE_TEMPLATES_PATH
		);
	}

	public static function picker() {
		Ai1wm_Template::render(
			'import/picker',
			array(),
			AI1WMNE_TEMPLATES_PATH
		);
	}

	public static function browser( $params = array() ) {
		ai1wm_setup_environment();

		// Set params
		if ( empty( $params ) ) {
			$params = stripslashes_deep( $_GET );
		}

		// Set bucket name
		$bucket_name = null;
		if ( isset( $params['bucket_name'] ) ) {
			$bucket_name = trim( $params['bucket_name'] );
		}

		// Set folder path
		$folder_path = null;
		if ( isset( $params['folder_path'] ) ) {
			$folder_path = trim( $params['folder_path'] );
		}

		// Set S3 client
		$s3 = new Ai1wmne_S3_Client(
			get_option( 'ai1wmne_s3_access_key', ai1wmne_aws_access_key() ),
			get_option( 'ai1wmne_s3_secret_key', ai1wmne_aws_secret_key() ),
			get_option( 'ai1wmne_s3_https_protocol', true )
		);

		$s3->set_api_endpoint( get_option( 'ai1wmne_s3_api_endpoint', false ) );
		$s3->set_bucket_template( get_option( 'ai1wmne_s3_bucket_template', false ) );

		// Get region name
		$region_name = get_option( 'ai1wmne_s3_region_name', ai1wmne_aws_region_name( AI1WMNE_S3_DEFAULT_REGION_NAME ) );

		// Get buckets
		$buckets = $s3->get_buckets( $region_name );

		// Set bucket name (ListAllMyBuckets)
		if ( count( $buckets ) === 0 ) {
			$bucket_name = get_option( 'ai1wmne_s3_bucket_name', ai1wm_archive_bucket() );
		}

		// Set bucket structure
		$response = array( 'items' => array(), 'num_hidden_files' => 0 );

		// Loop over items
		if ( $bucket_name ) {

			// Get bucket location
			$region_name = $s3->get_bucket_location( $bucket_name, $region_name );

			// Get bucket items
			$items = $s3->get_objects_by_bucket( $bucket_name, $region_name, array( 'delimiter' => '/', 'prefix' => $folder_path ) );

			// Loop over folders and files
			foreach ( $items as $item ) {
				if ( $item['type'] === 'folder' || pathinfo( $item['name'], PATHINFO_EXTENSION ) === 'wpress' ) {
					$response['items'][] = array(
						'index'       => null,
						'name'        => isset( $item['name'] ) ? $item['name'] : null,
						'path'        => isset( $item['path'] ) ? $item['path'] : null,
						'unix'        => isset( $item['date'] ) ? $item['date'] : null,
						'date'        => isset( $item['date'] ) ? human_time_diff( $item['date'] ) : null,
						'size'        => isset( $item['bytes'] ) ? ai1wm_size_format( $item['bytes'] ) : null,
						'bytes'       => isset( $item['bytes'] ) ? $item['bytes'] : null,
						'type'        => isset( $item['type'] ) ? $item['type'] : null,
						'bucket_name' => $bucket_name,
						'region_name' => $region_name,
						'folder_path' => $folder_path,
					);
				} else {
					$response['num_hidden_files']++;
				}
			}

			// Sort items by type desc and date desc
			Ai1wmve_File_Sorter::sort( $response['items'], Ai1wmve_File_Sorter::by_type_desc_date_desc( 'unix' ) );

		} else {

			// Loop over buckets
			foreach ( $buckets as $bucket_name ) {
				$response['items'][] = array(
					'name' => $bucket_name,
					'path' => $bucket_name,
					'type' => 'bucket',
				);
			}
		}

		echo json_encode( $response );
		exit;
	}

	public static function incremental( $params = array() ) {
		ai1wm_setup_environment();

		// Set params
		if ( empty( $params ) ) {
			$params = stripslashes_deep( $_GET );
		}

		// Set bucket name
		$bucket_name = null;
		if ( isset( $params['bucket_name'] ) ) {
			$bucket_name = trim( $params['bucket_name'] );
		}

		// Set folder path
		$folder_path = null;
		if ( isset( $params['folder_path'] ) ) {
			$folder_path = trim( $params['folder_path'] );
		}

		// Set S3 client
		$s3 = new Ai1wmne_S3_Client(
			get_option( 'ai1wmne_s3_access_key', ai1wmne_aws_access_key() ),
			get_option( 'ai1wmne_s3_secret_key', ai1wmne_aws_secret_key() ),
			get_option( 'ai1wmne_s3_https_protocol', true )
		);

		$s3->set_api_endpoint( get_option( 'ai1wmne_s3_api_endpoint', false ) );
		$s3->set_bucket_template( get_option( 'ai1wmne_s3_bucket_template', false ) );

		// Get bucket location
		$region_name = $s3->get_bucket_location( $bucket_name, $region_name );

		try {
			$file_content = $s3->get_file_content( sprintf( '/%s/incremental.backups.list', $folder_path ), $bucket_name, $region_name );
		} catch ( Ai1wmne_Error_Exception $e ) {
		}

		$items = array();
		if ( isset( $file_content ) ) {
			foreach ( str_getcsv( $file_content, "\n" ) as $row ) {
				if ( list( $file_index, $file_path, $file_size, $file_mtime ) = str_getcsv( $row ) ) {
					$items[] = array(
						'index'       => $file_index,
						'name'        => sprintf( __( 'Restore point %d', AI1WMNE_PLUGIN_NAME ), $file_index ),
						'path'        => $file_path,
						'date'        => get_date_from_gmt( date( 'Y-m-d H:i:s', $file_mtime ), 'M j, Y g:i a' ),
						'size'        => ai1wm_size_format( $file_size ),
						'bytes'       => $file_size,
						'bucket_name' => $bucket_name,
						'region_name' => $region_name,
						'folder_path' => $folder_path,
						'type'        => 'file',
					);
				}
			}
		}

		echo json_encode( array( 'items' => array_reverse( $items ), 'cursor' => null ) );
		exit;
	}
}
