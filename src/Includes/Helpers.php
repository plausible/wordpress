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
		$settings       = self::get_settings();
		$domain         = $settings['domain_name'];
		$default_domain = 'plausible.io';

		// Triggered when self hosted analytics is enabled.
		if ( 'true' === $settings['is_self_hosted_analytics'] ) {
			$default_domain = $settings['self_hosted_domain'];
		}

		$url = "https://{$default_domain}/js/plausible.js";

		// Triggered when custom domain is enabled.
		if ( 'true' === $settings['custom_domain'] ) {
			$custom_domain_prefix = $settings['custom_domain_prefix'];
			$url                  = "https://{$custom_domain_prefix}.{$domain}/js/index.js";
		}

		return $url;
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

		return "https://plausible.io/{$domain}";
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
		$settings = Helpers::get_settings();
		?>
		<label class="plausible-analytics-switch">
			<input <?php checked( $settings[ $name ], 'true' ); ?> class="plausible-analytics-switch-checkbox" name="plausible_analytics_settings[<?php echo $name; ?>]" value="1" type="checkbox" />
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
}
