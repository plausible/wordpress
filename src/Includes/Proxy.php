<?php
/**
 * Plausible Analytics | Proxy.
 *
 * @since      1.3.0
 *
 * @package    WordPress
 * @subpackage Plausible Analytics
 *
 * @copyright This code was copied from CAOS Pro, created by:
 *            @author Daan van den Bergh
 *            https://daan.dev/wordpress/caos-pro/
 */

namespace Plausible\Analytics\WP\Includes;

use Exception;

class Proxy {
	/**
	 * Proxy IP Headers used to detect the visitors IP prior to sending the data to Plausible's Measurement Protocol.
	 *
	 * @var array
	 *
	 * For CloudFlare compatibility HTTP_CF_CONNECTING_IP has been added.
	 *
	 * @see https://support.cloudflare.com/hc/en-us/articles/200170986-How-does-Cloudflare-handle-HTTP-Request-headers-
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
	 *
	 * @throws Exception
	 */
	public function __construct() {
		$this->namespace = Helpers::get_proxy_resource( 'namespace' ) . '/v1';
		$this->base      = Helpers::get_proxy_resource( 'base' );
		$this->endpoint  = Helpers::get_proxy_resource( 'endpoint' );

		$this->init();
	}

	/**
	 * Actions
	 *
	 * @return void
	 */
	private function init() {
		// No need to continue if Proxy isn't enabled.
		if ( Helpers::proxy_enabled() ) {
			add_action( 'rest_api_init', [ $this, 'register_route' ] );
		}
	}

	/**
	 * Register the API route.
	 *
	 * @return void
	 */
	public function register_route() {
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/' . $this->endpoint,
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'send' ],
					// There's no reason not to allow access to this API.
					'permission_callback' => '__return_true',
				],
				'schema' => null,
			]
		);
	}

	/**
	 * @return void
	 */
	public function send( $request ) {
		$params = $request->get_body();

		$ip  = $this->get_user_ip_address();
		$url = 'https://plausible.io/api/event';

		$response = wp_remote_post(
			$url,
			[
				'user-agent' => wp_kses( $_SERVER['HTTP_USER_AGENT'], 'strip' ),
				'headers'    => [
					'X-Forwarded-For' => $ip,
					'Content-Type'    => 'application/json',
				],
				'body'       => wp_kses_no_null( $params ),
			]
		);

		return $response;
	}

	/**
	 * @return string
	 */
	private function get_user_ip_address() {
		$ip = '';

		foreach ( self::PROXY_IP_HEADERS as $header ) {
			if ( $this->header_exists( $header ) ) {
				$ip = wp_kses( $_SERVER[ $header ], 'strip' );

				if ( strpos( $ip, ',' ) !== false ) {
					$ip = explode( ',', $ip );

					return $ip[0];
				}

				return $ip;
			}
		}
	}

	/**
	 * Checks if a HTTP header is set and is not empty.
	 *
	 * @param mixed $global
	 * @return bool
	 */
	private function header_exists( $global ) {
		return isset( $_SERVER[ $global ] ) && ! empty( $_SERVER[ $global ] );
	}
}
