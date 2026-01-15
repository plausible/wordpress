<?php
/**
 * @package Plausible Analytics Integration Tests - Helpers
 */

namespace Plausible\Analytics\Tests\Integration;

use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\InitOptions;

class InitOptionsTest extends TestCase {
	/**
	 * @return void
	 * @see InitOptions::maybe_track_logged_in_users()
	 */
	public function testTrackLoggedInUsers() {
		try {
			$class = new InitOptions();

			add_filter( 'plausible_analytics_settings', [ $this, 'enablePageviewProps' ] );

			$params = $class->maybe_track_logged_in_users();

			$this->assertArrayHasKey( 'customProperties', $params );
			$this->assertArrayHasKey( 'user_logged_in', $params['customProperties'] );

			global $current_user;

			$user         = new \WP_User();
			$user->ID     = 1;
			$user->roles  = [ 'test' ];
			$current_user = $user;

			$params = $class->maybe_track_logged_in_users();

			$this->assertArrayHasKey( 'customProperties', $params );
			$this->assertArrayHasKey( 'user_logged_in', $params['customProperties'] );
			$this->assertEquals( 'test', $params['customProperties']['user_logged_in'] );
		} finally {
			remove_filter( 'plausible_analytics_settings', [ $this, 'enablePageviewProps' ] );
		}
	}
}
