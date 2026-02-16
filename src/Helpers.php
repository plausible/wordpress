<?php
/**
 * Plausible Analytics | Helpers
 *
 * @since      1.0.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP;

use Exception;

/**
 * We use 'static' (late static binding) instead of 'self' (early binding), so we can mock (where needed) in our tests.
 */
class Helpers {
	/**
	 * Get Analytics URL.
	 *
	 * @return string
	 * @throws Exception
	 * @since  1.0.0
	 *
	 */
	public static function get_js_url( $local = false ) {
		$file_name = static::get_filename();

		/**
		 * If the Avoid Ad Blockers option is enabled, return URL pointing to the local file.
		 */
		if ( $local && static::proxy_enabled() ) {
			return esc_url( static::get_proxy_resource( 'cache_url' ) . $file_name . '.js' );
		}

		return esc_url( static::get_hosted_domain_url() . "/js/$file_name.js" );
	}

	/**
	 * Get filename (without file extension)
	 *
	 * @return string
	 * @throws Exception
	 *
	 * @codeCoverageIgnore
	 * @since 1.3.0
	 */
	public static function get_filename() {
		$client = static::get_client();

		if ( $client instanceof Client ) {
			return $client->get_tracker_id();
		}

		return '';
	}

	/**
	 * Build the API client.
	 *
	 * @return false|Client
	 *
	 * @codeCoverageIgnore This seam's only function is to keep our code testable.
	 */
	protected static function get_client() {
		$client = new ClientFactory();

		return $client->build();
	}

	/**
	 * Is the proxy enabled?
	 *
	 * @param array $settings Allows passing a current settings object.
	 *
	 * @return bool
	 */
	public static function proxy_enabled( $settings = [] ) {
		if ( empty( $settings ) ) {
			$settings = static::get_settings();
		}

		return ! empty( $settings['proxy_enabled'] ) || isset( $_GET['plausible_proxy'] );
	}

	/**
	 * Get Settings.
	 *
	 * @return array
	 * @since  1.0.0
	 * @access public
	 */
	public static function get_settings() {
		$defaults = [
			'domain_name'                => '',
			'api_token'                  => '',
			'enhanced_measurements'      => [
				EnhancedMeasurements::FOUR_O_FOUR,
				EnhancedMeasurements::FILE_DOWNLOADS,
				EnhancedMeasurements::OUTBOUND_LINKS,
				EnhancedMeasurements::FORM_COMPLETIONS,
				EnhancedMeasurements::SEARCH_QUERIES,
			],
			'affiliate_links'            => [],
			'query_params'               => [],
			'proxy_enabled'              => '',
			'enable_analytics_dashboard' => '',
			'shared_link'                => '',
			'excluded_pages'             => '',
			'tracked_user_roles'         => [],
			'expand_dashboard_access'    => [],
			'disable_toolbar_menu'       => '',
			'self_hosted_domain'         => '',
			'self_hosted_shared_link'    => '',
		];

		$settings = get_option( 'plausible_analytics_settings', [] );

		return apply_filters( 'plausible_analytics_settings', wp_parse_args( $settings, $defaults ) );
	}

	/**
	 * Get a proxy resource by name.
	 *
	 * @param string $resource_name
	 *
	 * @return string Value of resource from DB or empty string if Bypass ad blockers option is disabled.
	 * @throws Exception
	 */
	public static function get_proxy_resource( $resource_name = '' ) {
		$resources = static::get_proxy_resources();

		/**
		 * Create the cache directory if it doesn't exist.
		 */
		if ( ( $resource_name === 'cache_dir' || $resource_name === 'cache_url' ) && ! is_dir( $resources['cache_dir'] ) ) {
			wp_mkdir_p( $resources[ $resource_name ] );
		}

		return $resources[ $resource_name ] ?? '';
	}

