<?php
/**
 * @package Plausible Analytics Unit Tests - ClientFactory
 */

namespace Plausible\Analytics\Tests\Integration;

use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\Client;
use Plausible\Analytics\WP\ClientFactory;

class ClientFactoryTest extends TestCase {
	/**
	 * @see ClientFactory::build()
	 */
	public function testBuild() {
		delete_option( 'plausible_analytics_settings' );

		$clientFactory = new ClientFactory();
		$client        = $clientFactory->build();

		$this->assertFalse( $client );

		$clientFactory = new ClientFactory( 'test' );
		$client        = $clientFactory->build();

		$this->assertInstanceOf( Client::class, $client );
	}
}
