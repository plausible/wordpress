<?php
/**
 * Plausible Analytics | Setup.
 *
 * @since      1.3.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP;

class Setup {
	/**
	 * Filters and Hooks.
	 *
	 * @return void
	 */
	public function __construct() {
		register_activation_hook( PLAUSIBLE_ANALYTICS_PLUGIN_FILE, [ $this, 'create_cache_dir' ] );
		register_activation_hook( PLAUSIBLE_ANALYTICS_PLUGIN_FILE, [ $this, 'activate_cron' ] );
		register_deactivation_hook( PLAUSIBLE_ANALYTICS_PLUGIN_FILE, [ $this, 'deactivate_cron' ] );

		// Attach the cron script to the cron action.
		add_action( Cron::TASK_NAME, [ $this, 'load_cron_script' ] );

		// This assures that the local file is downloaded/updated when settings are saved.
		add_action( 'plausible_analytics_settings_saved', [ $this, 'load_cron_script' ] );
	}

	/**
	 * Create Cache-dir upon (re)activation.
	 */
	public function create_cache_dir() {
		$upload_dir = Helpers::get_proxy_resource( 'cache_dir' );

		if ( ! is_dir( $upload_dir ) ) {
			wp_mkdir_p( $upload_dir ); // @codeCoverageIgnore
		}
	}

	/**
	 * Register hook to schedule script in wp_cron()
	 *
	 * @codeCoverageIgnore
	 */
	public function activate_cron() {
		if ( ! wp_next_scheduled( Cron::TASK_NAME ) ) {
			wp_schedule_event( time(), 'daily', Cron::TASK_NAME );
		}
	}

	/**
	 * Deactivate cron when plugin is deactivated.
	 *
	 * @codeCoverageIgnore
	 */
	public function deactivate_cron() {
		if ( wp_next_scheduled( Cron::TASK_NAME ) ) {
			wp_clear_scheduled_hook( Cron::TASK_NAME );
		}
	}

	/**
	 * Triggers the cron script.
	 *
	 * @codeCoverageIgnore
	 */
	public function load_cron_script() {
		new Cron();
	}
}
