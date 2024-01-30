<?php
/**
 * Plausible Analytics | Admin Actions.
 * @since      1.0.0
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
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'register_assets' ] );
		add_action( 'admin_init', [ $this, 'maybe_redirect_to_wizard' ] );
	}

	/**
	 * Register Assets.
	 * @since  1.0.0
	 * @since  1.3.0 Don't load CSS admin-wide. JS needs to load admin-wide, since we're throwing admin-wide, dismissable notices.
	 * @access public
	 * @return void
	 */
	public function register_assets( $current_page ) {
		if ( $current_page === 'settings_page_plausible_analytics' || $current_page === 'dashboard_page_plausible_analytics_statistics' ) {
			\wp_enqueue_style(
				'plausible-admin',
				PLAUSIBLE_ANALYTICS_PLUGIN_URL . 'assets/dist/css/plausible-admin.css',
				'',
				filemtime( PLAUSIBLE_ANALYTICS_PLUGIN_DIR . 'assets/dist/css/plausible-admin.css' ),
				'all'
			);
		}

		\wp_enqueue_script(
			'plausible-admin',
			PLAUSIBLE_ANALYTICS_PLUGIN_URL . 'assets/dist/js/plausible-admin.js',
			'',
			filemtime( PLAUSIBLE_ANALYTICS_PLUGIN_DIR . 'assets/dist/js/plausible-admin.js' ),
			true
		);
	}

	/**
	 * Redirect to Configuration Wizard on first boot.
	 * @return void
	 */
	public function maybe_redirect_to_wizard() {
		// Make sure it only runs when requested by a browser.
		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		// If we're already on the Settings page, there's no need to redirect.
		if ( array_key_exists( 'page', $_GET ) && $_GET[ 'page' ] === 'plausible_analytics' ) {
			return;
		}

		$wizard_done = get_option( 'plausible_analytics_wizard_done', false );

		if ( ! $wizard_done ) {
			$url = admin_url( 'options-general.php?page=plausible_analytics#welcome' );

			wp_redirect( $url );

			exit;
		}
	}
}
