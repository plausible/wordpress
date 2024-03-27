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

defined( 'ABSPATH' ) || exit;

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
		$settings = [];

		if ( array_key_exists( 'option_name', $_POST ) &&
			$_POST[ 'option_name' ] == 'proxy_enabled' &&
			array_key_exists( 'option_value', $_POST ) &&
			$_POST[ 'option_value' ] == 'on' ) {
			$settings[ 'proxy_enabled' ] = 'on';
		}

		// No need to continue if Proxy isn't enabled .
		if ( Helpers::proxy_enabled( $settings ) ) {
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
	 * @return array|\WP_Error
	 */
	public function send( $request ) {
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
}
