<?php
/**
 * @package Plausible Analytics Integration Tests
 */

namespace Plausible\Analytics\Tests;

use Plausible\Analytics\WP\Helpers;

/**
 * Test-double for our @see Helpers class.
 */
class TestableHelpers extends Helpers {
	/**
	 * @return
	 */
	protected static function get_client() {
		return new class {
			public function get_tracker_id() {
				return 'pa-test-tracker-id';
			}
		};
	}
}
