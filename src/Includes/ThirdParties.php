<?php
/**
 * Plausible Analytics | Admin Filters.
 *
 * @since 1.0.0
 *
 * @package    WordPress
 * @subpackage Plausible Analytics
 */


namespace Plausible\Analytics\WP\Includes;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 *
 */
class ThirdParties {

	private $third_parties = [
		'wp_optimize',
		'w3_total_cache',
		'wp_rocket',
		'autoptimize',
		'siteground_optimizer',
	];

	/**
	 * Constructor.
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */
	public function __construct() {

		$this->run_third_parties_hooks();
	}

	/**
	 * @return void
	 */
	private function run_third_parties_hooks() {

		foreach ( $this->get_third_parties() as $third_party ) {
			if ( method_exists( $this, 'run_third_party_action_' . $third_party ) ) {
				$this->{'run_third_party_action_' . $third_party}();
			}
		}
	}

	/**
	 * @return string[]
	 */
	private function get_third_parties() {
		return $this->third_parties;
	}

	/**
	 * @return void
	 */
	public function run_third_party_action_wp_optimize() {
		add_filter( 'wp-optimize-minify-default-exclusions', [ $this, 'add_wp_optimize_filter' ], 10, 1 );
	}

	/**
	 * @param $exclude_js
	 *
	 * @return mixed
	 */
	public function add_wp_optimize_filter( $exclude_js ) {
		$exclude_js[] = Helpers::get_analytics_url();

		return $exclude_js;
	}


	/**
	 * @return void
	 */
	public function run_third_party_action_w3_total_cache() {
		add_filter(
			'w3tc_minify_js_script_tags',
			[
				$this,
				'add_w3_total_cache_filter',
			],
			10,
			1
		);
	}

	/**
	 * @param $data
	 *
	 * @return mixed
	 */
	public function add_w3_total_cache_filter( $excluded_js ) {
		$excluded_js = array_filter(
			$excluded_js,
			function ( $script ) {

				return strstr( $script, 'plausible-analytics-js-after' ) === false && strstr( $script, basename( Helpers::get_analytics_url() ) ) === false;
			}
		);

		return $excluded_js;
	}

	/**
	 * @return void
	 */
	public function run_third_party_action_wp_rocket() {
		add_filter( 'rocket_exclude_js', [ $this, 'add_wp_rocket_filter_js' ], 10, 1 );
		add_filter( 'rocket_excluded_inline_js_content', [ $this, 'add_wp_rocket_filter_inline_js' ], 10, 1 );
	}

	/**
	 * @param $excluded_js
	 *
	 * @return mixed
	 */
	function add_wp_rocket_filter_js( $excluded_js ) {
		$excluded_js[] = str_replace( home_url(), '', Helpers::get_analytics_url() );

		return $excluded_js;
	}

	/**
	 * @param $excluded_js
	 *
	 * @return mixed
	 */
	function add_wp_rocket_filter_inline_js( $excluded_js ) {
		$excluded_js[] = 'window.plausible';

		return $excluded_js;
	}

	/**
	 * @return void
	 */
	public function run_third_party_action_autoptimize() {
		add_filter( 'autoptimize_filter_js_exclude', [ $this, 'add_autoptimize_filter' ], 10, 1 );
	}

	/**
	 * @param $exclude_js
	 *
	 * @return mixed
	 */
	public function add_autoptimize_filter( $exclude_js ) {
		$exclude_js .= ', window.plausible,' . str_replace( home_url(), '', Helpers::get_analytics_url() );

		return $exclude_js;
	}


	/**
	 * @return void
	 */
	public function run_third_party_action_siteground_optimizer() {
		add_filter( 'sgo_javascript_combine_exclude', [ $this, 'add_siteground_optimizer_filter_js' ], 10, 1 );
		add_filter( 'sgo_js_minify_exclude', [ $this, 'add_siteground_optimizer_filter_js' ], 10, 1 );
		add_filter( 'sgo_js_async_exclude', [ $this, 'add_siteground_optimizer_filter_js' ], 10, 1 );
		add_filter(
			'sgo_javascript_combine_excluded_inline_content',
			[
				$this,
				'add_siteground_optimizer_filter_inline_js',
			],
			10,
			1
		);
	}

	/**
	 * @param $exclude_js
	 *
	 * @return mixed
	 */
	public function add_siteground_optimizer_filter_js( $exclude_js ) {

		$exclude_js[] = 'plausible-analytics';

		return $exclude_js;
	}

	/**
	 * @param $exclude_js
	 *
	 * @return mixed
	 */
	public function add_siteground_optimizer_filter_inline_js( $exclude_js ) {
		$exclude_js[] = 'window.plausible';

		return $exclude_js;
	}


}
