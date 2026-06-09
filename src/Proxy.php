<?php
/**
 * Plausible Analytics | Proxy.
 *
 * @since      1.3.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 * @copyright  This code was copied from CAOS Pro, created by:
 * @author     Daan van den Bergh
 *            https://daan.dev/wordpress/caos-pro/
 */

namespace Plausible\Analytics\WP;

use Exception;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Server;

class Proxy {
	const MAX_REQUEST_BYTES = 8192;

	/**
	 * Proxy IP Headers used to detect the visitors IP prior to sending the data to Plausible's Measurement Protocol.
	 *
	 * @see https://support.cloudflare.com/hc/en-us/articles/200170986-How-does-Cloudflare-handle-HTTP-Request-headers-
	 * @var array
	 * For CloudFlare compatibility HTTP_CF_CONNECTING_IP has been added.
	 */
	const PROXY_IP_HEADERS = [
		'HTTP_CF_CONNECTING_IP',
		'HTTP_X_FORWARDED_FOR',
		'REMOTE_ADDR',
		'HTTP_CLIENT_IP',
	];

	/**
	 * API namespace
	 *
	 * @var string
	 */
	private $namespace = '';

	/**
	 * API base
	 *
	 * @var string
	 */
	private $base = '';

	/**
	 * Endpoint
	 *
	 * @var string
	 */
	private $endpoint = '';

	/**
	 * Build properties.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function __construct( $init = true ) {
		$this->namespace = Helpers::get_proxy_resource( 'namespace' ) . '/v1';
		$this->base      = Helpers::get_proxy_resource( 'base' );
		$this->endpoint  = Helpers::get_proxy_resource( 'endpoint' );

		$this->init( $init );
	}

	/**
	 * Actions
	 *
	 * @return void
	 */
	private function init( $init ) {
		if ( ! $init ) {
			return;
		}

		$settings = [];

		if ( array_key_exists( 'option_name', $_POST ) && $_POST[ 'option_name' ] == 'proxy_enabled' && array_key_exists( 'option_value', $_POST ) && $_POST[ 'option_value' ] == 'on' ) {
			$settings[ 'proxy_enabled' ] = 'on'; // @codeCoverageIgnore
		}

		// No need to continue if Proxy isn't enabled .
		if ( Helpers::proxy_enabled( $settings ) ) {
			add_action( 'rest_api_init', [ $this, 'register_route' ] );
		}

		add_filter( 'rest_post_dispatch', [ $this, 'force_http_response_code' ], null, 3 );
		add_filter( 'rest_pre_dispatch', [ $this, 'maybe_block_namespace_index' ], 10, 3 );
		add_filter( 'rest_route_data', [ $this, 'hide_route_discovery' ], 10, 2 );
	}

	/**
	 * A public wrapper to programmatically send hits to the Plausible API.
	 *
	 * @see https://plausible.io/docs/events-api
	 *
	 * @param string $name   Name of the event, defaults to 'pageview', all other names are treated as custom events by the API.
	 * @param string $domain Domain of the site in Plausible where the event should be registered.
	 * @param string $url    URL of the page where the event was triggered.
	 * @param array  $props  Custom properties for the event.
	 *
	 * @return array|WP_Error
	 */
	public function do_request( $name = 'pageview', $domain = '', $url = '', $props = [] ) {
		$request = new \WP_REST_Request( 'POST', "/$this->namespace/v1/$this->base/$this->endpoint" );
		$body    = [
			'n' => $name,
			'd' => $domain ?: Helpers::get_domain(),
			'u' => $url ?: wp_get_referer(),
		];

		// URL is required, so if no $url was set and no referer was found, attempt to create it from the REQUEST_URI server variable.
		if ( empty( $body[ 'u' ] ) ) {
			$body[ 'u' ] = $this->generate_event_url(); // @codeCoverageIgnore
		}

		// Revenue events use a different approach.
		if ( isset( $props[ 'revenue' ] ) ) {
			$body[ 'revenue' ] = reset( $props ); // @codeCoverageIgnore
		} elseif ( ! empty( $props ) ) {
			$body[ 'p' ] = $props; // @codeCoverageIgnore
		}

		$request->set_body( wp_json_encode( $body ) );

		return $this->send_event( $request );
	}

