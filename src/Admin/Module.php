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
		add_filter( 'pre_update_option_plausible_analytics_settings', [ $this, 'maybe_install_module' ], 10 );
	}

	/**
	 * Decide whether we should install the module, or not.
	 *
	 * @since 1.3.0
	 *
	 * @return void
	 */
	public function maybe_install_module( $settings ) {
		if ( ! empty( $settings['avoid_ad_blockers'][0] ) ) {
			$this->install();
		} else {
			$this->uninstall();
		}

		return $settings;
	}

	/**
	 * Takes care of installing the MU plugin when the Proxy is enabled.
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
			wp_die( esc_html__( 'Cannot install Plausible Proxy Speed module', 'plausible-analytics' ) );
		}

		$results = copy_dir( PLAUSIBLE_ANALYTICS_PLUGIN_DIR . 'mu-plugin', WPMU_PLUGIN_DIR );

		if ( is_wp_error( $results ) ) {
			return $results;
		}

		add_option( 'plausible_analytics_proxy_speed_module_installed', true );

		return $results;
	}

	/**
	 * Uninstall the Speed Module and all related settings when the proxy is disabled.
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
}
