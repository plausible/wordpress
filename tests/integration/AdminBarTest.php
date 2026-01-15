<?php
/**
 * @package Plausible Analytics Integration Tests - Helpers
 */

namespace Plausible\Analytics\Tests\Integration;

use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\AdminBar;
use WP_Admin_Bar;

class AdminBarTest extends TestCase {
	/**
	 * @see AdminBar::admin_bar_node()
	 */
	public function testAdminBarNode() {
		$class = new AdminBar();

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
