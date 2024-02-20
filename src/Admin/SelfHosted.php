<?php
/**
 * Plausible Analytics | Admin Filters.
 * @since      1.0.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Admin;

defined( 'ABSPATH' ) || exit;

class SelfHosted {
	/**
	 * Build class.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Action and filter hooks.
	 * @return void
	 */
	private function init() {
		add_filter( 'pre_update_option_plausible_analytics_settings', [ $this, 'maybe_remove_api_token' ], 10 );
	}

	/**
	 * Removing the API token will effectively disable all auto provisioning, which Self Hosters can't use either way.
	 *
	 * @param $settings
	 *
	 * @return void
	 */
	public function maybe_remove_api_token( $settings ) {
		if ( empty( $settings[ 'self_hosted_domain' ] ) ) {
			return $settings;
		}

		$settings[ 'api_token' ] = '';

		return $settings;
	}
}
