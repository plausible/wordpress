<?php
/**
 * Plausible Analytics | Admin Actions.
 * @since      1.0.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Admin;

use Plausible\Analytics\WP\Includes\Helpers;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Actions {
	/**
	 * Constructor.
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_notices', [ $this, 'print_notices' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'register_assets' ] );
		add_action( 'wp_ajax_plausible_analytics_notice_dismissed', [ $this, 'dismiss_notice' ] );
		add_action( 'wp_ajax_plausible_analytics_save_admin_settings', [ $this, 'save_admin_settings' ] );
	}

	/**
	 * Takes care of printing the notice.
	 * Notices are now primarily used to display any information related to failures around the Proxy feature introduced in 1.3.0.
	 * If in the future admin-wide notices are used in different contexts, this function needs to be revised.
	 * @since 1.3.0
	 * @return void
	 */
	public function print_notices() {
		$notices = get_transient( Notice::TRANSIENT_NAME );
		$unset   = false;

		foreach ( $this->get_all_notices() as $notice_key => $notice_id ) {
			if ( get_transient( 'plausible_analytics_' . str_replace( '-', '_', $notice_id ) . '_notice_dismissed' ) ) {
				if ( strpos( $notice_key, 'ERROR' ) !== false ) {
					unset( $notices['all']['error'][ $notice_id ] );

					$unset = true;
				}

				if ( strpos( $notice_key, 'SUCCESS' ) !== false ) {
					unset( $notices['all']['success'][ $notice_id ] );

					$unset = true;
				}
			}
		}

		if ( $unset === true ) {
			set_transient( Notice::TRANSIENT_NAME, $notices );
		}

		Notice::print_notices();
	}

	/**
	 * Get all contants starting with NOTICE_ from the Notice class.
	 * This creates a unified way to deal with notice dismissal.
	 * @since 2.0.0
	 * @return array
	 */
	private function get_all_notices() {
		$reflection = new \ReflectionClass( new Notice() );
		$constants  = $reflection->getConstants();

		return array_filter(
			$constants,
			function ( $key ) {
				return strpos( $key, 'NOTICE_' ) !== false;
			},
			ARRAY_FILTER_USE_KEY
		);
	}

	/**
	 * Register Assets.
	 * @since  1.0.0
	 * @since  1.3.0 Don't load CSS admin-wide. JS needs to load admin-wide, since we're throwing admin-wide, dismissable notices.
	 * @access public
	 * @return void
	 */
	public function register_assets( $current_page ) {
		if ( $current_page === 'settings_page_plausible_analytics' || $current_page === 'dashboard_page_plausible_analytics_statistics' ) {
			\wp_enqueue_style(
				'plausible-admin',
				PLAUSIBLE_ANALYTICS_PLUGIN_URL . 'assets/dist/css/plausible-admin.css',
				'',
				filemtime( PLAUSIBLE_ANALYTICS_PLUGIN_DIR . 'assets/dist/css/plausible-admin.css' ),
				'all'
			);
		}

		\wp_enqueue_script(
			'plausible-admin',
			PLAUSIBLE_ANALYTICS_PLUGIN_URL . 'assets/dist/js/plausible-admin.js',
			'',
			filemtime( PLAUSIBLE_ANALYTICS_PLUGIN_DIR . 'assets/dist/js/plausible-admin.js' ),
			true
		);
	}

	/**
	 * Marks a notice as dismissed.
	 * @since v2.0.0 This logic has been revised to generate transients for each available notice separately.
	 * @return void
	 */
	public function dismiss_notice() {
		$id = sanitize_key( $_POST['id'] );

		set_transient( 'plausible_analytics_' . str_replace( '-', '_', $id ) . '_notice_dismissed', true );
	}

	/**
	 * Save Admin Settings
	 * @since 1.0.0
	 * @return void
	 */
	public function save_admin_settings() {
		// Sanitize all the post data before using.
		$post_data        = Helpers::clean( $_POST );
		$current_settings = Helpers::get_settings();

		// Security: Roadblock to check for unauthorized access.
		if ( 'plausible_analytics_save_admin_settings' === $post_data['action'] &&
			current_user_can( 'administrator' ) &&
			( ! empty( $post_data['roadblock'] ) && wp_verify_nonce( $post_data['roadblock'], 'plausible-analytics-settings-roadblock' ) ) ) {
			if ( ! empty( $post_data['plausible_analytics_settings']['domain_name'] ) ||
				isset( $post_data['plausible_analytics_settings']['self_hosted_domain'] ) ) {
				$current_settings = array_replace( $current_settings, $post_data['plausible_analytics_settings'] );

				// Update all the options to plausible settings.
				update_option( 'plausible_analytics_settings', $current_settings );

				$status  = 'success';
				$message = esc_html__( 'Settings saved successfully.', 'plausible-analytics' );
			} else {
				$status  = 'error';
				$message = esc_html__( 'Something went wrong.', 'plausible-analytics' );
			}

			do_action( 'plausible_analytics_settings_saved' );

			// Send response.
			wp_send_json_success(
				[
					'message' => $message,
					'status'  => $status,
				]
			);
		}
	}
}
