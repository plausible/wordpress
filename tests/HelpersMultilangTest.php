<?php

namespace Plausible\Analytics\Tests;

use Plausible\Analytics\WP\Helpers;

class HelpersMultilangTest extends TestCase {
	private $original_server;

	public function test_get_current_multilang_key() {
		// Mock multilang mode
		add_filter( 'plausible_analytics_is_multilang_domain_mode', '__return_true' );
		\Brain\Monkey\Functions\expect( 'home_url' )->andReturn( 'http://english.dev.local' );
		\Brain\Monkey\Filters\expectApplied( 'wpml_setting' )
			->with( [], 'language_domains' )
			->andReturn( [ 'en' => 'english.dev.local', 'nl' => 'dutch.dev.local' ] );

		// 1. Resolution via HTTP_HOST
		$_SERVER['HTTP_HOST'] = 'english.dev.local';
		$this->assertEquals( 'en', Helpers::get_current_multilang_key() );

		$_SERVER['HTTP_HOST'] = 'www.english.dev.local:8080';
		$this->assertEquals( 'en', Helpers::get_current_multilang_key() );

		// 2. Fallback to first domain
		$_SERVER['HTTP_HOST'] = 'unknown.local';
		$this->assertEquals( 'en', Helpers::get_current_multilang_key() );
	}

	public function test_get_domain_and_api_token() {
		\Brain\Monkey\Functions\expect( 'wp_parse_args' )->andReturnUsing( function ( $a, $b ) {
			return array_merge( $b, $a );
		} );
		\Brain\Monkey\Functions\expect( 'home_url' )->andReturn( 'http://example.org' );

		$settings = [
			'domain_name' => [
				'en'      => 'english.plausible.io',
				'nl'      => 'dutch.plausible.io',
				'default' => 'main.plausible.io',
			],
			'api_token'   => [
				'en'      => 'token-en',
				'nl'      => 'token-nl',
				'default' => 'token-default',
			],
		];

		add_filter( 'plausible_analytics_settings', function () use ( $settings ) {
			return $settings;
		} );

		// Mock current domain to 'en'
		add_filter( 'plausible_analytics_current_multilang_key', function () {
			return 'en';
		} );
		add_filter( 'plausible_analytics_is_multilang_domain_mode', '__return_true' );

		$this->assertEquals( 'english.plausible.io', Helpers::get_domain() );
		$this->assertEquals( 'token-en', Helpers::get_api_token() );

		// Switch to 'nl'
		remove_all_filters( 'plausible_analytics_current_multilang_key' );
		add_filter( 'plausible_analytics_current_multilang_key', function () {
			return 'nl';
		} );

		$this->assertEquals( 'dutch.plausible.io', Helpers::get_domain() );
		$this->assertEquals( 'token-nl', Helpers::get_api_token() );

		// Fallback to 'default' if current key not found
		remove_all_filters( 'plausible_analytics_current_multilang_key' );
		add_filter( 'plausible_analytics_current_multilang_key', function () {
			return 'de';
		} );
		$this->assertEquals( 'main.plausible.io', Helpers::get_domain() );
		$this->assertEquals( 'token-default', Helpers::get_api_token() );

		// Legacy string normalization test (via get_settings)
		remove_all_filters( 'plausible_analytics_settings' );
		\Brain\Monkey\Functions\expect( 'get_option' )
			->with( 'plausible_analytics_settings', [] )
			->andReturn( [
				'domain_name' => 'legacy.io',
				'api_token'   => 'legacy-token',
			] );

		$normalized = Helpers::get_settings();
		$this->assertEquals( [ 'default' => 'legacy.io' ], $normalized['domain_name'] );
		$this->assertEquals( [ 'default' => 'legacy-token' ], $normalized['api_token'] );

		$this->assertEquals( 'legacy.io', Helpers::get_domain() );
		$this->assertEquals( 'legacy-token', Helpers::get_api_token() );
	}

	public function test_is_multilang_domain_mode() {
		\Brain\Monkey\Functions\expect( 'home_url' )->andReturn( 'http://example.org' );

		// Mock WPML not active
		$this->assertFalse( Helpers::is_multilang_domain_mode() );

		// Mock WPML active, but wrong negotiation type
		if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
			define( 'ICL_SITEPRESS_VERSION', '4.5.0' );
		}

		\Brain\Monkey\Filters\expectApplied( 'wpml_setting' )
			->with( 0, 'language_negotiation_type' )
			->andReturn( 1 );
		\Brain\Monkey\Filters\expectApplied( 'wpml_setting' )
			->with( [], 'language_domains' )
			->andReturn( [ 'en' => 'english.dev.local' ] );

		$this->assertFalse( Helpers::is_multilang_domain_mode() );

		// Mock WPML active, correct negotiation type, but no domains
		\Brain\Monkey\Filters\expectApplied( 'wpml_setting' )
			->with( 0, 'language_negotiation_type' )
			->andReturn( 2 );
		\Brain\Monkey\Filters\expectApplied( 'wpml_setting' )
			->with( [], 'language_domains' )
			->andReturn( [] );
		$this->assertFalse( Helpers::is_multilang_domain_mode() );

		// Mock WPML active, correct negotiation type, and domains
		\Brain\Monkey\Filters\expectApplied( 'wpml_setting' )
			->with( 0, 'language_negotiation_type' )
			->andReturn( 2 );
		\Brain\Monkey\Filters\expectApplied( 'wpml_setting' )
			->with( [], 'language_domains' )
			->andReturn( [ 'en' => 'english.dev.local', 'nl' => 'dutch.dev.local' ] );
		$this->assertTrue( Helpers::is_multilang_domain_mode() );
	}

	protected function setUp(): void {
		parent::setUp();
		$this->original_server = $_SERVER;
	}

	protected function tearDown(): void {
		$_SERVER = $this->original_server;
		parent::tearDown();
	}
}
