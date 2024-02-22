<?php
/**
 * Plausible Analytics | Setup.
 * @since      1.3.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP;

defined( 'ABSPATH' ) || exit;

class Setup {
	/**
	 * Cron job handle
	 * @var string
	 */
	private $cron = 'plausible_analytics_update_js';

	/**
	 * Filters and Hooks.
	 * @return void
	 */
	public function __construct() {
		register_activation_hook( PLAUSIBLE_ANALYTICS_PLUGIN_FILE, [ $this, 'create_cache_dir' ] );
		register_activation_hook( PLAUSIBLE_ANALYTICS_PLUGIN_FILE, [ $this, 'activate_cron' ] );
		register_deactivation_hook( PLAUSIBLE_ANALYTICS_PLUGIN_FILE, [ $this, 'deactivate_cron' ] );

		// Attach the cron script to the cron action.
		add_action( $this->cron, [ $this, 'load_cron_script' ] );

		// This assures that the local file is updated when settings are saved.
		add_action( 'plausible_analytics_settings_saved', [ $this, 'load_cron_script' ] );
	}

	/**
	 * Create Cache-dir upon (re)activation.
	 */
	public function create_cache_dir() {
		$upload_dir = Helpers::get_proxy_resource( 'cache_dir' );

		if ( ! is_dir( $upload_dir ) ) {
			wp_mkdir_p( $upload_dir );
		}
	}

	/**
	 * Register hook to schedule script in wp_cron()
	 */
	public function activate_cron() {
		if ( ! wp_next_scheduled( $this->cron ) ) {
			wp_schedule_event( time(), 'daily', $this->cron );
		}
	}

	/**
	 * Deactivate cron when plugin is deactivated.
	 */
	public function deactivate_cron() {
		if ( wp_next_scheduled( $this->cron ) ) {
			wp_clear_scheduled_hook( $this->cron );
		}
	}

	/**
	 * Triggers the cron script.
	 */
	public function load_cron_script() {
		new \Plausible\Analytics\WP\Cron();
	}
}
