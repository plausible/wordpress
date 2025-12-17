<?php
/**
 * @package Plausible Analytics Integration Tests - Helpers
 */

namespace Plausible\Analytics\Tests\Integration;

use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\Actions;
use Plausible\Analytics\WP\Filters;
use Plausible\Analytics\WP\Helpers;
use WP_Admin_Bar;

class ActionsTest extends TestCase {
	/**
	 * @return void
	 * @throws \Exception
	 * @see Filters::maybe_add_pageview_props()
	 * @see Actions::maybe_register_assets()
	 * @see Filters::exclude_from_cloudflare_rocket_loader()
	 */
	public function testRegisterAssets() {
		global $post;

		$class = new Actions();

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

		$class->maybe_register_assets();

		$this->expectOutputContains( Helpers::get_filename( true ) );
		$this->expectOutputContains( 'test.dev' );
		$this->expectOutputContains( Helpers::get_rest_endpoint() );
		$this->expectOutputContains( 'event-author=' );
		$this->expectOutputContains( 'admin' );
		$this->expectOutputContains( 'event-category=' );
		$this->expectOutputContains( 'Uncategorized' );

		wp_print_head_scripts();

		remove_filter( 'plausible_analytics_settings', [ $this, 'enableProxy' ] );
		remove_filter( 'plausible_analytics_settings', [ $this, 'setDomain' ] );
		remove_filter( 'plausible_analytics_settings', [ $this, 'enablePageviewProps' ] );
	}

	/**
	 * @see Actions::admin_bar_node()
	 */
	public function testAdminBarNode() {
		$class = new Actions();

		if ( ! class_exists( 'WP_Admin_Bar' ) ) {
			require_once( ABSPATH . 'wp-includes/class-wp-admin-bar.php' );
		}

		wp_set_current_user( 1 );
		$admin_bar = new WP_Admin_Bar();
		$class->admin_bar_node( $admin_bar );
		$this->assertNotEmpty( $admin_bar->get_node( 'plausible-analytics' ) );

		wp_set_current_user( 0 );
		$admin_bar = new WP_Admin_Bar();
		$class->admin_bar_node( $admin_bar );
		$this->assertEmpty( $admin_bar->get_node( 'plausible-analytics' ) );
	}
}
