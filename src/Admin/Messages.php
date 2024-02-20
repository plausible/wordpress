<?php
/**
 * Plausible Analytics | Admin Actions.
 * @since      2.0.6
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * This class provides an alternative to the JS/Client approach to display error/notice messages in the Admin interface.
 */
class Messages {
	/**
	 * Sets an error.
	 *
	 * @param $message
	 * @param $expiration
	 *
	 * @return void
	 */
	public static function set_error( $message, $expiration = 5 ) {
		set_transient( 'plausible_analytics_error', $message, $expiration );
	}
}
