<?php

namespace Plausible\Analytics\WP;

use Exception;
use Plausible\Analytics\WP\Admin\Notice;
use Plausible\Analytics\WP\Client\Lib\GuzzleHttp\Client as GuzzleClient;
use Plausible\Analytics\WP\Client\Api\DefaultApi;
use Plausible\Analytics\WP\Client\Configuration;
use Plausible\Analytics\WP\Client\Model\GoalCreateRequestBulkGetOrCreate;
use Plausible\Analytics\WP\Client\Model\SharedLink;
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
	 * @return Client\Model\PaymentRequiredError|Client\Model\PlausibleWebPluginsAPIControllersGoalsCreate201Response|Client\Model\UnauthorizedError|Client\Model\UnprocessableEntityError|void
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
	 *
	 * @return void
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
}
