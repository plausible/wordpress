<?php

namespace Plausible\Analytics\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Filters;
use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\Helpers;

class HelpersTest extends TestCase {
	/**
	 * Covers line 157 in Helpers.php
	 *
	 * @see Helpers::get_domain()
	 */
	public function testGetDomainWithDefaultOnly() {
		$settings = [
			'domain_name' => [
				'default' => 'example.com',
				'fr'      => '',
			],
			'api_token'   => [ 'default' => '' ],
			'shared_link' => [ 'default' => '' ],
		];

		Monkey\Functions\expect( 'get_option' )
			->with( 'plausible_analytics_settings', [] )
			->andReturn( $settings );

		Monkey\Functions\expect( 'wp_parse_args' )
			->andReturnUsing(
				function ( $args, $defaults ) {
					return array_merge( $defaults, $args );
				}
			);

		// Mock is_language_per_domain_mode to return true
		Filters\expectApplied( 'plausible_analytics_language_per_domain_mode' )
			->andReturn( true );

		// Mock WPML filters
		Filters\expectApplied( 'wpml_setting' )
			->with( [], 'language_domains' )
			->andReturn( [ 'fr' => 'example.fr' ] );

		Filters\expectApplied( 'wpml_current_language' )
			->andReturn( 'fr' );

		Filters\expectApplied( 'plausible_analytics_current_language_domain_key' )
			->with( 'fr' )
			->andReturn( 'fr' );

		// Mock the constant ICL_SITEPRESS_VERSION which is checked in is_language_per_domain_mode
		if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
			define( 'ICL_SITEPRESS_VERSION', '1.0.0' );
		}

		$domain = Helpers::get_domain();

		$this->assertEquals( 'example.com', $domain );
	}

	/**
	 * Covers line 153 in Helpers.php
	 *
	 * @see Helpers::get_domain()
	 */
	public function testGetDomainWithLanguageKey() {
		$settings = [
			'domain_name' => [
				'default' => 'example.com',
				'fr'      => 'example.fr',
			],
			'api_token'   => [ 'default' => '' ],
			'shared_link' => [ 'default' => '' ],
		];

		Monkey\Functions\expect( 'get_option' )
			->with( 'plausible_analytics_settings', [] )
			->andReturn( $settings );

		Monkey\Functions\expect( 'wp_parse_args' )
			->andReturnUsing(
				function ( $args, $defaults ) {
					return array_merge( $defaults, $args );
				}
			);

		// Mock is_language_per_domain_mode to return true
		Filters\expectApplied( 'plausible_analytics_language_per_domain_mode' )
			->andReturn( true );

		// Mock WPML filters
		Filters\expectApplied( 'wpml_setting' )
			->with( [], 'language_domains' )
			->andReturn( [ 'fr' => 'example.fr' ] );

		Filters\expectApplied( 'wpml_current_language' )
			->andReturn( 'fr' );

		Filters\expectApplied( 'plausible_analytics_current_language_domain_key' )
			->with( 'fr' )
			->andReturn( 'fr' );

		// Mock the constant ICL_SITEPRESS_VERSION which is checked in is_language_per_domain_mode
		if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
			define( 'ICL_SITEPRESS_VERSION', '1.0.0' );
		}

		$domain = Helpers::get_domain();

		$this->assertEquals( 'example.fr', $domain );
	}

	/**
	 * Covers line 72 and 76 in Helpers.php
	 *
	 * @see Helpers::get_settings()
	 */
	public function testGetSettingsNormalization() {
		$settings = [
			'domain_name' => 'example.com',
			'api_token'   => 'test-token', // Line 72 target
			'shared_link' => 'https://plausible.io/share/example.com', // Line 76 target
		];

		Monkey\Functions\expect( 'get_option' )
			->with( 'plausible_analytics_settings', [] )
			->andReturn( $settings );

		Monkey\Functions\expect( 'wp_parse_args' )
			->andReturnUsing(
				function ( $args, $defaults ) {
					return array_merge( $defaults, $args );
				}
			);

		Filters\expectApplied( 'plausible_analytics_settings' )
			->once()
			->andReturnFirstArg();

		$normalized_settings = Helpers::get_settings();

		$this->assertIsArray( $normalized_settings['api_token'] );
		$this->assertEquals( [ 'default' => 'test-token' ], $normalized_settings['api_token'] );

		$this->assertIsArray( $normalized_settings['shared_link'] );
		$this->assertEquals( [ 'default' => 'https://plausible.io/share/example.com' ], $normalized_settings['shared_link'] );
	}

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}
}
