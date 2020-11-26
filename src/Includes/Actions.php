<?php
/**
 * Plausible Analytics | Actions.
 *
 * @since 1.0.0
 *
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Includes;

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
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
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
		$settings = Helpers::get_settings();

		// Bailout, if `administrator` user role accessing frontend.
		if ( 'false' === $settings['track_administrator'] && current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_enqueue_script( 'plausible-analytics', Helpers::get_analytics_url(), '', PLAUSIBLE_ANALYTICS_VERSION );

		// Load only when custom event goals are enabled.
		if ( 'true' === $settings['is_custom_event_goals'] ) {
			wp_add_inline_script( 'plausible-analytics', 'window.plausible = window.plausible || function() { (window.plausible.q = window.plausible.q || []).push(arguments) }' );
		}
	}
}
