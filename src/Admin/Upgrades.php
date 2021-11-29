<?php
/**
 * Plausible Analytics | Upgrades
 *
 * @since 1.3.0
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

/**
 * Class Upgrades
 *
 * @since 1.3.0
 */
class Upgrades {
	/**
	 * Constructor for Upgrades.
	 *
	 * @since  1.3.0
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
	 * @since  1.3.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_routines() {
		// Upgrade to version 1.3.0.
		if ( version_compare( PLAUSIBLE_ANALYTICS_VERSION, '1.3.0', '<' ) ) {
			$this->upgrade_to_130();
		}

		// Add required upgrade routines for future versions here.
	}

	/**
	 * Upgrade routine for 1.3.0
	 *
	 * @since  1.3.0
	 * @access public
	 *
	 * @return void
	 */
	public function upgrade_to_130() {
		$old_settings = Helpers::get_settings();
		$new_settings = $old_settings;

		$new_settings['is_custom_domain'] = $old_settings['custom_domain'];
		$new_settings['custom_domain']    = "{$old_settings['custom_domain_prefix']}.{$old_settings['domain_name']}";
		$new_settings['is_shared_link']   = $old_settings['embed_analytics'];

		if ( $old_settings['track_administrator'] ) {
			$new_settings['track_analytics'] = [ 'administrator' ];
		}

		// For self hosted plausible analytics.
		$new_settings['is_self_hosted_plausible_analytics'] = $old_settings['is_self_hosted_analytics'];

		// Update the new settings.
		update_option( 'plausible_analytics_settings', $new_settings );
	}
}

