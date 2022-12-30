<?php

namespace Plausible\Analytics\WP;

use Plausible\Analytics\WP\Admin;
use Plausible\Analytics\WP\Includes;
use Plausible\Analytics\WP\Includes\Helpers;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads and registers plugin functionality through WordPress hooks.
 *
 * @since 1.0.0
 */
final class Plugin {

	/**
	 * Registers functionality with WordPress hooks.
	 *
	 * @return void
	 * @since  1.0.0
	 * @access public
	 *
	 */
	public function register() {
		// Handle plugin activation and deactivation.
		register_activation_hook( PLAUSIBLE_ANALYTICS_PLUGIN_FILE, [ $this, 'activate' ] );
		register_deactivation_hook( PLAUSIBLE_ANALYTICS_PLUGIN_FILE, [ $this, 'deactivate' ] );
		register_uninstall_hook( PLAUSIBLE_ANALYTICS_PLUGIN_FILE, [ 'Plugin', 'uninstall' ] );

		// Register services used throughout the plugin.
		add_action( 'plugins_loaded', [ $this, 'register_services' ] );

		// Load text domain.
		add_action( 'init', [ $this, 'load_plugin_textdomain' ] );
	}

	/**
	 * Registers the individual services of the plugin.
	 *
	 * @return void
	 * @since  1.0.0
	 * @access public
	 *
	 */
	public function register_services() {
		if ( is_admin() ) {
			new Admin\Upgrades();
			new Admin\Settings();
			new Admin\Filters();
			new Admin\Actions();
		}

		new Includes\Actions();
		new Includes\Filters();
		new Includes\ThirdParties();

		/**
		 * @since  1.2.5
		 */
		new Includes\RestApi\Server();
	}

	/**
	 * Loads the plugin's translated strings.
	 *
	 * @return void
	 * @since  1.0.0
	 * @access public
	 *
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'plausible-analytics',
			false,
			dirname( plugin_basename( PLAUSIBLE_ANALYTICS_PLUGIN_FILE ) ) . '/languages/'
		);
	}

	/**
	 * Handles activation procedures during installation and updates.
	 *
	 * @param bool $network_wide Optional. Whether the plugin is being enabled on
	 *                           all network sites or a single site. Default false.
	 *
	 * @return void
	 * @since  1.0.0
	 * @access public
	 *
	 */
	public function activate( $network_wide = false ) {

		$is_default_settings_saved = get_option( 'plausible_analytics_is_default_settings_saved', false );

		if ( ! $is_default_settings_saved ) {

			$default_settings = [
				'domain_name'         => Helpers::get_domain(),
				'is_proxy'            => 'true',
				'track_administrator' => 'false',
			];

			Helpers::save_settings( $default_settings );
			update_option( 'plausible_analytics_is_default_settings_saved', true );
		}

		Admin\Actions::maybe_create_js_files();

		// Register the schedule_maybe_create_js_files() method to run when the 'wp' action is triggered
		add_action( 'wp', [ 'Helper', 'schedule_maybe_create_js_files' ] );

	}

	/**
	 * Handles deactivation procedures.
	 *
	 * @return void
	 * @since  1.0.0
	 * @access public
	 *
	 */
	public function deactivate() {

		// Remove the maybe_create_js_files event when the plugin is deactivated
		wp_clear_scheduled_hook( 'maybe_create_js_files' );

	}

	/**
	 * Handles uninstall procedures.
	 *
	 * @return void
	 * @since  1.2.5
	 * @access public
	 *
	 */
	public static function uninstall() {
		// Remove the maybe_create_js_files event when the plugin is uninstalled
		wp_clear_scheduled_hook( 'maybe_create_js_files' );
	}
}
