<?php
/**
 * @package Plausible Analytics Unit Tests - ClientFactory
 */

namespace Plausible\Analytics\Tests\Integration;

use Plausible\Analytics\Tests\TestCase;
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
}
