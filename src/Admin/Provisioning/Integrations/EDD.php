<?php
/**
 * Plausible Analytics | Provisioning | Integrations | EDD
 * @since      2.3.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Admin\Provisioning\Integrations;

use Plausible\Analytics\WP\Admin\Provisioning;
use Plausible\Analytics\WP\EnhancedMeasurements;
use Plausible\Analytics\WP\Integrations;

class EDD {
	/**
	 * @var Provisioning\Integrations $integrations
	 */
	private $integrations;

	/**
	 * @return void
	 *
	 * @codeCoverageIgnore
	 */
	public function __construct( $integrations ) {
		$this->integrations = $integrations;

		$this->init();
	}

	/**
	 * Action and filter hooks.
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore
	 */
	private function init() {
		add_action( 'update_option_plausible_analytics_settings', [ $this, 'maybe_create_edd_funnel' ], 10, 2 );
		add_action( 'update_option_plausible_analytics_settings', [ $this, 'maybe_delete_edd_goals' ], 11, 2 );
	}

	/**
	 * Creates an EDD purchase funnel if enhanced measurement is enabled and EDD is active.
	 *
	 * @param array $old_settings The previous settings before the update.
	 * @param array $settings The updated settings array.
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore Because it interacts with the Plugins API
	 */
	public function maybe_create_edd_funnel( $old_settings, $settings ) {
		if ( ! EnhancedMeasurements::is_enabled( EnhancedMeasurements::ECOMMERCE_REVENUE, $settings['enhanced_measurements'] ) || ! Integrations::is_edd_active() ) {
			return; // @codeCoverageIgnore
		}

		$edd = new Integrations\EDD( false );

		$this->integrations->create_integration_funnel( $edd->event_goals, __( 'EDD Purchase Funnel', 'plausible-analytics' ) );
	}

	/**
	 * * Delete all custom EDD event goals if Revenue setting is disabled. The funnel is deleted when the minimum
	 * * required no. of goals is no longer met.
	 *
	 * @param array $old_settings The previous settings before the update.
	 * @param array $settings The current updated settings.
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore Because it interacts with the Plugins API.
	 */
	public function maybe_delete_edd_goals( $old_settings, $settings ) {
		$enhanced_measurements = array_filter( $settings['enhanced_measurements'] );

		if ( EnhancedMeasurements::is_enabled( EnhancedMeasurements::ECOMMERCE_REVENUE, $enhanced_measurements ) ) {
			return;
		}

		$edd_integration = new Integrations\EDD( false );

		$this->integrations->delete_integration_goals( $edd_integration );
	}
}
