<?php
/**
 * @package Plausible Analytics Integration Tests - Helpers
 */

namespace Plausible\Analytics\Tests\Integration;

use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\Assets;
use Plausible\Analytics\WP\Integrations\FormSubmit;

class AssetsTest extends TestCase {
	/**
	 * @see Assets::maybe_enqueue_cloaked_affiliate_links_assets()
	 * @return void
	 * @throws \Exception
	 */
	public function testEnqueueCloakedAffiliateLinksScript() {
		try {
			add_filter( 'plausible_analytics_settings', [ $this, 'enableCloakedAffiliateLinks' ] );

			$class = $this->getMockBuilder( Assets::class )
			              ->disableOriginalConstructor()
			              ->onlyMethods( [ 'get_js_url' ] )
			              ->getMock();

			$this->removeAction( 'wp_enqueue_scripts', 'maybe_enqueue' );
			$this->removeAction( 'wp_enqueue_scripts', 'maybe_enqueue', 11 );

			$class->method( 'get_js_url' )
			      ->willReturn( 'https://plausible.test/js/plausible.js' );

			ob_start();

			$class->maybe_enqueue_main_script();
			$class->maybe_enqueue_cloaked_affiliate_links_assets();

			do_action( 'wp_footer' );

			$output = ob_get_clean();

			$this->assertStringContainsString( 'plausible-affiliate-links.js', $output );
			$this->assertStringContainsString( 'const plausibleAffiliateLinks', $output );
		} finally {
			remove_filter( 'plausible_analytics_settings', [ $this, 'enableCloakedAffiliateLinks' ] );
		}
	}

	/**
	 * @see Assets::maybe_enqueue_four_o_four_script()
	 * @return void
	 * @throws \Exception
	 */
	public function testEnqueueFourOFourScript() {
		try {
			add_filter( 'plausible_analytics_settings', [ $this, 'enableFourOFour' ] );
			add_filter( 'plausible_analytics_is_404', '__return_true' );

			$class = $this->getMockBuilder( Assets::class )
			              ->disableOriginalConstructor()
			              ->onlyMethods( [ 'get_js_url' ] )
			              ->getMock();

			$class->method( 'get_js_url' )
			      ->willReturn( 'https://plausible.test/js/plausible.js' );

			$class->maybe_enqueue_main_script();
			$class->maybe_enqueue_four_o_four_script();

			global $wp_scripts;

			$this->assertArrayHasKey( 'plausible-analytics', $wp_scripts->registered );
			$this->assertTrue( $this->arrayHasString( '404', $wp_scripts->registered['plausible-analytics']->extra['after'] ) );
		} finally {
			remove_filter( 'plausible_analytics_settings', [ $this, 'enableFourOFour' ] );
			remove_filter( 'plausible_analytics_is_404', '__return_true' );
		}
	}

	/**
	 * @see Assets::maybe_enqueue_main_script()
	 * @return void
	 * @throws \Exception
	 */
	public function testEnqueueMainScript() {
		try {
			add_filter( 'plausible_analytics_settings', [ $this, 'enableAdministratorTracking' ] );

			$class = $this->getMockBuilder( Assets::class )
			              ->disableOriginalConstructor()
			              ->onlyMethods( [ 'get_js_url' ] )
			              ->getMock();

			$this->removeAction( 'wp_enqueue_scripts', 'maybe_enqueue' );
			$this->removeAction( 'wp_enqueue_scripts', 'maybe_enqueue', 11 );

			$class->method( 'get_js_url' )
			      ->willReturn( 'https://plausible.test/js/plausible.js' );

			wp_set_current_user( 1 );
			$user = wp_get_current_user();
			$user->add_role( 'administrator' );

			$class->maybe_enqueue_main_script();

			global $wp_scripts;
			$data = $wp_scripts->get_data( 'plausible-analytics', 'after' );

			$this->assertStringContainsString( 'window.plausible', implode( '', $data ) );
			$this->assertStringContainsString( 'plausible.init', implode( '', $data ) );
		} finally {
			remove_filter( 'plausible_analytics_settings', [ $this, 'enableAdministratorTracking' ] );
			wp_set_current_user( null );
		}
	}

	/**
	 * @see Assets::maybe_enqueue_query_params_script()
	 * @return void
	 * @throws \Exception
	 */
	public function testEnqueueQueryParamsScript() {
		try {
			add_filter( 'plausible_analytics_settings', [ $this, 'enableQueryParams' ] );
			add_filter( 'plausible_analytics_settings', [ $this, 'setQueryParams' ] );

			$class = $this->getMockBuilder( Assets::class )
			              ->disableOriginalConstructor()
			              ->onlyMethods( [ 'get_js_url' ] )
			              ->getMock();

			$class->method( 'get_js_url' )
			      ->willReturn( 'https://plausible.test/js/plausible.js' );

			$class->maybe_enqueue_main_script();
			$class->maybe_enqueue_query_params_script();

			global $wp_scripts;

			$this->assertArrayHasKey( 'plausible-analytics', $wp_scripts->registered );
			$this->assertTrue( $this->arrayHasString( 'WP Query Parameters', $wp_scripts->registered['plausible-analytics']->extra['after'] ) );
		} finally {
			remove_filter( 'plausible_analytics_settings', [ $this, 'enableQueryParams' ] );
			remove_filter( 'plausible_analytics_settings', [ $this, 'setQueryParams' ] );
			unset( $_REQUEST['test'] );
		}
	}

