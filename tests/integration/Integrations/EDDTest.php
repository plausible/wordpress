<?php
/**
 * Plausible Analytics | EDD
 */

namespace Plausible\Analytics\Tests\Integration\Integrations;

use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\Integrations\EDD;
use function Brain\Monkey\Functions\when;

class EDDTest extends TestCase {
	/**
	 *
	 */
	public function testTrackPurchase() {
		when( 'edd_is_success_page' )->justReturn( true );
		when( 'edd_get_purchase_session' )->justReturn( [
			'purchase_key' => 'test-key'
		] );
		when( 'edd_get_order_by' )->justReturn( (object) [ 'id' => 1, 'total' => 10.00, 'currency' => 'EUR' ] );
		when( 'edd_get_order_meta' )->justReturn( false );
		when( 'edd_add_order_meta' )->justReturn( 1000 );

		$class = new EDD( false );

		ob_start();

		$class->track_purchase();

		$output = ob_get_clean();

		$this->assertStringContainsString( $class->event_goals['purchase'], $output );
		$this->assertStringContainsString( '{"revenue":{"amount":10,"currency":"EUR"}}', $output );
	}
}
