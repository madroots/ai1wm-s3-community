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

class Ai1wmne_Export_S3 {

	public static function execute( $params, Ai1wmne_S3_Client $s3 = null ) {

		// Get storage class
		if ( ! isset( $params['storage_class'] ) ) {
			$params['storage_class'] = get_option( 'ai1wmne_s3_storage_class', AI1WMNE_S3_DEFAULT_STORAGE_CLASS );
		}

		// Get bucket encryption
		if ( ! isset( $params['encryption'] ) ) {
			$params['encryption'] = get_option( 'ai1wmne_s3_encryption', false );
		}

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
		Ai1wm_Status::info( __( 'Connecting to S3 Client...', AI1WMNE_PLUGIN_NAME ) );

		// Open the archive file for writing
		$archive = new Ai1wm_Compressor( ai1wm_archive_path( $params ) );

		// Append EOF block
		$archive->close( true );

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

		// Create bucket if does not exist
		if ( ! $s3->is_bucket_available( $params['bucket_name'], $params['region_name'] ) ) {
			$s3->create_bucket( $params['bucket_name'], $params['region_name'] );
		}

		// Get upload ID
		if ( ai1wmne_is_incremental() ) {
			$params['upload_id'] = $s3->upload_multipart( sprintf( '/%s/incremental-backups/%s', $params['folder_name'], ai1wm_archive_name( $params ) ), $params['bucket_name'], $params['region_name'], $params['storage_class'], $params['encryption'] );
		} else {
			$params['upload_id'] = $s3->upload_multipart( sprintf( '/%s/%s', $params['folder_name'], ai1wm_archive_name( $params ) ), $params['bucket_name'], $params['region_name'], $params['storage_class'], $params['encryption'] );
		}

		// Set progress
		Ai1wm_Status::info( __( 'Done connecting to S3 Client.', AI1WMNE_PLUGIN_NAME ) );

		return $params;
	}
}
