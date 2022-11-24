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
use Plausible\Analytics\WP\Includes\RestApi\Controllers\RestEventController;
use Plausible\Analytics\WP\Admin\Actions;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helpers {

	/**
	 * Get Analytics URL.
	 *
	 * @return string
	 * @since  1.0.0
	 * @access public
	 *
	 */
	public static function get_analytics_url() {
		$settings         = self::get_settings();
		$default_domain   = 'plausible.io';
		$is_outbound_link = apply_filters( 'plausible_analytics_enable_outbound_links', true );
		$file_name        = $is_outbound_link ? 'script.outbound-links' : 'plausible';

		// Early return when there's a script path.
		if ( isset( $settings['is_proxy'] ) && $settings['is_proxy'] === 'true' && $settings['is_custom_path'] === 'true' && ! empty( $settings['script_path'] ) && is_string( $settings['script_path'] ) ) {
			return $settings['script_path'] . $file_name . '.js';
		}

		// Triggered when self-hosted analytics is enabled.
		if ( ! empty( $settings['is_self_hosted_analytics'] ) && 'true' === $settings['is_self_hosted_analytics'] ) {
			$default_domain = $settings['self_hosted_domain'];
		}

		$url = "https://{$default_domain}/js/{$file_name}.js";

		// Triggered when custom domain is enabled.
		if ( ! empty( $settings['is_proxy'] ) && 'true' === $settings['is_proxy'] ) {
			$upload_dir = wp_upload_dir();
			$upload_url = $upload_dir ['baseurl'];
			if ( is_ssl() ) {
				$upload_url = str_replace( 'http://', 'https://', $upload_url );
			}
			$url = trailingslashit( $upload_url ) . apply_filters( 'plausible_analytics_scripts_folder', 'stats' ) . "/js/{$file_name}.js";
		}

		return esc_url( $url );
	}

	/**
	 * Get Settings.
	 *
	 * @return array
	 * @since  1.0.0
	 * @access public
	 *
	 */
	public static function get_settings() {
		return get_option( 'plausible_analytics_settings', [] );
	}

	/**
	 *    Get all Analytics URLs from plausible.io
	 *    For future use
	 *
	 * @return array
	 * @since  1.2.5
	 * @access public
	 */
	public static function get_all_remote_urls() {
		$settings       = self::get_settings();
		$default_domain = 'plausible.io';
		$urls           = [];
		$folder         = '';

		$file_names = [
			'plausible',
			'script.outbound-links',
			'script.hash',
			'script.file-downloads',
			'script.manual',
			'script.local',
			'script.compat',
			'script.exclusions',
		];

		// Triggered when self hosted analytics is enabled.
		if ( ! empty( $settings['is_self_hosted_analytics'] ) && 'true' === $settings['is_self_hosted_analytics'] ) {
			$default_domain = $settings['self_hosted_domain'];
			$folder         = apply_filters( 'plausible_analytics_self_hosted_domain_scripts_folder', '' );
		}

		foreach ( $file_names as $file_name ) {
			$urls[] = esc_url( "https://{$default_domain}/{$folder}js/{$file_name}.js" );
		}

		return $urls;
	}

	/**
	 * Get Dashboard URL.
	 *
	 * @return string
	 * @since  1.0.0
	 * @access public
	 *
	 */
	public static function get_analytics_dashboard_url() {
		$settings = self::get_settings();
		if ( isset( $settings['domain_name'] ) ) {
			$domain = $settings['domain_name'];
		} else {
			$domain = Helpers::get_domain();
		}

		return esc_url( "https://plausible.io/{$domain}" );
	}

	/**
	 * Get Plain Domain.
	 *
	 * @return string
	 * @since  1.0.0
	 * @access public
	 *
	 */
	public static function get_domain() {
		$site_url = site_url();

		return preg_replace( '/^http(s?)\:\/\/(www\.)?/i', '', $site_url );
	}

	/**
	 * Toggle Switch HTML Markup.
	 *
	 * @param string $name Name of the toggle switch.
	 *
	 * @return void
	 * @since  1.2.5 Added the optional `force` argument.
	 * @access public
	 *
	 * @since  1.0.0
	 */
	public static function display_toggle_switch( $name, $force = null ) {
		$settings = Helpers::get_settings();
		if ( is_bool( $force ) ) {
			$settings[ $name ] = $force ? $settings[ $name ] : '';
		}
		$individual_settings = ! empty( $settings[ $name ] ) ? esc_html( $settings[ $name ] ) : '';
		?>
		<label class="plausible-analytics-switch">
			<input <?php checked( $individual_settings, 'true' ); ?> class="plausible-analytics-switch-checkbox"
																	 name="plausible_analytics_settings[<?php echo esc_attr( $name ); ?>]"
																	 value="1" type="checkbox"/>
			<span class="plausible-analytics-switch-slider"></span>
		</label>
		<?php
	}

	/**
	 * Check if Default Settings are saved.
	 *
	 * @return array
	 * @since  1.2.5
	 * @access public
	 *
	 */
	public static function is_default_settings_saved() {
		return get_option( 'plausible_analytics_is_default_settings_saved', false );
	}

	/**
	 * Get Plugin version from DB.
	 *
	 * @return array
	 * @since  1.2.5
	 * @access public
	 *
	 */
	public static function get_plugin_version_from_db() {
		return get_option( 'plausible_analytics_version', '1.0.0' );
	}

	/**
	 * Get Remote Default Data API URL.
	 *
	 * @return string
	 * @since  1.2.5
	 * @access public
	 *
	 */
	public static function get_default_data_api_url() {

		$url = 'https://plausible.io/api/event';

		$settings = Helpers::get_settings();

		// Triggered when self-hosted analytics is enabled.
		if ( ! empty( $settings['is_self_hosted_analytics'] ) && 'true' === $settings['is_self_hosted_analytics'] ) {
			$settings       = self::get_settings();
			$default_domain = $settings['self_hosted_domain'];
			$url            = "https://{$default_domain}/api/event";
		}

		return esc_url( $url );
	}

	/**
	 * Get Data API URL.
	 *
	 * @return string
	 * @since  1.2.2
	 * @access public
	 *
	 */
	public static function get_data_api_url() {

		$settings = self::get_settings();
		$url      = 'https://plausible.io/api/event';

		// Early return when there's a script path.
		if ( isset( $settings['is_proxy'] ) && $settings['is_proxy'] === 'true' && $settings['is_custom_path'] === 'true' && ! empty( $settings['event_path'] ) && is_string( $settings['event_path'] ) ) {
			return trailingslashit( $settings['event_path'] ) . 'event';
		}

		// Triggered when self-hosted analytics is enabled.
		if ( ! empty( $settings['is_self_hosted_analytics'] ) && 'true' === $settings['is_self_hosted_analytics'] ) {
			$self_hosted_domain = $settings['self_hosted_domain'];
			$url                = "https://{$self_hosted_domain}/api/event";
		}

		// Triggered when custom domain is enabled.
		if ( ! empty( $settings['is_proxy'] ) && 'true' === $settings['is_proxy'] ) {

			// Maybe Create proxy files
			if ( empty( $settings['is_rest'] ) ) {
				Actions::maybe_create_js_files();
			}

			if ( ! empty( $settings['is_rest'] ) && 'true' === $settings['is_rest'] ) {
				return RestEventController::get_event_route_url();
			}

			$upload_dir = wp_upload_dir();
			$upload_url = $upload_dir ['baseurl'];

			if ( is_ssl() ) {
				$upload_url = str_replace( 'http://', 'https://', $upload_url );
			}

			$url = trailingslashit( $upload_url ) . apply_filters( 'plausible_analytics_scripts_folder', 'stats' ) . '/api/event';

		}

		return esc_url( $url );
	}

	/**
	 * Sanitize the fields using this clean function.
	 *
	 * @param string|array $var Pass the string to sanitize.
	 *
	 * @return string|array
	 * @since  1.2.3
	 * @access public
	 *
	 */
	public static function clean( $var ) {
		if ( is_array( $var ) ) {
			return array_map( [ static::class, 'clean' ], $var );
		} else {
			return is_scalar( $var ) ? esc_attr( trim( $var ) ) : $var;
		}
	}

	/**
	 * Maybe Render Proxy Help Test according to $_SERVER vars
	 *
	 * @return string
	 * @since  1.2.5
	 * @access public
	 *
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

}
