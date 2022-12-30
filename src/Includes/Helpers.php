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
use Plausible\Analytics\WP\Includes\RestApi\ApiHelpers;
use Plausible\Analytics\WP\Includes\RestApi\Controllers\RestEventController;
use Plausible\Analytics\WP\Admin\Actions;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 *
 */
class Helpers {

	/**
	 * Schedule the maybe_create_js_files() method to run every 1 hour
	 *
	 * @return void
	 */
	public static function schedule_maybe_create_js_files() {
		// Check if the maybe_create_js_files event is already scheduled
		if ( ! wp_next_scheduled( 'maybe_create_js_files' ) ) {
			// If not, schedule the maybe_create_js_files event to run every 1 hour
			wp_schedule_event( time(), 'hourly', 'maybe_create_js_files' );
		}
	}

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
		$default_domain   = self::get_default_domain();
		$is_outbound_link = apply_filters( 'plausible_analytics_enable_outbound_links', true );
		$file_name        = $is_outbound_link ? 'script.outbound-links' : 'plausible';
		$folder           = '';

		// Early return when there's a script path.
		if ( isset( $settings['is_proxy'] ) && $settings['is_proxy'] === 'true' && $settings['is_custom_path'] === 'true' && ! empty( $settings['script_path'] ) && is_string( $settings['script_path'] ) ) {
			return $settings['script_path'] . $file_name . '.js';
		}

		// Triggered when self-hosted analytics is enabled.
		if ( ! empty( $settings['is_self_hosted_analytics'] ) && 'true' === $settings['is_self_hosted_analytics'] ) {
			$default_domain = apply_filters( 'plausible_analytics_self_hosted_domain', $settings['self_hosted_domain'] );
			$folder         = apply_filters( 'plausible_analytics_self_hosted_domain_scripts_folder', '' );
		}

		$url = "https://{$default_domain}/{$folder}js/{$file_name}.js";

		// Triggered when custom domain is enabled.
		if ( ! empty( $settings['is_proxy'] ) && 'true' === $settings['is_proxy'] ) {
			$upload_dir = wp_upload_dir();
			$upload_url = $upload_dir ['baseurl'];
			if ( is_ssl() ) {
				$upload_url = str_replace( 'http://', 'https://', $upload_url );
			}

			$child_folder_name = self::get_child_folder_name();

			/**
			 * Filters to rename the folder where the analytics scripts files will be created.
			 *
			 * @param string $child_folder_name The current child folder name.
			 *
			 * @return string The modified child folder name.
			 *
			 * @since 1.2.5
			 */
			$url = trailingslashit( $upload_url ) . $child_folder_name . "/js/{$file_name}.js";
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
		$settings = get_option( 'plausible_analytics_settings', [] );
		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		return $settings;

	}

	/**
	 * @return mixed|null
	 */
	public static function get_default_domain() {
		return apply_filters( 'plausible_analytics_default_domain', 'plausible.io' );
	}

	/**
	 * Get the child folder name
	 *
	 * @return string
	 * @since  1.2.5
	 * @access public
	 */
	public static function get_child_folder_name() {

		$settings = Helpers::get_settings();

		$child_folder_name = isset( $settings['child_folder_name'] ) && ! empty( $settings['child_folder_name'] ) ? $settings['child_folder_name'] : '';

		// Generate a random string for the child folder name if $child_folder is empty
		if ( empty( $child_folder_name ) ) {
			$child_folder_name = wp_generate_password( 10, false );
			// Remove any prohibited characters from the folder name
			$child_folder_name = preg_replace( '/[^a-zA-Z0-9-_]/', '', $child_folder_name );
			// Truncate the folder name if it is too long
			$child_folder_name = substr( $child_folder_name, 0, 255 );
		}

		return apply_filters( 'plausible_analytics_scripts_folder_name', $child_folder_name );
	}

