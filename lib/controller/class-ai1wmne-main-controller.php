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

class Ai1wmne_Main_Controller extends Ai1wmve_Main_Controller {

	/**
	 * Register plugin menus
	 *
	 * @return void
	 */
	public function admin_menu() {
		// Sub-level Settings menu
		add_submenu_page(
			'ai1wm_export',
			__( 'S3 Client Settings', AI1WMNE_PLUGIN_NAME ),
			__( 'S3 Client Settings', AI1WMNE_PLUGIN_NAME ),
			'export',
			'ai1wmne_settings',
			'Ai1wmne_Settings_Controller::index'
		);
	}

	/**
	 * Enqueue scripts and styles for Export Controller
	 *
	 * @param  string $hook Hook suffix
	 * @return void
	 */
	public function enqueue_export_scripts_and_styles( $hook ) {
		if ( stripos( 'toplevel_page_ai1wm_export', $hook ) === false ) {
			return;
		}

		if ( is_rtl() ) {
			wp_enqueue_style(
				'ai1wmne_export',
				Ai1wm_Template::asset_link( 'css/export.min.rtl.css', 'AI1WMNE' ),
				array( 'ai1wm_export' )
			);
		} else {
			wp_enqueue_style(
				'ai1wmne_export',
				Ai1wm_Template::asset_link( 'css/export.min.css', 'AI1WMNE' ),
				array( 'ai1wm_export' )
			);
		}

		wp_enqueue_script(
			'ai1wmne_export',
			Ai1wm_Template::asset_link( 'javascript/export.min.js', 'AI1WMNE' ),
			array( 'ai1wm_export' )
		);

		wp_localize_script(
			'ai1wmne_export',
			'ai1wmne_dependencies',
			array( 'messages' => $this->get_missing_dependencies() )
		);
	}

	/**
	 * Enqueue scripts and styles for Import Controller
	 *
	 * @param  string $hook Hook suffix
	 * @return void
	 */
	public function enqueue_import_scripts_and_styles( $hook ) {
		if ( stripos( 'all-in-one-wp-migration_page_ai1wm_import', $hook ) === false ) {
			return;
		}

		if ( is_rtl() ) {
			wp_enqueue_style(
				'ai1wmne_import',
				Ai1wm_Template::asset_link( 'css/import.min.rtl.css', 'AI1WMNE' ),
				array( 'ai1wm_import' )
			);
		} else {
			wp_enqueue_style(
				'ai1wmne_import',
				Ai1wm_Template::asset_link( 'css/import.min.css', 'AI1WMNE' ),
				array( 'ai1wm_import' )
			);
		}

		wp_enqueue_script(
			'ai1wmne_import',
			Ai1wm_Template::asset_link( 'javascript/import.min.js', 'AI1WMNE' ),
			array( 'ai1wm_import' )
		);

		wp_localize_script(
			'ai1wmne_import',
			'ai1wmne_import',
			array(
				'ajax' => array(
					'browser_url'     => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wmne_s3_browser' ) ),
					'incremental_url' => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wmne_s3_incremental' ) ),
				),
			)
		);

		wp_localize_script(
			'ai1wmne_import',
			'ai1wmne_dependencies',
			array( 'messages' => $this->get_missing_dependencies() )
		);
	}

	/**
	 * Enqueue scripts and styles for Settings Controller
	 *
	 * @param  string $hook Hook suffix
	 * @return void
	 */
	public function enqueue_settings_scripts_and_styles( $hook ) {
		if ( stripos( 'all-in-one-wp-migration_page_ai1wmne_settings', $hook ) === false ) {
			return;
		}

		if ( is_rtl() ) {
			wp_enqueue_style(
				'ai1wmne_settings',
				Ai1wm_Template::asset_link( 'css/settings.min.rtl.css', 'AI1WMNE' ),
				array( 'ai1wm_servmask' )
			);
		} else {
			wp_enqueue_style(
				'ai1wmne_settings',
				Ai1wm_Template::asset_link( 'css/settings.min.css', 'AI1WMNE' ),
				array( 'ai1wm_servmask' )
			);
		}

		wp_enqueue_script(
			'ai1wmne_settings',
			Ai1wm_Template::asset_link( 'javascript/settings.min.js', 'AI1WMNE' ),
			array( 'ai1wm_settings' )
		);

		wp_localize_script(
			'ai1wmne_settings',
			'ai1wm_feedback',
			array(
				'ajax'       => array(
					'url' => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wm_feedback' ) ),
				),
				'secret_key' => get_option( AI1WM_SECRET_KEY ),
			)
		);

		wp_localize_script(
			'ai1wmne_settings',
			'ai1wmne_dependencies',
			array( 'messages' => $this->get_missing_dependencies() )
		);
	}

