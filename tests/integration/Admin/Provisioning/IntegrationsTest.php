<?php
/**
 * Plausible Analytics | Admin | Provisioning | Integrations
 */

namespace Plausible\Analytics\Tests\Integration\Admin\Provisioning;

use Plausible\Analytics\WP\Admin\Provisioning;
use Plausible\Analytics\WP\Admin\Provisioning\Integrations;
use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\Client;

class IntegrationsTest extends TestCase {
	/**
	 * @see Integrations::delete_integration_goals()
	 */
	public function testDeleteIntegrationGoals() {
		$client = $this->getMockBuilder( Client::class )
		               ->onlyMethods( [ 'delete_goal' ] )
		               ->getMock();

		$client->method( 'delete_goal' )->willReturn( true );

		$provisioning = $this->getMockBuilder( Provisioning::class )
		                     ->setConstructorArgs( [ $client ] )
		                     ->onlyMethods( [ 'array_search_contains' ] )
		                     ->getMock();

		$provisioning->method( 'array_search_contains' )
		             ->willReturn( 1 );

		try {
			update_option( 'plausible_analytics_enhanced_measurements_goal_ids', [ 1, 2, 3 ] );

			$integration = new Integrations( $provisioning );
			$integration->delete_integration_goals( (object) [ 'event_goals' => [ '' ] ] );

			$goal_ids = get_option( 'plausible_analytics_enhanced_measurements_goal_ids' );

			$this->assertEmpty( $goal_ids );
		} finally {
			delete_option( 'plausible_analytics_enhanced_measurements_goal_ids' );
			$this->removeAction( 'update_option_plausible_analytics_settings', 'maybe_create_' );
			$this->removeAction( 'update_option_plausible_analytics_settings', 'maybe_delete_', 11 );
		}
	}

	/**
	 * Every language should get a Pageview goal for the URL it's served under, with the default language first,
	 * because that's the one that ends up in the funnel.
	 *
	 * @see Integrations::get_pageview_goal_paths()
	 * @return void
	 * @throws \ReflectionException
	 */
	public function testGetPageviewGoalPaths() {
		$this->withLanguages(
			function ( $paths ) {
				$this->assertEquals( [ '/product*', '/es/producto*', '/nl/product*' ], $paths( '/product*', 'default' ) );
			}
		);
	}

	/**
	 * In "domain per language" mode each domain serves exactly one language, from its own root, so no prefix should be
	 * added and only the goal for that domain's language should be created.
	 *
	 * @see Integrations::get_pageview_goal_paths()
	 * @return void
	 * @throws \ReflectionException
	 */
	public function testGetPageviewGoalPathsInLanguagePerDomainMode() {
		$per_domain = '__return_true';

		add_filter( 'plausible_analytics_language_per_domain_mode', $per_domain );

		try {
			$this->withLanguages(
				function ( $paths ) {
					$this->assertEquals( [ '/producto*' ], $paths( '/product*', 'es' ) );
					$this->assertEquals( [ '/product*' ], $paths( '/product*', 'default' ) );
				}
			);
		} finally {
			remove_filter( 'plausible_analytics_language_per_domain_mode', $per_domain );
		}
	}

	/**
	 * Without a multilingual plugin, the path should be left alone.
	 *
	 * @see Integrations::get_pageview_goal_paths()
	 * @return void
	 * @throws \ReflectionException
	 */
	public function testGetPageviewGoalPathsWithoutLanguages() {
		$this->assertEquals( [ '/product*' ], $this->getPageviewGoalPaths()( '/product*', 'default' ) );
	}

	/**
	 * Runs $test with a site that serves Spanish and Dutch besides its default language, of which Spanish has a
	 * translated product base.
	 *
	 * @param callable $test Receives a callable which returns the Pageview goal paths for a path/domain key.
	 *
	 * @return void
	 * @throws \ReflectionException
	 */
	private function withLanguages( $test ) {
		$languages = function () {
			return [ 'en', 'es', 'nl' ];
		};
		$default   = function () {
			return 'en';
		};
		$prefix    = function ( $prefix, $language ) {
			return $language === 'en' ? '' : $language;
		};
		$slug      = function ( $translated, $slug, $language ) {
			return $language === 'es' && $slug === 'product' ? 'producto' : $translated;
		};

		add_filter( 'plausible_analytics_active_languages', $languages );
		add_filter( 'plausible_analytics_default_language', $default );
		add_filter( 'plausible_analytics_language_url_prefix', $prefix, 10, 2 );
		add_filter( 'plausible_analytics_translated_url_slug', $slug, 10, 3 );

		try {
			$test( $this->getPageviewGoalPaths() );
		} finally {
			remove_filter( 'plausible_analytics_active_languages', $languages );
			remove_filter( 'plausible_analytics_default_language', $default );
			remove_filter( 'plausible_analytics_language_url_prefix', $prefix, 10 );
			remove_filter( 'plausible_analytics_translated_url_slug', $slug, 10 );
		}
	}

	/**
	 * @return callable
	 * @throws \ReflectionException
	 */
	private function getPageviewGoalPaths() {
		$integrations = ( new \ReflectionClass( Integrations::class ) )->newInstanceWithoutConstructor();
		$method       = new \ReflectionMethod( Integrations::class, 'get_pageview_goal_paths' );
		$method->setAccessible( true );

		return function ( $path, $domain_key ) use ( $method, $integrations ) {
			return $method->invoke( $integrations, $path, $domain_key, 'product' );
		};
	}
}
