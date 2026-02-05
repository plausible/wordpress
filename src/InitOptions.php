<?php
/**
 * Plausible Analytics | Filters.
 *
 * @since      1.0.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP;

use WP_Post;
use WP_Term;

class InitOptions {
	/**
	 * Constructor.
	 *
	 * @return void
	 * @since  1.0.0
	 * @access public
	 */
	public function __construct() {
		add_filter( 'plausible_analytics_init_options', [ $this, 'maybe_add_pageview_props' ] );
		add_filter( 'plausible_analytics_init_options', [ $this, 'maybe_add_proxy_options' ] );
		add_filter( 'plausible_analytics_init_options', [ $this, 'maybe_exclude_pageview' ] );
		add_filter( 'plausible_analytics_init_options', [ $this, 'maybe_track_logged_in_users' ] );
	}

	/**
	 * Adds custom parameters Author and Category if Custom Pageview Properties is enabled.
	 *
	 * @param $options array
	 *
	 * @return array
	 */
	public function maybe_add_pageview_props( $options = [] ) {
		$settings = Helpers::get_settings();

		if ( ! is_array( $settings['enhanced_measurements'] ) || ! in_array( EnhancedMeasurements::PAGEVIEW_PROPS, $settings['enhanced_measurements'] ) ) {
			return $options; // @codeCoverageIgnore
		}

		global $post;

		if ( ! $post instanceof WP_Post ) {
			return $options; // @codeCoverageIgnore
		}

		$author = $post->post_author;

		if ( $author ) {
			$author_name = get_the_author_meta( 'display_name', $author );

			$options['customProperties']['author'] = $author_name;
		}

		// Add support for the post-category and tags along with custom taxonomies.
		$taxonomies = get_object_taxonomies( $post->post_type );

		// Loop through existing taxonomies.
		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_the_terms( $post->ID, $taxonomy );

			// Skip the iteration if `$terms` is not an array.
			if ( ! is_array( $terms ) ) {
				continue; // @codeCoverageIgnore;
			}

			// Loop through the terms.
			foreach ( $terms as $term ) {
				if ( $term instanceof WP_Term ) {
					$options['customProperties'][ $taxonomy ] = $term->name;
				}
			}
		}

		return $options;
	}

	/**
	 * Modify the endpoint option if Proxy is enabled.
	 *
	 * @param array $options
	 *
	 * @return array
	 *
	 * @throws \Exception
	 */
	public function maybe_add_proxy_options( $options = [] ) {
		if ( ! Helpers::proxy_enabled() ) {
			return $options; // @codeCoverageIgnore
		}

		$options['endpoint'] = Helpers::get_endpoint_url();

		return $options;
	}

	/**
	 * Exclude this Pageview from tracking if it matches any of the defined patterns.
	 *
	 * @param $options
	 *
	 * @return array|mixed
	 */
	public function maybe_exclude_pageview( $options = [] ) {
		$settings = Helpers::get_settings();

		// Triggered when exclude pages is enabled.
		if ( empty( $settings['excluded_pages'] ) ) {
			return $options; // @codeCoverageIgnore
		}

		$excluded_pages  = $settings['excluded_pages'];
		$current_request = $this->get_current_request();

		if ( $this->url_matches_patterns( $current_request, $excluded_pages ) ) {
			$options['transformRequest'] = '() => { return null; }';
		}

		return $options;
	}

	/**
	 * This a seam for @see add_query_arg() to be mocked in unit tests.
	 *
	 * @codeCoverageIgnore
	 */
	protected function get_current_request() {
		return add_query_arg( null, null );
	}

	/**
	 * Does $url match any of the $patterns?
	 *
	 * @param $url
	 * @param $patterns
	 *
	 * @return bool
	 */
	private function url_matches_patterns( $url, $patterns ) {
		// Split string by new lines (\n) and comma (,)
		$patterns = preg_split( "/[\n,]+/", $patterns, - 1, PREG_SPLIT_NO_EMPTY );

		foreach ( $patterns as $pattern ) {
			// Escape regex-symbols (can't use preg_quote() here, because it escapes dashes).
			$regex = preg_replace( '/([.^$+?{}()[\]|])/', '\\\\$1', $pattern );

			// Convert * to regex syntax.
			$regex = str_replace( '*', '.*', $regex );

			// Full match.
			$regex = "#$regex#i";

			if ( preg_match( $regex, $url ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Adds a custom parameter User Logged In if Custom Properties is enabled.
	 *
	 * @param $options
	 *
	 * @return array
	 * @since v2.4.0
	 *
	 */
	public function maybe_track_logged_in_users( $options = [] ) {
		$settings = Helpers::get_settings();

		if ( ! is_array( $settings['enhanced_measurements'] ) || ! in_array( EnhancedMeasurements::PAGEVIEW_PROPS, $settings['enhanced_measurements'] ) ) {
			return $options; // @codeCoverageIgnore
		}

		$logged_in = _x( 'no', __( 'Value when user is not logged in.', 'plausible-analytics' ), 'plausible-analytics' );

		if ( is_user_logged_in() ) {
			$user  = wp_get_current_user();
			$roles = $user->roles;

			if ( ! empty( $roles ) ) {
				$logged_in = $roles[0];
			}
		}

		$options['customProperties']['user_logged_in'] = $logged_in;

		return $options;
	}
}
