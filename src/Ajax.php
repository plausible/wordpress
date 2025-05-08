<?php
/**
 * Plausible Analytics | Module.
 *
 * @since      1.3.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP;

use Plausible\Analytics\WP\Admin\Messages;
use Plausible\Analytics\WP\Admin\Settings\Hooks;
use Plausible\Analytics\WP\Admin\Settings\Page;
use Plausible\Analytics\WP\Client\ApiException;

/**
 * @codeCoverageIgnore At least until I figure out how to properly test AJAX requests in CI.
 */
class Ajax {
	/**
	 * Build class.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Action and filter hooks.
	 *
	 * @return void
	 */
	private function init() {
		add_action( 'wp_ajax_plausible_analytics_messages', [ $this, 'fetch_messages' ] );
		add_action( 'wp_ajax_plausible_analytics_quit_wizard', [ $this, 'quit_wizard' ] );
		add_action( 'wp_ajax_plausible_analytics_show_wizard', [ $this, 'show_wizard' ] );
		add_action( 'wp_ajax_plausible_analytics_toggle_option', [ $this, 'toggle_option' ] );
		add_action( 'wp_ajax_plausible_analytics_save_options', [ $this, 'save_options' ] );
	}

	/**
	 * Returns an array of messages fetched from transients for display by JS.
	 */
	public function fetch_messages() {
		$notice             = get_transient( Messages::NOTICE_TRANSIENT );
		$error              = get_transient( Messages::ERROR_TRANSIENT );
		$success            = get_transient( Messages::SUCCESS_TRANSIENT );
		$additional         = get_transient( Messages::ADDITIONAL_TRANSIENT ) ?: [];
		$additional_message = [];

		if ( ! empty( $additional ) ) {
			$additional_message = [
				'id'      => array_key_first( $additional ),
				'message' => $additional[ array_key_first( $additional ) ],
			];
		}

		$messages = apply_filters(
			'plausible_analytics_messages',
			[
				'notice'     => $notice,
				'error'      => $error,
				'success'    => $success,
				'additional' => $additional_message,
			]
		);

		wp_send_json_success( $messages, 200 );
	}

	/**
	 * Mark the wizard as finished, so it won't appear again, and optionally redirect.
	 *
	 * @return void
	 */
	public function quit_wizard() {
		$request_data = $this->clean( $_REQUEST );

		if ( ! current_user_can( 'manage_options' ) || wp_verify_nonce( $request_data[ '_nonce' ], 'plausible_analytics_quit_wizard' ) < 1 ) {
			Messages::set_error( __( 'Not allowed', 'plausible-analytics' ) );

			wp_send_json_error( null, 403 );
		}

		update_option( 'plausible_analytics_wizard_done', true );

		$this->maybe_handle_redirect( $request_data[ 'redirect' ] );

		wp_send_json_success();
	}

	/**
	 * Clean variables using `sanitize_text_field`.
	 * Arrays are cleaned recursively. Non-scalar values are ignored.
	 *
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
	 * Makes the AJAX request redirect instead of e.g. return JSON.
	 *
	 * @param $direction
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore
	 */
	private function maybe_handle_redirect( $direction ) {
		if ( ! empty( $direction ) ) {
			$url = admin_url( 'options-general.php?page=plausible_analytics' );

			// Redirect param points to a specific option.
			if ( strpos( $direction, 'self-hosted' ) !== false ) {
				$url .= '&tab=' . $direction;
			} elseif ( $direction !== '1' ) {
				$url .= '#' . $direction;
			}

			wp_redirect( $url );

			exit;
		}
	}

	/**
	 * Removes the plausible_analytics_wizard_done row from the wp_options table, effectively displaying the wizard on next page load.
	 *
	 * @return void
	 */
	public function show_wizard() {
		$request_data = $this->clean( $_REQUEST );

		if ( ! current_user_can( 'manage_options' ) || wp_verify_nonce( $request_data[ '_nonce' ], 'plausible_analytics_show_wizard' ) < 1 ) {
			Messages::set_error( __( 'Not allowed.', 'plausible-analytics' ) );

			wp_send_json_error( null, 403 );
		}

		delete_option( 'plausible_analytics_wizard_done' );

		$this->maybe_handle_redirect( $request_data[ 'redirect' ] );

		wp_send_json_success();
	}

	/**
	 * Save Admin Settings
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function toggle_option() {
		// Sanitize all the post data before using.
		$post_data = $this->clean( $_POST );
		$settings  = Helpers::get_settings();

		if ( ! current_user_can( 'manage_options' ) || wp_verify_nonce( $post_data[ '_nonce' ], 'plausible_analytics_toggle_option' ) < 1 ) {
			wp_send_json_error( __( 'Not allowed.', 'plausible-analytics' ), 403 );
		}

		if ( $post_data[ 'is_list' ] ) {
			/**
			 * Toggle lists.
			 */
			if ( $post_data[ 'toggle_status' ] === 'on' ) {
				// If toggle is on, store the value under a new key.
				if ( ! in_array( $post_data[ 'option_value' ], $settings[ $post_data[ 'option_name' ] ] ) ) {
					$settings[ $post_data[ 'option_name' ] ][] = $post_data[ 'option_value' ];
				}
			} else {
				// If toggle is off, find the key by its value and unset it.
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

		/**
		 * Allow devs to perform additional actions.
		 */
		do_action( 'plausible_analytics_settings_saved', $settings, $post_data[ 'option_name' ], $post_data[ 'toggle_status' ] );

		$option_label  = $post_data[ 'option_label' ];
		$toggle_status = $post_data[ 'toggle_status' ] === 'on' ? __( 'enabled', 'plausible-analytics' ) : __( 'disabled', 'plausible-analytics' );
		$message       = apply_filters(
			'plausible_analytics_toggle_option_success_message',
			sprintf( '%s %s.', $option_label, $toggle_status ),
			$post_data[ 'option_name' ],
			$post_data[ 'toggle_status' ]
		);

		Messages::set_success( $message );

		$additional = $this->maybe_render_additional_message( $post_data[ 'option_name' ], $post_data[ 'toggle_status' ] );

		Messages::set_additional( $additional, $post_data[ 'option_name' ] );

		wp_send_json_success( null, 200 );
	}

