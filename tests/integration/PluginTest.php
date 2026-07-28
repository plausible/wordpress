<?php
/**
 * @package Plausible Analytics Integration Tests - Plugin
 */

namespace Plausible\Analytics\Tests\Integration;

use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\AdminBar;
use Plausible\Analytics\WP\Plugin;

class PluginTest extends TestCase {
	/**
	 * A service whose file is missing (e.g. because WordPress is mid-update) should be skipped
	 * instead of failing the request.
	 *
	 * @see Plugin::load_service()
	 */
	public function testLoadServiceSkipsMissingClass() {
		$class  = new Plugin();
		$method = new \ReflectionMethod( $class, 'load_service' );
		$method->setAccessible( true );

		$this->assertNull( $method->invoke( $class, '\Plausible\Analytics\WP\ThisClassDoesNotExist' ) );
		$this->assertInstanceOf( AdminBar::class, $method->invoke( $class, AdminBar::class ) );
	}

	/**
	 * @see Plugin::register()
	 */
	public function testRegister() {
		$class = new Plugin();
		$class->register();

		define( 'WP_ADMIN', true );
		do_action( 'plugins_loaded' );

		$this->assertTrue( class_exists( '\Plausible\Analytics\WP\Admin\Provisioning' ) );
	}
}
