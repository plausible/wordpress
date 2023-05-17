<?php
/**
 * Plausible Analytics | Module.
 *
 * @since      1.3.0
 *
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Admin;

use Exception;
use Plausible\Analytics\WP\Admin\Notice;
use Plausible\Analytics\WP\Includes\Helpers;

class Module {
	/**
	 * Build properties.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Filters & Actions.
	 *
	 * @return void
	 */
	private function init() {
		add_action( 'admin_init', [ $this, 'maybe_show_notice' ] );
		add_action( 'admin_notices', [ $this, 'print_notices' ] );
		add_filter( 'pre_update_option_plausible_analytics_settings', [ $this, 'maybe_install_module' ], 9 );
		add_filter( 'pre_update_option_plausible_analytics_settings', [ $this, 'maybe_enable_proxy' ], 10, 2 );
	}

	/**
	 * Show an admin-wide notice if the Speed Module failed to install.
	 *
	 * @since 1.3.0
	 *
	 * @return void
	 */
	public function maybe_show_notice() {
		$settings = Helpers::get_settings();

		if ( ! empty( $settings['proxy_enabled'][0] ) && ! file_exists( WPMU_PLUGIN_DIR . 'plausible-proxy-speed-module.php' ) ) {
			$this->throw_notice();
		}
	}

	/**
	 * @since 1.3.0
	 *
	 * @return void
	 */
	private function throw_notice() {
		Notice::set_notice( sprintf( wp_kses( __( 'Plausible\'s proxy is enabled, but the Proxy Speed Module failed to install. Try <a href="%s" target="_blank">installing it manually</a>.', 'plausible-analytics' ), 'post' ), 'https://plausible.io/wordpress-analytics-plugin#if-the-proxy-script-is-slow' ), 'plausible-analytics-module-install-failed', 'error' );
	}

	/**
	 * Takes care of printing the notice.
	 *
	 * Notices are now primarily used to display any information related to failures around the Proxy feature introduced in 1.3.0.
	 * If in the future admin-wide notices are used in different contexts, this function needs to be revised.
	 *
	 * @since 1.3.0
	 *
	 * @return void
	 */
	public function print_notices() {
		if ( get_transient( 'plausible_analytics_notice_dismissed' ) ) {
			delete_transient( Notice::TRANSIENT_NAME );

			return;
		}

		Notice::print_notices();
	}

	/**
	 * Decide whether we should install the module, or not.
	 *
	 * @since 1.3.0
	 *
	 * @return void
	 */
	public function maybe_install_module( $settings ) {
		if ( ! empty( $settings['proxy_enabled'][0] ) ) {
			$this->install();
		} else {
			$this->uninstall();
		}

		return $settings;
	}

	/**
	 * Takes care of installing the M(ust)U(se) plugin when the Proxy is enabled.
	 *
	 * @since 1.3.0
	 *
	 * @return bool True on success, false on failure.
	 */
	public function install() {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return false;
		}

		WP_Filesystem();

		if ( ! is_dir( WPMU_PLUGIN_DIR ) ) {
			wp_mkdir_p( WPMU_PLUGIN_DIR );
			add_option( 'plausible_analytics_created_mu_plugins_dir', true );
		}

		if ( ! is_dir( WPMU_PLUGIN_DIR ) ) {
			$this->throw_notice();

			return false;
		}

		$results = copy_dir( PLAUSIBLE_ANALYTICS_PLUGIN_DIR . 'mu-plugin', WPMU_PLUGIN_DIR );

		// if ( is_wp_error( $results ) ) {
			$this->throw_notice();

			return false;
		// }

		add_option( 'plausible_analytics_proxy_speed_module_installed', true );

		return true;
	}

	/**
	 * Uninstall the Speed Module and all related settings when the proxy is disabled.
	 *
	 * @since 1.3.0
	 *
	 * @return bool True on success, false on failure.
	 */
	public function uninstall() {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return false;
		}

		$file_path = WP_CONTENT_DIR . '/mu-plugins/plausible-proxy-speed-module.php';

		if ( file_exists( $file_path ) ) {
			unlink( $file_path );
		}

		if ( get_option( 'plausible_analytics_created_mu_plugins_dir' ) && $this->dir_is_empty( WPMU_PLUGIN_DIR ) ) {
			rmdir( WPMU_PLUGIN_DIR );
		}

		delete_option( 'plausible_analytics_created_mu_plugins_dir' );
		delete_option( 'plausible_analytics_proxy_speed_module_installed' );
		delete_option( 'plausible_analytics_proxy_resources' );
		delete_transient( 'plausible_analytics_notice_dismissed' );

		return true;
	}

	/**
	 * Check if a directory is empty.
	 *
	 * This works because a new FilesystemIterator will initially point to the first file in the folder -
	 * if there are no files in the folder, valid() will return false.
	 *
	 * @see https://www.php.net/manual/en/directoryiterator.valid.php
	 *
	 * @since 1.3.0
	 *
	 * @param mixed $dir
	 * @return bool
	 */
	private function dir_is_empty( $dir ) {
		$iterator = new \FilesystemIterator( $dir );

		return ! $iterator->valid();
	}

	/**
	 * Test the proxy before enabling the option.
	 *
	 * @since 1.3.0
	 *
	 * @param mixed $settings
	 * @param mixed $old_settings
	 *
	 * @return mixed
	 *
	 * @throws Exception
	 */
	public function maybe_enable_proxy( $settings, $old_settings ) {
		$test_succeeded = $this->test_proxy( ! empty( $settings['proxy_enabled'][0] ) );

		if ( ! $test_succeeded && ! empty( $settings['proxy_enabled'][0] ) ) {
			Notice::set_notice( sprintf( wp_kses( __( 'Plausible\'s proxy couldn\'t be enabled, because the WordPress API is inaccessable. This might be due to a conflicting setting in a (security) plugin or server firewall. Make sure you whitelist requests to the Proxy\'s endpoint: <code>%1$s</code>. <a href="%2$s" target="_blank">Contact support</a> if you need help locating the issue.', 'plausible-analytics' ), 'post' ), Helpers::get_rest_endpoint( false ), 'https://plausible.io/contact' ), 'plausible-analytics-proxy-failed', 'error' );

			return $old_settings;
		}

		return $settings;
	}

	/**
	 * Runs a quick internal call to the WordPress API to make sure it's accessable.
	 *
	 * @since 1.3.0
	 *
	 * @return bool
	 *
	 * @throws Exception
	 */
	private function test_proxy( $run = true ) {
		// Should we run the test?
		if ( ! $run ) {
			return false;
		}

		$namespace = Helpers::get_proxy_resource( 'namespace' );
		$base      = Helpers::get_proxy_resource( 'base' );
		$endpoint  = Helpers::get_proxy_resource( 'endpoint' );
		$request   = new \WP_REST_Request( 'POST', "/$namespace/v1/$base/$endpoint" );
		$request->set_body(
			wp_json_encode(
				[
					'd' => 'plausible.test',
					'n' => 'pageview',
					'u' => 'https://plausible.test/test',
				]
			)
		);

		/** @var \WP_REST_Response $result */
		try {
			$result = rest_do_request( $request );
		} catch ( \Exception ) {
			//  There's no need to handle the error, because we don't want to display it anyway.
			return false;
		}

		return wp_remote_retrieve_response_code( $result->get_data() ) === 202;
	}
}
