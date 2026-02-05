<?php
/**
 * Plausible Analytics | Provisioning.
 * @since      2.0.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Admin;

use Plausible\Analytics\WP\Capabilities;
use Plausible\Analytics\WP\Client;
use Plausible\Analytics\WP\Client\ApiException;
use Plausible\Analytics\WP\Client\Model\GoalCreateRequestCustomEvent;
use Plausible\Analytics\WP\Client\Model\GoalCreateRequestPageview;
use Plausible\Analytics\WP\Client\Model\GoalCreateRequestRevenue;
use Plausible\Analytics\WP\ClientFactory;
use Plausible\Analytics\WP\EnhancedMeasurements;
use Plausible\Analytics\WP\Helpers;
use Plausible\Analytics\WP\Integrations;

class Provisioning {
	const CUSTOM_PROPERTIES = [
		'cart_total',
		'cart_total_items',
		'id',
		'name',
		'price',
		'product_id',
		'product_name',
		'quantity',
		'shipping',
		'subtotal',
		'subtotal_tax',
		'tax_class',
		'total',
		'total_tax',
		'variation_id',
	];

	/**
	 * @var Client $client
	 */
	public $client;

	/**
	 * @var ClientFactory
	 */
	private $client_factory;

	/**
	 * @var string[] $custom_event_goals
	 */
	private $custom_event_goals = [];

	/**
	 * @var string[] $custom_pageview_properties
	 */
	private $custom_pageview_properties = [
		'author',
		'category',
		'user_logged_in',
	];

	/**
	 * @var string[] $custom_search_properties
	 */
	private $custom_search_properties = [
		'search_query',
		'result_count',
		'search_source',
	];

	/**
	 * Build class.
	 *
	 * @param bool|Client $client Allows for mocking during CI.
	 *
	 * @throws ApiException
	 * @codeCoverageIgnore
	 */
	public function __construct( $client = null ) {
		$this->client = $client;

		if ( ! $this->client ) {
			$this->client_factory = new ClientFactory();
			$this->client         = $this->client_factory->build();
		}

		$this->custom_event_goals = [
			EnhancedMeasurements::FOUR_O_FOUR             => __( '404', 'plausible-analytics' ),
			EnhancedMeasurements::CLOAKED_AFFILIATE_LINKS => __( 'Cloaked Link: Click', 'plausible-analytics' ),
			EnhancedMeasurements::FORM_COMPLETIONS        => __( 'WP Form Completions', 'plausible-analytics' ),
			EnhancedMeasurements::QUERY_PARAMS            => __( 'WP Query Parameters', 'plausible-analytics' ),
			EnhancedMeasurements::SEARCH_QUERIES          => __( 'WP Search Queries', 'plausible-analytics' ),
		];

		$this->init();
	}

	/**
	 * Action & filter hooks.
	 * @return void
	 * @throws ApiException
	 * @codeCoverageIgnore
	 */
	private function init() {
		if ( ! $this->client instanceof Client || ! $this->client->validate_api_token() ) {
			return; // @codeCoverageIgnore
		}

		add_action( 'update_option_plausible_analytics_settings', [ $this, 'maybe_create_shared_link' ], 10, 2 );
		add_action( 'update_option_plausible_analytics_settings', [ $this, 'maybe_create_goals' ], 10, 2 );
		add_action( 'update_option_plausible_analytics_settings', [ $this, 'maybe_delete_goals' ], 11, 2 );
		add_action( 'update_option_plausible_analytics_settings', [ $this, 'maybe_create_custom_properties' ], 11, 2 );
		add_filter( 'pre_update_option_plausible_analytics_settings', [ $this, 'maybe_enable_customer_user_roles' ] );
		add_action( 'update_option_plausible_analytics_settings', [ $this, 'update_tracker_script_config' ], 10, 2 );
	}

	/**
	 * Create shared link when Enable Analytics Dashboard option is enabled.
	 *
	 * @param $old_settings
	 * @param $settings
	 */
	public function maybe_create_shared_link( $old_settings, $settings ) {
		if ( empty( $settings['enable_analytics_dashboard'] ) ) {
			return; // @codeCoverageIgnore
		}

		$this->client->create_shared_link();
	}

	/**
	 * Create Custom Event Goals for enabled Enhanced Measurements.
	 *
	 * @param $old_settings
	 * @param $settings
	 */
	public function maybe_create_goals( $old_settings, $settings ) {
		$enhanced_measurements = array_filter( $settings['enhanced_measurements'] );

		if ( empty( $enhanced_measurements ) ) {
			return; // @codeCoverageIgnore
		}

		$custom_event_keys = array_keys( $this->custom_event_goals );
		$goals             = [];

		foreach ( $enhanced_measurements as $measurement ) {
			if ( ! in_array( $measurement, $custom_event_keys ) ) {
				continue; // @codeCoverageIgnore
			}

			$goals[] = $this->create_goal_request( $this->custom_event_goals[ $measurement ] );
		}

		$this->create_goals( $goals );
	}

	/**
	 * @param string $name Event Name
	 * @param string $type CustomEvent|Revenue|Pageview
	 * @param string $currency Required if $type is Revenue
	 *
	 * @return GoalCreateRequestCustomEvent|GoalCreateRequestPageview|GoalCreateRequestRevenue
	 */
	public function create_goal_request( $name, $type = 'CustomEvent', $currency = '', $path = '' ) {
		$props = [
			'goal'      => [
				'event_name' => $name,
			],
			'goal_type' => "Goal.$type",
		];

		if ( $type === 'Revenue' ) {
			$props['goal']['currency'] = $currency;
		}

		if ( $type === 'Pageview' ) {
			unset( $props['goal']['event_name'] );

			$props['goal']['path'] = $path;
		}

		switch ( $type ) {
			case 'Pageview':
				return new Client\Model\GoalCreateRequestPageview( $props );
			case 'Revenue':
				return new Client\Model\GoalCreateRequestRevenue( $props );
			default: // CustomEvent
				return new Client\Model\GoalCreateRequestCustomEvent( $props );
		}
	}

	/**
	 * Create the goals using the API client and updates the IDs in the database.
	 *
	 * @param array $goals
	 *
	 * @return void
	 */
	public function create_goals( $goals ) {
		if ( empty( $goals ) ) {
			return; // @codeCoverageIgnore
		}

		$create_request = new Client\Model\GoalCreateRequestBulkGetOrCreate();
		$create_request->setGoals( $goals );
		$response = $this->client->create_goals( $create_request );

		if ( $response->valid() ) {
			$goals = $response->getGoals();
			$ids   = get_option( 'plausible_analytics_enhanced_measurements_goal_ids', [] );

			foreach ( $goals as $goal ) {
				$goal                  = $goal->getGoal();
				$ids[ $goal->getId() ] = $goal->getDisplayName();
			}

			if ( ! empty( $ids ) ) {
				update_option( 'plausible_analytics_enhanced_measurements_goal_ids', $ids );
			}
		}
	}

	/**
	 * Creates a funnel and creates goals if they don't exist.
	 *
	 * @param $name
	 * @param $steps
	 *
	 * @return void
	 * @codeCoverageIgnore Because this method should be mocked in tests if needed.
	 */
	public function create_funnel( $name, $steps ) {
		$create_request = new Client\Model\FunnelCreateRequest(
			[
				'funnel' => [
					'name'  => $name,
					'steps' => $steps,
				],
			]
		);

		$funnel = $this->client->create_funnel( $create_request );

		if ( ! $funnel instanceof Client\Model\Funnel || ! $funnel->valid() ) {
			return;
		}

		$ids   = get_option( 'plausible_analytics_enhanced_measurements_goal_ids', [] );
		$steps = $funnel->getFunnel()->getSteps();

		foreach ( $steps as $step ) {
			$goal = $step->getGoal();

			if ( ! empty( $goal ) ) {
				$ids[ $goal->getId() ] = $goal->getDisplayName();
			}
		}

		if ( ! empty( $ids ) ) {
			update_option( 'plausible_analytics_enhanced_measurements_goal_ids', $ids );
		}
	}

	/**
	 * Delete Custom Event Goals when an Enhanced Measurement is disabled.
	 *
	 * @param $old_settings
	 * @param $settings
	 *
	 * @codeCoverageIgnore Because we don't want to test if the API is working.
	 */
	public function maybe_delete_goals( $old_settings, $settings ) {
		$enhanced_measurements_old = array_filter( $old_settings['enhanced_measurements'] );
		$enhanced_measurements     = array_filter( $settings['enhanced_measurements'] );
		$disabled_settings         = array_diff( $enhanced_measurements_old, $enhanced_measurements );

		if ( empty( $disabled_settings ) ) {
			return;
		}

		$goals = get_option( 'plausible_analytics_enhanced_measurements_goal_ids', [] );

		foreach ( $goals as $id => $name ) {
			$key = array_search( $name, $this->custom_event_goals );

			if ( ! in_array( $key, $disabled_settings ) ) {
				continue; // @codeCoverageIgnore
			}

			$this->client->delete_goal( $id );

			unset( $goals[ $id ] );
		}

		// Refresh the stored IDs in the DB.
		update_option( 'plausible_analytics_enhanced_measurements_goal_ids', $goals );
	}

	/**
	 * Searches an array for the presence of $string within each element's value. Strips currencies using a regex, e.g.
	 * (USD), because these are added to revenue goals by Plausible.
	 *
	 * @param string $string
	 * @param array $haystack
	 *
	 * @return false|mixed
	 * @codeCoverageIgnore Because it can't be unit tested.
	 */
	public function array_search_contains( $string, $haystack ) {
		if ( preg_match( '/\([A-Z]*?\)/', $string ) ) {
			$string = preg_replace( '/ \([A-Z]*?\)/', '', $string );
		}

		foreach ( $haystack as $key => $value ) {
			if ( str_contains( $value, $string ) ) {
				return $key;
			}
		}

		return false;
	}

	/**
	 * @param array $old_settings
	 * @param array $settings
	 *
	 * @return void
	 * @codeCoverageIgnore Because we don't want to test if the API is working.
	 */
	public function maybe_create_custom_properties( $old_settings, $settings ) {
		$enhanced_measurements = $settings['enhanced_measurements'];

		if ( ! EnhancedMeasurements::is_enabled( EnhancedMeasurements::PAGEVIEW_PROPS, $enhanced_measurements ) &&
		     ! EnhancedMeasurements::is_enabled( EnhancedMeasurements::ECOMMERCE_REVENUE, $enhanced_measurements ) &&
		     ! EnhancedMeasurements::is_enabled( EnhancedMeasurements::SEARCH_QUERIES, $enhanced_measurements ) &&
		     ! EnhancedMeasurements::is_enabled( EnhancedMeasurements::QUERY_PARAMS, $enhanced_measurements ) ) {
			return; // @codeCoverageIgnore
		}

		$create_request = new Client\Model\CustomPropEnableRequestBulkEnable();
		$properties     = [];

		/**
		 * Enable Custom Properties for Authors & Categories option.
		 */
		if ( EnhancedMeasurements::is_enabled( EnhancedMeasurements::PAGEVIEW_PROPS, $enhanced_measurements ) ) {
			foreach ( $this->custom_pageview_properties as $property ) {
				$properties[] = new Client\Model\CustomProp( [ 'custom_prop' => [ 'key' => $property ] ] );
			}
		}

		/**
		 * Create Custom Properties for WooCommerce integration.
		 */
		if ( EnhancedMeasurements::is_enabled( EnhancedMeasurements::ECOMMERCE_REVENUE, $enhanced_measurements ) && ( Integrations::is_wc_active() || Integrations::is_edd_active() ) ) {
			foreach ( self::CUSTOM_PROPERTIES as $property ) {
				$properties[] = new Client\Model\CustomProp( [ 'custom_prop' => [ 'key' => $property ] ] );
			}
		}

		/**
		 * Create Custom Properties for Query Parameters option.
		 */
		if ( EnhancedMeasurements::is_enabled( EnhancedMeasurements::QUERY_PARAMS, $enhanced_measurements ) ) {
			foreach ( Helpers::get_settings()['query_params'] ?? [] as $query_param ) {
				$properties[] = new Client\Model\CustomProp( [ 'custom_prop' => [ 'key' => $query_param ] ] );
			}
		}

		/**
		 * Create Custom Properties for Search Queries option.
		 */
		if ( EnhancedMeasurements::is_enabled( EnhancedMeasurements::SEARCH_QUERIES, $enhanced_measurements ) ) {
			$caps = get_option( 'plausible_analytics_api_token_caps', [] );

			foreach ( $this->custom_search_properties as $property ) {
				if ( empty( $caps[ Capabilities::PROPS ] ) && ( $property === 'result_count' || $property == 'search_source' ) ) {
					continue;
				}

				$properties[] = new Client\Model\CustomProp( [ 'custom_prop' => [ 'key' => $property ] ] );
			}
		}

		if ( empty( $properties ) ) {
			return; // @codeCoverageIgnore
		}

		$create_request->setCustomProps( $properties );

		$this->client->enable_custom_property( $create_request );
	}

	/**
	 * Auto-enables tracking of the 'Customer' user role for WC, 'Subscriber' user role for EDD and 'EDD_Subscriber' user role for EDD Recurring
	 * if Revenue tracking and one of these plugins is enabled.
	 *
	 * @param $settings
	 *
	 * @return array
	 */
	public function maybe_enable_customer_user_roles( $settings ) {
		$enhanced_measurements = $settings['enhanced_measurements'];

		if ( EnhancedMeasurements::is_enabled( EnhancedMeasurements::ECOMMERCE_REVENUE, $enhanced_measurements ) ) {
			if ( Integrations::is_wc_active() && ! in_array( 'customer', $settings['tracked_user_roles'] ) ) {
				$settings['tracked_user_roles'][] = 'customer';
			}

			if ( Integrations::is_edd_active() && ! in_array( 'subscriber', $settings['tracked_user_roles'] ) ) {
				$settings['tracked_user_roles'][] = 'subscriber';
			}

			if ( Integrations::is_edd_recurring_active() && ! in_array( 'edd_subscriber', $settings['tracked_user_roles'] ) ) {
				$settings['tracked_user_roles'][] = 'edd_subscriber';
			}
		}

		return $settings;
	}

	/**
	 * Updates the tracker script config based on the enabled enhanced measurements.
	 *
	 * @return array The updated tracker script config.
	 */
	public function update_tracker_script_config( $old_settings, $settings ) {
		$config = [
			'file_downloads'     => false,
			'form_submissions'   => false,
			'hash_based_routing' => false,
			'installation_type'  => 'wordpress',
			'outbound_links'     => false,
		];

		if ( EnhancedMeasurements::is_enabled( EnhancedMeasurements::FILE_DOWNLOADS, $settings['enhanced_measurements'] ) ) {
			$config['file_downloads'] = true;
		}

		if ( EnhancedMeasurements::is_enabled( EnhancedMeasurements::FORM_COMPLETIONS, $settings['enhanced_measurements'] ) ) {
			$config['form_submissions'] = true;
		}

		if ( EnhancedMeasurements::is_enabled( EnhancedMeasurements::HASH_BASED_ROUTING, $settings['enhanced_measurements'] ) ) {
			$config['hash_based_routing'] = true;
		}

		if ( EnhancedMeasurements::is_enabled( EnhancedMeasurements::OUTBOUND_LINKS, $settings['enhanced_measurements'] ) ) {
			$config['outbound_links'] = true;
		}

		$config  = [ 'tracker_script_configuration' => $config ];
		$request = new Client\Model\TrackerScriptConfigurationUpdateRequest( $config );

		$this->client->update_tracker_script_configuration( $request );

		return $config;
	}
}
