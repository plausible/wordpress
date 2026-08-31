<?php
/**
 * Plausible Analytics | Provisioning | Integrations
 * @since      2.3.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Admin\Provisioning;

use Plausible\Analytics\WP\Admin\Provisioning;
use Plausible\Analytics\WP\Helpers;

class Integrations {
	/**
	 * @var Provisioning
	 */
	private $provisioning;

	/**
	 * Build class.
	 *
	 * We use DI to prevent circular dependency.
	 *
	 * @param Provisioning|null $provisioning
	 *
	 * @codeCoverageIgnore
	 */
	public function __construct( $provisioning = null ) {
		$this->provisioning = $provisioning;

		if ( ! $this->provisioning ) {
			$this->provisioning = new Provisioning();
		}

		$this->init();
	}

	/**
	 * Action & filter hooks.
	 *
	 * We use Dependency Injection to prevent circular dependency.
	 *
	 * @return void
	 * @codeCoverageIgnore This is merely a wrapper to load classes. No need to test.
	 */
	private function init() {
		new Integrations\WooCommerce( $this );
		new Integrations\EDD( $this );
	}

	/**
	 * @since 2.6.2 Added $post_type, to allow translating the Pageview goal's path.
	 *
	 * @param array  $event_goals
	 * @param string $funnel_name
	 * @param string $post_type   The integration's product post type, e.g. 'product'.
	 *
	 * @return void
	 * @codeCoverageIgnore We don't want to test the API.
	 */
	public function create_integration_funnel( $event_goals, $funnel_name, $post_type = '' ) {
		$all_ids = $this->provisioning->normalize_option(
			get_option( 'plausible_analytics_enhanced_measurements_goal_ids', [] )
		);

		foreach ( $this->provisioning->get_clients() as $key => $client ) {
			$goals = [];
			/**
			 * Goals which shouldn't (or can't) be part of the funnel.
			 */
			$extra_goals = [];

			foreach ( $event_goals as $event_key => $event_goal ) {
				if ( $event_key === 'remove-from-cart' ) {
					$extra_goals[] = $this->provisioning->create_goal_request( $event_goal );

					continue;
				}

				if ( $event_key === 'purchase' ) {
					$currency = \Plausible\Analytics\WP\Integrations::is_edd_active() ? edd_get_currency() : get_woocommerce_currency();
					$goals[]  = $this->provisioning->create_goal_request( $event_goal, 'Revenue', $currency );

					continue;
				}

				if ( $event_key === 'view-product' ) {
					$paths = $this->get_pageview_goal_paths( $this->get_goal_path( $event_goal ), $key, $post_type );

					/**
					 * A funnel step holds one goal, so the default language's path is the one that ends up in the
					 * funnel. The other languages get a goal of their own.
					 */
					$goals[] = $this->provisioning->create_goal_request( $event_goal, 'Pageview', null, array_shift( $paths ) );

					foreach ( $paths as $path ) {
						$extra_goals[] = $this->provisioning->create_goal_request( $event_goal, 'Pageview', null, $path );
					}

					continue;
				}

				$goals[] = $this->provisioning->create_goal_request( $event_goal );
			}

			if ( ! empty( $extra_goals ) ) {
				$all_ids = $this->provisioning->create_goals( $extra_goals, $client, $key, $all_ids );
			}

			$all_ids = $this->provisioning->create_funnel( $funnel_name, $goals, $client, $key, $all_ids );
		}
	}

	/**
	 * Returns the path of a "Visit /some/path*" goal.
	 *
	 * @since 2.6.2
	 *
	 * @param string $event_goal
	 *
	 * @return string
	 */
	private function get_goal_path( $event_goal ) {
		return '/' . preg_replace( '/^.*?\//', '', $event_goal );
	}

	/**
	 * Returns the Pageview goal paths for $path: one for each language that's served on $domain_key's domain.
	 *
	 * Multilingual plugins serve translated content under a language prefix (/es/product/...) and, when the post type's
	 * base slug is translated (e.g. WooCommerce Multilingual's Store URLs), under a translated base (/producto/...).
	 * A goal for the default language's path would never match those pageviews.
	 *
	 * The default language's path is always the first element.
	 *
	 * @since 2.6.2
	 *
	 * @param string $path       E.g. /product*
	 * @param string $domain_key The Language Domain the goal is created for.
	 * @param string $post_type  The post type $path's base slug belongs to.
	 *
	 * @return array
	 *
	 * @codeCoverageIgnore Because it depends on 3rd party plugins.
	 */
	private function get_pageview_goal_paths( $path, $domain_key, $post_type ) {
		$languages = Helpers::get_active_languages();

		if ( empty( $languages ) ) {
			return [ $path ];
		}

		$default = Helpers::get_default_language();

		if ( Helpers::is_language_per_domain_mode() ) {
			// Each domain serves exactly one language, from its own root.
			$languages = [ $domain_key === 'default' ? $default : $domain_key ];
		} else {
			$languages = array_merge( [ $default ], array_diff( $languages, [ $default ] ) );
		}

		$paths = [];

		foreach ( array_filter( $languages ) as $language ) {
			$paths[] = $this->localize_goal_path( $path, $language, $post_type );
		}

		$paths = array_values( array_unique( array_filter( $paths ) ) );

		return ! empty( $paths ) ? $paths : [ $path ];
	}

	/**
	 * Rewrites $path to the URL the given language is served under, e.g. /product* > /es/producto*.
	 *
	 * @since 2.6.2
	 *
	 * @param string $path
	 * @param string $language_code
	 * @param string $post_type
	 *
	 * @return string
	 *
	 * @codeCoverageIgnore Because it depends on 3rd party plugins.
	 */
	private function localize_goal_path( $path, $language_code, $post_type ) {
		$home_path = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
		$relative  = ltrim( $path, '/' );

		// On multisite subdirectory installs the site's path precedes the language prefix.
		if ( $home_path !== '' && strpos( $relative, "$home_path/" ) === 0 ) {
			$relative = substr( $relative, strlen( $home_path ) + 1 );
		} else {
			$home_path = '';
		}

		$suffix = '';

		if ( substr( $relative, -1 ) === '*' ) {
			$suffix   = '*';
			$relative = substr( $relative, 0, -1 );
		}

		$slug  = Helpers::translate_url_slug( trim( $relative, '/' ), $language_code, $post_type );
		$parts = array_filter( [ $home_path, Helpers::get_language_url_prefix( $language_code ), $slug ] );

		return '/' . implode( '/', $parts ) . $suffix;
	}

	/**
	 * Deletes the integration-specific goals using the stored goal IDs.
	 *
	 * @since 2.6.2 Also deletes the Pageview goals created for the other languages.
	 *
	 * @param object $integration The integration object containing event goals to be deleted.
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore We don't want to test the API.
	 */
	public function delete_integration_goals( $integration ) {
		$all_ids = $this->provisioning->normalize_option(
			get_option( 'plausible_analytics_enhanced_measurements_goal_ids', [] )
		);

		foreach ( $this->provisioning->get_clients() as $domain_key => $client ) {
			$goals       = $all_ids[ $domain_key ] ?? [];
			$event_goals = $this->add_localized_event_goals( (array) $integration->event_goals, $domain_key, $integration->post_type ?? '' );

			foreach ( $goals as $id => $name ) {
				$key = $this->provisioning->array_search_contains( $name, $event_goals );

				if ( $key ) {
					$client->delete_goal( $id );
					unset( $goals[ $id ] );
				}
			}

			if ( empty( $goals ) ) {
				unset( $all_ids[ $domain_key ] );
			} else {
				$all_ids[ $domain_key ] = $goals;
			}
		}

		update_option( 'plausible_analytics_enhanced_measurements_goal_ids', $all_ids );
	}

	/**
	 * Adds the view-product goal of every other language to $event_goals, so the goals created for those languages
	 * are recognized (and deleted) too.
	 *
	 * @since 2.6.2
	 *
	 * @param array  $event_goals
	 * @param string $domain_key
	 * @param string $post_type
	 *
	 * @return array
	 *
	 * @codeCoverageIgnore Because it depends on 3rd party plugins.
	 */
	private function add_localized_event_goals( $event_goals, $domain_key, $post_type ) {
		if ( empty( $event_goals['view-product'] ) ) {
			return $event_goals;
		}

		$event_goal = $event_goals['view-product'];
		$path       = $this->get_goal_path( $event_goal );

		foreach ( $this->get_pageview_goal_paths( $path, $domain_key, $post_type ) as $i => $localized_path ) {
			$event_goals[ "view-product-$i" ] = str_replace( $path, $localized_path, $event_goal );
		}

		return $event_goals;
	}
}
