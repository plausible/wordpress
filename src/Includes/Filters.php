<?php
/**
 * Plausible Analytics | Filters.
 *
 * @since 1.0.0
 *
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Includes;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Filters {
	/**
	 * Constructor.
	 *
	 * @return void
	 * @since  1.0.0
	 * @access public
	 *
	 */
	public function __construct() {
		add_filter( 'script_loader_tag', [ $this, 'add_plausible_attributes' ], 10, 2 );
	}

	/**
	 * Add Plausible Analytics attributes.
	 *
	 * @param string $tag Script tag.
	 * @param string $handle Script handle.
	 *
	 * @return mixed
	 * @since  1.0.0
	 * @access public
	 *
	 */
	public function add_plausible_attributes( $tag, $handle ) {
		// Bailout, if not `Plausible Analytics` script.
		if ( 'plausible-analytics' !== $handle ) {
			return $tag;
		}

		$settings = Helpers::get_settings();
		$api_url  = Helpers::get_data_api_url() . '/';

		if ( isset( $settings['domain_name'] ) ) {
			$domain_name = $settings['domain_name'];
		} else {
			$domain_name = Helpers::get_domain();
		}

		// add data-no-optimize data-cfasync attrs to ignore the JS from CloudFlare and other optimizer
		$params = "defer data-domain='{$domain_name}' data-api='{$api_url}' data-no-optimize data-cfasync='false'";

		// Triggered when exclude pages is enabled.
		if ( ! empty( $settings['is_exclude_pages'] ) && $settings['is_exclude_pages'] ) {
			$excluded_pages = $settings['excluded_pages'];
			$params        .= " data-exclude='{$excluded_pages}'";
		}

		return str_replace( ' src', " {$params} src", $tag );
	}
}
