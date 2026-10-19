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

class Ai1wmne_Import_Incremental_S3 {

	public static function execute( $params, Ai1wmne_S3_Client $s3 = null ) {

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
				$s3->get_file( $incremental_list, sprintf( '/%s/incremental.backups.list', $params['folder_path'] ), $params['bucket_name'], $params['region_name'] );
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

		$total_backups_files_size = 1;

		// Get total backups files size
		if ( isset( $params['file_index'] ) ) {
			for ( $i = 0; $i <= $params['file_index']; $i++ ) {
				$total_backups_files_size += $incremental_files[ $i ][1];
			}
		}

		// Set total backups files size
		$params['total_backups_files_size'] = $total_backups_files_size;

		// Set file path
		$params['file_path'] = $incremental_files[0][0];

		// Set file size
		$params['file_size'] = $incremental_files[0][1];

		// Set progress
		Ai1wm_Status::info( __( 'Done preparing incremental backup files.', AI1WMNE_PLUGIN_NAME ) );

		return $params;
	}
}
