<?php

namespace Plausible\Analytics\Tests;

use Plausible\Analytics\WP\EnhancedMeasurements;
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
		$settings['enhanced_measurements'] = [ EnhancedMeasurements::ECOMMERCE_REVENUE ];

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
		$settings['enhanced_measurements'] = [ EnhancedMeasurements::FORM_COMPLETIONS ];

		return $settings;
	}

	/**
	 * Enable Cloaked Affiliate Links by modifying the settings array.
	 *
	 * @param $settings
	 *
	 * @return void
	 */
	public function enableCloakedAffiliateLinks( $settings ) {
		$settings['enhanced_measurements'] = [ EnhancedMeasurements::CLOAKED_AFFILIATE_LINKS ];

		return $settings;
	}

	/**
	 * Enable the 404 option by modifying the settings array.
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function enableFourOFour( $settings ) {
		$settings['enhanced_measurements'] = [ EnhancedMeasurements::FOUR_O_FOUR ];

		return $settings;
	}

	/**
	 * Enable the Query Params option by modifying the settings array.
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function enableQueryParams( $settings ) {
		$settings['enhanced_measurements'] = [ EnhancedMeasurements::QUERY_PARAMS ];

		return $settings;
	}

	/**
	 * Enable the Search Queries option by modifying the settings array.
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function enableSearchQueries( $settings ) {
		$settings['enhanced_measurements'] = [ EnhancedMeasurements::SEARCH_QUERIES ];

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
		$settings['proxy_enabled'] = 'on';

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
		$settings['domain_name'] = 'test.dev';

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
		$settings['enhanced_measurements'] = [ 'pageview-props' ];

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
