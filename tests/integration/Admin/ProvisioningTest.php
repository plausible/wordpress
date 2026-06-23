<?php
/**
 * @package Plausible Analytis integration tests - Provisioning
 */

namespace Plausible\Analytics\Tests\Integration\Admin;

use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\Admin\Provisioning;
use Plausible\Analytics\WP\Client;
use Plausible\Analytics\WP\Client\ApiException;
use Plausible\Analytics\WP\Client\Model\Goal;
use Plausible\Analytics\WP\Client\Model\GoalPageviewAllOfGoal;
use Plausible\Analytics\WP\EnhancedMeasurements;
use Plausible\Analytics\WP\Helpers;
use function Brain\Monkey\Functions\when;

class ProvisioningTest extends TestCase {
	/**
	 * @see Provisioning::create_goal_request()
	 * @return void
	 */
	public function testCreateGoalRequest() {
		$class = new Provisioning( false );

		$pageview = $class->create_goal_request( 'Test Pageview', 'Pageview', null, '/test' );

		$this->assertInstanceOf( 'Plausible\Analytics\WP\Client\Model\GoalCreateRequestPageview', $pageview );

		$revenue = $class->create_goal_request( 'Test Revenue', 'Revenue', 'EUR' );

		$this->assertInstanceOf( 'Plausible\Analytics\WP\Client\Model\GoalCreateRequestRevenue', $revenue );

		$custom_event = $class->create_goal_request( 'Test Custom Event' );

		$this->assertInstanceOf( 'Plausible\Analytics\WP\Client\Model\GoalCreateRequestCustomEvent', $custom_event );
	}

	/**
	 * @see Provisioning::maybe_create_goals()
	 * @throws ApiException
	 */
	public function testCreateGoals() {
		$settings['enhanced_measurements'] = [
			'404',
			'outbound-links',
			'file-downloads',
			'search',
		];
		$mock                              = $this->getMockBuilder( Client::class )->onlyMethods( [ 'create_goals' ] )->getMock();
		$goals_array                       = [
			new Goal(
				[
					'goal'      => new GoalPageviewAllOfGoal( [
						'display_name' => '404',
						'id'           => 111,
						'path'         => null,
					] ),
					'goal_type' => 'Goal.CustomEvent',
				]
			),
			new Goal(
				[
					'goal'      => new GoalPageviewAllOfGoal( [
						'display_name' => 'Outbound Link: Click',
						'id'           => 222,
						'path'         => null,
					] ),
					'goal_type' => 'Goal.CustomEvent',
				]
			),
			new Goal(
				[
					'goal'      => new GoalPageviewAllOfGoal( [
						'display_name' => 'File Downloads',
						'id'           => 333,
						'path'         => null,
					] ),
					'goal_type' => 'Goal.CustomEvent',
				]
			),
			new Goal(
				[
					'goal'      => new GoalPageviewAllOfGoal( [
						'display_name' => 'Search',
						'id'           => 444,
						'path'         => null,
					] ),
					'goal_type' => 'Goal.Pageview',
				]
			),
		];
		$goals                             = new Client\Model\GoalListResponse();

		$goals->setGoals( $goals_array );
		$goals->setMeta( new Client\Model\GoalListResponseMeta() );
		$mock->method( 'create_goals' )->willReturn( $goals );

		$class = new Provisioning( $mock );

		$class->maybe_create_goals( [], $settings );

		$goal_ids = get_option( 'plausible_analytics_enhanced_measurements_goal_ids' );

		$this->assertCount( 1, $goal_ids );
		$this->assertCount( 4, $goal_ids['default'] );
		$this->assertArrayHasKey( 111, $goal_ids['default'] );
		$this->assertArrayHasKey( 222, $goal_ids['default'] );
		$this->assertArrayHasKey( 333, $goal_ids['default'] );
		$this->assertArrayHasKey( 444, $goal_ids['default'] );

		delete_option( 'plausible_analytics_enhanced_measurements_goal_ids' );
	}

	/**
	 * @see Provisioning::maybe_create_shared_link()
	 * @throws ApiException
	 */
	public function testCreateSharedLink() {
		$settings                               = [];
		$settings['enable_analytics_dashboard'] = 1;
		$mock                                   = $this->getMockBuilder( Client::class )->onlyMethods( [ 'bulk_create_shared_links' ] )->getMock();
		$sharedLinkObject                       = new Client\Model\SharedLinkSharedLink(
			[
				'id'                 => 'test',
				'name'               => 'Test',
				'href'               => 'http://example.org/test',
				'password_protected' => false,
			]
		);
		$sharedLink                             = new Client\Model\SharedLink();

		$sharedLink->setSharedLink( $sharedLinkObject );
		$mock->method( 'bulk_create_shared_links' )->willReturn( $sharedLink );

		$class = new Provisioning( $mock );

		$class->maybe_create_shared_link( [], $settings );

		$sharedLink = Helpers::get_settings()['shared_link'];

		$this->assertEquals( 'http://example.org/test', $sharedLink );
	}

