<?php
/**
 * Plausible Analytics | Compatibility.
 *
 * @since      1.2.5
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP;

use Exception;

/**
 * @codeCoverageIgnore Because this is to be tested in a headless browser.
 */
class Compatibility {
	/**
	 * A list of filters and actions to prevent our script from being manipulated by other plugins, known to cause issues.
	 * Our script is already <1KB, so there's no need to minify, combine or optimize it in any other way.
	 *
	 * @return void
	 */
	public function __construct() {
		// Autoptimize
		if ( defined( 'AUTOPTIMIZE_PLUGIN_VERSION' ) ) {
			add_filter( 'autoptimize_filter_js_exclude', [ $this, 'exclude_plausible_js_as_string' ] );
		}

		// Cloudflare Rocket Loader
		add_filter( 'script_loader_tag', [ $this, 'exclude_from_cloudflare_rocket_loader' ], 10, 2 );

		// LiteSpeed Cache
		if ( defined( 'LSCWP_V' ) ) {
			add_filter( 'litespeed_optimize_js_excludes', [ $this, 'exclude_plausible_js' ] );
			add_filter( 'litespeed_optm_js_defer_exc', [ $this, 'exclude_plausible_inline_js' ] );
			add_filter( 'litespeed_optm_gm_js_exc', [ $this, 'exclude_plausible_inline_js' ] );
		}

		// SG Optimizer
		if ( defined( '\SiteGround_Optimizer\VERSION' ) ) {
			add_filter( 'sgo_javascript_combine_exclude', [ $this, 'exclude_js_by_handle' ] );
			add_filter( 'sgo_js_minify_exclude', [ $this, 'exclude_js_by_handle' ] );
			add_filter( 'sgo_js_async_exclude', [ $this, 'exclude_js_by_handle' ] );
			add_filter( 'sgo_javascript_combine_excluded_inline_content', [ $this, 'exclude_plausible_inline_js' ] );
			add_filter( 'sgo_javascript_combine_excluded_external_paths', [ $this, 'exclude_plausible_js' ] );
		}

		// TranslatePress
		if ( defined( 'TRP_PLUGIN_VERSION' ) ) {
			add_filter( 'rest_url', [ $this, 'multilingual_compatibility' ], 10, 1 );
		}

		// W3 Total Cache
		if ( defined( 'W3TC_VERSION' ) ) {
			add_filter( 'w3tc_minify_js_script_tags', [ $this, 'unset_plausible_js' ] );
		}

		// WPML
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			add_filter( 'rest_url', [ $this, 'multilingual_compatibility' ], 10, 1 );
		}

		// WP Optimize
		if ( defined( 'WPO_VERSION' ) ) {
			add_filter( 'wp-optimize-minify-default-exclusions', [ $this, 'exclude_plausible_js' ] );
		}