	/**
	 * Get (and generate/store if non-existent) proxy resources.
	 *
	 * @return array
	 * @throws Exception
	 *
	 * @codeCoverageIgnore
	 */
	public static function get_proxy_resources() {
		static $resources;

		if ( $resources === null ) {
			$resources = get_option( 'plausible_analytics_proxy_resources', [] );
		}

		/**
		 * Force a refresh of our resources if the user recently switched to SSL and we still have non-SSL resources stored.
		 */
		if ( ! empty( $resources ) && is_ssl() && isset( $resources['cache_url'] ) && ( strpos( $resources['cache_url'], 'http:' ) !== false ) ) {
			$resources = [];
		}

		if ( empty( $resources ) ) {
			$cache_dir  = bin2hex( random_bytes( 5 ) );
			$upload_dir = wp_get_upload_dir();
			$resources  = [
				'namespace' => bin2hex( random_bytes( 3 ) ),
				'base'      => bin2hex( random_bytes( 2 ) ),
				'endpoint'  => bin2hex( random_bytes( 4 ) ),
				'cache_dir' => trailingslashit( $upload_dir['basedir'] ) . trailingslashit( $cache_dir ),
				'cache_url' => trailingslashit( $upload_dir['baseurl'] ) . trailingslashit( $cache_dir ),
			];

			update_option( 'plausible_analytics_proxy_resources', $resources );
		}

		return $resources;
	}

	/**
	 * Returns the URL of the domain where Plausible Analytics is hosted: self-hosted or cloud.
	 *
	 * @return string
	 */
	public static function get_hosted_domain_url() {
		$settings = static::get_settings();

		if ( defined( 'PLAUSIBLE_SELF_HOSTED_DOMAIN' ) ) {
			return esc_url( 'https://' . PLAUSIBLE_SELF_HOSTED_DOMAIN ); // @codeCoverageIgnore
		}

		if ( ! empty( $settings['self_hosted_domain'] ) ) {
			/**
			 * Until proven otherwise, let's just assume people are all on SSL.
			 */
			return esc_url( 'https://' . $settings['self_hosted_domain'] );
		}

		return esc_url( 'https://plausible.io' );
	}

	/**
	 * @param $option_name
	 * @param $option_value
	 *
	 * @return void
	 */
	public static function update_setting( $option_name, $option_value ) {
		$settings                 = static::get_settings();
		$settings[ $option_name ] = $option_value;

		update_option( 'plausible_analytics_settings', $settings );
	}

	/**
	 * A convenient way to retrieve the absolute path to the local JS file. Proxy should be enabled when this method is called!
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function get_js_path() {
		return static::get_proxy_resource( 'cache_dir' ) . static::get_filename() . '.js';
	}

	/**
	 * Get entered Domain Name or provide alternative if not entered.
	 *
	 * @return string
	 * @since  1.0.0
	 * @access public
	 */
	public static function get_domain() {
		$settings = static::get_settings();

		if ( ! empty( $settings['domain_name'] ) ) {
			return $settings['domain_name'];
		}

		$url = home_url();

		return preg_replace( '/^http(s?):\/\/(www\.)?/i', '', $url );
	}

	/**
	 * Get Data API URL.
	 *
	 * @return string
	 * @throws Exception
	 * @since  1.2.2
	 * @access public
	 */
	public static function get_endpoint_url() {
		if ( static::proxy_enabled() ) {
			// This will make sure the API endpoint is properly registered when we're testing.
			$append = isset( $_GET['plausible_proxy'] ) ? '?plausible_proxy=1' : '';

			return static::get_rest_endpoint() . $append;
		}

		return esc_url( static::get_hosted_domain_url() . '/api/event' );
	}

	/**
	 * Returns the Proxy's REST endpoint.
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function get_rest_endpoint( $abs_url = true ) {
		$namespace = static::get_proxy_resource( 'namespace' );
		$base      = static::get_proxy_resource( 'base' );
		$endpoint  = static::get_proxy_resource( 'endpoint' );

		$uri = "$namespace/v1/$base/$endpoint";

		if ( $abs_url ) {
			return get_rest_url( null, $uri );
		}

		return '/' . rest_get_url_prefix() . '/' . $uri;
	}

	/**
	 * Get user role for the logged-in user.
	 *
	 * @return string
	 * @since  1.3.0
	 * @access public
	 */
	public static function get_user_role() {
		global $current_user;

		$user_roles = $current_user->roles;

		return array_shift( $user_roles );
	}
}
