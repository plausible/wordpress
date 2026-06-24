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
	 * @var Client[]|null $clients_cache
	 */
	private $clients_cache = null;

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
	 * @codeCoverageIgnore
	 */
	public function __construct( $client = null ) {
		$this->client = $client;

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
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore
	 */
	private function init() {
		/** This hook should always be registered because it handles the case where no clients are yet registered. */
		add_action( 'add_option_plausible_analytics_settings', [ $this, 'maybe_provision_on_new_connect' ], 10, 2 );

		if ( empty( $this->get_clients() ) ) {
			return; // @codeCoverageIgnore
		}

		add_action( 'update_option_plausible_analytics_settings', [ $this, 'maybe_provision_on_connect' ], 10, 2 );
		add_action( 'update_option_plausible_analytics_settings', [ $this, 'maybe_create_shared_link' ], 10, 2 );
		add_action( 'update_option_plausible_analytics_settings', [ $this, 'maybe_create_goals' ], 10, 2 );
		add_action( 'update_option_plausible_analytics_settings', [ $this, 'maybe_delete_goals' ], 11, 2 );
		add_action( 'update_option_plausible_analytics_settings', [ $this, 'maybe_create_custom_properties' ], 11, 2 );
		add_filter( 'pre_update_option_plausible_analytics_settings', [ $this, 'maybe_enable_customer_user_roles' ] );
		add_action( 'update_option_plausible_analytics_settings', [ $this, 'update_tracker_script_config' ], 10, 2 );
	}

	/**
	 * Get an array of [ $key => Client ] for every configured domain with a non-empty API token.
	 *
	 * @param bool $force_refresh
	 *
	 * @return Client[]
	 */
	public function get_clients( $force_refresh = false ) {
		if ( $this->clients_cache !== null && ! $force_refresh ) {
			return $this->clients_cache;
		}

		if ( $this->client instanceof Client ) {
			return $this->clients_cache = [ 'default' => $this->client ];
		}

		$settings = Helpers::get_settings();

		if ( empty( $settings['api_token'] ) || ! is_array( $settings['api_token'] ) ) {
			return $this->clients_cache = [];
		}

		$clients = [];

		foreach ( $settings['api_token'] as $key => $token ) {
			if ( empty( $token ) ) {
				continue;
			}

			$filter = function () use ( $key ) {
				return $key;
			};

			add_filter( 'plausible_analytics_current_language_domain_key', $filter );

			$client = ( new ClientFactory() )->build();

			if ( $client instanceof Client && $client->validate_api_token() ) {
				$clients[ $key ] = $client;
			}

			remove_filter( 'plausible_analytics_current_language_domain_key', $filter );
		}

		return $this->clients_cache = $clients;
	}

	/**
	 * Searches an array for the presence of $string within each element's value. Strips currencies using a regex, e.g.
	 * (USD), because these are added to revenue goals by Plausible.
	 *
	 * @param string $string
	 * @param array  $haystack
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
	 * Creates a funnel and creates goals if they don't exist.
	 *
	 * @param        $name
	 * @param        $steps
	 * @param null   $client
	 * @param string $key
	 *
	 * @return void
	 * @codeCoverageIgnore Because this method should be mocked in tests if needed.
	 */
	public function create_funnel( $name, $steps, $client = null, $key = 'default', $all_ids = null ) {
		$create_request = new Client\Model\FunnelCreateRequest(
			[
				'funnel' => [
					'name'  => $name,
					'steps' => $steps,
				],
			]
		);

		if ( ! $client ) {
			$client = $this->client;
		}

		if ( $all_ids === null ) {
			$all_ids = $this->normalize_option( get_option( 'plausible_analytics_enhanced_measurements_goal_ids', [] ) );
		}

		$funnel = $client->create_funnel( $create_request );

		if ( ! $funnel instanceof Client\Model\Funnel || ! $funnel->valid() ) {
			return $all_ids;
		}

		$ids   = $all_ids[ $key ] ?? [];
		$steps = $funnel->getFunnel()->getSteps();

		foreach ( $steps as $step ) {
			$goal = $step->getGoal();

			if ( ! empty( $goal ) ) {
				$ids[ $goal->getId() ] = $goal->getDisplayName();
			}
		}

		if ( ! empty( $ids ) ) {
			$all_ids[ $key ] = $ids;
			update_option( 'plausible_analytics_enhanced_measurements_goal_ids', $all_ids );
		}

		return $all_ids;
	}

	/**
	 * Normalizes a per-domain option value.
	 *
	 * @param mixed $value
	 *
	 * @return array
	 */
	public function normalize_option( $value ) {
		if ( empty( $value ) || ! is_array( $value ) ) {
			return [];
		}

		$is_legacy = false;

		foreach ( $value as $item ) {
			if ( ! is_array( $item ) ) {
				$is_legacy = true;
				break;
			}
		}

		if ( $is_legacy ) {
			return [ 'default' => $value ];
		}

		return $value;
	}

	/**
	 * Create the shared link when the Enable Analytics Dashboard option is enabled.
	 *
	 * @param $old_settings
	 * @param $settings
	 */
	public function maybe_create_shared_link( $old_settings, $settings ) {
		if ( empty( $settings['enable_analytics_dashboard'] ) ) {
			return; // @codeCoverageIgnore
		}

		foreach ( $this->get_clients() as $client ) {
			$client->create_shared_link();
		}
	}

	/**
	 * Delete Custom Event Goals when an Enhanced Measurement is disabled.
	 *
	 * @param $old_settings
	 * @param $settings
	 *
	 * @codeCoverageIgnore Because we don't want to test it if the API is working.
	 */
	public function maybe_delete_goals( $old_settings, $settings ) {
		$enhanced_measurements_old = array_filter( $old_settings['enhanced_measurements'] );
		$enhanced_measurements     = array_filter( $settings['enhanced_measurements'] );
		$disabled_settings         = array_diff( $enhanced_measurements_old, $enhanced_measurements );

		if ( empty( $disabled_settings ) ) {
			return;
		}

		$all_ids = $this->normalize_option( get_option( 'plausible_analytics_enhanced_measurements_goal_ids', [] ) );

		foreach ( $this->get_clients() as $domain_key => $client ) {
			$goals = $all_ids[ $domain_key ] ?? [];

			foreach ( $goals as $id => $name ) {
				$key = array_search( $name, $this->custom_event_goals );

				if ( ! in_array( $key, $disabled_settings ) ) {
					continue; // @codeCoverageIgnore
				}

				$client->delete_goal( $id );

				unset( $goals[ $id ] );
			}

			$all_ids[ $domain_key ] = $goals;
		}

		// Refresh the stored IDs in the DB.
		update_option( 'plausible_analytics_enhanced_measurements_goal_ids', $all_ids );
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
	 * Wrapper for @see self::maybe_provision_on_connect() which fires exclusively on fresh installations.
	 *
	 * @param $_option_name
	 * @param $settings
	 *
	 * @return void
	 */
	public function maybe_provision_on_new_connect( $_option_name, $settings ) {
		$this->maybe_provision_on_connect( [], $settings );
	}

	/**
	 * Run provisioning for all enabled Enhanced Measurements if a new Language Domain is configured.
	 *
	 * @param $old_settings
	 * @param $settings
	 *
	 * @return void
	 */
	public function maybe_provision_on_connect( $old_settings, $settings ) {
		$old_tokens = is_array( $old_settings['api_token'] ) ? $old_settings['api_token'] : [ 'default' => $old_settings['api_token'] ];
		$new_tokens = is_array( $settings['api_token'] ) ? $settings['api_token'] : [ 'default' => $settings['api_token'] ];

		$changed_keys = [];

		foreach ( $new_tokens as $key => $token ) {
			if ( ! empty( $token ) && ( ! isset( $old_tokens[ $key ] ) || $old_tokens[ $key ] !== $token ) ) {
				$changed_keys[] = $key;
			}
		}

		if ( empty( $changed_keys ) ) {
			return;
		}

		// Refresh the client cache since a new token was just saved.
		$this->get_clients( true );

		$this->maybe_create_goals( $old_settings, $settings );
		$this->maybe_create_custom_properties( $old_settings, $settings );
		$this->update_tracker_script_config( $old_settings, $settings );
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

		$all_ids = $this->normalize_option( get_option( 'plausible_analytics_enhanced_measurements_goal_ids', [] ) );

		foreach ( $this->get_clients() as $key => $client ) {
			$all_ids = $this->create_goals( $goals, $client, $key, $all_ids );
		}

		update_option( 'plausible_analytics_enhanced_measurements_goal_ids', $all_ids );
	}

	/**
	 * @param string $name     Event Name
	 * @param string $type     CustomEvent|Revenue|Pageview
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
	 * @return array
	 */
	public function create_goals( $goals, $client = null, $key = 'default', $all_ids = null ) {
		if ( empty( $goals ) ) {
			return $all_ids ?? [];
		}

		if ( ! $client ) {
			$client = $this->client;
		}

		if ( $all_ids === null ) {
			$all_ids = $this->normalize_option( get_option( 'plausible_analytics_enhanced_measurements_goal_ids', [] ) );
		}

		$create_request = new Client\Model\GoalCreateRequestBulkGetOrCreate();
		$create_request->setGoals( $goals );
		$response = $client->create_goals( $create_request );

		if ( $response->valid() ) {
			$goals = $response->getGoals();
			$ids   = $all_ids[ $key ] ?? [];

			foreach ( $goals as $goal ) {
				$goal                  = $goal->getGoal();
				$ids[ $goal->getId() ] = $goal->getDisplayName();
			}

			if ( ! empty( $ids ) ) {
				$all_ids[ $key ] = $ids;
				/** IDs are stored in the DB after the loop. @see self::maybe_create_goals() */
			}
		}

		return $all_ids;
	}

	/**
	 * @param array $old_settings
	 * @param array $settings
	 *
	 * @return void
	 * @codeCoverageIgnore Because we don't want to test it if the API is working.
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
		 * Enable Custom Properties for the Authors & Categories option.
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

		$all_caps = $this->normalize_option( get_option( 'plausible_analytics_api_token_caps', [] ) );

		foreach ( $this->get_clients() as $key => $client ) {
			$domain_properties = $properties;

			/**
			 * Create the Custom Properties for the Search Queries option.
			 */
			if ( EnhancedMeasurements::is_enabled( EnhancedMeasurements::SEARCH_QUERIES, $enhanced_measurements ) ) {
				$caps = $all_caps[ $key ] ?? [];

				foreach ( $this->custom_search_properties as $property ) {
					if ( empty( $caps[ Capabilities::PROPS ] ) && ( $property === 'result_count' || $property == 'search_source' ) ) {
						continue;
					}

					$domain_properties[] = new Client\Model\CustomProp( [ 'custom_prop' => [ 'key' => $property ] ] );
				}
			}

			if ( empty( $domain_properties ) ) {
				continue; // @codeCoverageIgnore
			}

			$create_request->setCustomProps( $domain_properties );

			$client->enable_custom_property( $create_request );
		}
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

		foreach ( $this->get_clients() as $client ) {
			$client->update_tracker_script_configuration( $request );
		}

		return $config;
	}
}
