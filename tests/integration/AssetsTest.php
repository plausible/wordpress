<?php
/**
 * @package Plausible Analytics Integration Tests - Helpers
 */

namespace Plausible\Analytics\Tests\Integration;

use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\Assets;
use Plausible\Analytics\WP\EnhancedMeasurements;

class AssetsTest extends TestCase {
	/**
	 * @return void
	 * @throws \Exception
	 * @see Assets::maybe_enqueue_main_script()
	 */
	public function testEnqueueMainScript() {
		global $post;

		$post_id   = wp_insert_post(
			[
				'id'           => 1,
				'post_author'  => 1,
				'post_title'   => 'Test',
				'post_content' => 'Test',
			]
		);
		$test_post = get_post( $post_id );
		$post      = $test_post;

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

		do_action( 'wp_head' );

		$output = ob_get_clean();

		$this->assertStringContainsString( 'window.plausible', $output );
		$this->assertStringContainsString( 'plausible.init', $output );
	}

	/**
	 * @return void
	 * @throws \Exception
	 * @see Assets::maybe_enqueue_cloaked_affiliate_links_assets()
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

			do_action( 'wp_head' );

			$output = ob_get_clean();

			$this->assertStringContainsString( 'plausible-affiliate-links.js', $output );
			$this->assertStringContainsString( 'const plausibleAffiliateLinks', $output );
		} finally {
			remove_filter( 'plausible_analytics_settings', [ $this, 'enableCloakedAffiliateLinks' ] );
		}
	}

	/**
	 * @return void
	 * @throws \Exception
	 * @see Assets::maybe_enqueue_four_o_four_script()
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
	 * @return void
	 * @throws \Exception
	 * @see Assets::maybe_enqueue_query_params_script()
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
	 * @return void
	 * @throws \Exception
	 * @see Assets::maybe_enqueue_search_queries_script()
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
}
