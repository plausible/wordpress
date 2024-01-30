<?php
/**
 * Plausible Analytics | Module.
 * @since      1.3.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Includes;

use Plausible\Analytics\WP\Includes\Helpers;

class Ajax {
	/**
	 * @var array Allows defining custom success messages for options.
	 */
	private $option_success_messages = [];

	/**
	 * Build class.
	 */
	public function __construct() {
		$this->option_success_messages = [
			'Enable proxy' => __( 'Proxy', 'plausible-analytics' ),
		];

		$this->init();
	}

	/**
	 * Action and filter hooks.
	 * @return void
	 */
	private function init() {
		add_action( 'wp_ajax_plausible_analytics_quit_wizard', [ $this, 'quit_wizard' ] );
		add_action( 'wp_ajax_plausible_analytics_toggle_option', [ $this, 'toggle_option' ] );
		add_action( 'wp_ajax_plausible_analytics_save_options', [ $this, 'save_options' ] );
	}

	/**
	 * Mark the wizard as finished, so it won't appear again.
	 * @return void
	 */
	public function quit_wizard() {
		$post_data = $this->clean( $_POST );

		if ( $post_data[ 'action' ] !== 'plausible_analytics_quit_wizard' ||
			! current_user_can( 'manage_options' ) ||
			wp_verify_nonce( $post_data[ '_nonce' ], 'plausible_analytics_quit_wizard' ) < 1 ) {
			wp_send_json_error( __( 'Not allowed.', 'plausible-analytics' ), 403 );
		}

		update_option( 'plausible_analytics_wizard_done', true );
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
			wp_send_json_error( __( 'Not allowed.', 'plausible-analytics' ), 403 );
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

		$option_label = $post_data[ 'option_label' ];

		if ( in_array( $option_label, array_keys( $this->option_success_messages ) ) ) {
			$option_label = $this->option_success_messages[ $post_data[ 'option_label' ] ];
		}

		$toggle_status = $post_data[ 'toggle_status' ] === 'on' ? __( 'enabled', 'plausible-analytics' ) : __( 'disabled', 'plausible-analytics' );

		wp_send_json_success( sprintf( '%s %s.', $option_label, $toggle_status ), 200 );
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

		wp_send_json_success( __( 'Settings saved.', 'plausible-analytics' ), 200 );
	}
}
