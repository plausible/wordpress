<?php
/**
 * @package Plausible Analytics Integration Tests
 */

namespace Plausible\Analytics\Tests;

use Plausible\Analytics\WP\Client;
use Plausible\Analytics\WP\Helpers;

/**
 * Test-double for our @see Helpers class.
 */
class TestableHelpers extends Helpers {
	/**
	 * @return
	 */
	protected static function get_client() {
		return new class extends Client {
			public function get_tracker_id( $key = 'default' ) {
				return 'pa-test-tracker-id';
			}
		};
	}
}
