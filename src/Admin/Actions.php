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

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Actions {

	/**
	 * Constructor.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'register_assets' ] );
		add_action( 'wp_ajax_plausible_analytics_save_admin_settings', [ $this, 'save_admin_settings' ] );
	}

	/**
	 * Register Assets.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_assets() {
		\wp_enqueue_style( 'plausible-admin', PLAUSIBLE_ANALYTICS_PLUGIN_URL . 'assets/dist/css/plausible-admin.css', '', PLAUSIBLE_ANALYTICS_VERSION, 'all' );
		\wp_enqueue_script( 'plausible-admin', PLAUSIBLE_ANALYTICS_PLUGIN_URL . 'assets/dist/js/plausible-admin.js', '', PLAUSIBLE_ANALYTICS_VERSION, true );
	}

	public function save_admin_settings() {
		$post_data = array();
		
		$post_data['action'] = Helpers::get_text_clean(trim($_POST['action']));
		$post_data['roadblock'] = Helpers::get_text_clean(trim($_POST['roadblock']));
		$post_data['domain_name'] = Helpers::get_text_clean(trim($_POST['domain_name']));
		$post_data['custom_domain'] = Helpers::get_text_clean(trim($_POST['custom_domain']));
		$post_data['custom_domain_prefix'] = Helpers::get_text_clean(trim($_POST['custom_domain_prefix']));
		$post_data['is_self_hosted_analytics'] = Helpers::get_text_clean(trim($_POST['is_self_hosted_analytics']));
		$post_data['self_hosted_domain'] = Helpers::get_text_clean(trim($_POST['self_hosted_domain']));
		$post_data['embed_analytics'] = Helpers::get_text_clean(trim($_POST['embed_analytics']));
		$post_data['shared_link'] = Helpers::get_text_clean(trim($_POST['shared_link']));
		$post_data['track_administrator'] = Helpers::get_text_clean(trim($_POST['track_administrator']));

		// Security: Roadblock to check for unauthorized access.
		check_admin_referer( 'plausible-analytics-settings-roadblock', 'roadblock' );

		// Unset unnecessary posted data to store into database.
		unset( $post_data['action'] );
		unset( $post_data['roadblock'] );

		if ( !empty( $post_data['domain_name'] ) && !empty( $post_data['custom_domain_prefix'] ) && !empty( $post_data['self_hosted_domain'] ) && !empty( $post_data['shared_link'] ) ) {
			
			$status = 'success';

			update_option( 'plausible_analytics_settings', $post_data );

			$message = esc_html__( 'Settings saved successfully.', 'plausible-analytics' );
		} else {
			$status = 'error';
			$message = esc_html__( 'Something gone a wrong.', 'plausible-analytics' );
		}
		
		// Send response.
		wp_send_json_success(
			[
				'message' => $message,
				'status' => $status
			]
		);
	}
}
