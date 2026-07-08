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

		if ( ! defined( 'PLAUSIBLE_CI' ) ) {
			define( 'PLAUSIBLE_CI', true );
		}

		parent::__construct();
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

	/**
	 * Checks an array for a (partial) match with $string.
	 *
	 * @param $string string Needle.
	 * @param $array  array Haystack.
	 *
	 * @return bool
	 */
	public function arrayHasString( $string, $array ) {
		foreach ( $array as $element ) {
			if ( str_contains( $element, $string ) ) {
				return true;
			}
		}

		return false;
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

	public function enableAdministratorTracking( $settings ) {
		$settings['tracked_user_roles'][] = 'administrator';

		return $settings;
	}

	/**
	 * Enable View Stats in WordPress.
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function enableAnalyticsDashboard( $settings ) {
		$settings['enable_analytics_dashboard'] = 'on';

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
		$settings['affiliate_links'] = [ '/recommends/' ];

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
	 * Enable the Query Params option by modifying the settings array.
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function enableQueryParams( $settings ) {
		$settings['query_params'] = [ 'lang' ];

		return $settings;
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
	 * Removes any action that (partially) matches the given $callback.
	 *
	 * @param $hook
	 * @param $callback
	 * @param $priority
	 *
	 * @return void
	 */
	public function removeAction( $hook, $callback, $priority = 10 ) {
		global $wp_filter;

		if ( ! isset( $wp_filter[ $hook ] ) ) {
			return;
		}

		$callbacks = $wp_filter[ $hook ]->callbacks ?? [];
		$callbacks = $callbacks[ $priority ] ?? [];

		foreach ( array_keys( $callbacks ) as $callback_key ) {
			if ( str_contains( $callback_key, $callback ) ) {
				unset( $wp_filter[ $hook ]->callbacks[ $priority ][ $callback_key ] );
			}
		}
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
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function setExcludePageview( $settings ) {
		$settings['excluded_pages'] = "/checkout*,utm_\n*.example.com";

		return $settings;
	}

	/**
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function setExcludePageviewEdgeCaseAsterisk( $settings ) {
		$settings['excluded_pages'] = '*';

		return $settings;
	}

	/**
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function setExcludePageviewEdgeCaseSpace( $settings ) {
		$settings['excluded_pages'] = ' ';

		return $settings;
	}

	/**
	 * Set some test query params.
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function setQueryParams( $settings ) {
		$settings['query_params'] = [ 'test' ];

		$_REQUEST['test'] = 1;

		return $settings;
	}
}