	/**
	 * @see Assets::maybe_enqueue_search_queries_script()
	 * @return void
	 * @throws \Exception
	 */
	public function testEnqueueSearchQueriesScript() {
		try {
			add_filter( 'plausible_analytics_settings', [ $this, 'enableSearchQueries' ] );
			add_filter( 'plausible_analytics_is_search', '__return_true' );

			$class = $this->getMockBuilder( Assets::class )
			              ->disableOriginalConstructor()
			              ->onlyMethods( [ 'get_js_url' ] )
			              ->getMock();

			$class->method( 'get_js_url' )
			      ->willReturn( 'https://plausible.test/js/plausible.js' );

			$class->maybe_enqueue_main_script();
			$class->maybe_enqueue_search_queries_script();

			global $wp_scripts;

			$this->assertArrayHasKey( 'plausible-analytics', $wp_scripts->registered );
			$this->assertTrue( $this->arrayHasString( 'WP Search Queries', $wp_scripts->registered['plausible-analytics']->extra['after'] ) );
		} finally {
			remove_filter( 'plausible_analytics_settings', [ $this, 'enableSearchQueries' ] );
			remove_filter( 'plausible_analytics_is_search', '__return_true' );
		}
	}

	/**
	 * For a user role that isn't tracked (administrators by default), the tracker is registered but not enqueued, and
	 * neither is its plausible.init() call. Scripts that depend on it mustn't pull it onto the page anyway: the tracker
	 * then leaves window.plausible an object, so their calls throw "window.plausible is not a function".
	 *
	 * @see Assets::maybe_enqueue_cloaked_affiliate_links_assets()
	 * @see FormSubmit::add_js()
	 * @return void
	 * @throws \Exception
	 */
	public function testIntegrationsDontLoadTheTrackerForUntrackedRoles() {
		$user_id = wp_insert_user( [ 'user_login' => 'pa_untracked_' . wp_generate_password( 6, false ), 'user_pass' => wp_generate_password(), 'role' => 'administrator' ] );

		wp_set_current_user( $user_id );
		add_filter( 'plausible_analytics_settings', [ $this, 'enableCloakedAffiliateLinks' ] );
		add_filter( 'plausible_analytics_settings', [ $this, 'enableFormCompletions' ] );

		$assets = $this->getMockBuilder( Assets::class )->disableOriginalConstructor()->onlyMethods( [ 'get_js_url' ] )->getMock();
		$assets->method( 'get_js_url' )->willReturn( 'https://plausible.test/js/plausible.js' );
		$form_submit = $this->getMockBuilder( FormSubmit::class )->disableOriginalConstructor()->onlyMethods( [] )->getMock();

		try {
			$this->dequeueMainScript();

			$assets->maybe_enqueue_main_script();
			$form_submit->add_js();
			$assets->maybe_enqueue_cloaked_affiliate_links_assets();

			$this->assertTrue( wp_script_is( 'plausible-analytics', 'registered' ) );
			$this->assertFalse( wp_script_is( 'plausible-analytics', 'enqueued' ) );
			$this->assertFalse( wp_script_is( 'plausible-form-submit-integration', 'enqueued' ) );
			$this->assertFalse( wp_script_is( 'plausible-affiliate-links', 'enqueued' ) );

			// Once administrators are tracked, everything is loaded.
			add_filter( 'plausible_analytics_settings', [ $this, 'enableAdministratorTracking' ] );

			$assets->maybe_enqueue_main_script();
			$form_submit->add_js();
			$assets->maybe_enqueue_cloaked_affiliate_links_assets();

			$this->assertTrue( wp_script_is( 'plausible-analytics', 'enqueued' ) );
			$this->assertTrue( wp_script_is( 'plausible-form-submit-integration', 'enqueued' ) );
			$this->assertTrue( wp_script_is( 'plausible-affiliate-links', 'enqueued' ) );
		} finally {
			remove_filter( 'plausible_analytics_settings', [ $this, 'enableCloakedAffiliateLinks' ] );
			remove_filter( 'plausible_analytics_settings', [ $this, 'enableFormCompletions' ] );
			remove_filter( 'plausible_analytics_settings', [ $this, 'enableAdministratorTracking' ] );
			$this->dequeueMainScript();
			wp_set_current_user( 0 );
			wp_delete_user( $user_id );
		}
	}
}
