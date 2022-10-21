<?php
/**
 * Plausible Analytics | Admin Actions.
 *
 * @since 1.0.0
 *
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Admin;

use Plausible\Analytics\WP\Includes\Helpers;
use Plausible\Analytics\WP\Includes\RestApi\Controllers\RestEventController;
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
	 * @return void
	 * @since  1.0.0
	 * @access public
	 *
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'register_assets' ] );
		add_action( 'wp_ajax_plausible_analytics_save_admin_settings', [ $this, 'save_admin_settings' ] );
	}

	/**
	 * Register Assets.
	 *
	 * @return void
	 * @since  1.0.0
	 * @access public
	 *
	 */
	public function register_assets() {
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
		$post_data = Helpers::clean( $_POST );

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
				if ( empty ( $post_data['shared_link'] ) ) {
					$post_data['embed_analytics'] = 'false';
				}

				// Disable is_custom_path if no custom path provided
				if ( ( $post_data['is_proxy'] === 'false' || empty ( $post_data['script_path'] ) || empty ( $post_data['event_path'] ) ) ) {
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
				update_option( 'plausible_analytics_settings', $post_data );
				$message = esc_html__( 'Settings saved successfully.', 'plausible-analytics' );
			} else {
				$status  = 'error';
				$message = esc_html__( 'Something gone a wrong.', 'plausible-analytics' );
			}

			// Maybe create js files in WordPress root
			self::maybe_create_js_files();

			// Send response.
			wp_send_json_success(
				[
					'message' => $message,
					'status'  => $status
				]
			);
		}
	}

	/**
	 * Create JS files if needed
	 *
	 * @return void | WP_Error
	 * @since 1.2.5
	 *
	 */

	public static function maybe_create_js_files() {
		$settings = Helpers::get_settings();

		$is_proxy       = isset( $settings['is_proxy'] ) && $settings['is_proxy'] === 'true';
		$is_custom_path = isset( $settings['is_custom_path'] ) && $settings['is_custom_path'] === 'true';

		if ( $is_proxy && ! $is_custom_path ) {

			global $wp_filesystem;
			require_once( ABSPATH . '/wp-admin/includes/file.php' );
			WP_Filesystem();

			$urls = Helpers::get_all_remote_urls();

			/**
			 * Filters to modify the path of the parent folder where the analytics scripts folder will be created
			 *
			 * The Full path to the parent folder
			 * By default, the path is WP_CONTENT_DIR;
			 *
			 * @since 1.2.5
			 *
			 */

			$upload_dir = wp_upload_dir();
			$upload_dir = $upload_dir ['basedir'];
			$parent_folder = apply_filters( 'plausible_analytics_scripts_parent_folder', $upload_dir );


			/**
			 * Filters to rename the folder where the analytics scripts files will be created
			 *
			 * The folder name
			 * By default, the folder name is 'stats';
			 *
			 * @since 1.2.5
			 *
			 */
			$folder = trailingslashit( $parent_folder . DIRECTORY_SEPARATOR . apply_filters( 'plausible_analytics_scripts_folder', 'stats' ) );

			foreach ( $urls as $url ) {

				$data = wp_remote_get( $url );

				if ( is_wp_error( $data ) && false !== strpos( $data->get_error_message(), 'cURL error 60' ) ) {
					// Fix for expired certificates in old WordPress version
					$data = wp_remote_get( $url, array( 'sslverify' => false ) );
					if ( is_wp_error( $data ) ) {
						return $data;
					}
				} else if ( is_wp_error( $data ) ) {
					return $data;
				}

				$file_content = $data['body'];
				$file_name    = wp_basename( $url );
				$file_path    = $folder . 'js' . DIRECTORY_SEPARATOR;

				if ( ! is_dir( $file_path ) ) {
					if ( ! wp_mkdir_p( $file_path ) ) {
						return new WP_Error( 'mkdir', __( 'Error when creating the folder', 'plausible-analytics' ) );

					}
				}

				$result = $wp_filesystem->put_contents( $file_path . $file_name, $file_content );
				if ( ! $result ) {
					return new WP_Error( 'put_contents', __( 'Error when creating the file' . $file_name, 'plausible-analytics' ) );
				}

			}

			// test if restapi is working.

			$api_rest_url = RestEventController::get_event_route_url();

			$response = wp_remote_post( $api_rest_url, array( 'sslverify' => false ) );
			// Retrieve information
			$response_code    = wp_remote_retrieve_response_code( $response );

			if ( 200 == $response_code  ) {
				$settings['is_rest'] = 'true';
			} else {
				$settings['is_rest'] = 'false';
			}

			update_option( 'plausible_analytics_settings', $settings );

			$api_files_from = PLAUSIBLE_ANALYTICS_PLUGIN_DIR . 'api-event-files/';

			$api_files_to = $folder . 'api/event/';
			$api_files    = array_filter( glob( "$api_files_from*" ), "is_file" );

			if ( ! is_dir( $api_files_to ) ) {
				if ( ! wp_mkdir_p( $api_files_to ) ) {
					return new WP_Error( 'mkdir', __( 'Error when creating the API folder', 'plausible-analytics' ) );
				}
			}

			foreach ( $api_files as $api_file ) {
				copy( $api_file, $api_files_to . basename( $api_file ) );
			}

		}
	}

}
