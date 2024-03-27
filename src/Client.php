<?php

namespace Plausible\Analytics\WP;

use Exception;
use Plausible\Analytics\WP\Client\ApiException;
use Plausible\Analytics\WP\Client\Lib\GuzzleHttp\Client as GuzzleClient;
use Plausible\Analytics\WP\Client\Api\DefaultApi;
use Plausible\Analytics\WP\Client\Configuration;
use Plausible\Analytics\WP\Client\Model\Capabilities;
use Plausible\Analytics\WP\Client\Model\CapabilitiesFeatures;
use Plausible\Analytics\WP\Client\Model\CustomPropEnableRequestBulkEnable;
use Plausible\Analytics\WP\Client\Model\GoalCreateRequestBulkGetOrCreate;
use Plausible\Analytics\WP\Client\Model\PaymentRequiredError;
use Plausible\Analytics\WP\Client\Model\SharedLink;
use Plausible\Analytics\WP\Client\Model\UnauthorizedError;
use Plausible\Analytics\WP\Client\Model\UnprocessableEntityError;

defined( 'ABSPATH' ) || exit;

/**
 * This class acts as middleware between our OpenAPI generated API client and our WP plugin, and takes care of setting
 * the required credentials, so we can use the API in a unified manner.
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
			$token ?: Helpers::get_settings()[ 'api_token' ]
		);
		$this->api_instance = new DefaultApi( new GuzzleClient(), $config );
	}

	/**
	 * Validates the API token (password) set in the current instance and caches the state to a transient valid for 1 day.
	 *
	 * @return bool
	 * @throws ApiException
	 */
	public function validate_api_token() {
		$is_self_hosted = Helpers::get_settings()[ 'self_hosted_domain' ];

		if ( ! empty( $is_self_hosted ) ) {
			return false;
		}

		$token    = $this->api_instance->getConfig()->getPassword();
		$is_valid = ! empty( get_transient( 'plausible_analytics_valid_token' )[ $token ] );

		if ( $is_valid ) {
			return true;
		}

		$features = $this->get_features();

		if ( ! $features instanceof CapabilitiesFeatures ) {
			return false;
		}

		$data_domain = $this->get_data_domain();
		$is_valid    = strpos( $token, 'plausible-plugin' ) !== false && ! empty( $features->getGoals() ) && $data_domain === Helpers::get_domain();

		set_transient( 'plausible_analytics_valid_token', [ $token => $is_valid ], 86400 );

		return $is_valid;
	}

	/**
	 * Retrieve Features from Capabilities object.
	 *
	 * @return false|Client\Model\CapabilitiesFeatures
	 */
	private function get_features() {
		$capabilities = $this->get_capabilities();

		if ( $capabilities instanceof Capabilities ) {
			return $capabilities->getFeatures();
		}

		return false;
	}

	/**
	 * Retrieve all capabilities assigned to configured API token.
	 *
	 * @return bool|Client\Model\Capabilities
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
	 */
	private function get_data_domain() {
		$capabilities = $this->get_capabilities();

		if ( $capabilities instanceof Capabilities ) {
			return $capabilities->getDataDomain();
		}

		return false;
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
			$result = $this->api_instance->plausibleWebPluginsAPIControllersSharedLinksCreate(
				[ 'shared_link' => [ 'name' => 'WordPress - Shared Dashboard', 'password_protected' => false ] ]
			);
		} catch ( Exception $e ) {
			$this->send_json_error( $e, __( 'Something went wrong while creating Shared Link: %s', 'plausible-analytics' ) );
		}

		if ( $result instanceof SharedLink ) {
			$shared_link = $result->getSharedLink();
		}

		if ( ! empty( $shared_link->getHref() ) ) {
			Helpers::update_setting( 'shared_link', $shared_link->getHref() );
		}
	}

	/**
	 * @param Exception $e
	 * @param string    $error_message The human-readable part of the error message, requires a %s at the end!
	 *
	 * @return void
	 */
	private function send_json_error( $e, $error_message ) {
		if ( ! wp_doing_ajax() ) {
			return;
		}

		$code = $e->getCode();

		// Any error codes outside the 4xx range should show a generic error.
		if ( $code <= 399 || $code >= 500 ) {
			$message = __( 'Something went wrong, try again later.', 'plausible-analytics' );

			wp_send_json_error( $message );
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

		wp_send_json_error( sprintf( $error_message, $message ) );
	}

	/**
	 * Allows creating Custom Event Goals in bulk.
	 *
	 * @param GoalCreateRequestBulkGetOrCreate $goals
	 *
	 * @return Client\Model\PaymentRequiredError|Client\Model\PlausibleWebPluginsAPIControllersGoalsCreate201Response|Client\Model\UnauthorizedError|Client\Model\UnprocessableEntityError|null
	 */
	public function create_goals( $goals ) {
		try {
			return $this->api_instance->plausibleWebPluginsAPIControllersGoalsCreate( $goals );
		} catch ( Exception $e ) {
			$this->send_json_error( $e, __( 'Something went wrong while creating Custom Event Goal: %s', 'plausible-analytics' ) );
		}
	}

	/**
	 * Delete a Custom Event Goal by ID.
	 *
	 * @param int $id
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
	 */
	public function enable_custom_property( $enable_request ) {
		try {
			$this->api_instance->plausibleWebPluginsAPIControllersCustomPropsEnable( $enable_request );
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
