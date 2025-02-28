<?php
/**
 * Plausible Analytics | Debug
 *
 * @since     v2.3.0
 * @copyright 2025
 */

namespace Plausible\Analytics\WP;

class Debug {
	/**
	 * Call this function to log debug messages.
	 *
	 * @param string $message
	 *
	 * @return void
	 */
	public static function log( $message, $data = [] ) {
		if ( ! defined( 'PLAUSIBLE_DEBUG' ) || PLAUSIBLE_DEBUG !== true ) {
			return;
		}

		static $log_file;

		if ( $log_file === null ) {
			$log_file = trailingslashit( WP_CONTENT_DIR ) . 'plausible-debug.log';
		}

		// phpcs:ignore
		error_log( current_time( 'Y-m-d H:i:s' ) . ' ' . microtime() . ": $message\n", 3, $log_file );

		if ( ! empty( $data ) ) {
			// phpcs:ignore
			error_log( 'Var dump: ' . print_r( $data, true ), 3, $log_file );
		}
	}
}
