<?php
/**
 * Plugin Name: Plausible Analytics - Proxy Speed Module
 * Description: Speeds up Plausible Analytics' proxy for avoiding ad blockers.
 * Plugin URI: https://plausible.io
 * Author: Plausible HQ
 * Version: 1.0.0
 * Author URI: https://plausible.io
 *
 * Text Domain: plausible-analytics
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class PlausibleProxySpeed {

	/**
	 * Is current request a request to our proxy?
	 *
	 * @var bool
	 */
	private $is_proxy_request = false;

	/**
	 * Currenct request URI.
	 *
	 * @var string
	 */
	private $request_uri = '';

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
	 * Add filters and actions.
	 *
	 * @return void
	 */
	private function init() {
		add_filter( 'option_active_plugins', [ $this, 'filter_active_plugins' ] );
	}

	/**
	 * Helper method to retrieve Request URI. Checks several globals.
	 *
	 * @return mixed
	 */
	private function get_request_uri() {
		return $_SERVER['REQUEST_URI'];
	}

	/**
	 * Check if current request is a proxy request.
	 *
	 * @return bool
	 */
	private function is_proxy_request() {
		$namespace = get_option( 'plausible_analytics_proxy_resources' )['namespace'] ?? '';

		if ( ! $namespace ) {
			return false;
		}

		return strpos( $this->request_uri, $namespace ) !== false;
	}

	/**
	 * Filter the list of active plugins for custom endpoint requests.
	 *
	 * @param array $active_plugins The list of active plugins.
	 *
	 * @return array The filtered list of active plugins.
	 */
	function filter_active_plugins( $active_plugins ) {
		if ( ! $this->is_proxy_request || ! is_array( $active_plugins ) ) {
			return $active_plugins;
		}

		$allowed_plugin_files = [ 'plausible-analytics.php' ];
		$filtered_plugins     = [];

		foreach ( $active_plugins as $plugin ) {
			foreach ( $allowed_plugin_files as $allowed_plugin_file ) {
				if ( strpos( $plugin, $allowed_plugin_file ) !== false ) {
					$filtered_plugins[] = $plugin;
					break;
				}
			}
		}

		return $filtered_plugins;
	}
}

new PlausibleProxySpeed();
