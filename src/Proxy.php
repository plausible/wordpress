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
					// There's no reason not to allow access to this API.
					'permission_callback' => '__return_true',
				],
				'schema' => null,
			]
		);
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

		$response_code = wp_remote_retrieve_response_code( $response->get_data() );
		$response->set_status( $response_code );

		return $response;
	}
}
