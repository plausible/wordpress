<?php
/**
 * @package Plausible Analytics Unit Tests - ClientFactory
 */

namespace Plausible\Analytics\Tests\Integration;

use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\Helpers;
use Plausible\Analytics\WP\Proxy;

class ProxyTest extends TestCase {
	/**
	 * @see Proxy::generate_event_url()
	 * @return void
	 */
	public function testGenerateEventUrl() {
		$proxy = new Proxy( false );

		$url = $proxy->generate_event_url();

		$this->assertEquals( 'http://example.org', $url );

		$_SERVER[ 'REQUEST_URI' ] = '/test';

		$url = $proxy->generate_event_url();

		$this->assertEquals( 'http://example.org/test', $url );
	}

	/**
	 * @see Proxy::get_forwarded_headers()
	 * @return void
	 */
	public function testGetForwardedHeaders() {
		$proxy = new Proxy( false );

		$_SERVER['HTTP_ACCEPT_LANGUAGE']             = 'nl-NL,nl;q=0.9,en;q=0.8';
		$_SERVER['HTTP_SEC_CH_UA']                   = '"Chromium";v="128", "Not;A=Brand";v="24"';
		$_SERVER['HTTP_SEC_CH_UA_MOBILE']            = '?0';
		$_SERVER['HTTP_SEC_CH_UA_PLATFORM']          = '"Windows"';
		$_SERVER['HTTP_SEC_CH_UA_FULL_VERSION_LIST'] = '';
		$_SERVER['HTTP_SEC_FETCH_SITE']              = 'same-origin';

		try {
			$this->assertEquals(
				[
					'Accept-Language'    => 'nl-NL,nl;q=0.9,en;q=0.8',
					'Sec-CH-UA'          => '"Chromium";v="128", "Not;A=Brand";v="24"',
					'Sec-CH-UA-Mobile'   => '?0',
					'Sec-CH-UA-Platform' => '"Windows"',
				],
				$proxy->get_forwarded_headers()
			);
		} finally {
			unset(
				$_SERVER['HTTP_ACCEPT_LANGUAGE'],
				$_SERVER['HTTP_SEC_CH_UA'],
				$_SERVER['HTTP_SEC_CH_UA_MOBILE'],
				$_SERVER['HTTP_SEC_CH_UA_PLATFORM'],
				$_SERVER['HTTP_SEC_CH_UA_FULL_VERSION_LIST'],
				$_SERVER['HTTP_SEC_FETCH_SITE']
			);
		}
	}

	/**
	 * @see Proxy::send_event()
	 * @return void
	 */
	public function testSendEventForwardsHeaders() {
		$proxy = new Proxy( false );
		$sent  = [];

		$_SERVER['HTTP_ACCEPT_LANGUAGE']  = 'fr-FR';
		$_SERVER['HTTP_SEC_CH_UA_MOBILE'] = '?1';

		$capture = function ( $preempt, $args ) use ( &$sent ) {
			$sent = $args['headers'];

			return [ 'headers' => [], 'body' => '', 'response' => [ 'code' => 202, 'message' => 'Accepted' ], 'cookies' => [] ];
		};

		add_filter( 'pre_http_request', $capture, 10, 2 );

		try {
			$proxy->send_event( new \WP_REST_Request( 'POST' ) );

			$this->assertEquals( 'fr-FR', $sent['Accept-Language'] );
			$this->assertEquals( '?1', $sent['Sec-CH-UA-Mobile'] );
			$this->assertEquals( 'application/json', $sent['Content-Type'] );
			$this->assertArrayHasKey( 'X-Forwarded-For', $sent );
		} finally {
			remove_filter( 'pre_http_request', $capture );
			unset( $_SERVER['HTTP_ACCEPT_LANGUAGE'], $_SERVER['HTTP_SEC_CH_UA_MOBILE'] );
		}
	}

