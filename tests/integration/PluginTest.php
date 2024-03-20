<?php
/**
 * @package Plausible Analytics Integration Tests - Plugin
 */

namespace Plausible\Analytics\Tests\Integration;

use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\Plugin;

class PluginTest extends TestCase {
	/**
	 *
	 */
	public function testRegister() {
		$class = new Plugin();
		$class->register();

		do_action( 'plugins_loaded' );

		$this->assertTrue( class_exists( '\Plausible\Analytics\WP\Setup' ) );

		define( 'WP_ADMIN', true );

		$class->register();

		do_action( 'plugins_loaded' );

		$this->assertTrue( class_exists( '\Plausible\Analytics\WP\Admin\SelfHosted' ) );
	}
}
