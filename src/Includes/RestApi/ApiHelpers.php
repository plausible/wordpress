<?php
/**
 * Plausible Analytics | API Helpers
 *
 * @since 1.2.5
 *
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Includes\RestApi;

use Plausible\Analytics\WP\Includes\Helpers;
use Plausible\Analytics\WP\Includes\RestApi\Controllers\RestEventController;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 *
 */
class ApiHelpers {

	public static function get_namespace() {

		return Helpers::get_child_folder_name();

	}

	/**
	 * @param $url
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 * @since 1.2.5
	 *
	 */
	public static function send_proxy_request( $url, $request ) {

		$headers = [];

		foreach ( getallheaders() as $key => $value ) {
			if ( ! in_array( $key, [ 'Host', 'Accept-Encoding', 'X-Forwarded-For', 'Client-IP' ] ) ) {
				$headers[ $key ] = $value;
			}
		}

		ksort( $headers );

		// Request arguments.
		$args = [
			'headers' => $headers,
			'body'    => $request->get_body(),
			'method'  => $request->get_method(),
		];

		# Proxy
		$result = wp_remote_request( $url, $args );

		// Retrieve information
		$response_code    = wp_remote_retrieve_response_code( $result );
		$response_message = wp_remote_retrieve_response_message( $result );
		$response_body    = wp_remote_retrieve_body( $result );

		if ( ! is_wp_error( $result ) ) {
			wp_send_json(
				[
					'status'        => $response_code,
					'response'      => $response_message,
					'body_response' => $response_body,
				]
			);
		} else {
			return new WP_Error( $response_code, $response_message, $response_body );
		}

	}

	/**
	 * Check if the REST API is working.
	 *
	 * @return int|string The response code, or an empty string if the request failed.
	 * @since 1.2.5
	 *
	 */
	public static function check_rest_api() {
		$api_rest_url = RestEventController::get_event_route_url();

		$response = wp_remote_post(
			$api_rest_url,
			[
				'sslverify' => false,
				'timeout'   => 3,
			]
		);

		// Retrieve the response code.
		return wp_remote_retrieve_response_code( $response );
	}

}