	/**
	 * @see Provisioning::maybe_enable_customer_user_roles()
	 * @return void
	 */
	public function testMaybeEnableCustomerUserRole() {
		try {
			$class                             = new Provisioning( false );
			$settings                          = [];
			$settings['enhanced_measurements'] = [ 'revenue' ];
			$settings['tracked_user_roles']    = [];

			add_filter( 'plausible_analytics_integrations_woocommerce', '__return_true' );

			$new_settings = $class->maybe_enable_customer_user_roles( $settings );

			$this->assertTrue( in_array( 'customer', $new_settings['tracked_user_roles'] ) );
		} finally {
			remove_filter( 'plausible_analytics_integrations_woocommerce', '__return_true' );
		}

		try {
			add_filter( 'plausible_analytics_integrations_edd', '__return_true' );

			$new_settings = $class->maybe_enable_customer_user_roles( $settings );

			$this->assertTrue( in_array( 'subscriber', $new_settings['tracked_user_roles'] ) );
		} finally {
			remove_filter( 'plausible_analytics_integrations_edd', '__return_true' );
		}

		try {
			add_filter( 'plausible_analytics_integrations_edd_recurring', '__return_true' );

			$new_settings = $class->maybe_enable_customer_user_roles( $settings );

			$this->assertTrue( in_array( 'edd_subscriber', $new_settings['tracked_user_roles'] ) );
		} finally {
			remove_filter( 'plausible_analytics_integrations_edd_recurring', '__return_true' );
		}
	}

	/**
	 * Test multi-domain iteration.
	 */
	public function testMultiDomainIteration() {
		// Mock settings to have two domains with tokens.
		$settings              = Helpers::get_settings();
		$settings['api_token'] = [
			'default' => 'token-a',
			'nl'      => 'token-b',
		];
		update_option( 'plausible_analytics_settings', $settings );

		$mock_a = $this->getMockBuilder( Client::class )->disableOriginalConstructor()->onlyMethods( [ 'validate_api_token', 'create_shared_link' ] )->getMock();
		$mock_a->method( 'validate_api_token' )->willReturn( true );
		$mock_a->expects( $this->once() )->method( 'create_shared_link' );

		$mock_b = $this->getMockBuilder( Client::class )->disableOriginalConstructor()->onlyMethods( [ 'validate_api_token', 'create_shared_link' ] )->getMock();
		$mock_b->method( 'validate_api_token' )->willReturn( true );
		$mock_b->expects( $this->once() )->method( 'create_shared_link' );

		// We need a way to return these mocks. Since we can't easily mock ClientFactory::build() without more effort,
		// we'll mock get_clients_per_domain directly in a partial mock of Provisioning.
		$provisioning = $this->getMockBuilder( Provisioning::class )
		                     ->onlyMethods( [ 'get_clients' ] )
		                     ->getMock();

		$provisioning->method( 'get_clients' )->willReturn( [
			'default' => $mock_a,
			'nl'      => $mock_b,
		] );

		$provisioning->maybe_create_shared_link( [], [ 'enable_analytics_dashboard' => 1 ] );

		delete_option( 'plausible_analytics_settings' );
	}

	/**
	 * Test legacy data normalization.
	 */
	public function testNormalizePerDomainOption() {
		$provisioning = new Provisioning();

		// Flat format (legacy)
		$legacy_goals = [ 123 => 'Goal Name' ];
		$normalized   = $this->callMethod( $provisioning, 'normalize_per_domain_option', [ $legacy_goals ] );
		$this->assertEquals( [ 'default' => $legacy_goals ], $normalized );

		$legacy_caps = [ 'goals' => true, 'stats' => false ];
		$normalized  = $this->callMethod( $provisioning, 'normalize_per_domain_option', [ $legacy_caps ] );
		$this->assertEquals( [ 'default' => $legacy_caps ], $normalized );

		// New format
		$new_format = [ 'default' => [ 123 => 'Goal Name' ], 'fr' => [ 456 => 'Goal FR' ] ];
		$normalized = $this->callMethod( $provisioning, 'normalize_per_domain_option', [ $new_format ] );
		$this->assertEquals( $new_format, $normalized );
	}

	/**
	 * Helper to call private/protected methods.
	 */
	protected function callMethod( $obj, $name, array $args ) {
		$class  = new \ReflectionClass( $obj );
		$method = $class->getMethod( $name );
		$method->setAccessible( true );

		return $method->invokeArgs( $obj, $args );
	}

	/**
	 * @see Provisioning::update_tracker_script_config()
	 * @throws ApiException
	 */
	public function testUpdateTrackerScriptConfig() {
		$mock = $this->getMockBuilder( Client::class )->onlyMethods( [ 'update_tracker_script_configuration' ] )->getMock();
		$mock->method( 'update_tracker_script_configuration' )->willReturn( true );
		$class    = new Provisioning( $mock );
		$settings = [];

		// File Downloads enabled.
		try {
			$settings['enhanced_measurements'] = [ EnhancedMeasurements::FILE_DOWNLOADS ];

			$config = $class->update_tracker_script_config( [], $settings );

			$this->assertTrue( $config['tracker_script_configuration']['file_downloads'] == true );
		} finally {
			$settings = [];
		}

		// Form Submissions enabled.
		try {
			$settings['enhanced_measurements'] = [ EnhancedMeasurements::FORM_COMPLETIONS ];

			$config = $class->update_tracker_script_config( [], $settings );

			$this->assertTrue( $config['tracker_script_configuration']['form_submissions'] == true );
		} finally {
			$settings = [];
		}

		// Hash-Based Routing enabled.
		try {
			$settings['enhanced_measurements'] = [ EnhancedMeasurements::HASH_BASED_ROUTING ];

			$config = $class->update_tracker_script_config( [], $settings );

			$this->assertTrue( $config['tracker_script_configuration']['hash_based_routing'] == true );
		} finally {
			$settings = [];
		}

		// Outbound Links enabled.
		try {
			$settings['enhanced_measurements'] = [ EnhancedMeasurements::OUTBOUND_LINKS ];

			$config = $class->update_tracker_script_config( [], $settings );

			$this->assertTrue( $config['tracker_script_configuration']['outbound_links'] == true );
		} finally {
			$settings = [];
		}
	}
}
