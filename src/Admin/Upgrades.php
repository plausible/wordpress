<?php
/**
 * Plausible Analytics | Upgrades
 *
 * @since      1.2.5
 *
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Admin;

use Plausible\Analytics\WP\Includes\Helpers;
use Plausible\Analytics\WP\Admin;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Upgrades
 *
 * @since 1.2.5
 */
class Upgrades {
	/**
	 * Constructor for Upgrades.
	 *
	 * @since  1.2.5
	 * @access public
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_routines' ] );
	}

	/**
	 * Register routines for upgrades.
	 *
	 * This is intended for automatic upgrade routines having less resource intensive tasks.
	 *
	 * @since  1.2.5
	 * @access public
	 *
	 * @return void
	 */
	public function register_routines() {
		$plausible_analytics_version = get_option( 'plausible_analytics_version' );

		// If version doesn't exist, then consider it `1.0.0`.
		if ( ! $plausible_analytics_version ) {
			$plausible_analytics_version = '1.0.0';
		}

		// Upgrade to version 1.2.5.
		if ( version_compare( $plausible_analytics_version, '1.2.5', '<' ) ) {
			$this->upgrade_from_less_125();
		}

	// Upgrade from version < 1.2.5.
		if ( version_compare( $plausible_analytics_version, '1.2.5', '<=' ) ) {
			$this->upgrade_to_125();
		}

		// Add required upgrade routines for future versions here.
	}

	/**
	 * Upgrade routine for 1.2.5
	 *
	 * @since  1.2.5
	 * @access public
	 *
	 * @return void
	 */
	public function upgrade_to_125() {

		$settings = Helpers::get_settings();

		if ( ! isset( $settings['is_proxy'] ) ) {
			$settings['is_proxy'] = 'true';
			Admin\Actions::maybe_create_js_files();
		}

		if ( ! isset( $settings['track_administrator'] ) ) {
			$settings['track_administrator'] = 'false';
		}

		if ( ! isset( $settings['is_custom_path'] ) ) {
			$settings['is_custom_path'] = '';
		}

		unset( $settings['custom_domain'] );
		unset( $settings['custom_domain_prefix'] );

		update_option( 'plausible_analytics_settings', $settings );

		// Update the version in DB to the latest as upgrades completed.
		update_option( 'plausible_analytics_version', PLAUSIBLE_ANALYTICS_VERSION );
	}

	/**
	 * Upgrade routine from less than 1.2.5 
	 *
	 * @since  1.2.5
	 * @access public
	 *
	 * @return void
	 */
	public function upgrade_from_less_125() {

		$settings = Helpers::get_settings();

		if ( $settings['is_proxy'] == 'true' ||! isset( $settings['is_proxy'] ) ) {
			$settings['is_proxy'] = 'true';
			Admin\Actions::maybe_create_js_files();
		}

	}
}

