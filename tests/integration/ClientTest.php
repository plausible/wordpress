<?php

namespace Plausible\Analytics\Tests\Integration;

use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\Client;
use Plausible\Analytics\WP\Helpers;
use Plausible\Analytics\WP\Client\Model\CapabilitiesFeatures;

class ClientTest extends TestCase {
	/**
	 * Test legacy/non-array defensive check in is_api_token_valid
	 */
	public function test_is_api_token_valid_defensive_check() {
		$token  = 'plausible-plugin-token';
		$client = new Client( $token );

		// Test with string (invalid format)
		set_transient( 'plausible_analytics_valid_token', 'not-an-array', 86400 );
		$this->assertFalse( $client->is_api_token_valid() );

		// Test with null
		delete_transient( 'plausible_analytics_valid_token' );
		$this->assertFalse( $client->is_api_token_valid() );
	}

	/**
	 * @see Client::validate_api_token()
	 * @see Client::is_api_token_valid()
	 */
	public function test_validate_api_token_caching() {
		// Mock Client to avoid real API calls
		$token1 = 'plausible-plugin-token1';
		$token2 = 'plausible-plugin-token2';
		$domain = 'example.com';

		// We need to mock get_features and get_data_domain which are used in validate_api_token
		// But they are private/protected or use api_instance.
		// Since we want to test the caching logic in validate_api_token, let's see if we can mock the api_instance.

		// However, it might be easier to just test is_api_token_valid and the transient logic directly
		// if we can't easily mock the API response here.

		// Let's try to mock Client and only the parts that hit the API.
		$client1 = $this->getMockBuilder( Client::class )
		                ->setConstructorArgs( [ $token1 ] )
		                ->onlyMethods( [ 'get_features', 'get_data_domain' ] )
		                ->getMock();

		$features = new CapabilitiesFeatures();
		$features->setGoals( [ 'goal1' ] );

		$client1->method( 'get_features' )->willReturn( $features );
		$client1->method( 'get_data_domain' )->willReturn( Helpers::get_domain() );

		// Clear transient
		delete_transient( 'plausible_analytics_valid_token' );

		// Validate first token
		$this->assertTrue( $client1->validate_api_token() );
		$this->assertTrue( $client1->is_api_token_valid() );

		$cached = get_transient( 'plausible_analytics_valid_token' );
		$this->assertArrayHasKey( $token1, $cached );

		// Validate second token
		$client2 = $this->getMockBuilder( Client::class )
		                ->setConstructorArgs( [ $token2 ] )
		                ->onlyMethods( [ 'get_features', 'get_data_domain' ] )
		                ->getMock();
		$client2->method( 'get_features' )->willReturn( $features );
		$client2->method( 'get_data_domain' )->willReturn( Helpers::get_domain() );

		$this->assertTrue( $client2->validate_api_token() );
		$this->assertTrue( $client2->is_api_token_valid() );

		$cached = get_transient( 'plausible_analytics_valid_token' );

		$this->assertArrayHasKey( $token1, $cached );
		$this->assertArrayHasKey( $token2, $cached );
	}
}
