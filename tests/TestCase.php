<?php

namespace Plausible\Analytics\Tests;

use Yoast\WPTestUtils\BrainMonkey\TestCase as YoastTestCase;

class TestCase extends YoastTestCase {
	public function __construct() {
		/**
		 * During local unit testing this constant is required.
		 */
		if ( ! defined( 'ABSPATH' ) ) {
			define( 'ABSPATH', true );
		}

		/**
		 * Required for loading assets.
		 */
		if ( ! defined( 'PLAUSIBLE_TESTS_ROOT' ) ) {
			define( 'PLAUSIBLE_TESTS_ROOT', __DIR__ . '/' );
		}

		parent::__construct();
	}

	/**
	 * Enable Enhanced Measurements > Custom Events (Tagged Events)
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function enableRevenue( $settings ) {
		$settings[ 'enhanced_measurements' ] = [ 'revenue' ];

		return $settings;
	}

	/**
	 * Enable Enhanced Measurements > IE Compatibility
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function enableCompat( $settings ) {
		$settings[ 'enhanced_measurements' ] = [ 'compat' ];

		return $settings;
	}

	/**
	 * Enable form completions by modifying the settings array.
	 *
	 * @param array $settings The settings array to be modified.
	 *
	 * @return array The modified settings array including form completions.
	 */
	public function enableFormCompletions( $settings ) {
		$settings[ 'enhanced_measurements' ] = [ 'form-completions' ];

		return $settings;
	}

	/**
	 * Enable the proxy.
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function enableProxy( $settings ) {
		$settings[ 'proxy_enabled' ] = 'on';

		return $settings;
	}

	/**
	 * Dynamically disable the proxy.
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function disableProxy( $settings ) {
		$settings['proxy_enabled'] = '';

		return $settings;
	}

	/**
	 * Set domain_name option.
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function setDomain( $settings ) {
		$settings[ 'domain_name' ] = 'test.dev';

		return $settings;
	}

	/**
	 * Enable Enhanced Measurements > Categories & Authors.
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function enablePageviewProps( $settings ) {
		$settings[ 'enhanced_measurements' ] = [ 'pageview-props' ];

		return $settings;
	}

	/**
	 * Add user capability for testing.
	 *
	 * @return void
	 */
	public function addUserCap( $cap ) {
		add_filter(
			'user_has_cap',
			function ( $caps ) use ( $cap ) {
				return array_merge( $caps, [ $cap => true ] );
			}
		);
	}
}
