<?php
/**
 * @package Plausible Analytics Integration Tests - Ajax
 */

namespace Plausible\Analytics\Tests\Integration;

use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\Ajax;
use Plausible\Analytics\WP\Helpers;

class AjaxTest extends TestCase {
	/**
	 * @var Ajax
	 */
	private $ajax;

	/**
	 * Set up a test environment.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->ajax = new Ajax();

		// Ensure we are an admin for these tests.
		$this->addUserCap( 'manage_options' );

		// Mock nonce verification
		add_filter( 'nonce_user_logged_out', '__return_true' );
	}

	/**
	 * Test save_options with normal JSON data.
	 */
	public function testSaveOptionsSuccess() {
		$options = [
			[ 'name' => 'domain_name', 'value' => 'example.com' ],
			[ 'name' => 'proxy_enabled', 'value' => 'on' ],
		];

		$_POST['_nonce']  = wp_create_nonce( 'plausible_analytics_toggle_option' );
		$_POST['options'] = wp_json_encode( $options );

		// We use catch because wp_send_json_success calls die()
		try {
			$this->ajax->save_options();
		} catch ( \Exception $e ) {
			// Catching any unexpected exceptions
		}

		$settings = Helpers::get_settings();
		$this->assertEquals( 'example.com', $settings['domain_name'] );
		$this->assertEquals( 'on', $settings['proxy_enabled'] );
	}

	/**
	 * Test save_options with escaped JSON data (simulating WordPress's $_POST behavior).
	 * This specifically tests the fix with stripslashes().
	 */
	public function testSaveOptionsWithEscapedJson() {
		$options = [
			[ 'name' => 'domain_name', 'value' => 'escaped.com' ],
		];

		$json         = wp_json_encode( $options );
		$escaped_json = addslashes( $json );

		$_POST['_nonce']  = wp_create_nonce( 'plausible_analytics_toggle_option' );
		$_POST['options'] = $escaped_json;

		try {
			$this->ajax->save_options();
		} catch ( \Exception $e ) {
		}

		$settings = Helpers::get_settings();
		$this->assertEquals( 'escaped.com', $settings['domain_name'] );
	}

	/**
	 * Test save_options with invalid JSON.
	 */
	public function testSaveOptionsInvalidJson() {
		$_POST['_nonce']  = wp_create_nonce( 'plausible_analytics_toggle_option' );
		$_POST['options'] = 'invalid-json';

		// wp_send_json_error will be called, which we expect.
		// In a real WP environment it would exit.
		try {
			$this->ajax->save_options();
		} catch ( \Exception $e ) {
		}

		// Verify that settings were NOT updated to something weird.
		$settings = Helpers::get_settings();
		$this->assertNotEquals( 'invalid-json', $settings['domain_name'] );
	}
}
