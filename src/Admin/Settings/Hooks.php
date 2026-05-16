<?php
/**
 * Plausible Analytics | Settings Page Hooks.
 *
 * @since        2.1.0
 * @package      WordPress
 * @subpackage   Plausible Analytics
 *
 * @noinspection HtmlUnknownTarget
 */

namespace Plausible\Analytics\WP\Admin\Settings;

use Plausible\Analytics\WP\Helpers;

/**
 * @codeCoverageIgnore
 */
class Hooks extends API {
	/**
	 * Build class properties.
	 */
	public function __construct( $init = true ) {
		if ( $init ) {
			$this->init_hooks();
		}
	}

	/**
	 * Init action hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_filter( 'plausible_analytics_toggle_option_success_message', [ $this, 'maybe_modify_success_message' ], 10, 3 );
		add_action( 'plausible_analytics_settings_api_token_missing', [ $this, 'missing_api_token_warning' ] );
		add_action( 'plausible_analytics_settings_enable_analytics_dashboard_notice', [ $this, 'enable_analytics_dashboard_notice' ] );
		add_action( 'plausible_analytics_settings_option_disabled_by_missing_api_token', [ $this, 'option_disabled_by_missing_api_token' ] );
		add_action( 'plausible_analytics_settings_option_disabled_by_proxy', [ $this, 'option_disabled_by_proxy' ] );
		add_action( 'plausible_analytics_settings_option_not_available_in_ce', [ $this, 'option_na_in_ce' ] );
		add_action( 'plausible_analytics_settings_proxy_warning', [ $this, 'proxy_warning' ] );
	}

	/**
	 * Modifies "Enable proxy enabled" to "Proxy enabled", etc.
	 *
	 * @param $message
	 * @param $option_name
	 * @param $status
	 *
	 * @return string
	 */
	public function maybe_modify_success_message( $message, $option_name, $status ) {
		if ( $option_name !== 'proxy_enabled' ) {
			return $message;
		}

		if ( ! $status ) {
			return __( 'Proxy disabled.', 'plausible-analytics' );
		}

		return __( 'Proxy enabled.', 'plausible-analytics' );
	}

	/**
	 * Renders the warning for the Enable Proxy option.
	 *
	 * @since  1.3.0
	 * @output HTML
	 */
	public function proxy_warning() {
		if ( ! empty( Helpers::get_settings()[ 'self_hosted_domain' ] ) ) {
			$this->option_na_in_ce();
		} else {
			echo sprintf(
				wp_kses(
					// translators: %s: URL to Plausible contact page.
					__(
						'After enabling this option, please check your Plausible dashboard to make sure stats are being recorded. Are stats not being recorded? Do <a href="%s" target="_blank">reach out to us</a>. We\'re here to help!',
						'plausible-analytics'
					),
					'post'
				),
				'https://plausible.io/contact'
			);
		}
	}

	/**
	 * Show notice when Plugin Token notice is disabled.
	 *
	 * @output HTML
	 */
	public function option_na_in_ce() {
		echo wp_kses(
			__(
				'This feature is not available in Plausible Community Edition.',
				'plausible-analytics'
			),
			'post'
		);
	}

	/**
	 * Renders the analytics dashboard link if the option is enabled.
	 *
	 * @since  2.0.0
	 * @output HTML
	 */
	public function enable_analytics_dashboard_notice() {
		if ( ! empty( Helpers::get_settings()[ 'enable_analytics_dashboard' ] ) ) {
			echo sprintf(
				wp_kses(
					// translators: %s: URL to the analytics dashboard.
					__(
						'Your analytics dashboard is available <a href="%s">here</a>.',
						'plausible-analytics'
					),
					'post'
				),
				admin_url( 'index.php?page=plausible_analytics_statistics' )
			);
		}
	}

	/**
	 * Renders the Self-hosted warning if the Proxy is enabled.
	 *
	 * @since  1.3.3
	 * @output HTML
	 */
	public function option_disabled_by_proxy() {
		if ( Helpers::proxy_enabled() ) {
			echo wp_kses(
				__(
					'This option is disabled, because the <strong>Proxy</strong> setting is enabled under <em>Settings</em>.',
					'plausible-analytics'
				),
				'post'
			);
		}
	}

	/**
	 * Display missing Plugin Token warning.
	 *
	 * @output HTML
	 */
	public function missing_api_token_warning() {
		echo sprintf(
			wp_kses(
				__(
					'Please <a class="plausible-create-api-token hover:cursor-pointer underline">create a Plugin Token</a> and insert it into the Plugin Token field above.',
					'plausible-analytics'
				),
				'post'
			)
		);
	}

	/**
	 * Display option disabled by missing Plugin Token warning.
	 *
	 * @output HTML
	 */
	public function option_disabled_by_missing_api_token() {
		echo wp_kses(
			__(
				'Please <a class="plausible-create-api-token hover:cursor-pointer underline">create a Plugin Token</a> and insert it into the Plugin Token field above to enable this option.',
				'plausible-analytics'
			),
			'post'
		);
	}
}
