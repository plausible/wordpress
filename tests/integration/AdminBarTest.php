<?php
/**
 * @package Plausible Analytics Integration Tests - Helpers
 */

namespace Plausible\Analytics\Tests\Integration;

use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\AdminBar;
use WP_Admin_Bar;

class AdminBarTest extends TestCase {
	public function testAddAnalyticsNode() {
		try {
			add_filter( 'plausible_analytics_settings', [ $this, 'enableAnalyticsDashboard' ] );

			$class = $this->getMockBuilder( AdminBar::class )
			              ->disableOriginalConstructor()
			              ->onlyMethods( [ 'is_singular' ] )
			              ->getMock();

			$class->method( 'is_singular' )->willReturn( true );

			if ( ! class_exists( 'WP_Admin_Bar' ) ) {
				require_once( ABSPATH . 'wp-includes/class-wp-admin-bar.php' );
			}

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

			$args = $class->maybe_add_analytics( [], [ 'enable_analytics_dashboard' => 'on' ] );
			$this->assertNotEmpty( $args );
			$this->assertCount( 2, $args );
			$this->assertEquals( 'view-analytics-default', $args[0]['id'] );
			$this->assertEquals( 'view-page-analytics', $args[1]['id'] );
		} finally {
			remove_filter( 'plausible_analytics_settings', [ $this, 'enableAnalyticsDashboard' ] );
			wp_set_current_user( null );
			unset( $post );
		}
	}

	/**
	 * @see AdminBar::admin_bar_node()
	 */
	public function testAdminBarNode() {
		$class = new AdminBar();

		if ( ! class_exists( 'WP_Admin_Bar' ) ) {
			require_once( ABSPATH . 'wp-includes/class-wp-admin-bar.php' );
		}

		wp_set_current_user( 1 );
		$user = wp_get_current_user();
		$user->add_role( 'administrator' );
		$admin_bar = new WP_Admin_Bar();
		$class->admin_bar_node( $admin_bar );
		$this->assertNotEmpty( $admin_bar->get_node( 'plausible-analytics' ) );

		wp_set_current_user( null );
		$admin_bar = new WP_Admin_Bar();
		$class->admin_bar_node( $admin_bar );
		$this->assertEmpty( $admin_bar->get_node( 'plausible-analytics' ) );
	}

	public function testMaybeAddAnalyticsWithMultipleDomains() {
		$class = new AdminBar();

		$language_domains = [
			'default' => 'example.com',
			'nl'      => 'nl.example.com',
		];
		$callback         = function () use ( $language_domains ) {
			return $language_domains;
		};
		add_filter( 'plausible_analytics_language_domains', $callback );

		$settings = [
			'enable_analytics_dashboard' => 'on',
			'shared_link'                => [
				'nl' => 'https://plausible.io/share/nl.example.com?auth=token',
			],
		];

		$args = $class->maybe_add_analytics( [], $settings );

		remove_filter( 'plausible_analytics_language_domains', $callback );

		$this->assertCount( 2, $args );
		$this->assertEquals( 'view-analytics-default', $args[0]['id'] );
		$this->assertEquals( 'view-analytics-nl', $args[1]['id'] );
		$this->assertStringContainsString( 'domain=nl', $args[1]['href'] );
	}
}
