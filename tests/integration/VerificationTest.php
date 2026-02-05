<?php
/**
 * Plausible Analytics | Verification
 */

namespace Plausible\Analytics\Tests\Integration;

use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\Verification;

class VerificationTest extends TestCase {
	/**
	 * @return void
	 * @see Verification::maybe_insert_version_meta_tag()
	 */
	public function testVersionMetaTag() {
		try {
			$class = $this->getMockBuilder( Verification::class )
			              ->disableOriginalConstructor()
			              ->onlyMethods( [] )
			              ->getMock();

			ob_start();

			$class->maybe_insert_version_meta_tag();

			$output = ob_get_clean();

			$this->assertStringNotContainsString( 'plausible-analytics-version', $output );

			$_GET['plausible_verification'] = 1;

			ob_start();
			$class->maybe_insert_version_meta_tag();

			$output = ob_get_clean();

			$this->assertStringContainsString( 'plausible-analytics-version', $output );
		} finally {
			unset( $_GET['plausible_verification'] );
		}
	}
}
