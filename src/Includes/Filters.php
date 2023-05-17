<?php
/**
 * Plausible Analytics | Filters.
 *
 * @since      1.0.0
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
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function __construct() {
		add_filter( 'script_loader_tag', [ $this, 'add_plausible_attributes' ], 10, 2 );
	}

	/**
	 * Add Plausible Analytics attributes.
	 *
	 * @param string $tag    Script tag.
	 * @param string $handle Script handle.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return string
	 */
	public function add_plausible_attributes( $tag, $handle ) {
		// Bailout, if not `Plausible Analytics` script.
		if ( 'plausible-analytics' !== $handle ) {
			return $tag;
		}

		$settings    = Helpers::get_settings();
		$api_url     = Helpers::get_data_api_url();
		$domain_name = esc_html( $settings['domain_name'] );

		// We need the correct id attribute for IE compatibility.
		$id_replacement = " id='plausible'";
		$tag            = str_replace( " id='plausible-analytics-js'", $id_replacement, $tag );

		$params = "defer data-domain='{$domain_name}' data-api='{$api_url}'";

		// Triggered when exclude pages is enabled.
		if ( ! empty( $settings['excluded_pages'] ) && $settings['excluded_pages'] ) {
			$excluded_pages = $settings['excluded_pages'];
			$params        .= " data-exclude='{$excluded_pages}'";
		}

		$params = apply_filters( 'plausible_analytics_script_params', $params );

		return str_replace( ' src', " {$params} src", $tag );
	}
}
