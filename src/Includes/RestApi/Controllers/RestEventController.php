<?php
/**
 * REST API Event controller
 *
 * Handles requests to the /event endpoint.
 *
 * @since 1.2.5
 *
 * @package    WordPress
 * @subpackage Plausible Analytics
 */


namespace Plausible\Analytics\WP\Includes\RestApi\Controllers;

use Plausible\Analytics\WP\Includes\Helpers;
use Plausible\Analytics\WP\Includes\RestApi\ApiHelpers;
use WP_REST_Server;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * REST API Event controller class.
 *
 * @package Plausible Analytics\RestApi
 */
class RestEventController {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected static $namespace = 'stats/api';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected static $rest_base = 'event';

	/**
	 * Coupons actions.
	 */
	public function __construct() {

	}

	/**
	 * Register the routes for coupons.
	 */
	public function register_routes() {
		register_rest_route(
			self::$namespace,
			'/' . self::$rest_base,
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'event' ],
					'permission_callback' => '__return_true',
				],
			]
		);
	}

	/**
	 * @return void
	 */
	public function event( $request ) {
		$event_remote_route = Helpers::get_default_data_api_url();
		ApiHelpers::send_proxy_request( $event_remote_route, $request );
	}

	/**
	 * @return string
	 */
	static public function get_event_route_url() {
		return get_rest_url( null, self::$namespace . '/' . self::$rest_base );
	}

}
