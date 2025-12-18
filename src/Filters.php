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

class Filters {
	/**
	 * Constructor.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_filter( 'plausible_analytics_init_options', [ $this, 'maybe_enable_enhanced_measurement' ] );
		add_filter( 'plausible_analytics_init_options', [ $this, 'maybe_add_pageview_props' ] );
		add_filter( 'plausible_analytics_init_options', [ $this, 'maybe_add_proxy_options' ] );
		add_filter( 'plausible_analytics_init_options', [ $this, 'maybe_track_logged_in_users' ] );
	}

	/**
	 * @param $options
	 *
	 * @return void
	 */
	public function maybe_enable_enhanced_measurement( $options ) {
		if ( EnhancedMeasurements::is_enabled( EnhancedMeasurements::HASH_BASED_ROUTING ) ) {
			$options['hashBasedRouting'] = true;
		}

		if ( EnhancedMeasurements::is_enabled( EnhancedMeasurements::FILE_DOWNLOADS ) ) {
			$options['fileDownloads'] = true;
		}

		if ( EnhancedMeasurements::is_enabled( EnhancedMeasurements::FORM_COMPLETIONS ) ) {
			$options['formSubmissions'] = true;
		}

		if ( EnhancedMeasurements::is_enabled( EnhancedMeasurements::OUTBOUND_LINKS ) ) {
			$options['outboundLinks'] = true;
		}

		return $options;
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
			return $options;
		}

		$options['endpoint'] = Helpers::get_endpoint_url();

		return $options;
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
				$logged_in = $roles[ 0 ];
			}
		}

		$options['customProperties']['user_logged_in'] = $logged_in;

		return $options;
	}
}
