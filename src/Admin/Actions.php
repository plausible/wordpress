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
		add_action( 'admin_notices', [ $this, 'show_unconfigured_multilang_domains_notice' ] );
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
	 * @since  v1.0.0
	 * @since  v2.6.0 Only load assets on Plausible Analytics related pages.
	 *
	 *
	 * @access public
	 * @return void
	 */
	public function register_assets( $current_page ) {
		if ( $current_page !== 'settings_page_plausible_analytics' && $current_page !== 'dashboard_page_plausible_analytics_statistics' ) {
			return;
		}

		wp_enqueue_style(
			'plausible-admin',
			PLAUSIBLE_ANALYTICS_PLUGIN_URL . 'assets/dist/css/plausible-admin.css',
			'',
			filemtime( PLAUSIBLE_ANALYTICS_PLUGIN_DIR . 'assets/dist/css/plausible-admin.css' ),
			'all'
		);

		wp_register_script(
			'plausible-admin',
			PLAUSIBLE_ANALYTICS_PLUGIN_URL . 'assets/dist/js/plausible-admin.js',
			'',
			filemtime( PLAUSIBLE_ANALYTICS_PLUGIN_DIR . 'assets/dist/js/plausible-admin.js' ),
			[ 'in_footer' => true ]
		);

		wp_localize_script(
			'plausible-admin',
			'plausible_analytics_i18n',
			[
				'connected' => __( 'Connected', 'plausible-analytics' ),
				'connect'   => __( 'Connect', 'plausible-analytics' ),
				'nonce'     => wp_create_nonce( 'plausible_analytics_dismiss_multilang_notice' ),
			]
		);

		wp_enqueue_script( 'plausible-admin' );
	}

	/**
	 * Show a notice when multilang domain-per-language mode is active.
	 *
	 * @return void
	 */
	public function show_unconfigured_multilang_domains_notice() {
		if ( ! Helpers::is_multilang_mode() ) {
			return;
		}

		$multilang_plugin = Helpers::get_multilang_plugin_name();

		if ( empty( $multilang_plugin ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( get_user_meta( get_current_user_id(), 'plausible_analytics_multilang_notice_dismissed', true ) ) {
			return;
		}

		$settings_url = admin_url( 'options-general.php?page=plausible_analytics' );
		?>
		<div class="notice notice-info is-dismissible plausible-analytics-multilang-notice">
			<p>
				<?php printf(
				/* translators: 1: Multilang plugin name (e.g. WPML), 2: URL to settings page. */
					__( 'Plausible Analytics now supports %1$s\'s different domains per language! You can connect each language domain to its own Plausible site in the <a href="%2$s">plugin settings</a>. Note: tracking multiple domains may require upgrading your Plausible subscription.', 'plausible-analytics' ),
					$multilang_plugin,
					$settings_url
				); ?>
			</p>
		</div>
		<?php
	}
}
