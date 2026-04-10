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

class PlausibleProxySpeed {
	const MAX_REQUEST_BYTES = 8192;

	/**
	 * Is current request a request to our proxy?
	 *
	 * @var bool
	 */
	private $is_proxy_request = false;

	/**
	 * Current request URI.
	 *
	 * @var string
	 */
	private $request_uri = '';

	/**
	 * Proxy resources loaded from the DB.
	 *
	 * @var array
	 */
	private $resources = [];

	/**
	 * Cached request body.
	 *
	 * @var string|null
	 */
	private $raw_body = null;

	/**
	 * Build properties.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->resources         = $this->get_proxy_resources();
		$this->request_uri      = $this->get_request_uri();
		$this->is_proxy_request = $this->is_proxy_request();

		$this->init();
	}

	/**
	 * Helper method to retrieve Request URI. Checks several globals.
	 *
	 * @return mixed
	 */
	private function get_request_uri() {
		return $_SERVER[ 'REQUEST_URI' ];
	}

	/**
	 * Read proxy resources from the DB once.
	 *
	 * @return array
	 */
	private function get_proxy_resources() {
		$resources = get_option( 'plausible_analytics_proxy_resources', [] );

		return is_array( $resources ) ? $resources : [];
	}

	/**
	 * Check if current request is a proxy request.
	 *
	 * @return bool
	 */
	private function is_proxy_request() {
		$namespace = $this->resources[ 'namespace' ] ?? '';

		if ( ! $namespace ) {
			return false;
		}

		return strpos( $this->get_request_path(), '/wp-json/' . $namespace ) === 0;
	}

	/**
	 * Add filters and actions.
	 *
	 * @return void
	 */
	private function init() {
		$this->maybe_short_circuit_request();
		add_filter( 'option_active_plugins', [ $this, 'filter_active_plugins' ] );
	}

	/**
	 * Reject obvious probes as early as possible.
	 *
	 * @return void
	 */
	private function maybe_short_circuit_request() {
		if ( ! $this->is_proxy_request ) {
			return;
		}

		if ( $this->is_namespace_index_request() || ! $this->is_exact_proxy_endpoint_request() ) {
			$this->send_json_error( 404, 'rest_no_route', 'No route was found matching the URL and request method.' );
		}

		if ( $this->get_request_method() !== 'POST' ) {
			$this->send_json_error( 404, 'rest_no_route', 'No route was found matching the URL and request method.' );
		}

		if ( ! $this->has_json_content_type() ) {
			$this->send_json_error( 400, 'plausible_proxy_invalid_content_type', 'Proxy request must be sent as JSON.' );
		}

		if ( ! $this->has_valid_provenance() ) {
			$this->send_json_error( 404, 'rest_no_route', 'No route was found matching the URL and request method.' );
		}

		if ( $this->request_body_too_large() ) {
			$this->send_json_error( 413, 'plausible_proxy_body_too_large', 'Proxy request body too large.' );
		}

		if ( ! $this->has_valid_payload() ) {
			$this->send_json_error( 400, 'plausible_proxy_invalid_payload', 'Proxy request payload is invalid.' );
		}
	}

	/**
	 * @return string
	 */
	private function get_request_path() {
		return wp_parse_url( $this->request_uri, PHP_URL_PATH ) ?: '';
	}

	/**
	 * @return string
	 */
	private function get_exact_proxy_path() {
		$namespace = $this->resources[ 'namespace' ] ?? '';
		$base      = $this->resources[ 'base' ] ?? '';
		$endpoint  = $this->resources[ 'endpoint' ] ?? '';

		return '/wp-json/' . $namespace . '/v1/' . $base . '/' . $endpoint;
	}

	/**
	 * @return bool
	 */
	private function is_namespace_index_request() {
		return $this->get_request_path() === '/wp-json/' . ( $this->resources[ 'namespace' ] ?? '' ) . '/v1';
	}

	/**
	 * @return bool
	 */
	private function is_exact_proxy_endpoint_request() {
		return $this->get_request_path() === $this->get_exact_proxy_path();
	}