	/**
	 * Attempts to generate the Event URL from available resources.
	 *
	 * @return string
	 */
	public function generate_event_url() {
		$url            = '';
		$parts          = parse_url( $_SERVER[ 'REQUEST_URI' ] );
		$home_url_parts = parse_url( get_home_url() );

		if ( isset( $home_url_parts[ 'scheme' ] ) && isset( $home_url_parts[ 'host' ] ) && isset( $parts[ 'path' ] ) ) {
			$url = $home_url_parts[ 'scheme' ] . '://' . $home_url_parts [ 'host' ] . $parts[ 'path' ];
		}

		return $url;
	}

	/**
	 * Formats and sends $request to the Plausible API.
	 *
	 * @return array|WP_Error
	 */
	public function send_event( $request ) {
		$params = $request->get_body();

		$ip  = $this->get_user_ip_address();
		$url = 'https://plausible.io/api/event';
		$ua  = ! empty ( $_SERVER[ 'HTTP_USER_AGENT' ] ) ? wp_kses( $_SERVER[ 'HTTP_USER_AGENT' ], 'strip' ) : '';

		return wp_remote_post(
			$url,
			[
				'user-agent' => $ua,
				'headers'    => [
					'X-Forwarded-For' => $ip,
					'Content-Type'    => 'application/json',
				],
				'body'       => wp_kses_no_null( $params ),
			]
		);
	}

	/**
	 * @return string
	 *
	 * @codeCoverageIgnore
	 */
	private function get_user_ip_address() {
		$ip = '';

		foreach ( self::PROXY_IP_HEADERS as $header ) {
			if ( $this->header_exists( $header ) ) {
				$ip = wp_kses( $_SERVER[ $header ], 'strip' );

				if ( strpos( $ip, ',' ) !== false ) {
					$ip = explode( ',', $ip );

					return $ip[ 0 ];
				}

				return $ip;
			}
		}

		return $ip;
	}

	/**
	 * Checks if a HTTP header is set and is not empty.
	 *
	 * @param mixed $global
	 *
	 * @return bool
	 */
	private function header_exists( $global ) {
		return ! empty( $_SERVER[ $global ] );
	}

