<?php
/**
 * @package Plausible Analytics Integration Tests - Plugin
 */

namespace Plausible\Analytics\Tests\Integration;

use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\Plugin;

class PluginTest extends TestCase {
	/**
	 * @see Plugin::register()
	 */
	public function testRegister() {
		$class = new Plugin();
		$class->register();

		define( 'WP_ADMIN', true );
		do_action( 'plugins_loaded' );

		$this->assertTrue( class_exists( '\Plausible\Analytics\WP\Admin\SelfHosted' ) );
	}
}
