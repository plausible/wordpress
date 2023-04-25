<?php
/**
 * Plausible Analytics | Filters.
 *
 * @since      1.2.5
 *
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Includes;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Compatibility {
	/**
	 * A list of filters and actions to prevent our script from being manipulated by other plugins, known to cause issues.
	 *
	 * Our script is already <1KB, so there's no need to minify, combine or optimize it in any other way.
	 *
	 * @return void
	 */
	public function __construct() {
		if ( defined( 'WP_ROCKET_VERSION' ) ) {
			add_filter( 'rocket_excluded_inline_js_content', [ $this, 'exclude_plausible_inline_js' ] );
			add_filter( 'rocket_exclude_js', [ $this, 'exclude_plausible_js' ] );
			add_filter( 'rocket_minify_excluded_external_js', [ $this, 'exclude_plausible_js' ] );
		}
	}

	/**
	 * Dear WP Rocket, don't minify/combine our inline JS, please.
	 *
	 * @filter rocket_excluded_inline_js_content
	 *
	 * @param array $inline_js
	 * @since 1.2.5
	 *
	 * @return array
	 */
	public function exclude_plausible_inline_js( $inline_js ) {
		if ( ! isset( $inline_js['plausible'] ) ) {
			$inline_js['plausible'] = 'window.plausible';
		}

		return $inline_js;
	}

	/**
	 * Dear WP Rocket, don't minify/combine our external JS, please.
	 *
	 * @filter rocket_exclude_js
	 * @filter rocket_minify_excluded_external_js
	 *
	 * @param array $excluded_js
	 * @since 1.2.5
	 *
	 * @return array
	 */
	public function exclude_plausible_js( $excluded_js ) {
		$excluded_js[] = Helpers::get_js_url( true );

		return $excluded_js;
	}
}
