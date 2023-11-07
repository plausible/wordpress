<?php
/**
 * Plausible Analytics | Provisioning.
 * @since      2.0.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Admin;

use Plausible\Analytics\WP\Client;

class Provisioning {
	/**
	 * @var Client $client
	 */
	private $client;

	/**
	 * @var string[] $custom_event_goals
	 */
	private $custom_event_goals = [
		'404'            => '404',
		'outbound-links' => 'Outbound Link: Click',
		'file-downloads' => 'File Download',
	];

	/**
	 * Build class.
	 */
	public function __construct() {
		$this->client = new Client();

		$this->init();
	}

	/**
	 * Action & filter hooks.
	 * @return void
	 */
	private function init() {
		add_action( 'update_option_plausible_analytics_settings', [ $this, 'create_shared_link' ], 10, 2 );
		add_action( 'update_option_plausible_analytics_settings', [ $this, 'create_goals' ], 10, 2 );
		add_action( 'update_option_plausible_analytics_settings', [ $this, 'maybe_delete_goals' ], 11, 2 );
	}

	/**
	 * Create shared link when Enable Analytics Dashboard option is enabled.
	 *
	 * @param $old_settings
	 * @param $settings
	 *
	 * @return void
	 */
	public function create_shared_link( $old_settings, $settings ) {
		if ( empty( $settings['enable_analytics_dashboard'][0] ) ) {
			return;
		}

		$this->client->create_shared_link();
	}

	/**
	 * Create Custom Event Goals for enabled Enhanced Measurements.
	 *
	 * @param $old_settings
	 * @param $settings
	 *
	 * @return void
	 */
	public function create_goals( $old_settings, $settings ) {
		$enhanced_measurements = array_filter( $settings['enhanced_measurements'] );

		if ( empty( $enhanced_measurements ) ) {
			return;
		}

		$custom_event_keys = array_keys( $this->custom_event_goals );

		foreach ( $enhanced_measurements as $measurement ) {
			if ( ! in_array( $measurement, $custom_event_keys ) ) {
				continue;
			}

			$goal = new Client\Model\GoalCreateRequestCustomEvent(
				[
					'goal'      => [
						'event_name' => $this->custom_event_goals[ $measurement ],
					],
					'goal_type' => 'Goal.CustomEvent',
				]
			);

			$this->client->create_goal( $goal );
		}
	}

	/**
	 * Delete Custom Event Goals when an Enhanced Measurement is disabled.
	 *
	 * @param $old_settings
	 * @param $settings
	 *
	 * @return void
	 */
	public function maybe_delete_goals( $old_settings, $settings ) {
		$enhanced_measurements_old = array_filter( $old_settings['enhanced_measurements'] );
		$enhanced_measurements     = array_filter( $settings['enhanced_measurements'] );

		$disabled_settings = array_diff( $enhanced_measurements_old, $enhanced_measurements );

		if ( empty( $disabled_settings ) ) {
			return;
		}

		// $goals             = $this->client->retrieve_goals();
		$custom_event_keys = array_keys( $this->custom_event_goals );

		foreach ( $disabled_settings as $disabled_setting ) {
			if ( ! in_array( $disabled_setting, $custom_event_keys ) ) {
				continue;
			}

			// $this->client->delete_goal( $id );
		}
	}
}