	/**
	 * @return string
	 */
	private function get_request_method() {
		return strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ?? 'GET' );
	}

	/**
	 * @return bool
	 */
	private function has_json_content_type() {
		$content_type = $_SERVER[ 'CONTENT_TYPE' ] ?? $_SERVER[ 'HTTP_CONTENT_TYPE' ] ?? '';

		return strpos( strtolower( $content_type ), 'application/json' ) === 0;
	}

	/**
	 * @return bool
	 */
	private function has_valid_provenance() {
		if ( ! apply_filters( 'plausible_analytics_proxy_require_same_origin', true ) ) {
			return true;
		}

		$origin  = $_SERVER[ 'HTTP_ORIGIN' ] ?? '';
		$referer = $_SERVER[ 'HTTP_REFERER' ] ?? '';

		if ( $origin && $this->url_matches_home_host( $origin ) ) {
			return true;
		}

		if ( $referer && $this->url_matches_home_host( $referer ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @param string $url
	 *
	 * @return bool
	 */
	private function url_matches_home_host( $url ) {
		$home_host = wp_parse_url( home_url(), PHP_URL_HOST );
		$host      = wp_parse_url( $url, PHP_URL_HOST );

		if ( ! $home_host ) {
			return false;
		}

		if ( ! $host && strpos( $url, '/' ) === 0 ) {
			return true;
		}

		if ( ! $host ) {
			return false;
		}

		return $this->normalize_domain( $home_host ) === $this->normalize_domain( $host );
	}

	/**
	 * @param string $domain
	 *
	 * @return string
	 */
	private function normalize_domain( $domain ) {
		$domain = trim( strtolower( $domain ) );
		$domain = preg_replace( '/^https?:\/\//', '', $domain );
		$domain = preg_replace( '/^www\./', '', $domain );

		$parts = explode( '/', $domain );

		return rtrim( $parts[0], '.' );
	}

	/**
	 * @return bool
	 */
	private function request_body_too_large() {
		return strlen( $this->get_request_body() ) > self::MAX_REQUEST_BYTES;
	}

	/**
	 * @return bool
	 */
	private function has_valid_payload() {
		$data = json_decode( $this->get_request_body(), true );

		if ( ! is_array( $data ) ) {
			return false;
		}

		$allowed_keys = [ 'n', 'd', 'u', 'p', 'revenue' ];

		foreach ( array_keys( $data ) as $key ) {
			if ( ! in_array( $key, $allowed_keys, true ) ) {
				return false;
			}
		}

		if ( empty( $data['n'] ) || ! is_string( $data['n'] ) || strlen( $data['n'] ) > 120 ) {
			return false;
		}

		if ( empty( $data['d'] ) || $this->normalize_domain( $data['d'] ) !== $this->normalize_domain( $this->get_expected_domain() ) ) {
			return false;
		}

		if ( empty( $data['u'] ) || ! is_string( $data['u'] ) || ! $this->url_matches_home_host( $data['u'] ) ) {
			return false;
		}

		if ( isset( $data['p'] ) && ! is_array( $data['p'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Read and cache the request body once, capped slightly above the accepted limit.
	 *
	 * @return string
	 */
	private function get_request_body() {
		if ( $this->raw_body === null ) {
			$this->raw_body = (string) file_get_contents( 'php://input', false, null, 0, self::MAX_REQUEST_BYTES + 1 );
		}

		return $this->raw_body;
	}

	/**
	 * @return string
	 */
	private function get_expected_domain() {
		$settings = get_option( 'plausible_analytics_settings', [] );

		if ( is_array( $settings ) && ! empty( $settings['domain_name'] ) ) {
			return $settings['domain_name'];
		}

		return home_url();
	}

	/**
	 * @param int    $status
	 * @param string $code
	 * @param string $message
	 *
	 * @return void
	 */
	private function send_json_error( $status, $code, $message ) {
		status_header( $status );
		header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
		echo wp_json_encode(
			[
				'code'    => $code,
				'message' => $message,
				'data'    => [ 'status' => $status ],
			]
		);
		exit;
	}

	/**
	 * Filter the list of active plugins for custom endpoint requests.
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
