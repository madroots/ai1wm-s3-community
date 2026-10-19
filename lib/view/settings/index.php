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
?>

<div class="ai1wm-container">
	<div class="ai1wm-row">
		<div class="ai1wm-left">
			<div class="ai1wm-holder">
				<h1><i class="ai1wm-icon-gear"></i> <?php _e( 'S3 Client Settings', AI1WMNE_PLUGIN_NAME ); ?></h1>
				<br />
				<br />

				<?php if ( Ai1wm_Message::has( 'error' ) ) : ?>
					<div class="ai1wm-message ai1wm-error-message">
						<p><?php echo Ai1wm_Message::get( 'error' ); ?></p>
					</div>
				<?php elseif ( Ai1wm_Message::has( 'success' ) ) : ?>
					<div class="ai1wm-message ai1wm-success-message">
						<p><?php echo Ai1wm_Message::get( 'success' ); ?></p>
					</div>
				<?php endif; ?>

				<div id="ai1wmne-credentials">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php?action=ai1wmne_s3_connection' ) ); ?>">
						<div class="ai1wm-field">
							<label for="ai1wmne-s3-api-endpoint">
								<?php _e( 'S3 Endpoint', AI1WMNE_PLUGIN_NAME ); ?>
								<br />
								<input type="text" placeholder="<?php _e( 'Enter S3 Endpoint', AI1WMNE_PLUGIN_NAME ); ?>" id="ai1wmne-s3-api-endpoint" class="ai1wmne-settings-key" name="ai1wmne_s3_api_endpoint" value="<?php echo esc_attr( $api_endpoint ); ?>" />
							</label>
							<a href="https://help.servmask.com/knowledgebase/s3-client-endpoints/" target="_blank">
								<?php _e( 'Where to find your S3 Endpoint', AI1WMNE_PLUGIN_NAME ); ?>
							</a>
						</div>

						<div class="ai1wm-field">
							<label for="ai1wmne-s3-bucket-template">
								<?php _e( 'DNS-style bucket+hostname:port template for accessing a bucket', AI1WMNE_PLUGIN_NAME ); ?>
								<br />
								<input type="text" placeholder="<?php _e( 'Enter DNS-style bucket+hostname:port template', AI1WMNE_PLUGIN_NAME ); ?>" id="ai1wmne-s3-bucket-template" class="ai1wmne-settings-key" name="ai1wmne_s3_bucket_template" value="<?php echo esc_attr( $bucket_template ); ?>" />
							</label>
							<a href="https://help.servmask.com/knowledgebase/s3-client-endpoints/" target="_blank">
								<?php _e( 'Where to find your Bucket Template', AI1WMNE_PLUGIN_NAME ); ?>
							</a>
						</div>

						<div class="ai1wm-field">
							<label for="ai1wmne-s3-region-name">
								<?php _e( 'Region Name', AI1WMNE_PLUGIN_NAME ); ?>
								<br />
								<input type="text" placeholder="<?php _e( 'Enter Region Name', AI1WMNE_PLUGIN_NAME ); ?>" id="ai1wmne-s3-region-name" class="ai1wmne-settings-key" name="ai1wmne_s3_region_name" value="<?php echo esc_attr( $region_name ); ?>" />
							</label>
						</div>

						<div class="ai1wm-field">
							<label for="ai1wmne-s3-access-key">
								<?php _e( 'Access Key', AI1WMNE_PLUGIN_NAME ); ?>
								<br />
								<input type="text" placeholder="<?php _e( 'Enter Access Key', AI1WMNE_PLUGIN_NAME ); ?>" id="ai1wmne-s3-access-key" class="ai1wmne-settings-key" name="ai1wmne_s3_access_key" value="<?php echo esc_attr( $access_key ); ?>" />
							</label>
						</div>

						<div class="ai1wm-field">
							<label for="ai1wmne-s3-secret-key">
								<?php _e( 'Secret Key', AI1WMNE_PLUGIN_NAME ); ?>
								<br />
								<input type="text" placeholder="<?php ( $secret_key ) ? _e( 'Hidden', AI1WMNE_PLUGIN_NAME ) : _e( 'Enter Secret Key', AI1WMNE_PLUGIN_NAME ); ?>" id="ai1wmne-s3-secret-key" class="ai1wmne-settings-key" name="ai1wmne_s3_secret_key" autocomplete="off" />
							</label>
						</div>

						<div class="ai1wm-field">
							<label for="ai1wmne-s3-https-protocol">
								<input type="checkbox" id="ai1wmne-s3-https-protocol" name="ai1wmne_s3_https_protocol" value="1" <?php echo empty( $https_protocol ) ? null : 'checked'; ?> />
								<?php _e( 'Use HTTPS protocol', AI1WMNE_PLUGIN_NAME ); ?>
							</label>
						</div>

						<p>
							<button type="submit" class="ai1wm-button-blue" name="ai1wmne_s3_update" id="ai1wmne-s3-link">
								<i class="ai1wm-icon-enter"></i>
								<?php _e( 'Update', AI1WMNE_PLUGIN_NAME ); ?>
							</button>
						</p>
					</form>
				</div>
			</div>

			<?php if ( $buckets !== false ) : ?>
				<div id="ai1wmne-backups" class="ai1wm-holder">
					<h1><i class="ai1wm-icon-gear"></i> <?php _e( 'S3 Client Backups', AI1WMNE_PLUGIN_NAME ); ?></h1>
					<br />
					<br />

					<?php if ( Ai1wm_Message::has( 'bucket' ) ) : ?>
						<div class="ai1wm-message ai1wm-error-message">
							<p><?php echo Ai1wm_Message::get( 'bucket' ); ?></p>
						</div>
					<?php elseif ( Ai1wm_Message::has( 'settings' ) ) : ?>
						<div class="ai1wm-message ai1wm-success-message">
							<p><?php echo Ai1wm_Message::get( 'settings' ); ?></p>
						</div>
					<?php endif; ?>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php?action=ai1wmne_s3_settings' ) ); ?>">
						<article class="ai1wmne-article">
							<h3><?php _e( 'Bucket name', AI1WMNE_PLUGIN_NAME ); ?></h3>
							<p>
								<input type="text" placeholder="<?php _e( 'Enter Bucket Name', AI1WMNE_PLUGIN_NAME ); ?>" class="ai1wmne-bucket-name" name="ai1wmne_s3_bucket_name" id="ai1wmne-s3-bucket-name" value="<?php echo esc_attr( $bucket_name ); ?>" />
							</p>
						</article>

						<article class="ai1wmne-article">
							<h3><?php _e( 'Folder name', AI1WMNE_PLUGIN_NAME ); ?></h3>
							<p>
								<input type="text" placeholder="<?php _e( 'Enter Folder Name (optional)', AI1WMNE_PLUGIN_NAME ); ?>" class="ai1wmne-folder-name" name="ai1wmne_s3_folder_name" id="ai1wmne-s3-folder-name" value="<?php echo esc_attr( $folder_name ); ?>" />
							</p>
						</article>

						<article class="ai1wmne-article">
							<h3><?php _e( 'Storage class', AI1WMNE_PLUGIN_NAME ); ?></h3>
							<ul>
								<li>
									<label for="ai1wmne-s3-storage-class-standard">
										<input type="radio" name="ai1wmne_s3_storage_class" id="ai1wmne-s3-storage-class-standard" value="STANDARD" <?php echo $storage_class === 'STANDARD' ? 'checked="checked"' : null; ?> />
										<?php _e( 'Standard', AI1WMNE_PLUGIN_NAME ); ?>
										<a href="https://aws.amazon.com/s3/storage-classes/#General_Purpose" style="text-decoration: none;" target="_blank"><?php echo _e( '[?]', AI1WMNE_PLUGIN_NAME ); ?></a>
									</label>
								</li>
								<li>
									<label for="ai1wmne-s3-storage-class-standard-ia">
										<input type="radio" name="ai1wmne_s3_storage_class" id="ai1wmne-s3-storage-class-standard-ia" value="STANDARD_IA" <?php echo $storage_class === 'STANDARD_IA' ? 'checked="checked"' : null; ?> />
										<?php _e( 'Infrequent Access', AI1WMNE_PLUGIN_NAME ); ?>
										<a href="https://aws.amazon.com/s3/storage-classes/#Infrequent_Access" style="text-decoration: none;" target="_blank"><?php echo _e( '[?]', AI1WMNE_PLUGIN_NAME ); ?></a>
									</label>
								</li>
								<li>
									<label for="ai1wmne-s3-storage-class-reduced-redundancy">
										<input type="radio" name="ai1wmne_s3_storage_class" id="ai1wmne-s3-storage-class-reduced-redundancy" value="REDUCED_REDUNDANCY" <?php echo $storage_class === 'REDUCED_REDUNDANCY' ? 'checked="checked"' : null; ?> />
										<?php _e( 'Reduced Redundancy', AI1WMNE_PLUGIN_NAME ); ?>
										<a href="https://aws.amazon.com/s3/reduced-redundancy/" style="text-decoration: none;" target="_blank"><?php echo _e( '[?]', AI1WMNE_PLUGIN_NAME ); ?></a>
									</label>
								</li>
							</ul>
						</article>

						<article class="ai1wmne-article">
							<h3><?php _e( 'Encryption', AI1WMNE_PLUGIN_NAME ); ?></h3>
							<p>
								<label for="ai1wmne-s3-encryption">
									<input type="checkbox" id="ai1wmne-s3-encryption" name="ai1wmne_s3_encryption" value="AES256" <?php echo empty( $encryption ) ? null : 'checked'; ?> />
									<?php _e( 'Protect data at rest by using S3 Client master-key', AI1WMNE_PLUGIN_NAME ); ?>
									<a href="http://docs.aws.amazon.com/AmazonS3/latest/dev/UsingEncryption.html" style="text-decoration: none;" target="_blank"><?php echo _e( '[?]', AI1WMNE_PLUGIN_NAME ); ?></a>
								</label>
							</p>
						</article>

						<article class="ai1wmne-article">
							<h3><?php _e( 'Configure your backup plan', AI1WMNE_PLUGIN_NAME ); ?></h3>

							<p>
								<label for="ai1wmne-s3-cron-timestamp">
									<?php _e( 'Backup time:', AI1WMNE_PLUGIN_NAME ); ?>
									<input type="text" name="ai1wmne_s3_cron_timestamp" id="ai1wmne-s3-cron-timestamp" value="<?php echo esc_attr( get_date_from_gmt( date( 'Y-m-d H:i:s', $s3_cron_timestamp ), 'g:i a' ) ); ?>" autocomplete="off" />
									<code><?php echo ai1wm_get_timezone_string(); ?></code>
								</label>
							</p>

							<ul id="ai1wmne-s3-cron">
								<li>
									<label for="ai1wmne-s3-cron-hourly">
										<input type="checkbox" name="ai1wmne_s3_cron[]" id="ai1wmne-s3-cron-hourly" value="hourly" <?php echo in_array( 'hourly', $s3_backup_schedules ) ? 'checked' : null; ?> />
										<?php _e( 'Every hour', AI1WMNE_PLUGIN_NAME ); ?>
									</label>
								</li>
								<li>
									<label for="ai1wmne-s3-cron-daily">
										<input type="checkbox" name="ai1wmne_s3_cron[]" id="ai1wmne-s3-cron-daily" value="daily" <?php echo in_array( 'daily', $s3_backup_schedules ) ? 'checked' : null; ?> />
										<?php _e( 'Every day', AI1WMNE_PLUGIN_NAME ); ?>
									</label>
								</li>
								<li>
									<label for="ai1wmne-s3-cron-weekly">
										<input type="checkbox" name="ai1wmne_s3_cron[]" id="ai1wmne-s3-cron-weekly" value="weekly" <?php echo in_array( 'weekly', $s3_backup_schedules ) ? 'checked' : null; ?> />
										<?php _e( 'Every week', AI1WMNE_PLUGIN_NAME ); ?>
									</label>
								</li>
								<li>
									<label for="ai1wmne-s3-cron-monthly">
										<input type="checkbox" name="ai1wmne_s3_cron[]" id="ai1wmne-s3-cron-monthly" value="monthly" <?php echo in_array( 'monthly', $s3_backup_schedules ) ? 'checked' : null; ?> />
										<?php _e( 'Every month', AI1WMNE_PLUGIN_NAME ); ?>
									</label>
								</li>
							</ul>

							<p>
								<?php _e( 'Last backup date:', AI1WMNE_PLUGIN_NAME ); ?>
								<strong>
									<?php echo $last_backup_date; ?>
								</strong>
							</p>

							<p>
								<?php _e( 'Next backup date:', AI1WMNE_PLUGIN_NAME ); ?>
								<strong>
									<?php echo $next_backup_date; ?>
								</strong>
							</p>

							<p>
								<label for="ai1wmne-s3-incremental">
									<input type="checkbox" name="ai1wmne_s3_incremental" id="ai1wmne-s3-incremental" value="1" <?php echo empty( $incremental ) ? null : 'checked'; ?> />
									<?php _e( 'Enable incremental backups (optimize backup file size)', AI1WMNE_PLUGIN_NAME ); ?>
								</label>
							</p>
						</article>

						<article class="ai1wmne-article">
							<h3><?php _e( 'Notification settings', AI1WMNE_PLUGIN_NAME ); ?></h3>
							<p>
								<label for="ai1wmne-s3-notify-toggle">
									<input type="checkbox" id="ai1wmne-s3-notify-toggle" name="ai1wmne_s3_notify_toggle" <?php echo empty( $notify_ok_toggle ) ? null : 'checked'; ?> />
									<?php _e( 'Send an email when a backup is complete', AI1WMNE_PLUGIN_NAME ); ?>
								</label>
							</p>

							<p>
								<label for="ai1wmne-s3-notify-error-toggle">
									<input type="checkbox" id="ai1wmne-s3-notify-error-toggle" name="ai1wmne_s3_notify_error_toggle" <?php echo empty( $notify_error_toggle ) ? null : 'checked'; ?> />
									<?php _e( 'Send an email if a backup fails', AI1WMNE_PLUGIN_NAME ); ?>
								</label>
							</p>

							<p>
								<label for="ai1wmne-s3-notify-email">
									<?php _e( 'Email address', AI1WMNE_PLUGIN_NAME ); ?>
									<br />
									<input class="ai1wmne-email" type="email" id="ai1wmne-s3-notify-email" name="ai1wmne_s3_notify_email" value="<?php echo esc_attr( $notify_email ); ?>" />
								</label>
							</p>
						</article>

						<article class="ai1wmne-article">
							<h3><?php _e( 'Retention settings', AI1WMNE_PLUGIN_NAME ); ?></h3>
							<p>
								<div class="ai1wm-field">
									<label for="ai1wmne-s3-backups">
										<?php _e( 'Keep the most recent', AI1WMNE_PLUGIN_NAME ); ?>
										<input style="width: 4.5em;" type="number" min="0" name="ai1wmne_s3_backups" id="ai1wmne-s3-backups" value="<?php echo intval( $backups ); ?>" />
									</label>
									<?php _e( 'backups. <small>Default: <strong>0</strong> unlimited</small>', AI1WMNE_PLUGIN_NAME ); ?>
								</div>

								<div class="ai1wm-field">
									<label for="ai1wmne-s3-total">
										<?php _e( 'Limit the total size of backups to', AI1WMNE_PLUGIN_NAME ); ?>
										<input style="width: 4.5em;" type="number" min="0" name="ai1wmne_s3_total" id="ai1wmne-s3-total" value="<?php echo intval( $total ); ?>" />
									</label>
									<select style="margin-top: -2px;" name="ai1wmne_s3_total_unit" id="ai1wmne-s3-total-unit">
										<option value="MB" <?php echo strpos( $total, 'MB' ) !== false ? 'selected="selected"' : null; ?>><?php _e( 'MB', AI1WMNE_PLUGIN_NAME ); ?></option>
										<option value="GB" <?php echo strpos( $total, 'GB' ) !== false ? 'selected="selected"' : null; ?>><?php _e( 'GB', AI1WMNE_PLUGIN_NAME ); ?></option>
									</select>
									<?php _e( '<small>Default: <strong>0</strong> unlimited</small>', AI1WMNE_PLUGIN_NAME ); ?>
								</div>

								<div class="ai1wm-field">
									<label for="ai1wmne-s3-days">
										<?php _e( 'Remove backups older than ', AI1WMNE_PLUGIN_NAME ); ?>
										<input style="width: 4.5em;" type="number" min="0" name="ai1wmne_s3_days" id="ai1wmne-s3-days" value="<?php echo intval( $days ); ?>" />
									</label>
									<?php _e( 'days. <small>Default: <strong>0</strong> off</small>', AI1WMNE_PLUGIN_NAME ); ?>
								</div>
							</p>
						</article>

						<article class="ai1wmne-article">
							<h3><?php _e( 'Transfer settings', AI1WMNE_PLUGIN_NAME ); ?></h3>
							<div class="ai1wm-field">
								<label><?php _e( 'Slow Internet (Home)', AI1WMNE_PLUGIN_NAME ); ?></label>
								<input name="ai1wmne_s3_file_chunk_size" min="5242880" max="20971520" step="5242880" type="range" value="<?php echo $file_chunk_size; ?>" id="ai1wmne-s3-file-chunk-size" />
								<label><?php _e( 'Fast Internet (Internet Servers)', AI1WMNE_PLUGIN_NAME ); ?></label>
							</div>
						</article>

						<p>
							<button type="submit" class="ai1wm-button-blue" name="ai1wmne_s3_update" id="ai1wmne-s3-update">
								<i class="ai1wm-icon-database"></i>
								<?php _e( 'Update', AI1WMNE_PLUGIN_NAME ); ?>
							</button>
						</p>
					</form>
				</div>
			<?php endif; ?>
		</div>
		<div class="ai1wm-right">
			<div class="ai1wm-sidebar">
				<div class="ai1wm-segment">
					<?php if ( ! AI1WM_DEBUG ) : ?>
						<?php include AI1WM_TEMPLATES_PATH . '/common/share-buttons.php'; ?>
					<?php endif; ?>

					<h2><?php _e( 'Leave Feedback', AI1WMNE_PLUGIN_NAME ); ?></h2>

					<?php include AI1WM_TEMPLATES_PATH . '/common/leave-feedback.php'; ?>
				</div>
			</div>
		</div>
	</div>
</div>
