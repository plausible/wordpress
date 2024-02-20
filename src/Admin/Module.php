<?php
/**
 * Plausible Analytics | Module.
 * @since      1.3.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Admin;

use Exception;
use Plausible\Analytics\WP\Includes\Helpers;

class Module {
	/**
	 * Build properties.
	 * @return void
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Filters & Actions.
	 * @return void
	 */
	private function init() {
		add_action( 'update_option_plausible_analytics_settings', [ $this, 'maybe_install_module' ], 9, 2 );
		add_filter( 'pre_update_option_plausible_analytics_settings', [ $this, 'maybe_enable_proxy' ], 10, 2 );
	}

	/**
	 * Decide whether we should install the module, or not.
	 * @since 1.3.0
	 *
	 * @param array $settings Current settings, already written to the DB.
	 *
	 * @return void
	 */
	public function maybe_install_module( $old_settings, $settings ) {
		if ( $settings[ 'proxy_enabled' ] === 'on' && $old_settings[ 'proxy_enabled' ] !== 'on' ) {
			$this->install();
		} elseif ( $settings[ 'proxy_enabled' ] === '' && $old_settings[ 'proxy_enabled' ] === 'on' ) {
			$this->uninstall();
		}
	}

	/**
	 * Takes care of installing the M(ust)U(se) plugin when the Proxy is enabled.
	 * @since 1.3.0
	 * @return void.
	 */
	public function install() {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		WP_Filesystem();

		if ( ! is_dir( WPMU_PLUGIN_DIR ) ) {
			wp_mkdir_p( WPMU_PLUGIN_DIR );
			add_option( 'plausible_analytics_created_mu_plugins_dir', true );
		}

		if ( ! is_dir( WPMU_PLUGIN_DIR ) ) {
			$this->show_module_not_installed_error();
		}

		$results = copy_dir( PLAUSIBLE_ANALYTICS_PLUGIN_DIR . 'mu-plugin', WPMU_PLUGIN_DIR );

		if ( is_wp_error( $results ) ) {
			$this->show_module_not_installed_error();
		}

		add_option( 'plausible_analytics_proxy_speed_module_installed', true );
	}

	/**
	 * @since 1.3.0
	 * @return void
	 */
	private function show_module_not_installed_error() {
		$message = sprintf(
			wp_kses(
				__(
					'The proxy is enabled, but the proxy\'s speed module failed to install. Try <a href="%s" target="_blank">installing it manually</a>.',
					'plausible-analytics'
				),
				'post'
			),
			'https://plausible.io/wordpress-analytics-plugin#if-the-proxy-script-is-slow'
		);

		Messages::set_error( $message );
	}

	/**
	 * Uninstall the Speed Module, generates JS files and all related settings when the proxy is disabled.
	 * @since 1.3.0
	 * @return void.
	 */
	public function uninstall() {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		/**
		 * Clean up MU plugin.
		 */
		$file_path = WP_CONTENT_DIR . '/mu-plugins/plausible-proxy-speed-module.php';

		if ( file_exists( $file_path ) ) {
			unlink( $file_path );
		}

		if ( get_option( 'plausible_analytics_created_mu_plugins_dir' ) && $this->dir_is_empty( WPMU_PLUGIN_DIR ) ) {
			rmdir( WPMU_PLUGIN_DIR );
		}

		/**
		 * Clean up generated JS files in /uploads dir.
		 */
		$cache_dir = Helpers::get_proxy_resource( 'cache_dir' );
		$js_file   = Helpers::get_proxy_resource( 'file_alias' );

		if ( file_exists( $cache_dir . $js_file . '.js' ) ) {
			unlink( $cache_dir . $js_file . '.js' );
		}

		if ( $this->dir_is_empty( $cache_dir ) ) {
			rmdir( $cache_dir );
		}

		/**
		 * Clean up related DB entries.
		 */
		delete_option( 'plausible_analytics_created_mu_plugins_dir' );
		delete_option( 'plausible_analytics_proxy_speed_module_installed' );
		delete_option( 'plausible_analytics_proxy_resources' );
	}

	/**
	 * Check if a directory is empty.
	 * This works because a new FilesystemIterator will initially point to the first file in the folder -
	 * if there are no files in the folder, valid() will return false.
	 * @see   https://www.php.net/manual/en/directoryiterator.valid.php
	 * @since 1.3.0
	 *
	 * @param mixed $dir
	 *
	 * @return bool
	 */
	private function dir_is_empty( $dir ) {
		$iterator = new \FilesystemIterator( $dir );

		return ! $iterator->valid();
	}

	/**
	 * Test the proxy before enabling the option.
	 * @since 1.3.0
	 *
	 * @param mixed $settings
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function maybe_enable_proxy( $settings, $old_settings ) {
		/**
		 * No need to run this on each update run.
		 */
		if ( empty( $settings[ 'proxy_enabled' ] ) || ( $settings[ 'proxy_enabled' ] === 'on' && $old_settings[ 'proxy_enabled' ] === 'on' ) ) {
			return $settings;
		}

		$is_ssl = $this->is_ssl();

		if ( ! $is_ssl ) {
			Messages::set_notice(
				sprintf(
					__(
						'Please check that your proxy is functioning correctly. If you encounter any issues with tracking, <a href="%s" target="_blank">click here</a> for a list of potential solutions',
						'plausible-analytics'
					),
					'https://plausible.io/wordpress-analytics-plugin#stats-not-being-recorded-after-enabling-the-proxy'
				)
			);
		}

		$test_succeeded = $this->test_proxy( Helpers::proxy_enabled( $settings ) && wp_doing_ajax() );

		if ( ! $test_succeeded ) {
			Messages::set_error(
				sprintf(
					wp_kses(
						__(
							'Plausible\'s proxy couldn\'t be enabled, because the WordPress API is inaccessable. This might be due to a conflicting setting in a (security) plugin or server firewall. Make sure you whitelist requests to the Proxy\'s endpoint: <code>%1$s</code>. <a href="%2$s" target="_blank">Contact support</a> if you need help locating the issue.',
							'plausible-analytics'
						),
						'post'
					),
					Helpers::get_rest_endpoint( false ),
					'https://plausible.io/contact'
				)
			);

			// Disable the proxy.
			return $old_settings;
		}

		return $settings;
	}

	/**
	 * is_ssl() only checks the current scheme that is used, which fails on an Nginx Reverse Proxy (where the scheme is HTTP behind the proxy), this
	 * function is a custom wrapper which also checks the WordPress configuration for the presence of "https" in the configured Home URL.
	 * @return bool
	 */
	private function is_ssl() {
		return strpos( get_home_url(), 'https' ) !== false || is_ssl();
	}

	/**
	 * Runs a quick internal call to the WordPress API to make sure it's accessable.
	 * @since 1.3.0
	 * @return bool
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
		} catch ( \Exception $e ) {
			/**
			 * There's no need to handle the error, because we don't want to display it anyway.
			 * We'll leave the parameter for backwards compatibility.
			 */
			return false;
		}

		return wp_remote_retrieve_response_code( $result->get_data() ) === 202;
	}
}
