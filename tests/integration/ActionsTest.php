<?php
/**
 * @package Plausible Analytics Integration Tests - Helpers
 */

namespace Plausible\Analytics\Tests\Integration;

use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\Actions;
use Plausible\Analytics\WP\Helpers;
use WP_Admin_Bar;

class ActionsTest extends TestCase {
	/**
	 * @see Actions::maybe_register_assets()
	 * @return void
	 * @throws \Exception
	 */
	public function testRegisterAssets() {
		$class = new Actions();

		add_filter( 'plausible_analytics_settings', [ $this, 'enableProxy' ] );
		add_filter( 'plausible_analytics_settings', [ $this, 'setDomain' ] );

		$class->maybe_register_assets();

		$this->expectOutputContains( Helpers::get_filename( true ) );
		$this->expectOutputContains( 'test.dev' );
		$this->expectOutputContains( Helpers::get_rest_endpoint() );

		wp_print_head_scripts();

		remove_filter( 'plausible_analytics_settings', [ $this, 'enableProxy' ] );
		remove_filter( 'plausible_analytics_settings', [ $this, 'setDomain' ] );
	}

	/**
	 * @see Actions::admin_bar_node()
	 */
	public function testAdminBarNode() {
		$class = new Actions();

		if ( ! class_exists( 'WP_Admin_Bar' ) ) {
			require_once( ABSPATH . 'wp-includes/class-wp-admin-bar.php' );
		}

		$admin_bar = new WP_Admin_Bar();

		$class->admin_bar_node( $admin_bar );

		$this->assertNotEmpty( $admin_bar->get_node( 'plausible-analytics' ) );
	}
}
