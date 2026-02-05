<?php
/**
 * Plausible Analytics | Provisioning | Integrations | WooCommerce
 * @since      2.3.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Admin\Provisioning\Integrations;

use Plausible\Analytics\WP\Admin\Provisioning;
use Plausible\Analytics\WP\EnhancedMeasurements;
use Plausible\Analytics\WP\Integrations;

class WooCommerce {
	/**
	 * @var Provisioning\Integrations $integrations
	 */
	private $integrations;

	/**
	 * Build class.
	 *
	 * @codeCoverageIgnore
	 */
	public function __construct( $integrations ) {
		$this->integrations = $integrations;

		$this->init();
	}

	/**
	 * Action & filters hooks.
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore
	 */
	private function init() {
		add_action( 'update_option_plausible_analytics_settings', [ $this, 'maybe_create_woocommerce_funnel' ], 10, 2 );
		add_action( 'update_option_plausible_analytics_settings', [ $this, 'maybe_delete_woocommerce_goals' ], 11, 2 );
	}

	/**
	 * Checks whether the WooCommerce funnel should be created based on the provided settings
	 * and creates the funnel if the conditions are met.
	 *
	 * @param array $old_settings The previous settings before the update.
	 * @param array $settings The updated settings to check for enhanced measurement and WooCommerce integration.
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore Because it interacts with the Plugins API.
	 */
	public function maybe_create_woocommerce_funnel( $old_settings, $settings ) {
		if ( ! EnhancedMeasurements::is_enabled( EnhancedMeasurements::ECOMMERCE_REVENUE, $settings['enhanced_measurements'] ) || ! Integrations::is_wc_active() ) {
			return; // @codeCoverageIgnore
		}

		$woocommerce = new Integrations\WooCommerce( false );

		$this->integrations->create_integration_funnel( $woocommerce->event_goals, __( 'Woo Purchase Funnel', 'plausible-analytics' ) );
	}

	/**
	 * Delete all custom WooCommerce event goals if Revenue setting is disabled. The funnel is deleted when the minimum
	 * required no. of goals is no longer met.
	 *
	 * @param $old_settings
	 * @param $settings
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore Because we don't want to test if the API is working.
	 */
	public function maybe_delete_woocommerce_goals( $old_settings, $settings ) {
		$enhanced_measurements = array_filter( $settings['enhanced_measurements'] );

		// Setting is enabled, no need to continue.
		if ( EnhancedMeasurements::is_enabled( EnhancedMeasurements::ECOMMERCE_REVENUE, $enhanced_measurements ) || ! Integrations::is_wc_active() ) {
			return;
		}

		$woo_integration = new Integrations\WooCommerce( false );

		$this->integrations->delete_integration_goals( $woo_integration );
	}
}
