<?php
/**
 * Plugin Name: Plausible Analytics - Proxy Speed Module
 * Description: Speeds up Plausible Analytics' proxy for avoiding ad blockers.
 * Plugin URI: https://plausible.io
 * Author: Plausible HQ
 * Version: 1.0.2
 * Author URI: https://plausible.io
 *
 * Text Domain: plausible-analytics
 */

class PlausibleProxySpeed {
	/**
	 * Is the current request a request to our proxy?
	 *
	 * @var bool
	 */
	private $is_proxy_request;

	/**
	 * Current request URI.
	 *
	 * @var string
	 */
	private $request_uri;

	/**
	 * Build properties.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->request_uri      = $this->get_request_uri();
		$this->is_proxy_request = $this->is_proxy_request();

		$this->init();
	}

	/**
	 * Helper method to retrieve Request URI.
	 *
	 * @return string
	 */
	private function get_request_uri() {
		return $_SERVER['REQUEST_URI'] ?? '';
	}

	/**
	 * Check if the current request is a proxy request.
	 *
	 * The namespace must appear as a path segment under the REST prefix
	 * (e.g. /wp-json/<namespace>[/...]). Substring matches in query
	 * strings, fragments, or unrelated path segments are rejected.
	 *
	 * @return bool
	 */
	private function is_proxy_request() {
		$namespace = get_option( 'plausible_analytics_proxy_resources' )['namespace'] ?? '';

		if ( ! $namespace ) {
			return false;
		}

		$path = parse_url( $this->request_uri, PHP_URL_PATH );

		if ( ! is_string( $path ) || $path === '' ) {
			return false;
		}

		/**
		 * @see   rest_url() requires $wp_rewrite to be set. If it's not set yet, just assume this isn't a proxy request.
		 *
		 * @since v1.0.2
		 */
		global $wp_rewrite;

		if ( $wp_rewrite === null ) {
			return false;
		}

		$expected = function_exists( 'rest_url' )
			? untrailingslashit( (string) wp_parse_url( rest_url( trim( $namespace, '/' ) ), PHP_URL_PATH ) )
			: '/wp-json/' . trim( $namespace, '/' );

		return $path === $expected
		       || str_starts_with( $path, $expected . '/' );
	}

	/**
	 * Add filters and actions.
	 *
	 * @return void
	 */
	private function init() {
		add_filter( 'option_active_plugins', [ $this, 'filter_active_plugins' ] );
	}

	/**
	 * Filter the list of active plugins for custom endpoint requests.
	 *
	 * Uses basename() exact-match comparison instead of strpos(), so a
	 * plugin file path can only match if its filename is exactly in the
	 * allowlist.
	 *
	 * @param array $active_plugins The list of active plugins.
	 *
	 * @return array The filtered list of active plugins.
	 */
	public function filter_active_plugins( $active_plugins ) {
		if ( ! $this->is_proxy_request || ! is_array( $active_plugins ) ) {
			return $active_plugins;
		}

		$allowed_plugin_files = [ 'plausible-analytics.php' ];
		$filtered_plugins     = [];

		foreach ( $active_plugins as $plugin ) {
			if ( in_array( basename( $plugin ), $allowed_plugin_files, true ) ) {
				$filtered_plugins[] = $plugin;
			}
		}

		return $filtered_plugins;
	}
}

new PlausibleProxySpeed();