		// WP Rocket
		if ( defined( 'WP_ROCKET_VERSION' ) ) {
			add_filter( 'rocket_excluded_inline_js_content', [ $this, 'exclude_plausible_inline_js' ] );
			add_filter( 'rocket_exclude_js', [ $this, 'exclude_plausible_js' ] );
			add_filter( 'rocket_minify_excluded_external_js', [ $this, 'exclude_plausible_js' ] );
			add_filter( 'rocket_delay_js_exclusions', [ $this, 'exclude_plausible_inline_js' ] );
			add_filter( 'rocket_delay_js_exclusions', [ $this, 'exclude_by_proxy_endpoint' ] );
			add_filter( 'rocket_exclude_defer_js', [ $this, 'exclude_plausible_js_by_relative_url' ] );
		}
	}

	/**
	 * Adds window.plausible
	 *
	 * @param mixed $exclude_js
	 *
	 * @return string
	 */
	public function exclude_plausible_js_as_string( $exclude_js ) {
		$exclude_js .= ', window.plausible, ' . Helpers::get_js_url( true );

		return $exclude_js;
	}

	/**
	 * Add Plausible Analytics attributes.
	 *
	 * @param string $handle Script handle.
	 * @param string $tag Script tag.
	 *
	 * @return string
	 * @since  1.0.0
	 * @access public
	 *
	 */
	public function exclude_from_cloudflare_rocket_loader( $tag, $handle ) {
		// Bail if it's not our script.
		if ( 'plausible-analytics' !== $handle ) {
			return $tag; // @codeCoverageIgnore
		}

		$settings = Helpers::get_settings();

		/**
		 * the data-cfasync ensures this script isn't processed by CF Rocket Loader @see https://developers.cloudflare.com/speed/optimization/content/rocket-loader/ignore-javascripts/
		 */
		$params = "defer data-cfasync='false'";
		$params = apply_filters( 'plausible_analytics_script_params', $params );

		return str_replace( ' src', " {$params} src", $tag );
	}

	/**
	 * Dear WP Rocket/SG Optimizer/Etc., don't minify/combine our inline JS, please.
	 *
	 * @filter rocket_excluded_inline_js_content
	 * @since  1.2.5
	 *
	 * @param array $inline_js
	 *
	 * @return array
	 */
	public function exclude_plausible_inline_js( $inline_js ) {
		$inline_js[ 'plausible' ] = 'window.plausible';

		return $inline_js;
	}

	/**
	 * Dear WP Rocket/SG Optimizer/Etc., don't minify/combine/delay our external JS, please.
	 *
	 * @filter rocket_exclude_js
	 * @filter rocket_minify_excluded_external_js
	 * @since  1.2.5
	 *
	 * @param array $excluded_js
	 *
	 * @return array
	 */
	public function exclude_plausible_js( $excluded_js ) {
		$excluded_js[] = Helpers::get_js_url( true );

		return $excluded_js;
	}

	/**
	 * Remove Plausible.js (or the local file, when proxy is enabled) of the list of JS files to minify.
	 *
	 * @filter w3tc_minify_js_script_tags
	 * @since  2.4.0
	 *
	 * @param $script_tags
	 *
	 * @return array
	 * @throws Exception
	 */
	public function unset_plausible_js( $script_tags ) {
		return array_filter(
			$script_tags,
			function ( $tag ) {
				return str_contains( $tag, Helpers::get_js_url( true ) ) === false;
			}
		);
	}

	/**
	 * Dear WP Rocket/SG Optimizer/Etc., don't minify/combine/delay our external JS, please.
	 *
	 * @filter rocket_exclude_js
	 * @filter rocket_minify_excluded_external_js
	 * @since  1.2.5
	 *
	 * @param array $excluded_js
	 *
	 * @return array
	 */
	public function exclude_plausible_js_by_relative_url( $excluded_js ) {
		$excluded_js[] = preg_replace( '/http[s]?:\/\/.*?(\/)/', '$1', Helpers::get_js_url( true ) );

		return $excluded_js;
	}

	/**
	 * Some optimization plugins (WP Rocket) replace the JS src URL with their own URL, before being able to exclude it.
	 * So, when the proxy is enabled, exclusion fails. That's why we exclude again by proxy endpoint.
	 *
	 * @filter rocket_delay_js_exclusions
	 * @since  2.4.0
	 *
	 * @param $excluded_js
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function exclude_by_proxy_endpoint( $excluded_js ) {
		$excluded_js[] = Helpers::get_rest_endpoint( false );

		return $excluded_js;
	}

	/**
	 * Dear WP Rocket/SG Optimizer/Etc., don't minify/combine/delay our external JS, please.
	 *
	 * @filter rocket_exclude_js
	 * @filter rocket_minify_excluded_external_js
	 * @since  1.2.5
	 *
	 * @param array $excluded_js
	 *
	 * @return array
	 */
	public function exclude_js_by_handle( $excluded_handles ) {
		$excluded_handles[] = 'plausible-analytics';

		return $excluded_handles;
	}

	/**
	 * Multilingual plugins, e.g., TranslatePress and WPML, override the REST API URL to include
	 * the language 'subdirectory', which leads to 404 errors.
	 *
	 * This filter forces it back to default behavior.
	 *
	 * @filter rest_url
	 *
	 * @param mixed $url
	 *
	 * @return string|void
	 * @throws Exception
	 */
	public function multilingual_compatibility( $url ) {
		$rest_endpoint = Helpers::get_rest_endpoint( false );

		if ( strpos( $url, $rest_endpoint ) !== false ) {
			return get_option( 'home' ) . $rest_endpoint;
		}

		return $url;
	}
}
