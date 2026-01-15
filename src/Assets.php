<?php
/**
 * Plausible Analytics | Assets
 */

namespace Plausible\Analytics\WP;

class Assets {
	/**
	 * Build class.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Action/filter hooks.
	 *
	 * @return void
	 */
	private function init() {
		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_register_assets' ] );
	}


	/**
	 * Register Assets.
	 *
	 * @return void
	 * @throws \Exception
	 * @since  1.0.0
	 * @access public
	 */
	public function maybe_register_assets() {
		$settings  = Helpers::get_settings();
		$user_role = Helpers::get_user_role();

		/**
		 * Bail if tracked_user_roles is empty (which means no roles should be tracked) or,
		 * if the current role should not be tracked.
		 */
		if ( ( ! empty( $user_role ) && ! isset( $settings['tracked_user_roles'] ) ) || ( ! empty( $user_role ) && ! in_array( $user_role, $settings['tracked_user_roles'], true ) ) ) {
			return; // @codeCoverageIgnore
		}

		// Dummy script, which'll allow us to add inline scripts later.
		wp_register_script( 'plausible-analytics', false );
		wp_enqueue_script(
			'plausible-analytics',
			false,
			[],
			null,
			apply_filters( 'plausible_load_js_in_footer', false )
		);

		$url    = $this->get_js_url( true );
		$script = sprintf(
			'window.plausible=window.plausible||function(){(window.plausible.q=window.plausible.q||[]).push(arguments)},window.plausible.init=function(i){window.plausible.o=i||{}};var script=document.createElement("script");script.type="text/javascript",script.defer=!0,script.src="%s";var r=document.getElementsByTagName("script")[0];r.parentNode.insertBefore(script,r);',
			$url
		);
		$options = wp_json_encode( apply_filters( 'plausible_analytics_init_options', [] ) );
		// transformRequest and customProperties can contain a JS function.
		$options = preg_replace(
			'/"(transformRequest|customProperties)"\s*:\s*"(\(\)\s*=>\s*{[^}]*})"/',
			'"$1": $2',
			$options
		);
		$script  .= "\nplausible.init($options);";

		wp_add_inline_script( 'plausible-analytics', $script );

		// Track Cloaked Affiliate Links (if enabled)
		if ( EnhancedMeasurements::is_enabled( EnhancedMeasurements::CLOAKED_AFFILIATE_LINKS ) ) {
			wp_enqueue_script(
				'plausible-affiliate-links',
				PLAUSIBLE_ANALYTICS_PLUGIN_URL . 'assets/dist/js/plausible-affiliate-links.js',
				[ 'plausible-analytics' ],
				filemtime( PLAUSIBLE_ANALYTICS_PLUGIN_DIR . 'assets/dist/js/plausible-affiliate-links.js' ),
			);

			$affiliate_links = Helpers::get_settings()['affiliate_links'] ?? [];

			wp_add_inline_script( 'plausible-affiliate-links', 'const plausibleAffiliateLinks = ' . wp_json_encode( $affiliate_links ) . ';', 'before' );
		}

		// Track 404 pages (if enabled)
		if ( EnhancedMeasurements::is_enabled( EnhancedMeasurements::FOUR_O_FOUR ) && is_404() ) {
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
			$data       = str_replace( '"document.location.pathname"', 'document.location.pathname', $data );
			$event_name = EnhancedMeasurements::FOUR_O_FOUR;

			wp_add_inline_script(
				'plausible-analytics',
				"document.addEventListener('DOMContentLoaded', () => { plausible( $event_name, $data ); });"
			);
		}

		// Track query parameters (if enabled and set)
		if ( EnhancedMeasurements::is_enabled( EnhancedMeasurements::QUERY_PARAMS ) ) {
			$query_params = Helpers::get_settings()['query_params'] ?? [];
			$props        = [];

			foreach ( $query_params as $query_param ) {
				if ( isset( $_REQUEST[ $query_param ] ) ) {
					$props[ $query_param ] = $_REQUEST[ $query_param ];
				}
			}

			if ( ! empty( $props ) ) {
				$data = wp_json_encode(
					[
						'props' => $props,
					]
				);

				$script = "plausible('WP Query Parameters', $data );";

				wp_add_inline_script(
					'plausible-analytics',
					"document.addEventListener('DOMContentLoaded', () => {\n$script\n});"
				);
			}
		}

		// Track search results. Tracks a search event with the search term and the number of results, and a pageview with the site's search URL.
		if ( EnhancedMeasurements::is_enabled( EnhancedMeasurements::SEARCH_QUERIES ) && is_search() ) {
			global $wp_query;

			$search_source = isset( $_REQUEST['search_source'] ) ? sanitize_text_field( $_REQUEST['search_source'] ) : wp_get_referer();
			$data          = wp_json_encode(
				[
					'props' => [
						// convert queries to lowercase and remove trailing whitespace to ensure the same terms are grouped together
						'search_query'  => strtolower( trim( get_search_query() ) ),
						'result_count'  => $wp_query->found_posts,
						'search_source' => $search_source,
					],
				]
			);
			$script        = "plausible('WP Search Queries', $data );";

			wp_add_inline_script(
				'plausible-analytics',
				"document.addEventListener('DOMContentLoaded', () => {\n$script\n});"
			);
		}

		// This action allows you to add your own custom scripts!
		do_action( 'plausible_analytics_after_register_assets', $settings );
	}

	/**
	 * @param bool $local
	 *
	 * @return string
	 * @throws \Exception
	 */
	protected function get_js_url( bool $local = false ) {
		return Helpers::get_js_url( $local );
	}
}
