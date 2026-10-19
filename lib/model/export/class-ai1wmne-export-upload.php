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

class Ai1wmne_Export_Upload {

	public static function execute( $params, Ai1wmne_S3_Client $s3 = null ) {

		$params['completed'] = false;

		// Validate bucket name
		if ( ! isset( $params['bucket_name'] ) ) {
			throw new Ai1wm_Import_Exception( __( 'S3 Client Bucket Name is not specified.', AI1WMNE_PLUGIN_NAME ) );
		}

		// Validate region name
		if ( ! isset( $params['region_name'] ) ) {
			throw new Ai1wm_Import_Exception( __( 'S3 Client Region Name is not specified.', AI1WMNE_PLUGIN_NAME ) );
		}

		// Validate folder name
		if ( ! isset( $params['folder_name'] ) ) {
			throw new Ai1wm_Import_Exception( __( 'S3 Client Folder Name is not specified.', AI1WMNE_PLUGIN_NAME ) );
		}

		// Validate upload ID
		if ( ! isset( $params['upload_id'] ) ) {
			throw new Ai1wm_Import_Exception( __( 'S3 Client Upload ID is not specified.', AI1WMNE_PLUGIN_NAME ) );
		}

		// Set archive offset
		if ( ! isset( $params['archive_offset'] ) ) {
			$params['archive_offset'] = 0;
		}

		// Set multipart offset
		if ( ! isset( $params['multipart_offset'] ) ) {
			$params['multipart_offset'] = 0;
		}

		// Set archive size
		if ( ! isset( $params['archive_size'] ) ) {
			$params['archive_size'] = ai1wm_archive_bytes( $params );
		}

		// Set file chunk number
		if ( ! isset( $params['file_chunk_number'] ) ) {
			$params['file_chunk_number'] = 1;
		}

		// Set upload retries
		if ( ! isset( $params['upload_retries'] ) ) {
			$params['upload_retries'] = 0;
		}

		// Set upload backoff
		if ( ! isset( $params['upload_backoff'] ) ) {
			$params['upload_backoff'] = 1;
		}

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

		// Open the archive file for reading
		$archive = fopen( ai1wm_archive_path( $params ), 'rb' );

		// Set file chunk size for upload
		$file_chunk_size = get_option( 'ai1wmne_s3_file_chunk_size', AI1WMNE_DEFAULT_FILE_CHUNK_SIZE );

		// Read file chunk data
		if ( ( fseek( $archive, $params['archive_offset'] ) !== -1 )
				&& ( $file_chunk_data = fread( $archive, $file_chunk_size ) ) ) {

			try {

				$params['upload_retries'] += 1;
				$params['upload_backoff'] *= 2;

				// Upload file chunk data
				if ( ai1wmne_is_incremental() ) {
					$file_chunk_etag = $s3->upload_file_chunk( $file_chunk_data, sprintf( '/%s/incremental-backups/%s', $params['folder_name'], ai1wm_archive_name( $params ) ), $params['upload_id'], $params['bucket_name'], $params['region_name'], $params['file_chunk_number'] );
				} else {
					$file_chunk_etag = $s3->upload_file_chunk( $file_chunk_data, sprintf( '/%s/%s', $params['folder_name'], ai1wm_archive_name( $params ) ), $params['upload_id'], $params['bucket_name'], $params['region_name'], $params['file_chunk_number'] );
				}

				// Add file chunk ETag
				if ( ( $multipart = fopen( ai1wm_multipart_path( $params ), 'cb' ) ) ) {
					if ( fseek( $multipart, $params['multipart_offset'] ) !== -1 ) {
						fwrite( $multipart, $file_chunk_etag . PHP_EOL );
					}

					$params['multipart_offset'] = ftell( $multipart );

					fclose( $multipart );
				}

				// Unset upload retries
				unset( $params['upload_retries'] );
				unset( $params['upload_backoff'] );

			} catch ( Ai1wmne_Connect_Exception $e ) {
				sleep( $params['upload_backoff'] );
				if ( $params['upload_retries'] <= 5 ) {
					return $params;
				}

				throw $e;
			}

			// Set archive offset
			$params['archive_offset'] = ftell( $archive );

			// Set file chunk number
			$params['file_chunk_number'] += 1;

			// Set archive details
			$name = ai1wm_archive_name( $params );
			$size = ai1wm_archive_size( $params );

			// Get progress
			$progress = (int) ( ( $params['archive_offset'] / $params['archive_size'] ) * 100 );

			// Set progress
			if ( defined( 'WP_CLI' ) ) {
				WP_CLI::log( sprintf( __( 'Uploading %s (%s) [%d%% complete]', AI1WMNE_PLUGIN_NAME ), $name, $size, $progress ) );
			} else {
				Ai1wm_Status::info( sprintf( __( '<i class="ai1wmne-icon-s3"></i> Uploading <strong>%s</strong> (%s)<br />%d%% complete', AI1WMNE_PLUGIN_NAME ), $name, $size, $progress ) );
			}
		} else {

			// Add file chunk ETag
			$file_chunks = array();
			if ( ( $multipart = fopen( ai1wm_multipart_path( $params ), 'r' ) ) ) {
				while ( $file_chunk_sha1 = trim( fgets( $multipart ) ) ) {
					$file_chunks[] = $file_chunk_sha1;
				}

				fclose( $multipart );
			}

			// Complete upload file chunk data
			if ( ai1wmne_is_incremental() ) {
				$s3->upload_complete( $file_chunks, sprintf( '/%s/incremental-backups/%s', $params['folder_name'], ai1wm_archive_name( $params ) ), $params['upload_id'], $params['bucket_name'], $params['region_name'] );
			} else {
				$s3->upload_complete( $file_chunks, sprintf( '/%s/%s', $params['folder_name'], ai1wm_archive_name( $params ) ), $params['upload_id'], $params['bucket_name'], $params['region_name'] );
			}

			// Set last backup date
			update_option( 'ai1wmne_s3_timestamp', time() );

			// Unset storage class
			unset( $params['storage_class'] );

			// Unset encryption
			unset( $params['encryption'] );

			// Unset upload ID
			unset( $params['upload_id'] );

			// Unset archive offset
			unset( $params['archive_offset'] );

			// Unset multipart offset
			unset( $params['multipart_offset'] );

			// Unset archive size
			unset( $params['archive_size'] );

			// Unset file chunk number
			unset( $params['file_chunk_number'] );

			// Unset completed
			unset( $params['completed'] );
		}

		// Close the archive file
		fclose( $archive );

		return $params;
	}
}