	/**
	 * Save settings
	 *
	 * @param array $settings Settings.
	 *
	 * @return void
	 * @since  1.2.5
	 * @access public
	 */
	public static function save_settings( array $settings, array $old_settings = [] ) {
		update_option( 'plausible_analytics_settings', array_merge( $old_settings, $settings ) );
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

		// Triggered when self-hosted analytics is enabled.
		if ( ! empty( $settings['is_self_hosted_analytics'] ) && 'true' === $settings['is_self_hosted_analytics'] ) {
			$default_domain = apply_filters( 'plausible_analytics_self_hosted_domain', $settings['self_hosted_domain'] );
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
			$default_domain = apply_filters( 'plausible_analytics_self_hosted_domain', $settings['self_hosted_domain'] );
			$folder         = apply_filters( 'plausible_analytics_self_hosted_domain_scripts_folder', '' );
			$url            = "https://{$default_domain}/{$folder}api/event";
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
			$self_hosted_domain = apply_filters( 'plausible_analytics_self_hosted_domain', $settings['self_hosted_domain'] );
			$folder             = apply_filters( 'plausible_analytics_self_hosted_domain_scripts_folder', '' );
			$url                = "https://{$self_hosted_domain}/{$folder}api/event";
		}

		// Triggered when custom domain is enabled.
		if ( ! empty( $settings['is_proxy'] ) && 'true' === $settings['is_proxy'] ) {

			if ( ! empty( $settings['proxy_is_rest'] ) && 'true' === $settings['proxy_is_rest'] ) {
				return RestEventController::get_event_route_url();
			}

			$url = self::get_php_proxy_url();

		}

		/**
		 * Filters the data API URL to modify it.
		 *
		 * @param string $data_api_url The current data API URL.
		 *
		 * @return string The modified data API URL.
		 *
		 * @since 1.2.5
		 */

		return esc_url( apply_filters( 'plausible_analytics_data_api_url', $url ) );
	}

	/**
	 * @return string
	 */
	public static function get_php_proxy_url(): string {
		// Get the upload directory information and set the correct scheme (http or https) for the upload URL
		$upload_dir = wp_upload_dir();
		$upload_url = set_url_scheme( $upload_dir ['baseurl'] );

		$child_folder_name = self::get_child_folder_name();

		$url = trailingslashit( $upload_url ) . $child_folder_name;

		return $url;
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

	/**
	 * @param $parent_folder
	 *
	 * @return mixed|null
	 */
	public static function get_parent_folder( $parent_folder ) {
		/**
		 * Filters to modify the parent folder name using the 'plausible_analytics_scripts_parent_folder' filter.
		 *
		 * @param string $parent_folder The current parent folder name.
		 *
		 * @return string The modified parent folder name.
		 *
		 * @since 1.2.5
		 */

		return apply_filters( 'plausible_analytics_scripts_parent_folder', $parent_folder );
	}

	/**
	 * Gets the path to the folder where the analytics scripts files will be created.
	 *
	 * @param $parent_folder
	 * @param $child_folder_name
	 *
	 * @return string The path to the child folder.
	 *
	 * @since 1.2.5
	 */
	public static function get_child_folder_path( $parent_folder, $child_folder_name ) {
		/**
		 * Filters the path to the folder where the analytics scripts files will be created.
		 *
		 * @param string $parent_folder The parent folder.
		 * @param string $child_folder_name The current child folder name.
		 *
		 * @return string The modified path to the child folder.
		 *
		 * @since 1.2.5
		 */
		return apply_filters( 'plausible_analytics_child_folder_path', trailingslashit( $parent_folder . DIRECTORY_SEPARATOR . $child_folder_name ), $parent_folder, $child_folder_name );
	}

	/**
	 * Check if Default Settings are saved.
	 *
	 * @return array
	 * @since  1.2.5
	 * @access public
	 *
	 */
	public static function is_js_files_created() {
		return get_option( 'plausible_analytics_is_js_files_created', false );
	}

	/**
	 * Check if the php proxy api is working.
	 *
	 * @return int|string The response code, or an empty string if the request failed.
	 * @since 1.2.5
	 *
	 */
	public static function check_php_proxy_api() {

		$settings = self::get_settings();

		$api_php_proxy_url = self::get_php_proxy_url();

		$domain_name = isset( $settings['domain_name'] ) ? $settings['domain_name'] : Helpers::get_domain();
		$event_name  = 'plausible-analytics';
		$url         = get_site_url();

		$post_data = [
			'n' => $event_name,
			'u' => $url,
			'd' => $domain_name,
			'r' => null,
			'w' => null,
		];

		$response = wp_remote_post(
			$api_php_proxy_url,
			[
				'sslverify' => false,
				'body'      => $post_data,
				'timeout'   => 3,
			]
		);

		// Retrieve the response code.
		return wp_remote_retrieve_response_code( $response );
	}


}
