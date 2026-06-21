<?php

namespace Plausible\Analytics\Tests;

use Plausible\Analytics\WP\Helpers;

class HelpersMultilangTest extends TestCase {
	private $original_server;

	protected function setUp(): void {
		parent::setUp();
		$this->original_server = $_SERVER;
	}

	protected function tearDown(): void {
		$_SERVER = $this->original_server;
		parent::tearDown();
	}

	public function test_is_multilang_domain_mode() {
		// Mock WPML not active
		$this->assertFalse( Helpers::is_multilang_domain_mode() );

		// Mock WPML active, but wrong negotiation type
		define( 'ICL_SITEPRESS_VERSION', '4.5.0' );
		\Brain\Monkey\Filters\expectApplied( 'wpml_setting' )
			->with( false, 'language_negotiation_type' )
			->andReturn( 1 );
		$this->assertFalse( Helpers::is_multilang_domain_mode() );

		// Mock WPML active, correct negotiation type, but no domains
		\Brain\Monkey\Filters\expectApplied( 'wpml_setting' )
			->with( false, 'language_negotiation_type' )
			->andReturn( 2 );
		\Brain\Monkey\Filters\expectApplied( 'wpml_setting' )
			->with( [], 'language_domains' )
			->andReturn( [] );
		$this->assertFalse( Helpers::is_multilang_domain_mode() );

		// Mock WPML active, correct negotiation type, and domains
		\Brain\Monkey\Filters\expectApplied( 'wpml_setting' )
			->with( false, 'language_negotiation_type' )
			->andReturn( 2 );
		\Brain\Monkey\Filters\expectApplied( 'wpml_setting' )
			->with( [], 'language_domains' )
			->andReturn( [ 'en' => 'english.dev.local', 'nl' => 'dutch.dev.local' ] );
		$this->assertTrue( Helpers::is_multilang_domain_mode() );
	}

	public function test_get_current_multilang_domain() {
		// Mock multilang mode
		add_filter( 'plausible_analytics_is_multilang_domain_mode', '__return_true' );
		\Brain\Monkey\Filters\expectApplied( 'wpml_setting' )
			->with( [], 'language_domains' )
			->andReturn( [ 'en' => 'english.dev.local', 'nl' => 'dutch.dev.local' ] );

		// 1. Resolution via current language
		\Brain\Monkey\Filters\expectApplied( 'wpml_current_language', null )
			->andReturn( 'nl' );

		$this->assertEquals( 'dutch.dev.local', Helpers::get_current_multilang_domain() );

		// 2. Resolution via HTTP_HOST
		\Brain\Monkey\Filters\expectApplied( 'wpml_current_language', null )
			->andReturn( null );
		$_SERVER['HTTP_HOST'] = 'english.dev.local';
		$this->assertEquals( 'english.dev.local', Helpers::get_current_multilang_domain() );

		$_SERVER['HTTP_HOST'] = 'www.english.dev.local:8080';
		$this->assertEquals( 'english.dev.local', Helpers::get_current_multilang_domain() );

		// 3. Fallback to first domain
		$_SERVER['HTTP_HOST'] = 'unknown.local';
		$this->assertEquals( 'english.dev.local', Helpers::get_current_multilang_domain() );
	}

	public function test_get_domain_and_api_token() {
		$settings = [
			'domain_name' => [
				'en' => 'english.plausible.io',
				'nl' => 'dutch.plausible.io',
			],
			'api_token' => [
				'en' => 'token-en',
				'nl' => 'token-nl',
			],
		];

		add_filter( 'plausible_analytics_settings', function () use ( $settings ) {
			return $settings;
		} );

		// Mock current domain to 'en'
		add_filter( 'plausible_analytics_current_multilang_domain', function () {
			return 'en';
		} );
		add_filter( 'plausible_analytics_is_multilang_domain_mode', '__return_true' );

		$this->assertEquals( 'english.plausible.io', Helpers::get_domain() );
		$this->assertEquals( 'token-en', Helpers::get_api_token() );

		// Switch to 'nl'
		remove_all_filters( 'plausible_analytics_current_multilang_domain' );
		add_filter( 'plausible_analytics_current_multilang_domain', function () {
			return 'nl';
		} );

		$this->assertEquals( 'dutch.plausible.io', Helpers::get_domain() );
		$this->assertEquals( 'token-nl', Helpers::get_api_token() );

		// Fallback for domain_name if current not set
		remove_all_filters( 'plausible_analytics_current_multilang_domain' );
		add_filter( 'plausible_analytics_current_multilang_domain', function () {
			return 'de';
		} );
		$this->assertEquals( 'english.plausible.io', Helpers::get_domain() ); // first entry
		$this->assertEquals( '', Helpers::get_api_token() ); // empty for token

		// String shapes
		remove_all_filters( 'plausible_analytics_settings' );
		add_filter( 'plausible_analytics_settings', function () {
			return [
				'domain_name' => 'single.io',
				'api_token'   => 'single-token',
			];
		} );
		$this->assertEquals( 'single.io', Helpers::get_domain() );
		$this->assertEquals( 'single-token', Helpers::get_api_token() );
	}
}
