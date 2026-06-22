<?php
/**
 * Plausible Analytics | Admin Actions.
 *
 * @since      1.0.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Admin;

use Plausible\Analytics\WP\Helpers;

class Actions {
	/**
	 * Constructor.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'register_assets' ] );
		add_action( 'admin_init', [ $this, 'maybe_redirect_to_wizard' ] );
	}

	/**
	 * Redirect to Configuration Wizard on first boot.
	 *
	 * @return void
	 */
	public function maybe_redirect_to_wizard() {
		// Make sure it only runs when requested by (an admin in) a browser.
		if ( wp_doing_ajax() || wp_doing_cron() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// If we're already on the Settings page, there's no need to redirect.
		if ( array_key_exists( 'page', $_GET ) && $_GET['page'] === 'plausible_analytics' ) {
			return;
		}

		// Self-hosters should never be redirected to the settings screen, because the wizard isn't shown to them.
		$wizard_done = get_option( 'plausible_analytics_wizard_done', false ) || ! empty( Helpers::get_settings()['self_hosted_domain'] );

		if ( ! $wizard_done ) {
			$url = admin_url( 'options-general.php?page=plausible_analytics#welcome_slide' );

			wp_redirect( $url );

			exit;
		}
	}

	/**
	 * Register Assets.
	 *
	 * @since  1.0.0
	 * @since  1.3.0 Don't load CSS admin-wide. JS needs to load admin-wide, since we're throwing admin-wide, dismissable notices.
	 * @access public
	 * @return void
	 */
	public function register_assets( $current_page ) {
		if ( $current_page === 'settings_page_plausible_analytics' || $current_page === 'dashboard_page_plausible_analytics_statistics' ) {
			wp_enqueue_style(
				'plausible-admin',
				PLAUSIBLE_ANALYTICS_PLUGIN_URL . 'assets/dist/css/plausible-admin.css',
				'',
				filemtime( PLAUSIBLE_ANALYTICS_PLUGIN_DIR . 'assets/dist/css/plausible-admin.css' ),
				'all'
			);
		}

		wp_register_script(
			'plausible-admin',
			PLAUSIBLE_ANALYTICS_PLUGIN_URL . 'assets/dist/js/plausible-admin.js',
			'',
			filemtime( PLAUSIBLE_ANALYTICS_PLUGIN_DIR . 'assets/dist/js/plausible-admin.js' ),
			[ 'in_footer' => true ]
		);

		wp_localize_script( 'plausible-admin', 'plausible_analytics_i18n', [ 'connected' => __( 'Connected', 'plausible-analytics' ), 'connect' => __( 'Connect', 'plausible-analytics' ) ] );

		wp_enqueue_script( 'plausible-admin' );

		wp_add_inline_script( 'plausible-admin', 'var plausible_analytics_hosted_domain = "' . Helpers::get_hosted_domain_url() . '";' );
	}
}
