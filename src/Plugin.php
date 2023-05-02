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
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function register() {
		// Handle plugin activation and deactivation.
		register_activation_hook( PLAUSIBLE_ANALYTICS_PLUGIN_FILE, [ $this, 'activate' ] );
		register_deactivation_hook( PLAUSIBLE_ANALYTICS_PLUGIN_FILE, [ $this, 'deactivate' ] );

		// Register services used throughout the plugin. (WP Rocket runs at priority 10)
		add_action( 'plugins_loaded', [ $this, 'register_services' ], 9 );

		// Load text domain.
		add_action( 'init', [ $this, 'load_plugin_textdomain' ] );
	}

	/**
	 * Registers the individual services of the plugin.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_services() {
		if ( is_admin() ) {
			new Admin\Upgrades();
			new Admin\Settings\Page();
			new Admin\Filters();
			new Admin\Actions();
			new Admin\Module();
		}

		new Includes\Actions();
		new Includes\Compatibility();
		new Includes\Filters();
		new Includes\Proxy();
		new Includes\Setup();
	}

	/**
	 * Loads the plugin's translated strings.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
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
	 * @since  1.0.0
	 * @access public
	 *
	 * @param bool $network_wide Optional. Whether the plugin is being enabled on
	 *                           all network sites or a single site. Default false.
	 *
	 * @return void
	 */
	public function activate( $network_wide = false ) {
		$is_default_settings_saved = get_option( 'plausible_analytics_is_default_settings_saved', false );

		if ( ! $is_default_settings_saved ) {
			$domain_name      = Helpers::get_domain();
			$default_settings = [
				'domain_name'        => $domain_name,
				'tracked_user_roles' => [],
			];

			update_option( 'plausible_analytics_settings', $default_settings );
			update_option( 'plausible_analytics_is_default_settings_saved', true );
		}
	}

	/**
	 * Handles deactivation procedures.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function deactivate() {}
}