	/**
	 * Register the API route.
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore Because we have no way of knowing if the API works in integration tests.
	 */
	public function register_route() {
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/' . $this->endpoint,
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'send_event' ],
					'permission_callback' => [ $this, 'validate_proxy_request' ],
				],
				'schema' => null,
			]
		);
	}

	/**
	 * Reject namespace index probing so the randomized route is not self-discoverable.
	 *
	 * @param mixed           $result
	 * @param WP_REST_Server  $server
	 * @param WP_REST_Request $request
	 *
	 * @return mixed
	 */
	public function maybe_block_namespace_index( $result, $server, $request ) {
		if ( ! Helpers::proxy_enabled() || $request->get_route() !== '/' . $this->namespace ) {
			return $result;
		}

		return new WP_Error(
			'rest_no_route',
			__( 'No route was found matching the URL and request method.', 'plausible-analytics' ),
			[ 'status' => 404 ]
		);
	}

	/**
	 * Remove the proxy routes from REST discovery output.
	 *
	 * @param array $available
	 * @param array $routes
	 *
	 * @return array
	 */
	public function hide_route_discovery( $available, $routes ) {
		if ( ! Helpers::proxy_enabled() ) {
			return $available;
		}

		unset( $available[ '/' . $this->namespace ] );
		unset( $available[ '/' . $this->namespace . '/' . $this->base . '/' . $this->endpoint ] );

		return $available;
	}

	/**
	 * Validate the proxy request before we forward it to Plausible.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return true|WP_Error
	 */
	public function validate_proxy_request( $request ) {
		$max_request_bytes = (int) apply_filters( 'plausible_analytics_proxy_max_body_bytes', self::MAX_REQUEST_BYTES );
		$raw_body          = (string) $request->get_body();

		if ( $max_request_bytes > 0 && strlen( $raw_body ) > $max_request_bytes ) {
			return $this->rest_no_route();
		}

		if ( ! $this->has_json_content_type() ) {
			return $this->rest_no_route();
		}

		$params = $request->get_json_params();

		if ( ! is_array( $params ) ) {
			return $this->rest_no_route();
		}

		if ( ! $this->has_valid_provenance() ) {
			return $this->rest_no_route();
		}

		if ( ! $this->has_valid_payload( $params ) ) {
			return $this->rest_no_route();
		}

		return true;
	}

	/**
	 * Uniform rejection so probes can't tell which check failed.
	 *
	 * @return WP_Error
	 */
	private function rest_no_route() {
		return new WP_Error(
			'rest_no_route',
			__( 'No route was found matching the URL and request method.', 'plausible-analytics' ),
			[ 'status' => 404 ]
		);
	}

	/**
	 * Check the request's Content-Type header.
	 *
	 * @return bool
	 */
	private function has_json_content_type() {
		$content_type = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';

		if ( ! $content_type ) {
			return false;
		}

		return strpos( strtolower( $content_type ), 'application/json' ) === 0;
	}

	/**
	 * Require same-site Origin or Referer headers so blind scanners are rejected.
	 *
	 * @return bool
	 */
	private function has_valid_provenance() {
		$require_provenance = apply_filters( 'plausible_analytics_proxy_require_same_origin', true );

		if ( ! $require_provenance ) {
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

		return $this->normalize_domain( $host ) === $this->normalize_domain( $home_host );
	}

	/**
	 * Validate the JSON payload sent by the tracker.
	 *
	 * @param array $params
	 *
	 * @return bool
	 */
	private function has_valid_payload( $params ) {
		$allowed_keys = [ 'n', 'd', 'u', 'p', 'revenue' ];
		$event_name   = $params['n'] ?? '';
		$domain       = $params['d'] ?? '';
		$url          = $params['u'] ?? '';

		foreach ( array_keys( $params ) as $key ) {
			if ( ! in_array( $key, $allowed_keys, true ) ) {
				return false;
			}
		}

		if ( ! is_string( $event_name ) || $event_name === '' || strlen( $event_name ) > 120 ) {
			return false;
		}

		if ( ! is_string( $domain ) || $this->normalize_domain( $domain ) !== $this->normalize_domain( Helpers::get_domain() ) ) {
			return false;
		}

		if ( ! is_string( $url ) || strlen( $url ) > 2048 || ! $this->url_matches_home_host( $url ) ) {
			return false;
		}

		if ( isset( $params['p'] ) && ! is_array( $params['p'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Compare a URL-like value to the current site's host.
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	private function url_matches_home_host( $url ) {
		$home_host = wp_parse_url( home_url(), PHP_URL_HOST );

		if ( ! $home_host ) {
			return false;
		}

		$host = wp_parse_url( $url, PHP_URL_HOST );

		if ( ! $host && strpos( $url, '/' ) === 0 ) {
			return true;
		}

		if ( ! $host ) {
			return false;
		}

		return $this->normalize_domain( $host ) === $this->normalize_domain( $home_host );
	}

	/**
	 * Normalize a host/domain string for comparison.
	 *
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
	 * Make sure our response code is returned, instead of the default 200 on success.
	 *
	 * @param WP_HTTP_Response $response
	 * @param WP_REST_Server   $server
	 * @param WP_REST_Request  $request
	 *
	 * @return WP_HTTP_Response
	 *
	 * @codeCoverageIgnore
	 */
	public function force_http_response_code( $response, $server, $request ) {
		if ( strpos( $request->get_route(), $this->namespace ) === false ) {
			return $response; // @codeCoverageIgnore
		}

		$data = $response->get_data();

		if ( ! is_array( $data ) || empty( $data['response']['code'] ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $data );
		$response->set_status( $response_code );

		return $response;
	}
}
