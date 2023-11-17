<?php
/**
 * Plausible Analytics | Filters.
 * @since      1.0.0
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
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_filter( 'script_loader_tag', [ $this, 'add_plausible_attributes' ], 10, 2 );
		add_filter( 'rest_url', [ $this, 'wpml_compatibility' ], 10, 1 );
	}

	/**
	 * Add Plausible Analytics attributes.
	 * @since  1.0.0
	 * @access public
	 *
	 * @param string $handle Script handle.
	 * @param string $tag    Script tag.
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
		$tag    = preg_replace( "/\sid=(['\"])plausible-analytics-js(['\"])/", " id=$1plausible$2", $tag );
		$params = "defer data-domain='{$domain_name}' data-api='{$api_url}'";

		// Triggered when exclude pages is enabled.
		if ( ! empty( $settings['excluded_pages'] ) && $settings['excluded_pages'] ) {
			$excluded_pages = $settings['excluded_pages'];
			$params         .= " data-exclude='{$excluded_pages}'";
		}

		// Triggered when custom properties is enabled.
		if ( ! empty( $settings['enhanced_measurements'] ) && in_array( 'pageview-props', $settings['enhanced_measurements'] ) ) {
			$params .= ' event-page-type="' . $this->get_page_type() . '"';
		}

		$params = apply_filters( 'plausible_analytics_script_params', $params );

		return str_replace( ' src', " {$params} src", $tag );
	}

	/**
	 * Get page type.
	 * 
	 * @return string The page type.
	 */
	private function get_page_type() {
		global $wp_query;
		$page_type = 'notfound';

		if ( $wp_query->is_page ) {
			$page_type = is_front_page() ? 'front' : 'page';
		} elseif ( $wp_query->is_home ) {
			$page_type = 'home';
		} elseif ( $wp_query->is_single ) {
			$page_type = get_post_type();
		} elseif ( $wp_query->is_category ) {
			$page_type = 'category';
		} elseif ( $wp_query->is_tag ) {
			$page_type = 'tag';
		} elseif ( $wp_query->is_tax ) {
			$page_type = 'tax';
		} elseif ( $wp_query->is_archive ) {
			if ( $wp_query->is_day ) {
				$page_type = 'day';
			} elseif ( $wp_query->is_month ) {
				$page_type = 'month';
			} elseif ( $wp_query->is_year ) {
				$page_type = 'year';
			} elseif ( $wp_query->is_author ) {
				$page_type = 'author';
			} else {
				$page_type = 'archive';
			}
		} elseif ( $wp_query->is_search ) {
			$page_type = 'search';
		} elseif ( $wp_query->is_404 ) {
			$page_type = 'notfound';
		}

		return $page_type;
	}

	/**
	 * WPML overrides the REST API URL to include the language 'subdirectory', which leads to 404s.
	 * This forces it back to default behavior.
	 *
	 * @param mixed $url
	 *
	 * @return string|void
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