	/**
	 * Register listeners for actions
	 *
	 * @return void
	 */
	protected function activate_actions() {
		add_action( 'plugins_loaded', array( $this, 'ai1wm_notification' ), 20 );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_export_scripts_and_styles' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_import_scripts_and_styles' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_settings_scripts_and_styles' ), 20 );
	}

	/**
	 * Enable notifications
	 *
	 * @return void
	 */
	public function ai1wm_notification() {
		if ( ai1wmne_is_running() ) {
			add_filter( 'ai1wm_notification_ok_toggle', 'Ai1wmne_Settings_Controller::notify_ok_toggle' );
			add_filter( 'ai1wm_notification_ok_email', 'Ai1wmne_Settings_Controller::notify_email' );
			add_filter( 'ai1wm_notification_error_toggle', 'Ai1wmne_Settings_Controller::notify_error_toggle' );
			add_filter( 'ai1wm_notification_error_subject', 'Ai1wmne_Settings_Controller::notify_error_subject' );
			add_filter( 'ai1wm_notification_error_email', 'Ai1wmne_Settings_Controller::notify_email' );
		}
	}

	/**
	 * Export and import commands
	 *
	 * @return void
	 */
	public function ai1wm_commands() {
		if ( ai1wmne_is_running() ) {
			add_filter( 'ai1wm_export', 'Ai1wmne_Export_S3::execute', 250 );
			add_filter( 'ai1wm_export', 'Ai1wmne_Export_Upload::execute', 260 );
			add_filter( 'ai1wm_export', 'Ai1wmne_Export_Retention::execute', 280 );
			add_filter( 'ai1wm_export', 'Ai1wmne_Export_Done::execute', 290 );

			add_filter( 'ai1wm_import', 'Ai1wmne_Import_S3::execute', 20 );
			add_filter( 'ai1wm_import', 'Ai1wmne_Import_Download::execute', 30 );
			add_filter( 'ai1wm_import', 'Ai1wmne_Import_Settings::execute', 290 );
			add_filter( 'ai1wm_import', 'Ai1wmne_Import_Database::execute', 310 );

			remove_filter( 'ai1wm_export', 'Ai1wm_Export_Download::execute', 250 );
			remove_filter( 'ai1wm_import', 'Ai1wm_Import_Upload::execute', 5 );
		}

		if ( ai1wmne_is_incremental() ) {
			add_filter( 'ai1wm_export', 'Ai1wmne_Export_Incremental_Content::execute', 105 );
			add_filter( 'ai1wm_export', 'Ai1wmne_Export_Incremental_Media::execute', 115 );
			add_filter( 'ai1wm_export', 'Ai1wmne_Export_Incremental_Plugins::execute', 125 );
			add_filter( 'ai1wm_export', 'Ai1wmne_Export_Incremental_Themes::execute', 135 );
			add_filter( 'ai1wm_export', 'Ai1wmne_Export_Incremental_Backups::execute', 270 );

			add_filter( 'ai1wm_import', 'Ai1wmne_Import_Incremental_S3::execute', 20 );
			add_filter( 'ai1wm_import', 'Ai1wmne_Import_Incremental_Download::execute', 30 );

			remove_filter( 'ai1wm_export', 'Ai1wmne_Export_Retention::execute', 280 );
			remove_filter( 'ai1wm_import', 'Ai1wmne_Import_S3::execute', 20 );
			remove_filter( 'ai1wm_import', 'Ai1wmne_Import_Download::execute', 30 );
		}
	}

	public function get_missing_dependencies() {
		$extensions = array();
		if ( ! extension_loaded( 'curl' ) ) {
			$extensions[] = 'cURL';
		}

		if ( ! extension_loaded( 'libxml' ) ) {
			$extensions[] = 'libxml';
		}

		if ( ! extension_loaded( 'simplexml' ) ) {
			$extensions[] = 'SimpleXML';
		}

		$messages = array();
		if ( ! empty( $extensions ) ) {
			$messages[] = sprintf( __( 'Your PHP is missing: %s.', AI1WMNE_PLUGIN_NAME ), implode( ', ', $extensions ) );
		}

		return $messages;
	}

