<?php
/**
 * Plausible Analytics | Settings API.
 *
 * @since 1.3.0
 *
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Admin;

defined( 'ABSPATH' ) || exit;

class Notice {
	const TRANSIENT_NAME = 'plausible_analytics_notice';

	/** @var array $notices */
	public static $notices = [];

	/**
	 * @param        $message
	 * @param string $type (info|warning|error|success)
	 * @param string $screen_id
	 * @param bool   $json
	 * @param int    $code
	 */
	public static function set_notice( $message, $message_id = '', $type = 'success', $screen_id = 'all' ) {
		self::$notices = get_transient( self::TRANSIENT_NAME );

		if ( ! self::$notices ) {
			self::$notices = [];
		}

		self::$notices[ $screen_id ][ $type ][ $message_id ] = $message;

		set_transient( self::TRANSIENT_NAME, self::$notices );
	}

	/**
	 * Prints notice (if any) grouped by type.
	 */
	public static function print_notices() {
		$admin_notices = get_transient( self::TRANSIENT_NAME );

		if ( is_array( $admin_notices ) ) {
			$current_screen = get_current_screen();

			foreach ( $admin_notices as $screen => $notice ) {
				if ( $current_screen->id !== $screen && $screen !== 'all' ) {
					continue;
				}

				foreach ( $notice as $type => $message ) {
					?>
					<div id="plausible-analytics-message" class="notice notice-<?php echo $type; ?> is-dismissible">
						<?php foreach ( $message as $line ) : ?>
							<p><strong><?php echo $line; ?></strong></p>
						<?php endforeach; ?>
					</div>
					<?php
				}
			}
		}

		delete_transient( self::TRANSIENT_NAME );
	}
}
