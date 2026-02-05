<?php

/**
 * Plausible Analytics | Integrations
 * @since      2.1.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP;

class Integrations {
	const PURCHASE_TRACKED_META_KEY = '_plausible_analytics_purchase_tracked';

	const SCRIPT_WRAPPER = '<script defer id="plausible-analytics-integration-tracking">document.addEventListener("DOMContentLoaded", () => { %s });</script>';

	/**
	 * Build class.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Run available integrations.
	 * @return void
	 */
	private function init() {
		// WooCommerce
		if ( self::is_wc_active() && EnhancedMeasurements::is_enabled( EnhancedMeasurements::ECOMMERCE_REVENUE ) ) {
			new Integrations\WooCommerce(); // @codeCoverageIgnore
		}

		// Easy Digital Downloads
		if ( self::is_edd_active() && EnhancedMeasurements::is_enabled( EnhancedMeasurements::ECOMMERCE_REVENUE ) ) {
			new Integrations\EDD(); // @codeCoverageIgnore
		}

		// Form Plugins
		if ( EnhancedMeasurements::is_enabled( EnhancedMeasurements::FORM_COMPLETIONS ) ) {
			new Integrations\FormSubmit(); // @codeCoverageIgnore
		}

		// Search Queries
		if ( EnhancedMeasurements::is_enabled( EnhancedMeasurements::SEARCH_QUERIES ) ) {
			new Integrations\Search(); // @codeCoverageIgnore
		}
	}

	/**
	 * Checks if WooCommerce is installed and activated.
	 * @return bool
	 */
	public static function is_wc_active() {
		return apply_filters( 'plausible_analytics_integrations_woocommerce', function_exists( 'WC' ) );
	}

	/**
	 * Checks if Easy Digital Downloads is installed and activated.
	 * @return bool
	 */
	public static function is_edd_active() {
		return apply_filters( 'plausible_analytics_integrations_edd', function_exists( 'EDD' ) );
	}

	/**
	 * Checks if EDD Recurring is installed and activated.
	 * @return mixed|null
	 */
	public static function is_edd_recurring_active() {
		return apply_filters( 'plausible_analytics_integrations_edd_recurring', function_exists( 'EDD_Recurring' ) );
	}
}
