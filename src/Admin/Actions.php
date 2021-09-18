<?php
/**
 * Plausible Analytics | Admin Actions.
 *
 * @since 1.0.0
 *
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Admin;

use Plausible\Analytics\WP\Includes\Helpers;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Actions {

	/**
	 * Constructor.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'register_assets' ] );
		add_action( 'wp_ajax_plausible_analytics_save_admin_settings', [ $this, 'save_admin_settings' ] );
	}

	/**
	 * Register Assets.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_assets() {
		\wp_enqueue_style( 'plausible-admin', PLAUSIBLE_ANALYTICS_PLUGIN_URL . 'assets/dist/css/plausible-admin.css', '', PLAUSIBLE_ANALYTICS_VERSION, 'all' );
		\wp_enqueue_script( 'plausible-admin', PLAUSIBLE_ANALYTICS_PLUGIN_URL . 'assets/dist/js/plausible-admin.js', '', PLAUSIBLE_ANALYTICS_VERSION, true );
	}

	/**
	 * Save Admin Settings.
	 *
	 * @since  1.3.0
	 * @access public
	 *
	 * @return void
	 */
	public function save_admin_settings() {
		$post_data = Helpers::clean( $_POST );
		$settings  = Helpers::get_settings();

		// Security: Roadblock to check for unauthorized access.
		check_admin_referer( 'plausible-analytics-settings-roadblock', 'roadblock' );

		// Prepare new settings.
		$new_settings = wp_parse_args( $post_data['plausible_analytics_settings'], $settings );

		// Save Settings.
		update_option( 'plausible_analytics_settings', $post_data['plausible_analytics_settings'] );

		// Send response.
		wp_send_json_success(
			[
				'message' => esc_html__( 'Settings saved successfully.', 'plausible-analytics' ),
			]
		);
	}
}
