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
		add_action( 'wp_ajax_plausible_analytics_toggle_option', [ $this, 'toggle_option' ] );
		add_action( 'wp_ajax_plausible_analytics_save_options', [ $this, 'save_options' ] );
	}

	/**
	 * Takes care of printing the notice.
	 * Notices are now primarily used to display any information related to failures around the Proxy feature introduced in 1.3.0.
	 * If in the future admin-wide notices are used in different contexts, this function needs to be revised.
	 * @since 1.3.0
	 * @return void
	 */
	public function print_notices() {
		$notices = get_transient( Notice::TRANSIENT_NAME ) ?: [];
		$unset   = false;

		foreach ( $this->get_all_notices() as $notice_key => $notice_id ) {
			if ( get_transient( 'plausible_analytics_' . str_replace( '-', '_', $notice_id ) . '_notice_dismissed' ) ) {
				if ( strpos( $notice_key, 'ERROR' ) !== false ) {
					unset( $notices[ 'all' ][ 'error' ][ 'plausible-analytics-' . $notice_id ] );

					$unset = true;
				}

				if ( strpos( $notice_key, 'SUCCESS' ) !== false ) {
					unset( $notices[ 'all' ][ 'success' ][ 'plausible-analytics-' . $notice_id ] );

					$unset = true;
				}
			}
		}

		if ( $unset === true ) {
			set_transient( Notice::TRANSIENT_NAME, $notices );
		}

		//		Notice::print_notices();
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
		$id = sanitize_key( $_POST[ 'id' ] );

		set_transient( str_replace( '-', '_', $id ) . '_notice_dismissed', true );
	}

	/**
	 * Save Admin Settings
	 * @since 1.0.0
	 * @return void
	 */
	public function toggle_option() {
		// Sanitize all the post data before using.
		$post_data = $this->clean( $_POST );
		$settings  = Helpers::get_settings();

		if ( $post_data[ 'action' ] !== 'plausible_analytics_toggle_option' ||
			! current_user_can( 'manage_options' ) ||
			wp_verify_nonce( $post_data[ '_nonce' ], 'plausible_analytics_toggle_option' ) < 1 ) {
			return;
		}

		if ( $post_data[ 'is_list' ] ) {
			/**
			 * Toggle lists.
			 */
			if ( $post_data[ 'toggle_status' ] === 'on' ) {
				if ( ! in_array( $post_data[ 'option_value' ], $settings[ $post_data[ 'option_name' ] ] ) ) {
					$settings[ $post_data[ 'option_name' ] ][] = $post_data[ 'option_value' ];
				}
			} else {
				if ( ( $key = array_search( $post_data[ 'option_value' ], $settings[ $post_data[ 'option_name' ] ] ) ) !== false ) {
					unset( $settings[ $post_data[ 'option_name' ] ][ $key ] );
				}
			}
		} else {
			/**
			 * Single toggles.
			 */
			$settings[ $post_data[ 'option_name' ] ] = $post_data[ 'toggle_status' ];
		}

		// Update all the options to plausible settings.
		update_option( 'plausible_analytics_settings', $settings );

		do_action( 'plausible_analytics_settings_saved' );

		// Send response.
		wp_send_json_success();
	}

	/**
	 * Clean variables using `sanitize_text_field`.
	 * Arrays are cleaned recursively. Non-scalar values are ignored.
	 * @since  1.3.0
	 * @access public
	 *
	 * @param string|array $var Sanitize the variable.
	 *
	 * @return string|array
	 */
	private function clean( $var ) {
		// If the variable is an array, recursively apply the function to each element of the array.
		if ( is_array( $var ) ) {
			return array_map( [ $this, 'clean' ], $var );
		}

		// If the variable is a scalar value (string, integer, float, boolean).
		if ( is_scalar( $var ) ) {
			// Parse the variable using the wp_parse_url function.
			$parsed = wp_parse_url( $var );
			// If the variable has a scheme (e.g. http:// or https://), sanitize the variable using the esc_url_raw function.
			if ( isset( $parsed[ 'scheme' ] ) ) {
				return esc_url_raw( wp_unslash( $var ), [ $parsed[ 'scheme' ] ] );
			}

			// If the variable does not have a scheme, sanitize the variable using the sanitize_text_field function.
			return sanitize_text_field( wp_unslash( $var ) );
		}

		// If the variable is not an array or a scalar value, return the variable unchanged.
		return $var;
	}

	/**
	 * Save Options
	 * @return void
	 */
	public function save_options() {
		// Sanitize all the post data before using.
		$post_data = $this->clean( $_POST );
		$settings  = Helpers::get_settings();

		if ( $post_data[ 'action' ] !== 'plausible_analytics_save_options' ||
			! current_user_can( 'manage_options' ) ||
			wp_verify_nonce( $post_data[ '_nonce' ], 'plausible_analytics_toggle_option' ) < 1 ) {
			return;
		}

		$options = json_decode( $post_data[ 'options' ] );

		if ( empty( $options ) ) {
			return;
		}

		foreach ( $options as $option ) {
			$settings[ $option->name ] = $option->value;
		}

		update_option( 'plausible_analytics_settings', $settings );

		wp_send_json_success();
	}
}
