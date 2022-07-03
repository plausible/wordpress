<?php
/**
 * Plausible Analytics | Actions.
 *
 * @since 1.0.0
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
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
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
	public function register_assets() {
		$settings  = Helpers::get_settings();
		$user_role = Helpers::get_user_role();

		// Bailout, if `administrator` user role accessing frontend.
		if (
			! empty( $user_role ) &&
			! in_array( $user_role, $settings['track_analytics'], true )
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
		if ( ! is_admin() ) {
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