	/**
	 * Check whether All-in-One WP Migration has been loaded
	 *
	 * @return void
	 */
	public function ai1wm_loaded() {
		if ( is_multisite() ) {
			add_action( 'network_admin_menu', array( $this, 'admin_menu' ), 20 );
		} else {
			add_action( 'admin_menu', array( $this, 'admin_menu' ), 20 );
		}

		// S3 Client init cron
		add_action( 'init', 'Ai1wmne_Settings_Controller::init_cron' );

		// S3 Client connection
		add_action( 'admin_post_ai1wmne_s3_connection', 'Ai1wmne_Settings_Controller::connection' );

		// S3 Client settings
		add_action( 'admin_post_ai1wmne_s3_settings', 'Ai1wmne_Settings_Controller::settings' );

		// Cron settings
		add_action( 'ai1wmne_s3_hourly_export', 'Ai1wm_Export_Controller::export' );
		add_action( 'ai1wmne_s3_daily_export', 'Ai1wm_Export_Controller::export' );
		add_action( 'ai1wmne_s3_weekly_export', 'Ai1wm_Export_Controller::export' );
		add_action( 'ai1wmne_s3_monthly_export', 'Ai1wm_Export_Controller::export' );

		// Picker
		add_action( 'ai1wm_import_left_end', 'Ai1wmne_Import_Controller::picker' );

		// Add export button
		add_filter( 'ai1wm_export_s3_client', 'Ai1wmne_Export_Controller::button' );

		// Add import button
		add_filter( 'ai1wm_import_s3_client', 'Ai1wmne_Import_Controller::button' );
	}

	/**
	 * WP CLI commands: extension
	 *
	 * @return void
	 */
	public function wp_cli_extension() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command(
				'ai1wm s3-client',
				'Ai1wmne_S3_Client_WP_CLI_Command',
				array(
					'shortdesc'     => __( 'All-in-One WP Migration Command for S3 Client', AI1WMNE_PLUGIN_NAME ),
					'before_invoke' => array( $this, 'activate_extension_commands' ),
				)
			);
			WP_CLI::add_command(
				'ai1wm s3-client incremental',
				'Ai1wmne_S3_Client_WP_CLI_Incremental_Command',
				array(
					'shortdesc'     => __( 'All-in-One WP Migration Command for S3 Client incremental backups', AI1WMNE_PLUGIN_NAME ),
					'before_invoke' => array( $this, 'activate_extension_incremental_commands' ),
				)
			);
		}
	}

	/**
	 * Activates extension specific commands
	 *
	 * @return void
	 */
	public function activate_extension_commands() {
		$_GET['s3-client'] = 1;
		$this->ai1wm_commands();
	}

	/**
	 * Activates extension specific commands
	 *
	 * @return void
	 */
	public function activate_extension_incremental_commands() {
		$_GET['incremental'] = 1;
		$this->activate_extension_commands();
	}

	/**
	 * Display All-in-One WP Migration notice
	 *
	 * @return void
	 */
	public function ai1wm_notice() {
		?>
		<div class="error">
			<p>
				<?php
				_e(
					'S3 Client Extension requires <a href="https://wordpress.org/plugins/all-in-one-wp-migration/" target="_blank">All-in-One WP Migration</a> to be activated.',
					AI1WMNE_PLUGIN_NAME
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Add links to plugin list page
	 *
	 * @return array
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( $file === AI1WMNE_PLUGIN_BASENAME ) {
			$links[] = __( '<a href="https://best-web.sk/" target="_blank">Extension Support</a>', AI1WMNE_PLUGIN_NAME );

			$upstream_version = ai1wmne_upstream_version();
			if ( $upstream_version && version_compare( $upstream_version, AI1WMNE_UPSTREAM_VERSION, '>' ) ) {
				$links[] = sprintf(
					__( 'Fork %1$s is based on upstream %2$s; upstream %3$s is available for manual review. Vendor auto-update is disabled.', AI1WMNE_PLUGIN_NAME ),
					esc_html( AI1WMNE_FORK_VERSION ),
					esc_html( AI1WMNE_UPSTREAM_VERSION ),
					esc_html( $upstream_version )
				);
			} else {
				$links[] = sprintf(
					__( 'Fork %1$s is based on upstream %2$s. Vendor auto-update is disabled.', AI1WMNE_PLUGIN_NAME ),
					esc_html( AI1WMNE_FORK_VERSION ),
					esc_html( AI1WMNE_UPSTREAM_VERSION )
				);
			}

			if (
				defined( 'AI1WM_VERSION' ) &&
				AI1WM_VERSION !== 'develop' &&
				version_compare( AI1WM_VERSION, AI1WMNE_MAX_REVIEWED_AI1WM_VERSION, '>' )
			) {
				$links[] = sprintf(
					__( 'Compatibility review needed: base plugin %1$s is newer than reviewed version %2$s.', AI1WMNE_PLUGIN_NAME ),
					esc_html( AI1WM_VERSION ),
					esc_html( AI1WMNE_MAX_REVIEWED_AI1WM_VERSION )
				);
			}
		}

		return $links;
	}

	/**
	 * Register initial router
	 *
	 * @return void
	 */
	public function router() {
		if ( current_user_can( 'import' ) ) {
			add_action( 'wp_ajax_ai1wmne_s3_browser', 'Ai1wmne_Import_Controller::browser' );
			add_action( 'wp_ajax_ai1wmne_s3_incremental', 'Ai1wmne_Import_Controller::incremental' );
		}
	}
}
