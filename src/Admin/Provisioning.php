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
	 * @todo Add error handling and throw a notice if creating the goal failed.
	 */
	public function create_goals( $old_settings, $settings ) {
		$enhanced_measurements = array_filter( $settings['enhanced_measurements'] );

		if ( empty( $enhanced_measurements ) ) {
			return;
		}

		$custom_event_keys = array_keys( $this->custom_event_goals );

		foreach ( $enhanced_measurements as $i => $measurement ) {
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
}
