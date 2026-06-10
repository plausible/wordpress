<?php
/**
 * Plausible Analytics | Assets
 */

namespace Plausible\Analytics\WP;

class Assets {
	/**
	 * Build class.
	 *
	 * @param bool $init
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
		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_enqueue_main_script' ], 1 );
		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_enqueue_cloaked_affiliate_links_assets' ], 11 );
		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_enqueue_four_o_four_script' ], 11 );
		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_enqueue_query_params_script' ], 11 );
		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_enqueue_search_queries_script' ], 11 );
	}

	/**
	 * Enqueue cloaked affiliate links assets if the option is enabled.
	 *
	 * @return void
	 */
	public function maybe_enqueue_cloaked_affiliate_links_assets() {
		if ( EnhancedMeasurements::is_enabled( EnhancedMeasurements::CLOAKED_AFFILIATE_LINKS ) && Helpers::main_script_is_registered() ) {
			wp_enqueue_script(
				'plausible-affiliate-links',
				PLAUSIBLE_ANALYTICS_PLUGIN_URL . 'assets/dist/js/plausible-affiliate-links.js',
				[ 'plausible-analytics' ],
				filemtime( PLAUSIBLE_ANALYTICS_PLUGIN_DIR . 'assets/dist/js/plausible-affiliate-links.js' ),
				[ 'in_footer' => true ],
			);

			$affiliate_links = Helpers::get_settings()['affiliate_links'] ?? [];

			wp_add_inline_script( 'plausible-affiliate-links', 'const plausibleAffiliateLinks = ' . wp_json_encode( $affiliate_links ) . ';', 'before' );
		}
	}

	/**
	 * Enqueue 404 script if the option is enabled.
	 *
	 * @return void
	 */
	public function maybe_enqueue_four_o_four_script() {
		$is_404 = apply_filters( 'plausible_analytics_is_404', is_404() );

		if ( EnhancedMeasurements::is_enabled( EnhancedMeasurements::FOUR_O_FOUR ) && $is_404 && Helpers::main_script_is_registered() ) {
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
	}

	/**
	 * Register main JS if this user should be tracked.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 * @throws \Exception
	 */
	public function maybe_enqueue_main_script() {
		$settings  = Helpers::get_settings();
		$user_role = Helpers::get_user_role();
		$url       = $this->get_js_url( true );

		if ( ! $url ) {
			echo '<!-- ' . __( 'Please enter your plugin token to start using Plausible Analytics.', 'plausible-analytics' ) . " -->\n";

			return;
		}

		/**
		 * This is a dummy script that will allow us to attach inline scripts further down the line.
		 */
		wp_register_script( 'plausible-analytics', $url, [], null, apply_filters( 'plausible_load_js_in_footer', false ) );

		/**
		 * Bail if tracked_user_roles is empty (which means no roles should be tracked) or if the current role should not be tracked.
		 */
		if ( ( ! empty( $user_role ) && ! isset( $settings['tracked_user_roles'] ) ) || ( ! empty( $user_role ) && ! in_array( $user_role, $settings['tracked_user_roles'], true ) ) ) {
			return; // @codeCoverageIgnore
		}

		wp_enqueue_script( 'plausible-analytics' );

		$script  = 'window.plausible=window.plausible||function(){(plausible.q=plausible.q||[]).push(arguments)},plausible.init=plausible.init||function(i){plausible.o=i||{}};';
		$options = wp_json_encode( apply_filters( 'plausible_analytics_init_options', [] ) );
		// transformRequest and customProperties can contain a JS function.
		$options = preg_replace(
			'/"(transformRequest|customProperties)"\s*:\s*"(\(\)\s*=>\s*{[^}]*})"/',
			'"$1": $2',
			$options
		);
		$script  .= "\nplausible.init($options);";

		wp_add_inline_script( 'plausible-analytics', $script );

		// This action allows you to add your own custom scripts!
		do_action( 'plausible_analytics_after_register_assets', $settings );
	}

	/**
	 * This seam keeps our code testable.
	 *
	 * @param bool $local
	 *
	 * @return string
	 * @throws \Exception
	 *
	 * @codeCoverageIgnore Because Helpers are tested elsewhere.
	 */
	protected function get_js_url( bool $local = false ) {
		return Helpers::get_js_url( $local );
	}

	/**
	 * Enqueue Query Params script if the option is enabled.
	 *
	 * @return void
	 */
	public function maybe_enqueue_query_params_script() {
		if ( EnhancedMeasurements::is_enabled( EnhancedMeasurements::QUERY_PARAMS ) && Helpers::main_script_is_registered() ) {
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
	}

	/**
	 * Enqueue the Search Queries script if the option is enabled.
	 *
	 * @return void
	 */
	public function maybe_enqueue_search_queries_script() {
		$is_search = apply_filters( 'plausible_analytics_is_search', is_search() );

		if ( EnhancedMeasurements::is_enabled( EnhancedMeasurements::SEARCH_QUERIES ) && $is_search && Helpers::main_script_is_registered() ) {
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
	}
}
