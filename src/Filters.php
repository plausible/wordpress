<?php
/**
 * Plausible Analytics | Filters.
 *
 * @since      1.0.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP;

use WP_Term;
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
	 * @return void
	 */
	public function __construct() {
		add_filter( 'script_loader_tag', [ $this, 'add_plausible_attributes' ], 10, 2 );
		add_filter( 'plausible_analytics_script_params', [ $this, 'maybe_add_custom_params' ] );
	}

	/**
	 * Add Plausible Analytics attributes.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param string $handle Script handle.
	 * @param string $tag    Script tag.
	 *
	 * @return string
	 */
	public function add_plausible_attributes( $tag, $handle ) {
		// Bail if it's not our script.
		if ( 'plausible-analytics' !== $handle ) {
			return $tag; // @codeCoverageIgnore
		}

		$settings    = Helpers::get_settings();
		$api_url     = Helpers::get_data_api_url();
		$domain_name = Helpers::get_domain();

		// We need the correct id attribute for IE compatibility.
		$tag = preg_replace( "/\sid=(['\"])plausible-analytics-js(['\"])/", " id=$1plausible$2", $tag );
		/**
		 * the data-cfasync ensures this script isn't processed by CF Rocket Loader @see https://developers.cloudflare.com/speed/optimization/content/rocket-loader/ignore-javascripts/
		 */
		$params = "data-domain='{$domain_name}' data-api='{$api_url}' data-cfasync='false'";

		// Triggered when exclude pages is enabled.
		if ( ! empty( $settings[ 'excluded_pages' ] ) && $settings[ 'excluded_pages' ] ) {
			$excluded_pages = $settings[ 'excluded_pages' ]; // @codeCoverageIgnore
			$params         .= " data-exclude='{$excluded_pages}'"; // @codeCoverageIgnore
		}

		$params = apply_filters( 'plausible_analytics_script_params', $params );

		return str_replace( ' src', " {$params} src", $tag );
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

		if ( ! is_array( $settings[ 'enhanced_measurements' ] ) || ! in_array( 'pageview-props', $settings[ 'enhanced_measurements' ] ) ) {
			return $params; // @codeCoverageIgnore
		}

		global $post;

		if ( ! $post instanceof \WP_Post ) {
			return $params; // @codeCoverageIgnore
		}

		$author = $post->post_author;

		if ( $author ) {
			$author_name = get_the_author_meta( 'display_name', $author );

			$params .= " event-author='$author_name'";
		}

		// Add support for post category and tags along with custom taxonomies.
		$taxonomies = get_object_taxonomies( $post->post_type );

		// Loop through existing taxonomies.
		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_the_terms( $post->ID, $taxonomy );

			// Skip the iteration, if `$terms` is not array.
			if ( ! is_array( $terms ) ) {
				continue; // @codeCoverageIgnore;
			}

			// Loop through the terms.
			foreach ( $terms as $term ) {
				if ( $term instanceof WP_Term ) {
					$params .= " event-{$taxonomy}=\"{$term->name}\"";
				}
			}
		}

		return apply_filters( 'plausible_analytics_pageview_properties', $params );
	}
}
