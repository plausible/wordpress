<?php
/**
 * @package Plausible Analytics Integration Tests - Helpers
 */

namespace Plausible\Analytics\Tests\Integration;

use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\Filters;

class FiltersTest extends TestCase {
	/**
	 * @see Filters::add_plausible_attributes()
	 */
	public function testAddPlausibleAttributes() {
		$class = new Filters();
		$tag   = $class->add_plausible_attributes( '<script id="plausible-analytics-js" src="test.js">', 'plausible-analytics' );

		$this->assertStringContainsString( 'example.org', $tag );
		$this->assertStringContainsString( 'plausible.io/api/event', $tag );
		$this->assertStringContainsString( 'plausible-analytics-js', $tag );

		add_filter( 'plausible_analytics_settings', [ $this, 'enableCompat' ] );

		$class = new Filters();
		$tag   = $class->add_plausible_attributes( '<script id="plausible-analytics-js" src="test.js">', 'plausible-analytics' );

		remove_filter( 'plausible_analytics_settings', [ $this, 'enableCompat' ] );

		$this->assertStringNotContainsString( 'plausible-analytics-js', $tag );
	}

	/**
	 * @see Filters::maybe_track_logged_in_users()
	 *
	 * @return void
	 */
	public function testTrackLoggedInUsers() {
		$class = new Filters();

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
