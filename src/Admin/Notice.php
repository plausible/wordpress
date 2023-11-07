<?php
/**
 * Plausible Analytics | Settings API.
 * @since      1.3.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Admin;

defined( 'ABSPATH' ) || exit;

class Notice {
	const TRANSIENT_NAME                                  = 'plausible_analytics_notice';

	const NOTICE_ERROR_MODULE_INSTALL_FAILED              = 'module-install-failed';

	const NOTICE_ERROR_PROXY_TEST_FAILED                  = 'proxy-test-failed';

	const NOTICE_ERROR_SHARED_LINK_FAILED                 = 'shared-link-failed';

	const NOTICE_ERROR_CUSTOM_EVENT_GOAL_FAILED           = 'custom-event-goal-failed';

	const NOTICE_ERROR_DELETE_CUSTOM_EVENT_GOAL_FAILED    = 'delete-custom-event-goal-failed';

	const NOTICE_ERROR_RETRIEVE_CUSTOM_EVENT_GOALS_FAILED = 'retrieve-custom-event-goals-failed';

	/** @var array $notices */
	public static $notices = [];

	/**
	 * @param        $message
	 * @param string $type (info|warning|error|success)
	 * @param string $screen_id
	 * @param bool   $json
	 * @param int    $code
	 */
	public static function set_notice( $message, $notice_id = '', $type = 'success', $screen_id = 'all' ) {
		self::$notices = get_transient( self::TRANSIENT_NAME );

		if ( ! self::$notices ) {
			self::$notices = [];
		}

		self::$notices[ $screen_id ][ $type ][ 'plausible-analytics-' . $notice_id ] = $message;

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

				foreach ( $notice as $type => $messages ) {
					?>
					<?php foreach ( $messages as $id => $line ) : ?>
						<div id="<?php echo $id; ?>" class="notice notice-<?php echo $type; ?> is-dismissible">
							<p><strong><?php echo $line; ?></strong></p>
						</div>
					<?php endforeach; ?>
					<?php
				}
			}
		}
	}
}
