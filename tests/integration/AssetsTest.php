<?php
/**
 * @package Plausible Analytics Integration Tests - Helpers
 */

namespace Plausible\Analytics\Tests\Integration;

use Plausible\Analytics\Tests\TestableHelpers;
use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\Assets;
use Plausible\Analytics\WP\Helpers;

class AssetsTest extends TestCase {
	/**
	 * @return void
	 * @throws \Exception
	 */
	public function testRegisterAssets() {
		try {
			global $post;

			add_filter( 'plausible_analytics_settings', [ $this, 'enableProxy' ] );
			add_filter( 'plausible_analytics_settings', [ $this, 'setDomain' ] );
			add_filter( 'plausible_analytics_settings', [ $this, 'enablePageviewProps' ] );

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

			$class->maybe_register_assets();

			$this->expectOutputContains( TestableHelpers::get_filename() );
			$this->expectOutputContains( 'test.dev' );
			$this->expectOutputContains( Helpers::get_rest_endpoint() );
			$this->expectOutputContains( 'event-author=' );
			$this->expectOutputContains( 'admin' );
			$this->expectOutputContains( 'event-category=' );
			$this->expectOutputContains( 'Uncategorized' );

			wp_print_head_scripts();
		} finally {
			remove_filter( 'plausible_analytics_settings', [ $this, 'enableProxy' ] );
			remove_filter( 'plausible_analytics_settings', [ $this, 'setDomain' ] );
			remove_filter( 'plausible_analytics_settings', [ $this, 'enablePageviewProps' ] );
		}
	}
}
