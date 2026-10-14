<?php
/**
 * @package Plausible Analytics Integration Tests - User roles that aren't tracked
 */

namespace Plausible\Analytics\Tests\Integration;

use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\Helpers;
use Plausible\Analytics\WP\Integrations\EDD;
use Plausible\Analytics\WP\Integrations\FormSubmit;
use Plausible\Analytics\WP\Integrations\WooCommerce;
use function Brain\Monkey\Functions\when;

/**
 * Events sent from the server (add to cart, remove from cart, EDD checkout, form submissions) should respect the
 * "Track analytics for user roles" setting, like the tracker in the browser does.
 */
class UntrackedRolesTest extends TestCase {
	/**
	 * @var int
	 */
	private $user_id;

	/**
	 * @var array The events that would have been sent to Plausible Analytics.
	 */
	private $sent = [];

	/**
	 * @return void
	 */
	protected function set_up() {
		parent::set_up();

		$this->user_id = wp_insert_user( [ 'user_login' => 'pa_role_' . wp_generate_password( 6, false ), 'user_pass' => wp_generate_password(), 'role' => 'administrator' ] );
		$this->sent    = [];

		add_filter( 'pre_http_request', [ $this, 'capture_event' ], 10, 3 );
	}

	/**
	 * @return void
	 */
	protected function tear_down() {
		remove_filter( 'pre_http_request', [ $this, 'capture_event' ] );
		remove_filter( 'plausible_analytics_settings', [ $this, 'enableAdministratorTracking' ] );
		wp_set_current_user( 0 );
		wp_delete_user( $this->user_id );

		parent::tear_down();
	}

	/**
	 * Records events instead of sending them.
	 *
	 * @param false|array $response
	 * @param array       $args
	 * @param string      $url
	 *
	 * @return false|array
	 */
	public function capture_event( $response, $args, $url ) {
		if ( strpos( $url, '/api/event' ) === false ) {
			return $response;
		}

		$this->sent[] = json_decode( $args['body'], true )['n'] ?? '';

		return [ 'headers' => [], 'body' => '', 'response' => [ 'code' => 202, 'message' => 'Accepted' ], 'cookies' => [], 'filename' => null ];
	}

	/**
	 * @see Helpers::is_current_user_tracked()
	 * @return void
	 */
	public function testIsCurrentUserTracked() {
		wp_set_current_user( 0 );
		$this->assertTrue( Helpers::is_current_user_tracked() );

		wp_set_current_user( $this->user_id );
		$this->assertFalse( Helpers::is_current_user_tracked() );

		add_filter( 'plausible_analytics_settings', [ $this, 'enableAdministratorTracking' ] );
		$this->assertTrue( Helpers::is_current_user_tracked() );
	}

	/**
	 * @see WooCommerce::track_add_to_cart()
	 * @see WooCommerce::track_remove_cart_item()
	 * @return void
	 */
	public function testWooCommerceCartEventsSkipUntrackedRoles() {
		when( 'wc_get_permalink_structure' )->justReturn( [ 'product_base' => 'product' ] );
		wp_set_current_user( $this->user_id );

		$class = new WooCommerce( false );
		$class->track_add_to_cart( $this->getMockBuilder( 'WC_Product' )->getMock(), [ 'id' => 1, 'quantity' => 1 ] );
		$class->track_remove_cart_item( 'key', $this->getMockBuilder( 'WC_Cart' )->getMock() );

		$this->assertEquals( [], $this->sent );
	}

	/**
	 * @see EDD::track_add_to_cart()
	 * @see EDD::track_remove_cart_item()
	 * @see EDD::track_entered_checkout()
	 * @return void
	 */
	public function testEDDEventsSkipUntrackedRoles() {
		wp_set_current_user( $this->user_id );

		$class = new EDD( false );
		$class->track_add_to_cart( 1, [], [] );
		$class->track_remove_cart_item( 0 );
		$class->track_entered_checkout();

		$this->assertEquals( [], $this->sent );
	}

	/**
	 * @see FormSubmit::track_gravity_forms_submission()
	 * @return void
	 */
	public function testFormSubmissionsRespectTrackedRoles() {
		$class = $this->getMockBuilder( FormSubmit::class )->disableOriginalConstructor()->onlyMethods( [] )->getMock();
		$form  = [ 'source_url' => home_url( '/contact/' ) ];

		wp_set_current_user( $this->user_id );
		$class->track_gravity_forms_submission( $form );
		$this->assertEquals( [], $this->sent );

		// Once administrators are tracked, the submission is sent.
		add_filter( 'plausible_analytics_settings', [ $this, 'enableAdministratorTracking' ] );
		$class->track_gravity_forms_submission( $form );
		$this->assertEquals( [ 'WP Form Completions' ], $this->sent );
	}
}
