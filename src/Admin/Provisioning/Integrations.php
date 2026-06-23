<?php
/**
 * Plausible Analytics | Provisioning | Integrations
 * @since      2.3.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Admin\Provisioning;

use Plausible\Analytics\WP\Admin\Provisioning;

class Integrations {
	/**
	 * @var Provisioning
	 */
	private $provisioning;

	/**
	 * Build class.
	 *
	 * We use DI to prevent circular dependency.
	 *
	 * @param Provisioning|null $provisioning
	 *
	 * @codeCoverageIgnore
	 */
	public function __construct( $provisioning = null ) {
		$this->provisioning = $provisioning;

		if ( ! $this->provisioning ) {
			$this->provisioning = new Provisioning();
		}

		$this->init();
	}

	/**
	 * Action & filter hooks.
	 *
	 * We use Dependency Injection to prevent circular dependency.
	 *
	 * @return void
	 * @codeCoverageIgnore This is merely a wrapper to load classes. No need to test.
	 */
	private function init() {
		new Integrations\WooCommerce( $this );
		new Integrations\EDD( $this );
	}

	/**
	 * @param array  $event_goals
	 * @param string $funnel_name
	 *
	 * @return void
	 * @codeCoverageIgnore We don't want to test the API.
	 */
	public function create_integration_funnel( $event_goals, $funnel_name ) {
		$goals = [];

		foreach ( $event_goals as $event_key => $event_goal ) {
			if ( $event_key === 'remove-from-cart' ) {
				foreach ( $this->provisioning->get_clients() as $key => $client ) {
					$this->provisioning->create_goals( [ $this->provisioning->create_goal_request( $event_goal ) ], $client, $key );
				}

				continue;
			}

			if ( $event_key === 'purchase' ) {
				$currency = \Plausible\Analytics\WP\Integrations::is_edd_active() ? edd_get_currency() : get_woocommerce_currency();
				$goals[]  = $this->provisioning->create_goal_request( $event_goal, 'Revenue', $currency );

				continue;
			}

			if ( $event_key === 'view-product' ) {
				$path    = preg_replace( '/^.*?\//', '', $event_goal );
				$goals[] = $this->provisioning->create_goal_request( $event_goal, 'Pageview', null, '/' . $path );

				continue;
			}

			$goals[] = $this->provisioning->create_goal_request( $event_goal );
		}

		foreach ( $this->provisioning->get_clients() as $key => $client ) {
			$this->provisioning->create_funnel( $funnel_name, $goals, $client, $key );
		}
	}

	/**
	 * Deletes the integration-specific goals using the stored goal IDs.
	 *
	 * @param object $integration The integration object containing event goals to be deleted.
	 *
	 * @return void
	 */
	public function delete_integration_goals( $integration ) {
		$all_ids = $this->provisioning->normalize_option(
			get_option( 'plausible_analytics_enhanced_measurements_goal_ids', [] )
		);

		foreach ( $this->provisioning->get_clients() as $domain_key => $client ) {
			$goals = $all_ids[ $domain_key ] ?? [];

			foreach ( $goals as $id => $name ) {
				$key = $this->provisioning->array_search_contains( $name, $integration->event_goals );

				if ( $key ) {
					$client->delete_goal( $id );
					unset( $goals[ $id ] );
				}
			}

			$all_ids[ $domain_key ] = $goals;
		}

		update_option( 'plausible_analytics_enhanced_measurements_goal_ids', $all_ids );
	}
}
