<?php
/**
 * @package Plausible Analytics Integration Tests - Helpers
 */

namespace Plausible\Analytics\Tests\Integration;

use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\Actions;
use Plausible\Analytics\WP\Helpers;

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
}
