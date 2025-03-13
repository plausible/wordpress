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
}
