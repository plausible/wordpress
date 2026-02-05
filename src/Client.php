<?php

namespace Plausible\Analytics\WP;

use Exception;
use Plausible\Analytics\WP\Capabilities as WPCapabilities;
use Plausible\Analytics\WP\Admin\Messages;
use Plausible\Analytics\WP\Client\ApiException;
use Plausible\Analytics\WP\Client\Lib\GuzzleHttp\Client as GuzzleClient;
use Plausible\Analytics\WP\Client\Api\DefaultApi;
use Plausible\Analytics\WP\Client\Configuration;
use Plausible\Analytics\WP\Client\Model\Capabilities;
use Plausible\Analytics\WP\Client\Model\CapabilitiesFeatures;
use Plausible\Analytics\WP\Client\Model\CustomPropEnableRequestBulkEnable;
use Plausible\Analytics\WP\Client\Model\FunnelCreateRequest;
use Plausible\Analytics\WP\Client\Model\GoalCreateRequestBulkGetOrCreate;
use Plausible\Analytics\WP\Client\Model\GoalListResponse;
use Plausible\Analytics\WP\Client\Model\PaymentRequiredError;
use Plausible\Analytics\WP\Client\Model\SharedLink;
use Plausible\Analytics\WP\Client\Model\UnauthorizedError;
use Plausible\Analytics\WP\Client\Model\UnprocessableEntityError;

/**
 * This class acts as middleware between our OpenAPI generated API client and our WP plugin, and takes care of setting
 * the required configuration, so we can use the Client in a unified manner.
 */
class Client {
	/**
	 * @var DefaultApi $api_instance
	 */
	private $api_instance;

	/**
	 * Setup basic authorization, basic_auth.
	 *
	 * @param string $token Allows to specify the token, e.g. when it's not stored in the DB yet.
	 */
	public function __construct( $token = '' ) {
		$config             = Configuration::getDefaultConfiguration()->setUsername( 'WordPress' )->setPassword(
			$token
		)->setHost( Helpers::get_hosted_domain_url() );
		$this->api_instance = new DefaultApi( new GuzzleClient(), $config );
	}

	/**
	 * Validates the Plugin Token (password) set in the current instance and caches the state to a transient valid for 1 day.
	 *
	 * @return bool
	 * @throws ApiException
	 */
	public function validate_api_token() {
		if ( $this->is_api_token_valid() ) {
			return true; // @codeCoverageIgnore
		}

		$features = $this->get_features();

		if ( ! $features instanceof CapabilitiesFeatures ) {
			return false; // @codeCoverageIgnore
		}

		$data_domain = $this->get_data_domain();
		$token       = $this->api_instance->getConfig()->getPassword();
		$is_valid    = str_contains( $token, 'plausible-plugin' ) && ! empty( $features->getGoals() ) && $data_domain === Helpers::get_domain();

		/**
		 * Don't cache invalid API tokens.
		 */
		if ( $is_valid ) {
			set_transient( 'plausible_analytics_valid_token', [ $token => true ], 86400 ); // @codeCoverageIgnore

			$this->update_capabilities( $token ); // @codeCoverageIgnore
		}

		return $is_valid;
	}

	/**
	 * Is currently stored token valid?
	 *
	 * @return bool
	 */
	public function is_api_token_valid() {
		$token        = $this->api_instance->getConfig()->getPassword();
		$valid_tokens = get_transient( 'plausible_analytics_valid_token' );

		return isset( $valid_tokens[ $token ] ) && $valid_tokens[ $token ] === true;
	}

	/**
	 * Retrieve Features from Capabilities object.
	 *
	 * @return false|Client\Model\CapabilitiesFeatures
	 */
	public function get_features() {
		$capabilities = $this->get_capabilities();

		if ( $capabilities instanceof Capabilities ) {
			return $capabilities->getFeatures();
		}

		return false; // @codeCoverageIgnore
	}

