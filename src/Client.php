<?php

namespace Plausible\Analytics\WP;

use Exception;
use Plausible\Analytics\WP\Client\Lib\GuzzleHttp\Client as GuzzleClient;
use Plausible\Analytics\WP\Client\Api\DefaultApi;
use Plausible\Analytics\WP\Client\Configuration;
use Plausible\Analytics\WP\Client\Model\CustomPropEnableRequest;
use Plausible\Analytics\WP\Client\Model\CustomPropEnableRequestBulkEnable;
use Plausible\Analytics\WP\Client\Model\CustomPropListResponse;
use Plausible\Analytics\WP\Client\Model\GoalCreateRequestBulkGetOrCreate;
use Plausible\Analytics\WP\Client\Model\PaymentRequiredError;
use Plausible\Analytics\WP\Client\Model\SharedLink;
use Plausible\Analytics\WP\Client\Model\UnauthorizedError;
use Plausible\Analytics\WP\Client\Model\UnprocessableEntityError;
use Plausible\Analytics\WP\Includes\Helpers;

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
	 */
	public function __construct() {
		$config             = Configuration::getDefaultConfiguration()->setUsername( 'WordPress' )->setPassword(
			Helpers::get_settings()[ 'api_token' ]
		);
		$this->api_instance = new DefaultApi( new GuzzleClient(), $config );
	}

	/**
	 * Checks if a password is set. It doesn't validate the password!
	 * @return bool
	 */
	public function check_password() {
		$password = $this->api_instance->getConfig()->getPassword();

		return ! empty( $password );
	}

	/**
	 * Create Shared Link in Plausible Dashboard.
	 * @return void
	 */
	public function create_shared_link() {
		$shared_link = (object) [];

		try {
			$result = $this->api_instance->plausibleWebPluginsAPIControllersSharedLinksCreate(
				[ 'shared_link' => [ 'name' => 'WordPress - Shared Dashboard', 'password_protected' => false ] ]
			);
		} catch ( Exception $e ) {
			if ( wp_doing_ajax() ) {
				wp_send_json_error(
					sprintf(
						__( 'Something went wrong while creating Shared Link: %s', 'plausible-analytics' ),
						$e->getMessage()
					)
				);
			}
		}

		if ( $result instanceof SharedLink ) {
			$shared_link = $result->getSharedLink();
		}

		if ( ! empty( $shared_link->getHref() ) ) {
			Helpers::update_setting( 'shared_link', $shared_link->getHref() );
		}
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
			if ( wp_doing_ajax() ) {
				wp_send_json_error(
					sprintf(
						__( 'Something went wrong while creating Custom Event Goal: %s', 'plausible-analytics' ),
						$e->getMessage()
					)
				);
			}
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
			if ( wp_doing_ajax() ) {
				wp_send_json_error(
					sprintf(
						__(
							'Something went wrong while trying to delete a Custom Event Goal. Please delete it manually. The error message was: %s',
							'plausible-analytics'
						),
						$e->getMessage()
					)
				);
			}
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
			if ( wp_doing_ajax() ) {
				$message       = $e->getMessage();
				$response_body = $e->getResponseBody();

				if ( $response_body !== null ) {
					$response_json = json_decode( $response_body );

					if ( ! empty( $response_json->errors[ 0 ]->detail ) ) {
						$message = $response_json->errors[ 0 ]->detail;
					}
				}

				wp_send_json_error(
					sprintf(
						__(
							'Something went wrong while trying to enable Pageview Properties. The error message was: %s',
							'plausible-analytics'
						),
						$message
					)
				);
			}
		}
	}
}
