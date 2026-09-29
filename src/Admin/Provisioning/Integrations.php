<?php
/**
 * Plausible Analytics | Provisioning | Integrations
 * @since      2.3.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Admin\Provisioning;

use Plausible\Analytics\WP\Admin\Provisioning;
use Plausible\Analytics\WP\Client;
use Plausible\Analytics\WP\Helpers;

class Integrations {
	/**
	 * @var Provisioning
	 */
	private $provisioning;

	/**
	 * @var array The goals of each Language Domain's site, keyed by domain key. Retrieved once per request and shared by
	 *            the WooCommerce and EDD funnels. @see self::get_existing_goals()
	 */
	private $existing_goals = [];

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
			$currency = ! empty( $event_goals['purchase'] ) ? $this->get_purchase_goal_currency( $event_goals['purchase'], $key, $client ) : '';

			// The existing goals couldn't be read, so the purchase goal's currency is unknown. Skip this domain rather
			// than risk a rejected funnel; it's provisioned on the next settings save.
			if ( $currency === null ) {
				continue;
			}

			$goals = [];
			/**
			 * Goals which shouldn't (or can't) be part of the funnel.
			 */
			$extra_goals        = [];
			$view_product_paths = [];

			foreach ( $event_goals as $event_key => $event_goal ) {
				if ( $event_key === 'remove-from-cart' ) {
					$extra_goals[] = $this->provisioning->create_goal_request( $event_goal );

					continue;
				}

				if ( $event_key === 'purchase' ) {
					$goals[] = $this->provisioning->create_goal_request( $event_goal, 'Revenue', $currency );

					continue;
				}

				if ( $event_key === 'view-product' ) {
					$view_product_paths = $this->get_pageview_goal_paths( $this->get_goal_path( $event_goal ), $key, $post_type );

					/**
					 * A funnel step holds one goal, so the default language's path is the one that ends up in the
					 * funnel. The other languages get a goal of their own.
					 */
					$goals[] = $this->provisioning->create_goal_request( $event_goal, 'Pageview', null, $view_product_paths[0] );

					foreach ( array_slice( $view_product_paths, 1 ) as $path ) {
						$extra_goals[] = $this->provisioning->create_goal_request( $event_goal, 'Pageview', null, $path );
					}

					continue;
				}

				$goals[] = $this->provisioning->create_goal_request( $event_goal );
			}

			if ( ! empty( $extra_goals ) ) {
				$all_ids = $this->provisioning->create_goals( $extra_goals, $client, $key, $all_ids );

				// Persist immediately so the localized Pageview goals stay tracked (and thus cleanable) even if the
				// funnel creation below fails and never gets to save them.
				update_option( 'plausible_analytics_enhanced_measurements_goal_ids', $all_ids );
			}

			if ( ! empty( $view_product_paths ) ) {
				$all_ids = $this->maybe_dismantle_outdated_funnel( $funnel_name, $view_product_paths[0], $key, $client, $all_ids );
			}

			$all_ids = $this->provisioning->create_funnel( $funnel_name, $goals, $client, $key, $all_ids );

			$all_ids = $this->reconcile_view_product_goals( $view_product_paths, $key, $client, $all_ids );
		}
	}

	/**
	 * Makes Plausible remove $key's funnel when its view-product step targets a path that's no longer served there, e.g.
	 * "Visit /product*" on a domain that serves its products under /producto/, so it's recreated with the current steps.
	 *
	 * Funnels are sequential, so such a funnel never gets past its first step. The API can't update or delete a funnel,
	 * and creating it returns an existing funnel of the same name unchanged. But Plausible removes a funnel once fewer
	 * than two of its steps remain, which happens when their goals are deleted. So every step's goal is deleted except
	 * the last one, the purchase (Revenue) goal, whose currency can't be changed. create_funnel() then recreates the
	 * funnel and its goals. The goals' history is kept, as Plausible computes conversions from the events themselves.
	 *
	 * Deleted goals can't be restored, so there's nothing to roll back if recreating the funnel fails: it no longer
	 * exists then, and the next settings save creates it. A goal that couldn't be deleted keeps its stored ID.
	 *
	 * Note: a user-made funnel that shares one of these goals loses that step.
	 *
	 * @since 2.6.2
	 *
	 * @param string $funnel_name
	 * @param string $view_product_path The path the funnel's view-product step should target, e.g. /producto*.
	 * @param string $key
	 * @param Client $client
	 * @param array  $all_ids
	 *
	 * @return array The (possibly pruned) goal-ID map.
	 *
	 * @codeCoverageIgnore We don't want to test the API.
	 */
	private function maybe_dismantle_outdated_funnel( $funnel_name, $view_product_path, $key, $client, $all_ids ) {
		foreach ( (array) $client->get_funnels() as $funnel ) {
			$steps = $funnel['funnel']['steps'] ?? [];

			if ( ( $funnel['funnel']['name'] ?? '' ) !== $funnel_name || count( $steps ) < 2 ) {
				continue;
			}

			$first_step = (string) ( $steps[0]['goal']['display_name'] ?? '' );

			// Only the view-product step is checked: it's the only step whose goal changes, with the languages served.
			if ( strpos( $first_step, 'Visit ' ) !== 0 || $first_step === sprintf( 'Visit %s', $view_product_path ) ) {
				return $all_ids;
			}

			foreach ( array_slice( $steps, 0, -1 ) as $step ) {
				$id = $step['goal']['id'] ?? null;

				if ( $id && $client->delete_goal( $id ) ) {
					unset( $all_ids[ $key ][ $id ] );
				}
			}

			update_option( 'plausible_analytics_enhanced_measurements_goal_ids', $all_ids );

			break;
		}

		return $all_ids;
	}

	/**
	 * Returns the currency to create $key's purchase (Revenue) goal in.
	 *
	 * A Revenue goal's name is unique per site and its currency can't be changed. So, when $key's dashboard already
	 * has this goal (e.g. created in the store's base currency before 2.6.2), its currency is kept: requesting another
	 * currency is rejected (422), which would abort the funnel. Deleting and recreating the goal isn't an option either:
	 * the existing funnel would lose its purchase step, and funnels can't be updated through the API.
	 *
	 * @since 2.6.2
	 *
	 * @param string $event_goal The purchase goal's name.
	 * @param string $key        The Language Domain the goal is created for.
	 * @param Client $client
	 *
	 * @return string|null ISO 4217 currency code, or null when the existing goals couldn't be retrieved.
	 *
	 * @codeCoverageIgnore We don't want to test the API.
	 */
	private function get_purchase_goal_currency( $event_goal, $key, $client ) {
		$goals = $this->get_existing_goals( $key, $client );

		if ( $goals === false ) {
			return null;
		}

		foreach ( $goals as $goal ) {
			if ( ( $goal['goal_type'] ?? '' ) === 'Goal.Revenue' &&
			     ( $goal['goal']['event_name'] ?? '' ) === $event_goal &&
			     ! empty( $goal['goal']['currency'] ) ) {
				return $goal['goal']['currency'];
			}
		}

		return Helpers::get_currency_for_language( $key );
	}

	/**
	 * Returns the goals of $key's site, retrieving them only once per request.
	 *
	 * @since 2.6.2
	 *
	 * @param string $key
	 * @param Client $client
	 *
	 * @return array|false @see Client::get_goals()
	 *
	 * @codeCoverageIgnore We don't want to test the API.
	 */
	private function get_existing_goals( $key, $client ) {
		if ( ! isset( $this->existing_goals[ $key ] ) ) {
			$this->existing_goals[ $key ] = $client->get_goals();
		}

		return $this->existing_goals[ $key ];
	}

	/**
	 * Removes stale localized view-product goals for $key's domain: a non-localized "Visit /product*" left by a
	 * pre-2.6.2 install, or a goal for a path no longer served. Provisioning is otherwise create-only, so those would
	 * linger alongside the current localized goals.
	 *
	 * This runs only after the current goals have been (re)created, and never when the current paths can't be trusted:
	 * it deletes nothing while a multilingual plugin is active but its language list is empty (the paths would fall
	 * back to the unlocalized "/product*" and the localized goals would be wrongly deleted), nor unless every current
	 * goal is already present (so a failed (re)create can't leave the domain without a view-product goal). View-product
	 * goals are the only Pageview ("Visit ") goals the plugin creates.
	 *
	 * @since 2.6.2
	 *
	 * @param array  $view_product_paths The current view-product goal paths, @see self::get_pageview_goal_paths().
	 *                                   Empty when the integration has no view-product goal.
	 * @param string $key
	 * @param Client $client
	 * @param array  $all_ids
	 *
	 * @return array The (possibly pruned) goal-ID map.
	 *
	 * @codeCoverageIgnore Because it depends on 3rd party plugins.
	 */
	private function reconcile_view_product_goals( $view_product_paths, $key, $client, $all_ids ) {
		if ( empty( $view_product_paths ) ||
		     ( Helpers::get_multilang_plugin() && empty( Helpers::get_active_languages() ) ) ) {
			return $all_ids;
		}

		$current_view_product = array_map(
			static function ( $path ) {
				return sprintf( 'Visit %s', $path );
			},
			$view_product_paths
		);

		// Only prune once every current goal is present, so a failed (re)create can't leave the domain goalless.
		if ( array_diff( $current_view_product, (array) ( $all_ids[ $key ] ?? [] ) ) ) {
			return $all_ids;
		}

		$deleted_stale = false;

		foreach ( $all_ids[ $key ] ?? [] as $id => $name ) {
			if ( strpos( (string) $name, 'Visit ' ) === 0 && ! in_array( $name, $current_view_product, true ) &&
			     $client->delete_goal( $id ) ) {
				unset( $all_ids[ $key ][ $id ] );
				$deleted_stale = true;
			}
		}

		if ( $deleted_stale ) {
			update_option( 'plausible_analytics_enhanced_measurements_goal_ids', $all_ids );
		}

		return $all_ids;
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
		$relative = Helpers::get_home_relative_path( $path );
		// On multisite subdirectory installs the site's path precedes the language prefix.
		$home_path = $relative !== trim( $path, '/' ) ? Helpers::get_home_path() : '';
		$suffix    = '';

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
	 * @since 2.6.2 Also deletes the Pageview goals created for the other languages, including languages that have since
	 *        been removed (whose localized goal name can no longer be regenerated).
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

		/**
		 * A view-product goal is a Pageview goal named "Visit <path>*", whose localized path depends on the languages
		 * that were active when it was created. Those may since have changed (a language removed), so regenerating the
		 * name from the current languages no longer matches it, and it would be orphaned. The plugin only ever stores
		 * its own goals, and the view-product goals are the only Pageview goals it creates, so when this integration
		 * has a view-product goal every stored "Visit " goal is one of ours and can be removed regardless of the
		 * current languages.
		 */
		$delete_view_product = ! empty( $integration->event_goals['view-product'] );

		foreach ( $this->provisioning->get_clients() as $domain_key => $client ) {
			$goals = $all_ids[ $domain_key ] ?? [];

			foreach ( $goals as $id => $name ) {
				$is_view_product = $delete_view_product && strpos( (string) $name, 'Visit ' ) === 0;

				if ( ( $is_view_product || $this->provisioning->array_search_contains( $name, $integration->event_goals ) ) &&
				     $client->delete_goal( $id ) ) {
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
}
