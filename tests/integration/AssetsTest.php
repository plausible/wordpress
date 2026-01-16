<?php
/**
 * @package Plausible Analytics Integration Tests - Helpers
 */

namespace Plausible\Analytics\Tests\Integration;

use Plausible\Analytics\Tests\TestableHelpers;
use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\Assets;
use Plausible\Analytics\WP\EnhancedMeasurements;
use Plausible\Analytics\WP\Helpers;

class AssetsTest extends TestCase {
	/**
	 * @return void
	 * @throws \Exception
	 * @see Assets::maybe_enqueue_main_script()
	 *
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
		              ->onlyMethods( [ 'get_js_url' ] )
		              ->getMock();

		$class->method( 'get_js_url' )
		      ->willReturn( 'https://plausible.test/js/plausible.js' );

		$class->maybe_enqueue_main_script();

		$this->expectOutputContains( 'window.plausible' );
		$this->expectOutputContains( 'plausible.init' );

		wp_print_head_scripts();
	}

	/**
	 * @return void
	 * @throws \Exception
	 * @see Assets::maybe_enqueue_cloaked_affiliate_links_assets()
	 *
	 */
	public function testEnqueueCloakedAffiliateLinksScript() {
		try {
			add_filter( 'plausible_analytics_settings', [ $this, 'enableCloakedAffiliateLinks' ] );

			$class = $this->getMockBuilder( Assets::class )
			              ->onlyMethods( [ 'get_js_url' ] )
			              ->getMock();

			$class->method( 'get_js_url' )
			      ->willReturn( 'https://plausible.test/js/plausible.js' );

			$class->maybe_enqueue_main_script();
			$class->maybe_enqueue_cloaked_affiliate_links_assets();

			$this->expectOutputContains( 'plausible-affiliate-links.js' );
			$this->expectOutputContains( 'const plausibleAffiliateLinks' );

			wp_print_head_scripts();
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

			$class = $this->getMockBuilder( Assets::class )
			              ->onlyMethods( [ 'get_js_url' ] )
			              ->getMock();

			$class->method( 'get_js_url' )
			      ->willReturn( 'https://plausible.test/js/plausible.js' );

			$class->maybe_enqueue_main_script();
			$class->maybe_enqueue_four_o_four_script();

			$this->expectOutputContains( EnhancedMeasurements::FOUR_O_FOUR );

			wp_print_head_scripts();
		} finally {
			remove_filter( 'plausible_analytics_settings', [ $this, 'enableFourOFour' ] );
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

			$class = $this->getMockBuilder( Assets::class )
			              ->onlyMethods( [ 'get_js_url' ] )
			              ->getMock();

			$class->method( 'get_js_url' )
			      ->willReturn( 'https://plausible.test/js/plausible.js' );

			$class->maybe_enqueue_main_script();
			$class->maybe_enqueue_query_params_script();

			$this->expectOutputContains( 'WP Query Parameters' );

			wp_print_head_scripts();
		} finally {
			remove_filter( 'plausible_analytics_settings', [ $this, 'enableQueryParams' ] );
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

			$class = $this->getMockBuilder( Assets::class )
			              ->onlyMethods( [ 'get_js_url' ] )
			              ->getMock();

			$class->method( 'get_js_url' )
			      ->willReturn( 'https://plausible.test/js/plausible.js' );

			$class->maybe_enqueue_main_script();
			$class->maybe_enqueue_search_queries_script();

			$this->expectOutputContains( 'WP Search Queries' );

			wp_print_head_scripts();
		} finally {
			remove_filter( 'plausible_analytics_settings', [ $this, 'enableSearchQueries' ] );
		}
	}
}
