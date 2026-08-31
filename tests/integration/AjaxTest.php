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
	 * Clean up after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		$_POST = [];

		remove_filter( 'nonce_user_logged_out', '__return_true' );
	}

	/**
	 * Test save_options with keyed/multilang data.
	 */
	public function testSaveKeyedOptionsSuccess() {
		$language_domain = 'german.dev.local';
		$options         = [
			[ 'name' => "domain_name[$language_domain]", 'value' => 'german.example.com' ],
			[ 'name' => "api_token[$language_domain]", 'value' => 'plausible-plugin-german-token' ],
		];

		$_POST['_nonce']  = wp_create_nonce( 'plausible_analytics_toggle_option' );
		$_POST['options'] = wp_json_encode( $options );

		// Mock the API response to avoid real API calls and handle the validation in Ajax::save_options
		set_transient( 'plausible_analytics_valid_token', [ 'plausible-plugin-german-token' => true ] );

		try {
			$this->ajax->save_options();
		} catch ( \Exception $e ) {
		}

		$settings = Helpers::get_settings();

		$this->assertArrayHasKey( $language_domain, $settings['domain_name'] );
		$this->assertEquals( 'german.example.com', $settings['domain_name'][ $language_domain ] );
		$this->assertArrayHasKey( $language_domain, $settings['api_token'] );
		$this->assertEquals( 'plausible-plugin-german-token', $settings['api_token'][ $language_domain ] );
	}

	/**
	 * TranslatePress keys its language domains by locale (e.g. nl_NL). The underscore must survive key
	 * sanitization, otherwise the setting is stored under a key that's never read back.
	 *
	 * @see OptionsParser::parse_keyed_options()
	 */
	public function testSaveKeyedOptionsKeepsUnderscoreInKey() {
		$language_domain = 'nl_NL';
		$options         = [
			[ 'name' => "domain_name[$language_domain]", 'value' => 'tp-dutch.example.com' ],
			[ 'name' => "api_token[$language_domain]", 'value' => 'plausible-plugin-dutch-token' ],
		];

		$_POST['_nonce']  = wp_create_nonce( 'plausible_analytics_toggle_option' );
		$_POST['options'] = wp_json_encode( $options );

		set_transient( 'plausible_analytics_valid_token', [ 'plausible-plugin-dutch-token' => true ] );

		try {
			$this->ajax->save_options();
		} catch ( \Exception $e ) {
		}

		$settings = Helpers::get_settings();

		$this->assertArrayHasKey( $language_domain, $settings['domain_name'] );
		$this->assertEquals( 'tp-dutch.example.com', $settings['domain_name'][ $language_domain ] );
		$this->assertArrayHasKey( $language_domain, $settings['api_token'] );
		$this->assertEquals( 'plausible-plugin-dutch-token', $settings['api_token'][ $language_domain ] );
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
		$this->assertNotEquals( 'invalid-json', $settings['domain_name']['default'] );
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
		$this->assertEquals( 'example.com', $settings['domain_name']['default'] );
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
		$this->assertEquals( 'escaped.com', $settings['domain_name']['default'] );
	}
}
