<?php

/**
 * Plausible Analytics | Admin Actions.
 *
 * This class contains methods that register and handle WordPress actions related to the Plausible Analytics admin settings.
 *
 * @since 1.0.0
 *
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Admin;

use Plausible\Analytics\WP\Includes\Helpers;
use Plausible\Analytics\WP\Includes\RestApi\ApiHelpers;
use Plausible\Analytics\WP\Includes\RestApi\Controllers\RestEventController;
use WP_Error;
use function wp_enqueue_script;
use function wp_enqueue_style;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Actions {

	/**
	 * Constructor.
	 *
	 * Registers the action hooks for this class.
	 *
	 * @return void
	 * @since  1.0.0
	 * @access public
	 *
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'register_assets' ] );
		add_action( 'wp_ajax_plausible_analytics_save_admin_settings', [ $this, 'save_admin_settings' ] );
		add_action( 'maybe_create_js_files', [ $this, 'run_maybe_create_js_files' ] );
	}

	/**
	 * Runs the maybe_create_js_files() method.
	 *
	 * @return void
	 * @since 1.2.5
	 * @access public
	 *
	 */
	public static function run_maybe_create_js_files() {
		PlausibleAnalytics::maybe_create_js_files();
	}

	/**
	 * Create JS files if needed
	 *
	 * @return void | WP_Error
	 * @since 1.2.5
	 *
	 */

	public static function maybe_create_js_files() {

		$errors = [];

		$settings                    = Helpers::get_settings();
		$settings['proxy_is_rest']   = 'false';
		$settings['proxy_is_folder'] = 'false';

		$is_proxy                      = isset( $settings['is_proxy'] ) && $settings['is_proxy'] === 'true';
		$is_custom_path                = isset( $settings['is_custom_path'] ) && $settings['is_custom_path'] === 'true';
		$settings['child_folder_name'] = $child_folder_name = Helpers::get_child_folder_name();

		if ( $is_proxy && ! $is_custom_path ) {

			global $wp_filesystem;
			require_once( ABSPATH . '/wp-admin/includes/file.php' );
			WP_Filesystem();

			$urls = Helpers::get_all_remote_urls();

			$upload_dir    = wp_upload_dir();
			$upload_dir    = $upload_dir ['basedir'];
			$parent_folder = Helpers::get_parent_folder( $upload_dir );
			$child_folder  = Helpers::get_child_folder_path( $parent_folder, $child_folder_name );

			$script_files_path = $child_folder . 'js' . DIRECTORY_SEPARATOR;

			// Check for errors when creating the folder
			if ( ! is_dir( $script_files_path ) ) {
				if ( ! wp_mkdir_p( $script_files_path ) ) {
					$settings['child_folder_name'] = '';

					$error_message = sprintf( __( 'Error occurred while trying to create folder "%s.', 'plausible-analytics' ), $script_files_path );
					error_log( "[Plausible Analytics] $error_message" );
					$errors[] = new WP_Error( 'proxy-mkdir', $error_message );
				}
			}

			if ( ! is_wp_error( $errors ) ) {

				// Loop through the URLs
				foreach ( $urls as $url ) {
					// Get remote files content
					$data = wp_remote_get( $url, [ 'timeout' => 5 ] );

					// Check for errors when fetching the remote file
					if ( is_wp_error( $data ) ) {
						// Check for a specific error code (cURL error 60)
						if ( false !== strpos( $data->get_error_message(), 'cURL error 60' ) ) {
							// Fix for expired certificates in old WordPress version
							$data = wp_remote_get(
								$url,
								[
									'sslverify' => false,
									'timeout'   => 5,
								]
							);
							if ( is_wp_error( $data ) ) {
								$error_message = sprintf( __( 'Error occurred while trying to retrieve URL "%1$s": %2$s', 'plausible-analytics' ), $url, $data->get_error_message() );
								error_log( "[Plausible Analytics] $error_message" );
								$errors[] = new WP_Error( 'proxy-wp-remote_get', $error_message );
								continue;
							}
						} else {
							$error_message = sprintf( __( 'Error occurred while trying to retrieve URL "%1$s": %2$s', 'plausible-analytics' ), $url, $data->get_error_message() );
							error_log( "[Plausible Analytics] $error_message" );
							$errors[] = new WP_Error( 'proxy-wp-remote_get', $error_message );
							continue;
						}
					}

					$file_content = $data['body'];
					$file_name    = wp_basename( $url );

					// Check for errors when saving the file
					$result = $wp_filesystem->put_contents( $script_files_path . $file_name, $file_content );

					if ( is_wp_error( $result ) ) {
						$error_message = sprintf( __( 'Error occurred while creating file "%s".', 'plausible-analytics' ), $file_name );
						error_log( "[Plausible Analytics] $error_message" );
						$errors[] = new WP_Error( 'proxy-put-contents', $error_message );
					}
				}

				$api_files_from = PLAUSIBLE_ANALYTICS_PLUGIN_DIR . 'api-event-files/';

				$api_files_to = $child_folder;
				$api_files    = array_filter( glob( "$api_files_from*" ), 'is_file' );

				if ( ! is_dir( $api_files_to ) ) {
					if ( ! wp_mkdir_p( $api_files_to ) ) {
						$error_message = __( 'Error when creating the API folder', 'plausible-analytics' );
						error_log( "[Plausible Analytics] $error_message" );
						$errors[] = new WP_Error( 'proxy-mkdir-api', $error_message );

					}
				}

				foreach ( $api_files as $api_file ) {
					copy( $api_file, $api_files_to . basename( $api_file ) );
				}

				if ( ! is_wp_error( $errors ) ) {

					$response = Helpers::check_php_proxy_api();

					if ( 202 === $response ) {
						$settings['proxy_is_folder'] = 'true';
					} else {
						$error_message = sprintf( __( 'PHP proxy returned error code %d when trying to access URL', 'plausible-analytics' ), $response );
						error_log( "[Plausible Analytics] $error_message" );
						$errors[] = new WP_Error( 'proxy-api-php', $error_message, $response );

					}
				}

				// At this point, all folders and files was created
				if ( ! is_wp_error( $errors ) ) {
					update_option( 'plausible_analytics_is_js_files_created', true );
					Helpers::schedule_maybe_create_js_files();

					// Check the status of the REST API
					$response = ApiHelpers::check_rest_api();

					if ( 202 === $response ) {
						// If the REST API is working, set the 'proxy_is_rest' setting to 'true'
						$settings['proxy_is_rest'] = 'true';
					} else {
						$error_message = sprintf( __( 'REST API returned error code %d when trying to access endpoint', 'plausible-analytics' ), $response );
						error_log( "[Plausible Analytics] $error_message" );
						$errors[] = new WP_Error( 'proxy-api-rest', $error_message, $response );

					}
				}
			}

			if ( 'false' == $settings['proxy_is_folder'] && 'false' == $settings['proxy_is_rest'] ) {
				$settings['is_proxy'] = 'false';
				wp_clear_scheduled_hook( 'maybe_create_js_files' );
			}
		}

		Helpers::save_settings( $settings );

		return $errors;

	}

	/**
	 * Registers the Assets.
	 *
	 * Enqueues the styles and scripts used by the Plausible Analytics admin settings page.
	 *
	 * @return void
	 * @since  1.0.0
	 * @access public
	 *
	 */
	public
	function register_assets() {
		wp_enqueue_style( 'plausible-admin', PLAUSIBLE_ANALYTICS_PLUGIN_URL . 'assets/dist/css/plausible-admin.css', '', PLAUSIBLE_ANALYTICS_VERSION, 'all' );
		wp_enqueue_script( 'plausible-admin', PLAUSIBLE_ANALYTICS_PLUGIN_URL . 'assets/dist/js/plausible-admin.js', '', PLAUSIBLE_ANALYTICS_VERSION, true );
	}

	/**
	 * Save Admin Settings
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */
	public function save_admin_settings() {
		// Sanitize all the post data before using.
		$post_data    = Helpers::clean( $_POST );
		$old_settings = Helpers::get_settings();

		// Security: Roadblock to check for unauthorized access.
		if (
			'plausible_analytics_save_admin_settings' === $post_data['action'] &&
			current_user_can( 'administrator' ) &&
			(
				! empty( $post_data['roadblock'] ) &&
				wp_verify_nonce( $post_data['roadblock'], 'plausible-analytics-settings-roadblock' )
			)
		) {
			// Unset unnecessary posted data to store into database.
			unset( $post_data['action'] );
			unset( $post_data['roadblock'] );

			if (
				! empty( $post_data['is_proxy'] ) &&
				! empty( $post_data['is_custom_path'] ) &&
				! empty( $post_data['is_self_hosted_analytics'] ) &&
				! empty( $post_data['embed_analytics'] ) &&
				! empty( $post_data['track_administrator'] )

			) {

				if ( empty( $post_data['domain_name'] ) ) {
					$post_data['domain_name'] = Helpers::get_domain();
				}

				// Disable embed_analytics if no shared link provided
				if ( empty( $post_data['shared_link'] ) ) {
					$post_data['embed_analytics'] = 'false';
				}

				// Disable is_custom_path if no custom path provided
				if ( ( $post_data['is_proxy'] === 'false' || empty( $post_data['script_path'] ) || empty( $post_data['event_path'] ) ) ) {
					$post_data['is_custom_path'] = 'false';
				}

				// trailing slash on paths settings if filled
				if ( ! empty( $post_data['script_path'] ) ) {
					$post_data['script_path'] = trailingslashit( $post_data['script_path'] );
				}
				if ( ! empty( $post_data['event_path'] ) ) {
					$post_data['event_path'] = trailingslashit( $post_data['event_path'] );
				}

				$status = 'success';

				Helpers::save_settings( $post_data, $old_settings );
				$message = esc_html__( 'Settings saved successfully.', 'plausible-analytics' );
			} else {
				$status  = 'error';
				$message = esc_html__( 'Something gone a wrong.', 'plausible-analytics' );
			}

			// Maybe create js files
			$error = self::maybe_create_js_files();

			// Send response.
			wp_send_json_success(
				[
					'message' => $message,
					'status'  => $status,
					'error'   => $error,
				]
			);
		}
	}

}
