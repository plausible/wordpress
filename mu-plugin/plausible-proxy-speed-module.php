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
	const MAX_REQUEST_BYTES = 8192;

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
		$this->resources        = $this->get_proxy_resources();
		$this->request_uri      = $this->get_request_uri();
		$this->is_proxy_request = $this->is_proxy_request();

		$this->init();
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
	 * (e.g., /wp-json/<namespace>[/...]). Substring matches in query
	 * strings, fragments, or unrelated path segments are rejected.
	 *
	 * @return bool
	 */
	private function is_proxy_request() {
		$namespace = $this->resources['namespace'] ?? '';

		if ( ! $namespace ) {
			return false;
		}

		return str_starts_with( $this->get_request_path(), '/wp-json/' . $namespace );
	}

	/**
	 * @return string
	 */
	private function get_request_path() {
		return wp_parse_url( $this->request_uri, PHP_URL_PATH ) ?: '';
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
			$this->send_rest_no_route();
		}

		if ( $this->get_request_method() !== 'POST' ) {
			$this->send_rest_no_route();
		}

		if ( ! $this->has_json_content_type() ) {
			$this->send_rest_no_route();
		}

		if ( ! $this->has_valid_provenance() ) {
			$this->send_rest_no_route();
		}

		if ( $this->request_body_too_large() ) {
			$this->send_rest_no_route();
		}

		if ( ! $this->has_valid_payload() ) {
			$this->send_rest_no_route();
		}
	}

	/**
	 * @return bool
	 */
	private function is_namespace_index_request() {
		return $this->get_request_path() === '/wp-json/' . ( $this->resources['namespace'] ?? '' ) . '/v1';
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
	private function get_exact_proxy_path() {
		$namespace = $this->resources['namespace'] ?? '';
		$base      = $this->resources['base'] ?? '';
		$endpoint  = $this->resources['endpoint'] ?? '';

		return '/wp-json/' . $namespace . '/v1/' . $base . '/' . $endpoint;
	}

	/**
	 * Uniform rejection so probes can't tell which check failed.
	 *
	 * @return void
	 */
	private function send_rest_no_route() {
		$this->send_json_error( 404, 'rest_no_route', 'No route was found matching the URL and request method.' );
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
	 * @return string
	 */
	private function get_request_method() {
		return strtoupper( $_SERVER['REQUEST_METHOD'] ?? 'GET' );
	}

	/**
	 * @return bool
	 */
	private function has_json_content_type() {
		$content_type = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';

		return str_starts_with( strtolower( $content_type ), 'application/json' );
	}

	/**
	 * @return bool
	 */
	private function has_valid_provenance() {
		if ( ! apply_filters( 'plausible_analytics_proxy_require_same_origin', true ) ) {
			return true;
		}

		$origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
		$referer = $_SERVER['HTTP_REFERER'] ?? '';

		if ( $origin && $this->host_matches_home( $origin ) ) {
			return true;
		}

		if ( $referer && $this->host_matches_home( $referer ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Strict same-host check for HTTP headers (Origin/Referer).
	 *
	 * Rejects relative paths — headers must carry a full origin.
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	private function host_matches_home( $url ) {
		$home_host = wp_parse_url( home_url(), PHP_URL_HOST );

		if ( ! $home_host ) {
			return false;
		}

		$host = wp_parse_url( $url, PHP_URL_HOST );

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

		if ( ! isset( $data['n'] ) || ! is_string( $data['n'] ) || $data['n'] === '' || strlen( $data['n'] ) > 120 ) {
			return false;
		}

		if ( ! isset( $data['d'] ) || ! is_string( $data['d'] ) || $this->normalize_domain( $data['d'] ) !== $this->normalize_domain( $this->get_expected_domain() ) ) {
			return false;
		}

		if ( ! isset( $data['u'] ) || ! is_string( $data['u'] ) || $data['u'] === '' || strlen( $data['u'] ) > 2048 || ! $this->url_matches_home_host( $data['u'] ) ) {
			return false;
		}

		if ( isset( $data['p'] ) && ! is_array( $data['p'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @return string
	 */
	private function get_expected_domain() {
		$settings = get_option( 'plausible_analytics_settings', [] );

		if ( is_array( $settings ) && ! empty( $settings['domain_name'] ) ) {
			return $settings['domain_name'];
		}

		return preg_replace( '/^http(s?):\/\/(www\.)?/i', '', home_url() );
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

		if ( ! $host && str_starts_with( $url, '/' ) ) {
			return true;
		}

		if ( ! $host ) {
			return false;
		}

		return $this->normalize_domain( $home_host ) === $this->normalize_domain( $host );
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
