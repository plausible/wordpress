<?php
/**
 * Plausible Analytics | Actions.
 * @since      1.0.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP;

class Actions {
	/**
	 * Constructor.
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_head', [ $this, 'insert_version_meta_tag' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_register_assets' ] );
		add_action( 'admin_bar_menu', [ $this, 'admin_bar_node' ], 100 );
	}

	/**
	 * This <meta> tag "tells" the Plausible API which version of the plugin is used, to allow tailored error messages,
	 * specific to the plugin version.
	 * @return void
	 */
	public function insert_version_meta_tag() {
		$version = PLAUSIBLE_ANALYTICS_VERSION;

		echo "<meta name='plausible-analytics-version' content='$version' />\n";
	}

	/**
	 * Register Assets.
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function maybe_register_assets() {
		$settings  = Helpers::get_settings();
		$user_role = Helpers::get_user_role();

		/**
		 * Bail if tracked_user_roles is empty (which means no roles should be tracked) or,
		 * if current role should not be tracked.
		 */
		if ( ( ! empty( $user_role ) && ! isset( $settings[ 'tracked_user_roles' ] ) ) || ( ! empty( $user_role ) && ! in_array( $user_role, $settings[ 'tracked_user_roles' ], true ) ) ) {
			return; // @codeCoverageIgnore
		}

		$version = Helpers::proxy_enabled() && file_exists( Helpers::get_js_path() ) ? filemtime( Helpers::get_js_path() ) : PLAUSIBLE_ANALYTICS_VERSION;

		wp_enqueue_script(
			'plausible-analytics',
			Helpers::get_js_url( true ),
			'',
			$version,
			apply_filters( 'plausible_load_js_in_footer', false )
		);

		// Goal tracking inline script (Don't disable this as it is required by 404).
		wp_add_inline_script(
			'plausible-analytics',
			'window.plausible = window.plausible || function() { (window.plausible.q = window.plausible.q || []).push(arguments) }'
		);

		// Track Cloaked Affiliate Links (if enabled)
		if ( Helpers::is_enhanced_measurement_enabled( 'affiliate-links' ) ) {
			wp_enqueue_script(
				'plausible-affiliate-links',
				PLAUSIBLE_ANALYTICS_PLUGIN_URL . 'assets/dist/js/plausible-affiliate-links.js',
				[ 'plausible-analytics' ],
				filemtime( PLAUSIBLE_ANALYTICS_PLUGIN_DIR . 'assets/dist/js/plausible-affiliate-links.js' ),
			);

			$affiliate_links = Helpers::get_settings()[ 'affiliate_links' ] ?? [];

			wp_add_inline_script( 'plausible-affiliate-links', 'const plausibleAffiliateLinks = ' . wp_json_encode( $affiliate_links ) . ';', 'before' );
		}

		// Track 404 pages (if enabled)
		if ( Helpers::is_enhanced_measurement_enabled( '404' ) && is_404() ) {
			$data = wp_json_encode(
				[
					'props' => [
						'path' => 'document.location.pathname',
					],
				]
			);

			/**
			 * document.location.pathname is a variable. @see wp_json_encode() doesn't allow passing variable, only strings. This fixes that.
			 */
			$data = str_replace( '"document.location.pathname"', 'document.location.pathname', $data );

			wp_add_inline_script(
				'plausible-analytics',
				"document.addEventListener('DOMContentLoaded', function () { plausible( '404', $data ); });"
			);
		}

		// Track search results. Tracks a search event with the search term and the number of results, and a pageview with the site's search URL.
		if ( Helpers::is_enhanced_measurement_enabled( 'search' ) && is_search() ) {
			global $wp_query;

			$data   = wp_json_encode(
				[
					'props' => [
						// convert queries to lowercase and remove trailing whitespace to ensure same terms are grouped together
						'search_query' => strtolower( trim( get_search_query() ) ),
						'result_count' => $wp_query->found_posts,
					],
				]
			);
			$script = "plausible('WP Search Queries', $data );";

			wp_add_inline_script(
				'plausible-analytics',
				"document.addEventListener('DOMContentLoaded', function() {\n$script\n});"
			);
		}

		// This action allows you to add your own custom scripts!
		do_action( 'plausible_analytics_after_register_assets', $settings );
	}

	/**
	 * Create admin bar nodes.
	 * @since  1.3.0
	 * @access public
	 *
	 * @param \WP_Admin_Bar $admin_bar Admin bar object.
	 *
	 * @return void
	 */
	public function admin_bar_node( $admin_bar ) {
		$disable = ! empty( Helpers::get_settings()[ 'disable_toolbar_menu' ] );

		if ( $disable ) {
			return; // @codeCoverageIgnore
		}

		$settings     = Helpers::get_settings();
		$current_user = wp_get_current_user();

		$has_access             = false;
		$user_roles_have_access = array_merge(
			[ 'administrator' ],
			$settings[ 'expand_dashboard_access' ] ?? []
		);

		foreach ( $current_user->roles as $role ) {
			if ( in_array( $role, $user_roles_have_access, true ) ) {
				$has_access = true;
				break;
			}
		}

		if ( ! $has_access ) {
			return;
		}

		// Add main admin bar node.
		$args[] = [
			'id'    => 'plausible-analytics',
			'title' => 'Plausible Analytics',
		];

		if ( ! empty( $settings[ 'enable_analytics_dashboard' ] ) || ( ! empty( $settings[ 'self_hosted_domain' ] ) && ! empty( $settings[ 'self_hosted_shared_link' ] ) ) ) {
			$args[] = [
				'id'     => 'view-analytics',
				'title'  => esc_html__( 'View Analytics', 'plausible-analytics' ),
				'href'   => admin_url( 'index.php?page=plausible_analytics_statistics' ),
				'parent' => 'plausible-analytics',
			];

			// Add link to individual page stats.
			if ( is_singular() ) {
				global $post;
				$uri = wp_make_link_relative( get_permalink( $post->ID ) );

				$args[] = [
					'id'     => 'view-page-analytics',
					'title'  => esc_html__( 'View Page Analytics', 'plausible-analytics' ),
					'href'   => add_query_arg(
						'page-url',
						is_home() ? '' : $uri,
						admin_url( 'index.php?page=plausible_analytics_statistics' )
					),
					'parent' => 'plausible-analytics',
				];
			}
		}

		// Add link to Plausible Settings page.
		if ( current_user_can( 'manage_options' ) ) {
			$args[] = [
				'id'     => 'settings',
				'title'  => esc_html__( 'Settings', 'plausible-analytics' ),
				'href'   => admin_url( 'options-general.php?page=plausible_analytics' ),
				'parent' => 'plausible-analytics',
			];
		}

		foreach ( $args as $arg ) {
			$admin_bar->add_node( $arg );
		}
	}
}
