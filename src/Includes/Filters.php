<?php
/**
 * Plausible Analytics | Filters.
 * @since      1.0.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Includes;

use WP_Term;
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
		add_filter( 'plausible_analytics_script_params', [ $this, 'maybe_add_custom_params' ] );
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
		$domain_name = esc_html( $settings[ 'domain_name' ] );

		// We need the correct id attribute for IE compatibility.
		$tag    = preg_replace( "/\sid=(['\"])plausible-analytics-js(['\"])/", " id=$1plausible$2", $tag );
		$params = "defer data-domain='{$domain_name}' data-api='{$api_url}'";

		// Triggered when exclude pages is enabled.
		if ( ! empty( $settings[ 'excluded_pages' ] ) && $settings[ 'excluded_pages' ] ) {
			$excluded_pages = $settings[ 'excluded_pages' ];
			$params         .= " data-exclude='{$excluded_pages}'";
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
	 * @throws Exception
	 */
	public function wpml_compatibility( $url ) {
		$rest_endpoint = Helpers::get_rest_endpoint( false );

		if ( strpos( $url, $rest_endpoint ) !== false ) {
			return get_option( 'home' ) . $rest_endpoint;
		}

		return $url;
	}

	/**
	 * Adds custom parameters Author and Category if Custom Pageview Properties is enabled.
	 *
	 * @param $params
	 *
	 * @return mixed|void
	 */
	public function maybe_add_custom_params( $params ) {
		$settings = Helpers::get_settings();

		if ( ! in_array( 'pageview-props', $settings[ 'enhanced_measurements' ] ) ) {
			return $params;
		}

		global $post;

		$author = $post->post_author;

		if ( $author ) {
			$author_name = get_the_author_meta( 'display_name', $author );

			$params .= " event-author='$author_name'";
		}

		$categories = get_the_category( $post->ID );

		if ( ! is_array( $categories ) ) {
			return $params;
		}

		foreach ( $categories as $category ) {
			if ( $category instanceof WP_Term ) {
				$params .= " event-category='$category->name'";
			}
		}

		return apply_filters( 'plausible_analytics_pageview_properties', $params );
	}
}
