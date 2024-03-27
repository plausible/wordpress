<?php
/**
 * Plausible Analytics | Helpers
 * @since      1.0.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP;

use Exception;
use WpOrg\Requests\Exception\InvalidArgument;

defined( 'ABSPATH' ) || exit;

class Helpers {
	/**
	 * Get Analytics URL.
	 * @since  1.0.0
	 *
	 * @param bool $local Return the Local JS file IF proxy is enabled.
	 *
	 * @return string
	 */
	public static function get_js_url( $local = false ) {
		$settings       = self::get_settings();
		$file_name      = self::get_filename( $local );
		$default_domain = 'plausible.io';
		$domain         = $default_domain;

		/**
		 * If Avoid ad blockers is enabled, return URL to local file.
		 */
		if ( $local && self::proxy_enabled() ) {
			return esc_url( self::get_proxy_resource( 'cache_url' ) . $file_name . '.js' );
		}

		// Allows for hard-coding the self-hosted domain.
		if ( defined( 'PLAUSIBLE_SELF_HOSTED_DOMAIN' ) ) {
			$domain = PLAUSIBLE_SELF_HOSTED_DOMAIN;
		}

		/**
		 * Set $domain to self_hosted_domain if it exists.
		 */
		if ( ! empty( $settings[ 'self_hosted_domain' ] ) && $domain === $default_domain ) {
			$domain = $settings[ 'self_hosted_domain' ];
		}

		$url = "https://{$domain}/js/{$file_name}.js";

		return esc_url( $url );
	}

	/**
	 * Get Settings.
	 * @since  1.0.0
	 * @access public
	 * @return array
	 */
	public static function get_settings() {
		$defaults = [
			'domain_name'                => '',
			'api_token'                  => '',
			'enhanced_measurements'      => [],
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
	 * Get filename (without file extension)
	 * @since 1.3.0
	 * @return string
	 */
	public static function get_filename( $local = false ) {
		$settings  = self::get_settings();
		$file_name = 'plausible';

		if ( $local && self::proxy_enabled() ) {
			return self::get_proxy_resource( 'file_alias' );
		}

		foreach ( [ 'outbound-links', 'file-downloads', 'tagged-events', 'revenue', 'pageview-props', 'compat', 'hash' ] as $extension ) {
			if ( is_array( $settings[ 'enhanced_measurements' ] ) && in_array( $extension, $settings[ 'enhanced_measurements' ], true ) ) {
				$file_name .= '.' . $extension;
			}
		}

		// Load exclusions.js if any excluded pages are set.
		if ( ! empty( $settings[ 'excluded_pages' ] ) ) {
			$file_name .= '.' . 'exclusions';
		}

		return $file_name;
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
			$settings = self::get_settings();
		}

		return ! empty( $settings[ 'proxy_enabled' ] ) || isset( $_GET[ 'plausible_proxy' ] );
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
		$resources = self::get_proxy_resources();

		/**
		 * Create the cache directory if it doesn't exist.
		 */
		if ( ( $resource_name === 'cache_dir' || $resource_name === 'cache_url' ) && ! is_dir( $resources[ 'cache_dir' ] ) ) {
			wp_mkdir_p( $resources[ $resource_name ] );
		}

		return $resources[ $resource_name ] ?? '';
	}

	/**
	 * Get (and generate/store if non-existent) proxy resources.
	 * @return array
	 * @throws Exception
	 */
	public static function get_proxy_resources() {
		static $resources;

		if ( $resources === null ) {
			$resources = get_option( 'plausible_analytics_proxy_resources', [] );
		}

		/**
		 * Force a refresh of our resources if the user recently switched to SSL and we still have non-SSL resources stored.
		 */
		if ( ! empty( $resources ) && is_ssl() && isset( $resources[ 'cache_url' ] ) && ( strpos( $resources[ 'cache_url' ], 'http:' ) !== false ) ) {
			$resources = [];
		}

		if ( empty( $resources ) ) {
			$cache_dir  = bin2hex( random_bytes( 5 ) );
			$upload_dir = wp_get_upload_dir();
			$resources  = [
				'namespace'  => bin2hex( random_bytes( 3 ) ),
				'base'       => bin2hex( random_bytes( 2 ) ),
				'endpoint'   => bin2hex( random_bytes( 4 ) ),
				'cache_dir'  => trailingslashit( $upload_dir[ 'basedir' ] ) . trailingslashit( $cache_dir ),
				'cache_url'  => trailingslashit( $upload_dir[ 'baseurl' ] ) . trailingslashit( $cache_dir ),
				'file_alias' => bin2hex( random_bytes( 4 ) ),
			];

			update_option( 'plausible_analytics_proxy_resources', $resources );
		}

		return $resources;
	}

	/**
	 * @param $option_name
	 * @param $option_value
	 *
	 * @return void
	 */
	public static function update_setting( $option_name, $option_value ) {
		$settings                 = self::get_settings();
		$settings[ $option_name ] = $option_value;

		update_option( 'plausible_analytics_settings', $settings );
	}

	/**
	 * A convenient way to retrieve the absolute path to the local JS file. Proxy should be enabled when this method is called!
	 * @return string
	 * @throws Exception
	 */
	public static function get_js_path() {
		return self::get_proxy_resource( 'cache_dir' ) . self::get_filename( true ) . '.js';
	}

	/**
	 * Downloads a remote file to this server.
	 * @since 1.3.0
	 *
	 * @param string $local_file  Absolute path to where to store the $remote_file.
	 * @param string $remote_file Full URL to file to download.
	 *
	 * @return bool True when successful. False if it fails.
	 * @throws Exception
	 * @throws InvalidArgument
	 */
	public static function download_file( $remote_file, $local_file ) {
		$file_contents = wp_remote_get( $remote_file );

		if ( is_wp_error( $file_contents ) ) {
			// TODO: add error handling?
			return false;
		}

		/**
		 * Some servers don't do a full overwrite if file already exists, so we delete it first.
		 */
		if ( file_exists( $local_file ) ) {
			unlink( $local_file );
		}

		$write = file_put_contents( $local_file, wp_remote_retrieve_body( $file_contents ) );

		return $write > 0;
	}

	/**
	 * Get entered Domain Name or provide alternative if not entered.
	 * @since  1.0.0
	 * @access public
	 * @return string
	 */
	public static function get_domain() {
		$settings = self::get_settings();

		if ( ! empty( $settings[ 'domain_name' ] ) ) {
			return $settings[ 'domain_name' ];
		}

		$url = home_url();

		return preg_replace( '/^http(s?)\:\/\/(www\.)?/i', '', $url );
	}

	/**
	 * Get Data API URL.
	 * @since  1.2.2
	 * @access public
	 * @return string
	 */
	public static function get_data_api_url() {
		$settings = self::get_settings();
		$url      = 'https://plausible.io/api/event';

		if ( self::proxy_enabled() ) {
			// This'll make sure the API endpoint is properly registered when we're testing.
			$append = isset( $_GET[ 'plausible_proxy' ] ) ? '?plausible_proxy=1' : '';

			return self::get_rest_endpoint() . $append;
		}

		// Triggered when self-hosted analytics is enabled.
		if ( ! empty( $settings[ 'self_hosted_domain' ] ) ) {
			$default_domain = $settings[ 'self_hosted_domain' ];
			$url            = "https://{$default_domain}/api/event";
		}

		return esc_url( $url );
	}

	/**
	 * Returns the Proxy's REST endpoint.
	 * @return string
	 * @throws Exception
	 */
	public static function get_rest_endpoint( $abs_url = true ) {
		$namespace = self::get_proxy_resource( 'namespace' );
		$base      = self::get_proxy_resource( 'base' );
		$endpoint  = self::get_proxy_resource( 'endpoint' );

		$uri = "$namespace/v1/$base/$endpoint";

		if ( $abs_url ) {
			return get_rest_url( null, $uri );
		}

		return '/' . rest_get_url_prefix() . '/' . $uri;
	}

	/**
	 * Get user role for the logged-in user.
	 * @since  1.3.0
	 * @access public
	 * @return string
	 */
	public static function get_user_role() {
		global $current_user;

		$user_roles = $current_user->roles;

		return array_shift( $user_roles );
	}
}
