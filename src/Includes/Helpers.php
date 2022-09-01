<?php
/**
 * Plausible Analytics | Helpers
 *
 * @since 1.0.0
 *
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Includes;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helpers {

	/**
	 * Get Plain Domain.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return string
	 */
	public static function get_domain() {
		$site_url = site_url();
		$domain   = preg_replace( '/^http(s?)\:\/\/(www\.)?/i', '', $site_url );

		return $domain;
	}

	/**
	 * Get Analytics URL.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return string
	 */
	public static function get_analytics_url() {
		$settings         = self::get_settings();
		$domain           = $settings['domain_name'];
		$default_domain   = 'plausible.io';
		$is_outbound_link = apply_filters( 'plausible_analytics_enable_outbound_links', true );
		$file_name        = $is_outbound_link ? 'plausible.outbound-links' : 'plausible';

		// Triggered when self hosted analytics is enabled.
		if (
			! empty( $settings['is_self_hosted_analytics'] ) &&
			'true' === $settings['is_self_hosted_analytics']
		) {
			$default_domain = $settings['self_hosted_domain'];
		}

		$url = "https://{$default_domain}/js/{$file_name}.js";

		// Triggered when custom domain is enabled.
		if (
			! empty( $settings['is_proxy'] ) &&
			'true' === $settings['is_proxy']
		) {
			$url = "https://{$domain}/js/{$file_name}.js";
		}

		return esc_url( $url );
	}

	/**
	 * Get Dashboard URL.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return string
	 */
	public static function get_analytics_dashboard_url() {
		$settings = self::get_settings();
		$domain   = $settings['domain_name'];

		return esc_url( "https://plausible.io/{$domain}" );
	}

	/**
	 * Toggle Switch HTML Markup.
	 *
	 * @param string $name Name of the toggle switch.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public static function display_toggle_switch( $name ) {
		$settings            = Helpers::get_settings();
		$individual_settings = ! empty( $settings[ $name ] ) ? esc_html( $settings[ $name ] ) : '';
		?>
		<label class="plausible-analytics-switch">
			<input <?php checked( $individual_settings, 'true' ); ?> class="plausible-analytics-switch-checkbox" name="plausible_analytics_settings[<?php echo esc_attr( $name ); ?>]" value="1" type="checkbox" />
			<span class="plausible-analytics-switch-slider"></span>
		</label>
		<?php
	}

	/**
	 * Get Settings.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return array
	 */
	public static function get_settings() {
		return get_option( 'plausible_analytics_settings', [] );
	}

	/**
	 * Get Data API URL.
	 *
	 * @since  1.2.2
	 * @access public
	 *
	 * @return string
	 */
	public static function get_data_api_url() {
		$settings = self::get_settings();
		$url      = 'https://plausible.io/api/event';

		// Triggered when self hosted analytics is enabled.
		if (
			! empty( $settings['is_self_hosted_analytics'] ) &&
			'true' === $settings['is_self_hosted_analytics']
		) {
			$default_domain = $settings['self_hosted_domain'];
			$url            = "https://{$default_domain}/api/event";
		}

		// Triggered when custom domain is enabled.
		if (
			! empty( $settings['is_proxy'] ) &&
			'true' === $settings['is_proxy']
		) {
			$domain               = $settings['domain_name'];
			$url                  = "https://{$domain}/api/event";
		}

		return esc_url( $url );
	}

	/**
	 * Sanitize the fields using this clean function.
	 *
	 * @param string|array $var Pass the string to sanitize.
	 *
	 * @since  1.2.3
	 * @access public
	 *
	 * @return string|array
	 */
	public static function clean( $var ) {
		if ( is_array( $var ) ) {
			return array_map( [ static::class, 'clean' ], $var );
		} else {
			return is_scalar( $var ) ? sanitize_text_field( trim( $var ) ) : $var;
		}
	}

	/**
	 * Maybe Render Proxy Help Test according to $_SERVER vars
	 *
	 * @since  1.3.0
	 * @access public
	 *
	 * @return string
	 */
	public static function get_server_software() {

		$server_software = false;

		if ( isset( $_SERVER['SERVER_SOFTWARE'] ) ) {
			if ( ! empty( $_SERVER['SERVER_SOFTWARE'] ) ) {
				if ( false !== strpos( $_SERVER['SERVER_SOFTWARE'], 'nginx' ) ) {
					$server_software = 'nginx';
				}

				if ( false !== strpos( $_SERVER['SERVER_SOFTWARE'], 'Apache' ) ) {
					$server_software = 'apache';
				}
			}
		}

		if ( isset( $_SERVER['HTTP_CDN_LOOP'] ) ) {
			if ( ! empty( $_SERVER['HTTP_CDN_LOOP'] ) ) {
				if ( false !== strpos( $_SERVER['HTTP_CDN_LOOP'], 'cloudflare' ) ) {
					$server_software = 'cloudflare';
				}
			}
		}

		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
				if ( false !== strpos( $_SERVER['HTTP_USER_AGENT'], 'Amazon CloudFront' ) ) {
					$server_software = 'cloudfront';
				}
			}
		}

		return $server_software;

	}

	/**
	 * Maybe Render Proxy Help Test according to $_SERVER vars
	 *
	 * @since  1.3.0
	 * @access public
	 *
	 * @return string
	 */
	public static function check_proxy_is_working() {
		$url = self::get_analytics_url();

		$args = array(
			'timeout'     => 5,
			'sslverify' => false,
		);

		$response 		= wp_remote_head( $url , $args );
		$response_code 	= wp_remote_retrieve_response_code( $response );

		return ( 200 === $response_code );

	}

}
