<?php
/**
 * @package Plausible Analytics Integration Tests - Compatibility
 */

namespace Plausible\Analytics\Tests\Integration;

use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\Compatibility;

class CompatibilityTest extends TestCase {
	/**
	 * The tracker script should load async, so a script that can't be reached doesn't hold up DOMContentLoaded (like a
	 * deferred script does), and be excluded from Cloudflare's Rocket Loader.
	 *
	 * @see Compatibility::exclude_from_cloudflare_rocket_loader()
	 * @return void
	 */
	public function testTrackerScriptLoadsAsync() {
		$tag = ( new Compatibility() )->exclude_from_cloudflare_rocket_loader(
			'<script src="https://example.org/js/pa-test.js" id="plausible-analytics-js"></script>',
			'plausible-analytics'
		);

		$this->assertStringContainsString( " async data-cfasync='false'", $tag );
		$this->assertStringNotContainsString( 'defer', $tag );
	}

	/**
	 * Hummingbird's Asset Optimization should leave our scripts alone, and only ours.
	 *
	 * @see Compatibility::exclude_from_hummingbird_asset_optimization()
	 * @return void
	 */
	public function testExcludeFromHummingbirdAssetOptimization() {
		$class = new Compatibility();

		$this->assertTrue( $class->exclude_from_hummingbird_asset_optimization( false, 'plausible-analytics', '', 'scripts' ) );
		$this->assertTrue( $class->exclude_from_hummingbird_asset_optimization( false, 'plausible-form-submit-integration', '', 'scripts' ) );
		$this->assertFalse( $class->exclude_from_hummingbird_asset_optimization( false, 'jquery-core', '', 'scripts' ) );
		$this->assertFalse( $class->exclude_from_hummingbird_asset_optimization( false, 'plausible-analytics', '', 'styles' ) );
		// Another plugin's decision is kept.
		$this->assertTrue( $class->exclude_from_hummingbird_asset_optimization( true, 'jquery-core', '', 'scripts' ) );
	}

	/**
	 * Hummingbird's Delay JavaScript should leave our scripts alone.
	 *
	 * @see Compatibility::exclude_from_hummingbird_delay_js()
	 * @return void
	 */
	public function testExcludeFromHummingbirdDelayJs() {
		$this->assertEquals( [ 'existing', 'plausible' ], ( new Compatibility() )->exclude_from_hummingbird_delay_js( [ 'existing' ] ) );
	}
}
