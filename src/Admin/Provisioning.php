<?php
/**
 * Plausible Analytics | Provisioning.
 * @since      2.0.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Admin;

use Plausible\Analytics\WP\Client;

class Provisioning {
	/**
	 * Build class.
	 */
	public function __construct() {
		add_action( 'update_option_plausible_analytics_settings', [ $this, 'create_shared_link' ], 10, 2 );
	}

	/**
	 * Create shared link when Enable Analytics Dashboard option is enabled.
	 *
	 * @param $old_settings
	 * @param $settings
	 *
	 * @return void
	 */
	public function create_shared_link( $old_settings, $settings ) {
		if ( empty( $settings['enable_analytics_dashboard'][0] ) ) {
			return;
		}

		$client = new Client();

		$client->create_shared_link();
	}
}
