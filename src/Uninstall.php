<?php
/**
 * Plausible Analytics | Uninstall script.
 *
 * @since      1.3.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP;

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * This class is run upon uninstall and cleans up any data in the database and leftover files added by this plugin.
 *
 * @package Plausible\Analytics\WP
 *
 * @codeCoverageIgnore
 */
class Uninstall {
	/**
	 * Trigger logic.
	 *
	 * @return void
	 */
	public function run() {
		$this->delete_options();
		$this->delete_transients();
		$this->delete_proxy_speed_module();
	}

	/**
	 * Delete options.
	 *
	 * @return void
	 */
	private function delete_options() {
		delete_option( 'plausible_analytics_settings' );
		delete_option( 'plausible_analytics_version' );
		delete_option( 'plausible_analytics_proxy_resources' );
		delete_option( 'plausible_analytics_created_mu_plugins_dir' );
		delete_option( 'plausible_analytics_proxy_speed_module_installed' );
	}

	/**
	 * Delete transients.
	 *
	 * @return void
	 */
	private function delete_transients() {
		delete_transient( 'plausible_analytics_notice_dismissed' );
		delete_transient( 'plausible_analytics_notice' );
	}

	/**
	 * Deletes the Proxy Speed Module from the mu-plugins directory.
	 *
	 * @return void
	 */
	private function delete_proxy_speed_module() {
		$file_path = WP_CONTENT_DIR . '/mu-plugins/plausible-proxy-speed-module.php';

		if ( file_exists( $file_path ) ) {
			unlink( $file_path );
		}
	}
}
