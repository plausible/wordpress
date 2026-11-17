<?php
/**
 * Plausible Analytics | Form Submissions
 */

namespace Plausible\Analytics\Tests\Integration\Integrations;

use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\Integrations\FormSubmit;

class FormSubmitTest extends TestCase {
	/**
	 * @var array The paths of the events that would have been sent to Plausible Analytics.
	 */
	private $sent = [];

	/**
	 * @return void
	 */
	protected function set_up() {
		parent::set_up();

		$this->sent = [];

		add_filter( 'pre_http_request', [ $this, 'capture_event' ], 10, 3 );
	}

	/**
	 * @return void
	 */
	protected function tear_down() {
		remove_filter( 'pre_http_request', [ $this, 'capture_event' ] );
		unset( $_POST['_wpcf7_container_post'], $_SERVER['HTTP_REFERER'] );

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

		$this->sent[] = json_decode( $args['body'], true )['p']['path'] ?? '';

		return [ 'headers' => [], 'body' => '', 'response' => [ 'code' => 202, 'message' => 'Accepted' ], 'cookies' => [], 'filename' => null ];
	}

	/**
	 * @see FormSubmit::maybe_track_submission()
	 * @return void
	 */
	public function testMaybeTrackSubmission() {
		$class  = $this->getMockBuilder( FormSubmit::class )->disableOriginalConstructor()->onlyMethods( [] )->getMock();
		$result = $this->getMockBuilder( \stdClass::class )->addMethods( [ 'get_invalid_fields' ] )->getMock();
		$result->method( 'get_invalid_fields' )->willReturn( [] );

		// Form inside a post.
		$post_id                        = wp_insert_post( [ 'post_title' => 'Contact', 'post_name' => 'contact', 'post_status' => 'publish' ] );
		$_POST['_wpcf7_container_post'] = (string) $post_id;
		$class->maybe_track_submission( $result, [] );
		wp_delete_post( $post_id, true );

		// Form outside the loop, e.g. in a widget.
		$_POST['_wpcf7_container_post'] = '0';
		$_SERVER['HTTP_REFERER']        = home_url( '/about/team/' );
		$class->maybe_track_submission( $result, [] );

		// Non-AJAX submission, which posts to the page containing the form.
		$request_uri            = $_SERVER['REQUEST_URI'] ?? null;
		$_SERVER['REQUEST_URI'] = '/about/team/';
		$class->maybe_track_submission( $result, [] );
		$_SERVER['REQUEST_URI'] = $request_uri;

		// No container post and no referer.
		unset( $_POST['_wpcf7_container_post'], $_SERVER['HTTP_REFERER'] );
		$class->maybe_track_submission( $result, [] );

		$this->assertEquals( [ '/contact/', '/about/team/', '/about/team/' ], $this->sent );
	}
}
