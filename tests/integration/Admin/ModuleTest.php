<?php
/**
 * @package Plausible Analytis integration tests - Module
 */

namespace Plausible\Analytics\Tests\Integration\Admin;

use Exception;
use Plausible\Analytics\Tests\TestableHelpers;
use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\Admin\Module;
use Plausible\Analytics\WP\Helpers;
use Plausible\Analytics\WP\Proxy;

class ModuleTest extends TestCase {
	/**
	 * @see Module::install()
	 */
	public function testInstallModule() {
		try {
			add_filter( 'plausible_analytics_settings', [ $this, 'disableProxy' ] );

			$old_settings = Helpers::get_settings();
		} finally {
			remove_filter( 'plausible_analytics_settings', [ $this, 'disableProxy' ] );
		}

		try {
			add_filter( 'plausible_analytics_settings', [ $this, 'enableProxy' ] );

			$settings = Helpers::get_settings();
		} finally {
			remove_filter( 'plausible_analytics_settings', [ $this, 'enableProxy' ] );
		}

		$this->addUserCap( 'install_plugins' );

		$class = new Module();
		$class->maybe_install_module( $old_settings, $settings );

		$this->assertTrue( get_option( 'plausible_analytics_proxy_speed_module_installed' ) );
	}

	/**
	 * @see Module::uninstall()
	 */
	public function testUninstallModule() {
		try {
			add_filter( 'plausible_analytics_settings', [ $this, 'enableProxy' ] );

			$old_settings = Helpers::get_settings();
		} finally {
			remove_filter( 'plausible_analytics_settings', [ $this, 'enableProxy' ] );
		}

		try {
			add_filter( 'plausible_analytics_settings', [ $this, 'disableProxy' ] );

			$settings = Helpers::get_settings();
		} finally {
			remove_filter( 'plausible_analytics_settings', [ $this, 'disableProxy' ] );
		}

		$this->addUserCap( 'install_plugins' );

		$module = $this->getMockBuilder( Module::class )
		               ->onlyMethods( [ 'get_filename', 'dir_is_empty' ] )
		               ->getMock();

		$module->method( 'get_filename' )
		       ->willReturn( 'test-file' );

		$module->method( 'dir_is_empty' )
		       ->willReturn( true );

		$module->maybe_install_module( $old_settings, $settings );

		$this->assertFalse( get_option( 'plausible_analytics_proxy_speed_module_installed' ) );
	}

	/**
	 * @see Module::maybe_enable_proxy()
	 * @throws Exception
	 */
	public function testEnableProxy() {
		try {
			add_filter( 'plausible_analytics_settings', [ $this, 'enableProxy' ] );

			new Proxy();

			$settings = Helpers::get_settings();
		} finally {
			remove_filter( 'plausible_analytics_settings', [ $this, 'enableProxy' ] );
		}

		try {
			add_filter( 'plausible_analytics_module_run_test_proxy', '__return_true' );

			$old_settings = Helpers::get_settings();
			$class        = new Module();
			$new_settings = $class->maybe_enable_proxy( $settings, $old_settings );

			$this->assertEquals( 'on', $new_settings['proxy_enabled'] );
		} finally {
			remove_filter( 'plausible_analytics_module_run_test_proxy', '__return_true' );
		}
	}
}
