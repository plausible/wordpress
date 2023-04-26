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
 *
 * @package Plausible Analytics
 * @category Safe Mode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class ProxySpeed {
	/**
	 * Is current request a request to our proxy?
	 *
	 * @var bool
	 */
	private $is_proxy_request = false;

	/**
	 * Build properties.
	 *
	 * @return void
	 */
	public function __construct() {
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

	private function is_proxy_request() {
		return true;
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

new ProxySpeed();
