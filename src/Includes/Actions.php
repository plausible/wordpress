<?php
/**
 * Plausible Analytics | Actions.
 *
 * @since      1.0.0
 *
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Includes;

use Plausible\Analytics\WP\Includes\Helpers;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Actions {

	/**
	 * Constructor.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_register_assets' ] );
		add_action( 'admin_bar_menu', [ $this, 'admin_bar_node' ], 100 );
	}

	/**
	 * Register Assets.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function maybe_register_assets() {
		$settings  = Helpers::get_settings();
		$user_role = Helpers::get_user_role();

		/**
		 * Bail if tracked_user_roles is empty (which means no roles should be tracked) or,
		 * if current role should not be tracked.
		 */
		if (
			( ! empty( $user_role )
				&& ! isset( $settings['tracked_user_roles'] ) )
			|| ( ! empty( $user_role )
				&& ! in_array( $user_role, $settings['tracked_user_roles'], true ) )
			) {
			return;
		}

		wp_enqueue_script( 'plausible-analytics', Helpers::get_analytics_url(), '', PLAUSIBLE_ANALYTICS_VERSION );

		// Goal tracking inline script (Don't disable this as it is required by 404).
		wp_add_inline_script( 'plausible-analytics', 'window.plausible = window.plausible || function() { (window.plausible.q = window.plausible.q || []).push(arguments) }' );

		// Track 404 pages.
		if ( apply_filters( 'plausible_analytics_enable_404', true ) && is_404() ) {
			wp_add_inline_script( 'plausible-analytics', 'plausible("404",{ props: { path: document.location.pathname } });' );
		}

		// Track search results. Tracks a search event with the search term and the number of results, and a pageview with the site's search URL.
		if ( apply_filters( 'plausible_analytics_track_search', true ) && is_search() ) {
			$search_url = str_replace( '%search%', '', get_site_url( null, $GLOBALS['wp_rewrite']->get_search_permastruct() ) );
			$data = 'plausible("pageview", { u: "' . esc_attr( $search_url ) . '" });' .
					'plausible( \'Search\', {props: {keyword: \'' . get_search_query() . '\', resultCount: ' . $GLOBALS['wp_query']->found_posts . '}});';
			wp_add_inline_script( 'plausible-analytics', $data );
		}
	}

	/**
	 * Create admin bar nodes.
	 *
	 * @param \WP_Admin_Bar $admin_bar Admin bar object.
	 *
	 * @return void
	 * @since  1.3.0
	 * @access public
	 */
	public function admin_bar_node( $admin_bar ) {
		$disable = ! empty( Helpers::get_settings()['disable_toolbar_menu'][0] );

		if ( $disable ) {
			return;
		}

		// Add main admin bar node.
		$args = [
			'id'    => 'plausible-admin-bar',
			'title' => 'Plausible Analytics',
		];
		$admin_bar->add_node( $args );

		// Add link to view all stats.
		$args   = [];
		$args[] = [
			'id'     => 'view-analytics',
			'title'  => esc_html__( 'View Analytics', 'plausible-analytics' ),
			'href'   => admin_url( 'index.php?page=plausible_analytics_statistics' ),
			'parent' => 'plausible-admin-bar',
		];

		// Add link to individual page stats.
		if ( is_singular() ) {
			global $post;
			$args[] = [
				'id'     => 'view-page-analytics',
				'title'  => esc_html__( 'View Page Analytics', 'plausible-analytics' ),
				'href'   => add_query_arg( 'page-url', is_home() ? '' : trailingslashit( urlencode( '/' . $post->post_name ) ), admin_url( 'index.php?page=plausible_analytics_statistics' ) ),
				'parent' => 'plausible-admin-bar',
			];
		}

		// Add link to Plausible Settings page.
		$args[] = [
			'id'     => 'settings',
			'title'  => esc_html__( 'Settings', 'plausible-analytics' ),
			'href'   => admin_url( 'options-general.php?page=plausible_analytics' ),
			'parent' => 'plausible-admin-bar',
		];
		foreach ( $args as $arg ) {
			$admin_bar->add_node( $arg );
		}
	}
}
