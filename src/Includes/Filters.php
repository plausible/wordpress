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

use Exception;

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
		add_filter( 'rest_url', [ $this, 'wpml_compatibility' ], 10, 1 );
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
		$params         = "defer data-domain='{$domain_name}' data-api='{$api_url}'";

		// Triggered when exclude pages is enabled.
		if ( ! empty( $settings['excluded_pages'] ) && $settings['excluded_pages'] ) {
			$excluded_pages = $settings['excluded_pages'];
			$params        .= " data-exclude='{$excluded_pages}'";
		}

		$params = apply_filters( 'plausible_analytics_script_params', $params );

		return str_replace( ' src', " {$params} src", $tag );
	}

	/**
	 * WPML overrides the REST API URL to include the language 'subdirectory', which leads to 404s.
	 * This forces it back to default behavior.
	 *
	 * @param mixed $url
	 *
	 * @return string|void
	 *
	 * @throws Exception
	 */
	public function wpml_compatibility( $url ) {
		$rest_endpoint = Helpers::get_rest_endpoint( false );

		if ( strpos( $url, $rest_endpoint ) !== false ) {
			return get_option( 'home' ) . $rest_endpoint;
		}

		return $url;
	}
}