	/**
	 * @see Proxy::validate_request()
	 * @return void
	 */
	public function testValidateRequest() {
		add_filter( 'plausible_analytics_settings', [ $this, 'setLanguageDomains' ] );

		$_SERVER['HTTP_HOST'] = 'alias.test';

		$event = [ 'n' => 'pageview', 'd' => 'test.dev', 'u' => 'http://example.org/page', 'v' => 35, 'p' => [ 'a' => 'b' ] ];

		try {
			$this->assertTrue( $this->isValid( $event ) );
			$this->assertTrue( $this->isValid( [ 'd' => 'es.test.dev', 'u' => 'https://www.es.test.dev/' ] + $event, [ 'origin' => 'https://es.test.dev' ] ) );
			$this->assertTrue( $this->isValid( [ 'u' => 'https://alias.test/' ] + $event, [ 'origin' => 'https://alias.test:8443' ] ) );
			$this->assertTrue( $this->isValid( $event, [ 'referer' => 'http://example.org/page' ] ) );
			$this->assertTrue( $this->isValid( $event, [ 'origin' => 'null', 'referer' => 'http://example.org/page' ] ) );
			$this->assertTrue( $this->isValid( [ 'p' => array_fill( 0, 30, str_repeat( 'a', 2000 ) ) ] + $event ) );

			// Invalid events.
			$this->assertFalse( $this->isValid( [ 'd' => 'evil.test' ] + $event ) );
			$this->assertFalse( $this->isValid( [ 'd' => [ 'test.dev' ] ] + $event ) );
			$this->assertFalse( $this->isValid( [ 'n' => '' ] + $event ) );
			$this->assertFalse( $this->isValid( [ 'u' => 'http://evil.test/' ] + $event ) );
			$this->assertFalse( $this->isValid( [ 'u' => 'http://example.org.evil.test/' ] + $event ) );
			$this->assertFalse( $this->isValid( [ 'u' => '/page' ] + $event ) );
			$this->assertFalse( $this->isValid( [ 'p' => [ 'a' => str_repeat( 'a', Proxy::MAX_BODY_BYTES ) ] ] + $event ) );
			$this->assertFalse( $this->isValid( 'not-json' ) );

			// Invalid sources.
			$this->assertFalse( $this->isValid( $event, [] ) );
			$this->assertFalse( $this->isValid( $event, [ 'origin' => 'http://evil.test' ] ) );

			add_filter( 'plausible_analytics_proxy_require_same_site', '__return_false' );

			$this->assertTrue( $this->isValid( $event, [] ) );
		} finally {
			remove_filter( 'plausible_analytics_proxy_require_same_site', '__return_false' );
			remove_filter( 'plausible_analytics_settings', [ $this, 'setLanguageDomains' ] );
			unset( $_SERVER['HTTP_HOST'] );
		}
	}

	/**
	 * Dispatches requests through the REST server to verify that the endpoint is hidden, and which status it returns.
	 *
	 * @see Proxy::hide_from_index()
	 * @see Proxy::hide_namespace_index()
	 * @see Proxy::force_http_response_code()
	 * @return void
	 */
	public function testEndpointIsHidden() {
		add_filter( 'plausible_analytics_settings', [ $this, 'enableProxy' ] );
		add_filter( 'plausible_analytics_settings', [ $this, 'setDomain' ] );

		$accept = function () {
			return [ 'headers' => [], 'body' => 'ok', 'response' => [ 'code' => 202, 'message' => 'Accepted' ], 'cookies' => [] ];
		};

		add_filter( 'pre_http_request', $accept );

		global $wp_rest_server;

		$original_server = $wp_rest_server;
		$wp_rest_server  = null;
		$route           = substr( Helpers::get_rest_endpoint( false ), strlen( '/' . rest_get_url_prefix() ) );
		$namespace       = Helpers::get_proxy_resource( 'namespace' ) . '/v1';

		new Proxy();

		try {
			$server = rest_get_server();

			$this->assertArrayHasKey( $route, $server->get_routes() );

			$index = $server->dispatch( new \WP_REST_Request( 'GET', '/' ) )->get_data();

			$this->assertNotEmpty( $index['routes'] );
			$this->assertStringNotContainsString( Helpers::get_proxy_resource( 'namespace' ), (string) wp_json_encode( [ $index['namespaces'], array_keys( $index['routes'] ) ] ) );
			$this->assertTrue( array_values( $index['namespaces'] ) === $index['namespaces'] );
			$this->assertEquals( 404, $server->dispatch( new \WP_REST_Request( 'GET', "/$namespace" ) )->get_status() );

			// Rejected requests keep their status.
			$request  = new \WP_REST_Request( 'POST', $route );
			$response = apply_filters( 'rest_post_dispatch', $server->dispatch( $request ), $server, $request );

			$this->assertEquals( 404, $response->get_status() );

			// Events sent by the tracker are forwarded.
			$request->set_header( 'content-type', 'text/plain' );
			$request->set_header( 'origin', 'http://example.org' );
			$request->set_body( wp_json_encode( [ 'n' => 'pageview', 'd' => 'test.dev', 'u' => 'http://example.org/' ] ) );
			$response = apply_filters( 'rest_post_dispatch', $server->dispatch( $request ), $server, $request );

			$this->assertEquals( 202, $response->get_status() );
		} finally {
			$wp_rest_server = $original_server;

			$this->removeAction( 'rest_api_init', 'register_route' );
			$this->removeAction( 'rest_index', 'hide_from_index' );
			$this->removeAction( 'rest_pre_dispatch', 'hide_namespace_index' );
			remove_filter( 'pre_http_request', $accept );
			remove_filter( 'plausible_analytics_settings', [ $this, 'enableProxy' ] );
			remove_filter( 'plausible_analytics_settings', [ $this, 'setDomain' ] );
		}
	}

	/**
	 * @param array $settings
	 *
	 * @return array
	 */
	public function setLanguageDomains( $settings ) {
		$settings['domain_name'] = [ 'default' => 'test.dev', 'es' => 'es.test.dev' ];

		return $settings;
	}

	/**
	 * @param array|string $event
	 * @param array        $headers
	 *
	 * @return bool
	 */
	private function isValid( $event, $headers = [ 'origin' => 'http://example.org' ] ) {
		$proxy   = new Proxy( false );
		$request = new \WP_REST_Request( 'POST' );

		$request->set_body( is_array( $event ) ? wp_json_encode( $event ) : $event );
		$request->set_headers( [ 'content-type' => 'text/plain' ] + $headers );

		return $proxy->validate_request( $request ) === true;
	}
}
