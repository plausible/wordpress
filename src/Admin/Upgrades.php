<?php
/**
 * Plausible Analytics | Upgrades
 * @since      1.3.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Admin;

use Exception;
use Plausible\Analytics\WP\Includes\Helpers;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Upgrades
 * @since 1.3.0
 */
class Upgrades {
	/**
	 * Constructor for Upgrades.
	 * @since  1.3.0
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'run' ] );
	}

	/**
	 * Register routines for upgrades.
	 * This is intended for automatic upgrade routines having less resource intensive tasks.
	 * @since  1.3.0
	 * @access public
	 * @return void
	 */
	public function run() {
		$plausible_analytics_version = get_option( 'plausible_analytics_version' );

		// If version doesn't exist, then consider it `1.0.0`.
		if ( ! $plausible_analytics_version ) {
			$plausible_analytics_version = '1.0.0';
		}

		if ( version_compare( $plausible_analytics_version, '1.2.5', '<' ) ) {
			$this->upgrade_to_125();
		}

		if ( version_compare( $plausible_analytics_version, '1.2.6', '<' ) ) {
			$this->upgrade_to_126();
		}

		if ( version_compare( $plausible_analytics_version, '1.3.1', '<' ) ) {
			$this->upgrade_to_131();
		}

		if ( version_compare( $plausible_analytics_version, '1.3.2', '<' ) ) {
			$this->upgrade_to_132();
		}

		if ( version_compare( $plausible_analytics_version, '2.0.0', '<' ) ) {
			$this->upgrade_to_200();
		}

		if ( version_compare( $plausible_analytics_version, '2.0.3', '<' ) ) {
			$this->upgrade_to_203();
		}

		// Add required upgrade routines for future versions here.
	}

	/**
	 * Upgrade routine for 1.2.5
	 * Cleans Custom Domain related options from database, as it was removed in this version.
	 * @since  1.2.5
	 * @access public
	 * @return void
	 */
	public function upgrade_to_125() {
		$old_settings = Helpers::get_settings();
		$new_settings = $old_settings;

		if ( isset( $old_settings[ 'custom_domain_prefix' ] ) ) {
			unset( $new_settings[ 'custom_domain_prefix' ] );
		}

		if ( isset( $old_settings[ 'custom_domain' ] ) ) {
			unset( $new_settings[ 'custom_domain' ] );
		}

		if ( isset( $old_settings[ 'is_custom_domain' ] ) ) {
			unset( $new_settings[ 'is_custom_domain' ] );
		}

		// Enable Outbound links by default.
		$new_settings[ 'enhanced_measurements' ] = [ 'outbound-links' ];

		if ( ! empty( $old_settings[ 'track_administrator' ] ) && $old_settings[ 'track_administrator' ] === 'true' ) {
			$new_settings[ 'tracked_user_roles' ] = [ 'administrator' ];
		}

		update_option( 'plausible_analytics_settings', $new_settings );

		update_option( 'plausible_analytics_version', '1.2.5' );
	}

	/**
	 * Get rid of the previous "example.com" default for self_hosted_domain.
	 * @since 1.2.6
	 * @return void
	 */
	public function upgrade_to_126() {
		$old_settings = Helpers::get_settings();
		$new_settings = $old_settings;

		if ( ! empty( $old_settings[ 'self_hosted_domain' ] ) && strpos( $old_settings[ 'self_hosted_domain' ], 'example.com' ) !== false ) {
			$new_settings[ 'self_hosted_domain' ] = '';
		}

		update_option( 'plausible_analytics_settings', $new_settings );

		update_option( 'plausible_analytics_version', '1.2.6' );
	}

	/**
	 * Upgrade to 1.3.1
	 * - Enables 404 pages tracking by default.
	 * @return void
	 */
	public function upgrade_to_131() {
		$settings = Helpers::get_settings();

		if ( ! in_array( '404', $settings[ 'enhanced_measurements' ], true ) ) {
			array_unshift( $settings[ 'enhanced_measurements' ], '404' );
		}

		update_option( 'plausible_analytics_settings', $settings );

		update_option( 'plausible_analytics_version', '1.3.1' );
	}

	/**
	 * Upgrade to 1.3.2
	 * - Updates the Proxy Resource, Cache URL to be protocol relative.
	 * @return void
	 * @throws Exception
	 */
	private function upgrade_to_132() {
		$proxy_resources = Helpers::get_proxy_resources();

		$proxy_resources[ 'cache_url' ] = str_replace( [ 'https:', 'http:' ], '', $proxy_resources[ 'cache_url' ] );

		update_option( 'plausible_analytics_proxy_resources', $proxy_resources );

		update_option( 'plausible_analytics_version', '1.3.2' );
	}

	/**
	 * Cleans the settings of the old, unneeded sub-arrays for settings.
	 * @return void
	 */
	private function upgrade_to_200() {
		$settings     = Helpers::get_settings();
		$toggle_lists = [
			'enhanced_measurements',
			'tracked_user_roles',
			'expand_dashboard_access',
		];

		foreach ( $settings as $option_name => $option_value ) {
			if ( ! is_array( $option_value ) ) {
				continue;
			}

			// For toggle lists, we only need to clean out the no longer needed zero values.
			if ( in_array( $option_name, $toggle_lists ) ) {
				$settings[ $option_name ] = array_filter( $option_value );

				continue;
			}

			// Single toggle.
			$clean_value = array_filter( $option_value );

			// Disabled options are now stored as (more sensible) empty strings instead of empty arrays.
			if ( empty( $clean_value ) ) {
				$settings[ $option_name ] = '';

				continue;
			}

			// Any other value will now default to 'on'.
			$settings[ $option_name ] = 'on';
		}

		/**
		 * Migrate the shared link option for self hosters who use it.
		 */
		if ( ! empty( $settings[ 'self_hosted_domain' ] ) && ! empty( $settings[ 'shared_link' ] ) ) {
			$settings[ 'self_hosted_shared_link' ] = $settings[ 'shared_link' ];
			$settings[ 'shared_link' ]             = '';
		}

		update_option( 'plausible_analytics_settings', $settings );

		update_option( 'plausible_analytics_version', '2.0.0' );

		// No longer need this db entry.
		delete_option( 'plausible_analytics_is_default_settings_saved' );

		// We no longer need to store transient to keep notices dismissed.
		delete_transient( 'plausible_analytics_module_install_failed_notice_dismissed' );
		delete_transient( 'plausible_analytics_proxy_test_failed_notice_dismissed' );
		delete_transient( 'plausible_analytics_notice' );
	}

	/**
	 * Makes sure the View Stats option is enabled for users that previously set a shared link.
	 * @return void
	 */
	private function upgrade_to_203() {
		$settings = Helpers::get_settings();

		if ( ! empty( $settings[ 'shared_link' ] ) ) {
			$settings[ 'enable_analytics_dashboard' ] = 'on';
		}

		update_option( 'plausible_analytics_settings', $settings );
	}
}
