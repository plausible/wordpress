<?php
/**
 * @package Plausible Analytics Integration Tests - Helpers
 */

namespace Plausible\Analytics\Tests\Integration;

use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\InitOptions;

class FiltersTest extends TestCase {
	/**
	 * @see InitOptions::exclude_from_cloudflare_rocket_loader()
	 */
	public function testAddPlausibleAttributes() {
		$class = new InitOptions();
		$tag = $class->exclude_from_cloudflare_rocket_loader( '<script id="plausible-analytics-js" src="test.js">', 'plausible-analytics' );

		$this->assertStringContainsString( 'example.org', $tag );
		$this->assertStringContainsString( 'plausible.io/api/event', $tag );
		$this->assertStringContainsString( 'plausible-analytics-js', $tag );

		add_filter( 'plausible_analytics_settings', [ $this, 'enableCompat' ] );

		$class = new InitOptions();
		$tag = $class->exclude_from_cloudflare_rocket_loader( '<script id="plausible-analytics-js" src="test.js">', 'plausible-analytics' );

		remove_filter( 'plausible_analytics_settings', [ $this, 'enableCompat' ] );

		$this->assertStringNotContainsString( 'plausible-analytics-js', $tag );
	}

	/**
	 * @return void
	 * @see InitOptions::maybe_track_logged_in_users()
	 *
	 */
	public function testTrackLoggedInUsers() {
		$class = new InitOptions();

		add_filter( 'plausible_analytics_settings', [ $this, 'enablePageviewProps' ] );

		$params = $class->maybe_track_logged_in_users( '' );

		$this->assertStringContainsString( 'no', $params );

		global $current_user;

		$user         = new \WP_User();
		$user->ID     = 1;
		$user->roles  = [ 'test' ];
		$current_user = $user;

		$params = $class->maybe_track_logged_in_users( '' );

		remove_filter( 'plausible_analytics_settings', [ $this, 'enablePageviewProps' ] );

		$this->assertStringContainsString( 'test', $params );
	}
}
