<?php
/**
 * Plausible Analytics | Admin | Provisioning | Integrations
 */

namespace Plausible\Analytics\Tests\Integration\Admin\Provisioning;

use Plausible\Analytics\WP\Admin\Provisioning;
use Plausible\Analytics\WP\Admin\Provisioning\Integrations;
use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\Client;

class IntegrationsTest extends TestCase {
	/**
	 * @see Integrations::delete_integration_goals()
	 */
	public function testDeleteIntegrationGoals() {
		$client = $this->getMockBuilder( Client::class )
		               ->onlyMethods( [ 'delete_goal' ] )
		               ->getMock();

		$client->method( 'delete_goal' )->willReturn( true );

		$provisioning = $this->getMockBuilder( Provisioning::class )
		                     ->setConstructorArgs( [ $client ] )
		                     ->onlyMethods( [ 'array_search_contains' ] )
		                     ->getMock();

		$provisioning->method( 'array_search_contains' )
		             ->willReturn( 1 );

		try {
			update_option( 'plausible_analytics_enhanced_measurements_goal_ids', [ 1, 2, 3 ] );

			$integration = new Integrations( $provisioning );
			$integration->delete_integration_goals( (object) [ 'event_goals' => [ '' ] ] );

			$goal_ids = get_option( 'plausible_analytics_enhanced_measurements_goal_ids' );

			$this->assertEmpty( $goal_ids );
		} finally {
			delete_option( 'plausible_analytics_enhanced_measurements_goal_ids' );
			$this->removeAction( 'update_option_plausible_analytics_settings', 'maybe_create_' );
			$this->removeAction( 'update_option_plausible_analytics_settings', 'maybe_delete_', 11 );
		}
	}
}