	/**
	 * Retrieve all capabilities assigned to configured Plugin Token.
	 *
	 * @return bool|Client\Model\Capabilities
	 *
	 * @codeCoverageIgnore
	 */
	private function get_capabilities() {
		try {
			return $this->api_instance->plausibleWebPluginsAPIControllersCapabilitiesIndex();
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Retrieve Data Domain property from Capabilities object.
	 *
	 * @return false|string
	 *
	 * @codeCoverageIgnore
	 */
	private function get_data_domain() {
		$capabilities = $this->get_capabilities();

		if ( $capabilities instanceof Capabilities ) {
			return $capabilities->getDataDomain();
		}

		return false;
	}

	/**
	 * Stores the capabilities for the currently entered API token in the DB for later use.
	 *
	 * @param $token
	 *
	 * @return false|array
	 *
	 * @codeCoverageIgnore
	 */
	private function update_capabilities( $token = '' ) {
		$client_factory = new ClientFactory( $token );
		/** @var Client $client */
		$client = $client_factory->build();

		if ( ! $client instanceof Client ) {
			return false;
		}

		/** @var Client\Model\CapabilitiesFeatures $features */
		$features = $client->get_features();

		if ( ! $features ) {
			return false;
		}

		$caps = [
			WPCapabilities::FUNNELS => $features->getFunnels(),
			WPCapabilities::GOALS   => $features->getGoals(),
			WPCapabilities::PROPS   => $features->getProps(),
			WPCapabilities::REVENUE => $features->getRevenueGoals(),
			WPCapabilities::STATS   => $features->getStatsApi(),
		];

		update_option( 'plausible_analytics_api_token_caps', $caps );

		return $caps;
	}

	/**
	 * Retrieve the configured Tracker ID and stores it in the options table.
	 *
	 * @return string
	 *
	 * @codeCoverageIgnore Because we don't want to test WordPress core functionality.
	 */
	public function get_tracker_id() {
		$id = get_option( 'plausible_analytics_tracker_id' );

		if ( ! $id ) {
			$tracker_configuration = $this->get_configuration();
			$id                    = $tracker_configuration->getId();

			update_option( 'plausible_analytics_tracker_id', $id );
		}

		return $id;
	}

	/**
	 * Retrieve the configured Tracker Script Configuration.
	 *
	 * @return false|Client\Model\TrackerScriptConfigurationTrackerScriptConfiguration
	 *
	 * @codeCoverageIgnore Because we don't want to test the API's response.
	 */
	private function get_configuration() {
		try {
			$configuration = $this->api_instance->plausibleWebPluginsAPIControllersTrackerScriptConfigurationGet();

			return $configuration->getTrackerScriptConfiguration();
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Update the configured Tracker Script Configuration.
	 *
	 * @param \Plausible\Analytics\WP\Client\Model\TrackerScriptConfigurationUpdateRequest $tracker_script_config_update_request
	 *
	 * @codeCoverageIgnore
	 */
	public function update_tracker_script_configuration( $tracker_script_config_update_request ) {
		try {
			$this->api_instance->plausibleWebPluginsAPIControllersTrackerScriptConfigurationUpdate(
				$tracker_script_config_update_request
			);
		} catch ( Exception $e ) {
			$this->send_json_error(
				$e,
				__(
					'Something went wrong while updating tracker script configuration: %s',
					'plausible-analytics'
				)
			);
		}
	}

	/**
	 * @param Exception $e
	 * @param string $error_message The human-readable part of the error message, requires a %s at the end!
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore
	 */
	private function send_json_error( $e, $error_message ) {
		if ( ! wp_doing_ajax() ) {
			return;
		}

		$code = $e->getCode();

		// Any error codes outside the 4xx range should show a generic error.
		if ( $code <= 399 || $code >= 500 ) {
			Messages::set_error( __( 'Something went wrong, try again later.', 'plausible-analytics' ) );

			wp_send_json_error( null, $code );
		}

		$message       = $e->getMessage();
		$response_body = $e->getResponseBody();

		if ( $response_body !== null ) {
			$response_json = json_decode( $response_body );

			if ( ! empty( $response_json->errors ) ) {
				$message = '';

				foreach ( $response_json->errors as $error_no => $error ) {
					$message .= $error->detail;

					if ( $error_no + 1 === count( $response_json->errors ) ) {
						$message .= '.';
					} elseif ( count( $response_json->errors ) > 1 ) {
						$message .= ', ';
					}
				}
			}
		}

		Messages::set_error( sprintf( $error_message, $message ) );

		$caps = $this->update_capabilities();

		wp_send_json_error( [ 'capabilities' => $caps ], $code );
	}

	/**
	 * Create Shared Link in Plausible Dashboard.
	 *
	 * @return void
	 */
	public function create_shared_link() {
		$shared_link = (object) [];
		$result      = (object) [];

		try {
			$result = $this->bulk_create_shared_links();
			// @codeCoverageIgnoreStart
		} catch ( Exception $e ) {
			$this->send_json_error( $e, __( 'Something went wrong while creating Shared Link: %s', 'plausible-analytics' ) );
			// @codeCoverageIgnoreEnd
		}

		if ( $result instanceof SharedLink ) {
			$shared_link = $result->getSharedLink();
		}

		if ( ! empty( $shared_link->getHref() ) ) {
			Helpers::update_setting( 'shared_link', $shared_link->getHref() );
		}
	}

	/**
	 * @return SharedLink|UnauthorizedError|UnprocessableEntityError
	 * @throws ApiException
	 *
	 * @codeCoverageIgnore
	 */
	public function bulk_create_shared_links() {
		return $this->api_instance->plausibleWebPluginsAPIControllersSharedLinksCreate(
			[ 'shared_link' => [ 'name' => 'WordPress - Shared Dashboard', 'password_protected' => false ] ]
		);
	}

	/**
	 * Allows creating Custom Event Goals in bulk.
	 *
	 * @param GoalCreateRequestBulkGetOrCreate $goals
	 *
	 * @return GoalListResponse|PaymentRequiredError|UnauthorizedError|UnprocessableEntityError|void
	 *
	 * @codeCoverageIgnore
	 */
	public function create_goals( $goals ) {
		try {
			return $this->api_instance->goalGetOrCreate( $goals );
		} catch ( Exception $e ) {
			$this->send_json_error( $e, __( 'Something went wrong while creating Custom Event Goal: %s', 'plausible-analytics' ) );
		}
	}

	/**
	 * Allows creating Funnels in bulk.
	 *
	 * @param FunnelCreateRequest $funnel
	 *
	 * @return Client\Model\Funnel|PaymentRequiredError|UnauthorizedError|UnprocessableEntityError|void
	 *
	 * @codeCoverageIgnore
	 */
	public function create_funnel( $funnel ) {
		try {
			return $this->api_instance->funnelGetOrCreate( $funnel );
		} catch ( Exception $e ) {
			$this->send_json_error( $e, __( 'Something went wrong while creating Funnel: %s', 'plausible-analytics' ) );
		}
	}

	/**
	 * Delete a Custom Event Goal by ID.
	 *
	 * @param int $id
	 *
	 * @codeCoverageIgnore
	 */
	public function delete_goal( $id ) {
		try {
			$this->api_instance->plausibleWebPluginsAPIControllersGoalsDelete( $id );
		} catch ( Exception $e ) {
			$this->send_json_error(
				$e,
				__(
					'Something went wrong while deleting a Custom Event Goal: %s',
					'plausible-analytics'
				)
			);
		}
	}

	/**
	 * Enable (or get) a custom property.
	 *
	 * @param CustomPropEnableRequestBulkEnable $enable_request
	 *
	 * @throws PaymentRequiredError|UnauthorizedError|UnprocessableEntityError
	 *
	 * @codeCoverageIgnore
	 */
	public function enable_custom_property( $enable_request ) {
		try {
			$this->api_instance->customPropGetOrEnable( $enable_request );
		} catch ( Exception $e ) {
			$this->send_json_error(
				$e,
				__(
					'Something went wrong while enabling Pageview Properties: %s',
					'plausible-analytics'
				)
			);
		}
	}
}
