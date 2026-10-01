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
}