	/**
	 * Adds the 'additional' array element to $message if applicable.
	 *
	 * @param $option_name
	 * @param $option_value
	 *
	 * @return string
	 */
	private function maybe_render_additional_message( $option_name, $option_value ) {
		$additional_message_html = '';
		$hooks                   = new Hooks( false );

		if ( $option_name === 'proxy_enabled' && $option_value !== '' ) {
			$additional_message_html = $hooks->render_hook_field( Page::PROXY_WARNING_HOOK );
		}

		if ( $option_name === 'enable_analytics_dashboard' && $option_value !== '' ) {
			$additional_message_html = $hooks->render_hook_field( Page::ENABLE_ANALYTICS_DASH_NOTICE );
		}

		if ( $option_name === 'api_token' && $option_value === '' ) {
			$additional_message_html = $hooks->render_hook_field( Page::API_TOKEN_MISSING_HOOK );
		}

		return $additional_message_html;
	}

	/**
	 * Save Options
	 *
	 * @return void
	 * @throws ApiException
	 */
	public function save_options() {
		// Sanitize all the post-data before using.
		$post_data = $this->clean( $_POST );
		$settings  = Helpers::get_settings();

		if ( ! current_user_can( 'manage_options' ) || wp_verify_nonce( $post_data[ '_nonce' ], 'plausible_analytics_toggle_option' ) < 1 ) {
			Messages::set_error( __( 'Not allowed.', 'plausible-analytics' ) );

			wp_send_json_error( null, 403 );
		}

		$options = json_decode( $post_data[ 'options' ] );

		if ( empty( $options ) ) {
			Messages::set_error( __( 'No options found to save.', 'plausible-analytics' ) );

			wp_send_json_error( null, 400 );
		}

		/**
		 * If we're dealing with an array of inputs (e.g. item[0], item[1], etc.), we need to convert $options , before storing it in the database.
		 *
		 * @since 2.4.0
		 */
		$input_array_elements = array_filter(
			$options,
			function ( $option ) {
				return preg_match( '/\[[0-9]+]/', $option->name );
			}
		);

		if ( count( $input_array_elements ) > 0 ) {
			$options            = [];
			$array_name         = preg_replace( '/\[[0-9]+]/', '', $input_array_elements[ 0 ]->name );
			$options[ 0 ]       = (object) [];
			$options[ 0 ]->name = $array_name;

			foreach ( $input_array_elements as $input_array_element ) {
				if ( $input_array_element->value ) {
					$options[ 0 ]->value[] = $input_array_element->value;
				}
			}
		}

		foreach ( $options as $option ) {
			// Clean spaces
			if ( is_string( $option->value ) ) {
				$settings[ $option->name ] = trim( $option->value );
			} else {
				$settings[ $option->name ] = $option->value;
			}

			// Validate Plugin Token if this is the Plugin Token field.
			if ( $option->name === 'api_token' ) {
				$this->validate_api_token( $option->value );

				$additional = $this->maybe_render_additional_message( $option->name, $option->value );

				Messages::set_additional( $additional, $option->name );
			}
		}

		update_option( 'plausible_analytics_settings', $settings );

		Messages::set_success( __( 'Settings saved.', 'plausible-analytics' ) );

		wp_send_json_success( null, 200 );
	}

	/**
	 * Validate the entered Plugin Token, before storing it to the DB. wp_send_json_error() ensures that code execution stops.
	 *
	 * @param string $token
	 *
	 * @return void
	 * @throws ApiException
	 */
	private function validate_api_token( $token = '' ) {
		$client_factory = new ClientFactory( $token );
		$client         = $client_factory->build();

		if ( $client instanceof Client && ! $client->validate_api_token() ) {
			$hosted_domain = Helpers::get_hosted_domain_url();
			$domain        = Helpers::get_domain();

			Messages::set_error(
				sprintf(
					__(
						'Oops! The Plugin Token you used is invalid. Please <a href="%s" target="_blank">create a new token</a>. <a target="_blank" href="%s">Read more</a>',
						'plausible-analytics'
					),
					"$hosted_domain/$domain/settings/integrations?new_token=WordPress",
					'https://plausible.io/wordpress-analytics-plugin#oops-the-token-you-used-is-invalid'
				)
			);

			wp_send_json_error( 'invalid_api_token', 400 );
		}
	}
}
