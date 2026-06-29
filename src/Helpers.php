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
	 * Returns the API token.
	 *
	 * @return string
	 */
	public static function get_api_token() {
		$settings = static::get_settings();
		$current  = static::get_current_language_domain_key();

		return $settings['api_token'][ $current ] ?? $settings['api_token']['default'] ?? '';
	}

	/**
	 * Get Settings.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return array
	 */
	public static function get_settings() {
		$defaults = [
			'domain_name'                => [ 'default' => '' ],
			'api_token'                  => [ 'default' => '' ],
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

		$settings = function_exists( 'get_option' ) ? get_option( 'plausible_analytics_settings', [] ) : [];
		$settings = wp_parse_args( $settings, $defaults );

		/**
		 * Normalization: Ensure domain_name and api_token are always arrays.
		 */
		if ( ! is_array( $settings['domain_name'] ) ) {
			$settings['domain_name'] = [ 'default' => $settings['domain_name'] ];
		}

		if ( ! is_array( $settings['api_token'] ) ) {
			$settings['api_token'] = [ 'default' => $settings['api_token'] ];
		}

		return apply_filters( 'plausible_analytics_settings', $settings );
	}

	/**
	 * Returns the key of the currently used Language Domain.
	 *
	 * @since v2.6.0
	 *
	 * @return string
	 */
	public static function get_current_language_domain_key() {
		if ( ! static::is_multilang_mode() ) {
			return 'default';
		}

		$language_domains = apply_filters( 'wpml_setting', [], 'language_domains' );
		$current_language = apply_filters( 'wpml_current_language', null );

		if ( $current_language && isset( $language_domains[ $current_language ] ) ) {
			return (string) apply_filters( 'plausible_analytics_current_language_domain_key', $current_language );
		}

		return (string) apply_filters( 'plausible_analytics_current_language_domain_key', 'default' );
	}

	/**
	 * Returns true only when WPML is active, its negotiation type is "different domain per language", AND at least one domain is configured.
	 *
	 * @since v2.6.0
	 *
	 * @return bool
	 */
	public static function is_multilang_mode() {
		static $is_multilang;

		if ( defined( 'PLAUSIBLE_CI' ) && PLAUSIBLE_CI ) {
			$is_multilang = null;
		}

		if ( $is_multilang !== null ) {
			return $is_multilang;
		}

		/**
		 * If WPML is not active, we can assume we're not in multilang mode.
		 */
		if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
			return $is_multilang = (bool) apply_filters( 'plausible_analytics_is_multilang_mode', false );
		}

		$negotiation_type = (int) apply_filters( 'wpml_setting', 0, 'language_negotiation_type' );
		$domains          = apply_filters( 'wpml_setting', [], 'language_domains' );
		$value            = $negotiation_type === 2 && ! empty( $domains );

		return $is_multilang = (bool) apply_filters( 'plausible_analytics_is_multilang_mode', $value );
	}

	/**
	 * Returns the name of the current Plausible domain.
	 *
	 * @since v2.6.0 This is now mapped to language domains to provide compatibility with multilang plugins, like WPML.
	 *
	 * @return string
	 */
	public static function get_domain() {
		$settings    = static::get_settings();
		$current_key = static::get_current_language_domain_key();
		$domain_name = $settings['domain_name'][ $current_key ] ?? '';

		if ( ! empty( $domain_name ) ) {
			return $domain_name;
		}

		if ( ! empty( $settings['domain_name']['default'] ) ) {
			return $settings['domain_name']['default'];
		}

		$url = home_url();

		return preg_replace( '/^http(s?):\/\/(www\.)?/i', '', $url );
	}

	/**
	 * Get Data API URL.
	 *
	 * @since  1.2.2
	 * @access public
	 * @return string
	 * @throws Exception
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
	 * A convenient way to retrieve the absolute path to the local JS file. Proxy should be enabled when this method is called!
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function get_js_path() {
		$filename = static::get_filename();

		if ( empty( $filename ) ) {
			return ''; // @codeCoverageIgnore
		}

		return static::get_proxy_resource( 'cache_dir' ) . $filename . '.js';
	}

	/**
	 * Get filename (without file extension)
	 *
	 * @since 1.3.0
	 * @return string
	 * @throws Exception
	 *
	 * @codeCoverageIgnore
	 */
	public static function get_filename() {
		$client = static::get_client();

		if ( $client instanceof Client ) {
			return $client->get_tracker_id( static::get_current_language_domain_key() );
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
	 * Get Analytics URL. Returns an empty string if @see self::get_filename() returns empty.
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function get_js_url( $local = false ) {
		$file_name = static::get_filename();

		if ( empty( $file_name ) ) {
			return ''; // @codeCoverageIgnore
		}

		/**
		 * If the Avoid Ad Blockers option is enabled, return URL pointing to the local file.
		 */
		if ( $local && static::proxy_enabled() ) {
			return esc_url( static::get_proxy_resource( 'cache_url' ) . $file_name . '.js' );
		}

		return esc_url( static::get_hosted_domain_url() . "/js/$file_name.js" );
	}

	/**
	 * @since v2.6.0 Provide compatibility with multilang plugins, like WPML.
	 *
	 * @return array
	 */
	public static function get_language_domains() {
		$domains = apply_filters( 'wpml_setting', [], 'language_domains' );
		$main    = home_url();

		// WPML's language_domains omits the default language; prepend the main WP domain.
		if ( ! in_array( $main, $domains, true ) ) {
			$domains = array_merge( [ 'default' => $main ], $domains );
		}

		return apply_filters( 'plausible_analytics_language_domains', $domains );
	}

	/**
	 * Get the name of the active multilang plugin.
	 *
	 * @return string
	 */
	public static function get_multilang_plugin_name() {
		$name = '';

		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$name = 'WPML';
		}

		return apply_filters( 'plausible_analytics_multilang_plugin_name', $name );
	}

	/**
	 * Get user role for the logged-in user.
	 *
	 * @since  1.3.0
	 * @access public
	 * @return string
	 */
	public static function get_user_role() {
		global $current_user;

		$user_roles = $current_user->roles;

		return array_shift( $user_roles );
	}

	/**
	 * Checks if the main Plausible Analytics script is registered.
	 *
	 * @return bool
	 */
	public static function main_script_is_registered() {
		return wp_script_is( 'plausible-analytics', 'registered' );
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
}
