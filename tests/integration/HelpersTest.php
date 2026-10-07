<?php
/**
 * @package Plausible Analytics Integration Tests - Helpers
 */

namespace Plausible\Analytics\Tests\Integration;

use Exception;
use Plausible\Analytics\Tests\TestableHelpers;
use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\Cron;
use Plausible\Analytics\WP\Helpers;
use function Brain\Monkey\Functions\when;

class HelpersTest extends TestCase {
	/**
	 * Enable excluded pages option.
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function addExcludedPages( $settings ) {
		$settings['excluded_pages'] = 'test';

		return $settings;
	}

	/**
	 * Enable Enhanced Measurements > Outbound Links.
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function enableOutboundLinks( $settings ) {
		$settings['enhanced_measurements'] = [ 'outbound-links' ];

		return $settings;
	}

	/**
	 * Enable Enhanced Measurements > Search Queries
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function enableSearch( $settings ) {
		$settings['enhanced_measurements'] = [ 'search' ];

		return $settings;
	}

	/**
	 * Enable Self Hosted domain.
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function enableSelfHostedDomain( $settings ) {
		$settings['self_hosted_domain'] = 'self-hosted-test.org';

		return $settings;
	}

	/**
	 * Set domain.
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function setDomain( $settings ) {
		$settings['domain_name'] = [ 'default' => 'test.dev' ];

		return $settings;
	}

	/**
	 * @see Helpers::get_endpoint_url()
	 * @return void
	 */
	public function testGetDataApiUrl() {
		delete_option( 'plausible_analytics_settings' );
		$url = Helpers::get_endpoint_url();
		$this->assertEquals( 'https://plausible.io/api/event', $url );

		try {
			add_filter( 'plausible_analytics_settings', [ $this, 'enableProxy' ] );

			$url = Helpers::get_endpoint_url();

			$this->assertMatchesRegularExpression( '~http://example.org/index.php\?rest_route=/[0-9a-z]{6}/v1/[0-9a-z]{4}/[0-9a-z]{8}~', $url );
		} finally {
			remove_filter( 'plausible_analytics_settings', [ $this, 'enableProxy' ] );
		}

		try {
			add_filter( 'plausible_analytics_settings', [ $this, 'enableSelfHostedDomain' ] );

			$url = Helpers::get_endpoint_url();

			$this->assertEquals( 'https://self-hosted-test.org/api/event', $url );
		} finally {
			remove_filter( 'plausible_analytics_settings', [ $this, 'enableSelfHostedDomain' ] );
		}
	}

	/**
	 * @see Helpers::get_domain()
	 * @return void
	 */
	public function testGetDomain() {
		try {
			update_option( 'plausible_analytics_settings', [ 'domain_name' => [ 'default' => 'example.org' ] ] );
			$domain = Helpers::get_domain();

			$this->assertEquals( 'example.org', $domain );

			add_filter( 'plausible_analytics_settings', [ $this, 'setDomain' ] );

			$domain = Helpers::get_domain();

			$this->assertEquals( 'test.dev', $domain );
		} finally {
			remove_filter( 'plausible_analytics_settings', [ $this, 'setDomain' ] );
		}
	}

	/**
	 * @see Helpers::get_domain()
	 * @return void
	 */
	public function testGetDomainWithDefaultOnly() {
		$settings = [
			'domain_name' => [
				'default' => 'example.com',
				'fr'      => '',
			],
		];

		update_option( 'plausible_analytics_settings', $settings );

		$filter_mode    = function () {
			return true;
		};
		$filter_domains = function () {
			return [ 'fr' => 'example.fr' ];
		};
		$filter_lang    = function () {
			return 'fr';
		};
		$filter_key     = function () {
			return 'fr';
		};

		add_filter( 'plausible_analytics_language_per_domain_mode', $filter_mode );
		add_filter( 'wpml_setting', $filter_domains, 10, 2 );
		add_filter( 'wpml_current_language', $filter_lang );
		add_filter( 'plausible_analytics_current_language_domain_key', $filter_key );

		try {
			$domain = Helpers::get_domain();
			$this->assertEquals( 'example.com', $domain );
		} finally {
			remove_filter( 'plausible_analytics_language_per_domain_mode', $filter_mode );
			remove_filter( 'wpml_setting', $filter_domains );
			remove_filter( 'wpml_current_language', $filter_lang );
			remove_filter( 'plausible_analytics_current_language_domain_key', $filter_key );
		}
	}

	/**
	 * @see Helpers::get_domain()
	 * @return void
	 */
	public function testGetDomainWithLanguageKey() {
		$settings = [
			'domain_name' => [
				'default' => 'example.com',
				'fr'      => 'example.fr',
			],
		];

		update_option( 'plausible_analytics_settings', $settings );

		$filter_mode    = function () {
			return true;
		};
		$filter_domains = function () {
			return [ 'fr' => 'example.fr' ];
		};
		$filter_lang    = function () {
			return 'fr';
		};
		$filter_key     = function () {
			return 'fr';
		};

		add_filter( 'plausible_analytics_language_per_domain_mode', $filter_mode );
		add_filter( 'wpml_setting', $filter_domains, 10, 2 );
		add_filter( 'wpml_current_language', $filter_lang );
		add_filter( 'plausible_analytics_current_language_domain_key', $filter_key );

		try {
			$domain = Helpers::get_domain();
			$this->assertEquals( 'example.fr', $domain );
		} finally {
			remove_filter( 'plausible_analytics_language_per_domain_mode', $filter_mode );
			remove_filter( 'wpml_setting', $filter_domains );
			remove_filter( 'wpml_current_language', $filter_lang );
			remove_filter( 'plausible_analytics_current_language_domain_key', $filter_key );
		}
	}

	/**
	 * @see Helpers::get_js_path()
	 * @return void
	 * @throws Exception
	 */
	public function testGetJsPath() {
		$path       = TestableHelpers::get_js_path();
		$upload_dir = wp_get_upload_dir()['basedir'];

		$this->assertMatchesRegularExpression( "~$upload_dir/[a-z0-9]{10}/pa-test-tracker-id.js~", $path );
	}

	/**
	 * The bundled HTTP client rejects non-ASCII hosts, so internationalized domains should be converted to punycode.
	 *
	 * @see Helpers::to_ascii_domain()
	 * @return void
	 */
	public function testToAsciiDomain() {
		$this->assertEquals( 'plausible.example.com', Helpers::to_ascii_domain( 'plausible.example.com' ) );
		$this->assertEquals( 'plausible.example.com:8000', Helpers::to_ascii_domain( 'plausible.example.com:8000' ) );
		$this->assertEquals( 'plausible.xn--mller-kva.de', Helpers::to_ascii_domain( 'plausible.müller.de' ) );
		$this->assertEquals( 'plausible.xn--mller-kva.de:8000', Helpers::to_ascii_domain( 'plausible.müller.de:8000' ) );
		$this->assertEquals( 'xn--mller-kva.de/plausible', Helpers::to_ascii_domain( 'müller.de/plausible' ) );
		// Deviation characters are kept (nontransitional processing): faß.de and fass.de are different domains.
		$this->assertEquals( 'xn--fa-hia.de', Helpers::to_ascii_domain( 'faß.de' ) );
	}

	/**
	 * @see Helpers::get_hosted_domain_url()
	 * @return void
	 */
	public function testGetHostedDomainUrlWithInternationalizedDomain() {
		$settings = function ( $settings ) {
			$settings['self_hosted_domain'] = 'plausible.müller.de';

			return $settings;
		};

		add_filter( 'plausible_analytics_settings', $settings );

		try {
			$this->assertEquals( 'https://plausible.xn--mller-kva.de', Helpers::get_hosted_domain_url() );
		} finally {
			remove_filter( 'plausible_analytics_settings', $settings );
		}
	}

	/**
	 * @see Helpers::get_js_url()
	 */
	public function testGetJsUrl() {
		$url = TestableHelpers::get_js_url();

		$this->assertEquals( 'https://plausible.io/js/pa-test-tracker-id.js', $url );

		try {
			add_filter( 'plausible_analytics_settings', [ $this, 'enableProxy' ] );

			$local_file = TestableHelpers::get_js_path();

			// Until the cron has downloaded the local file, the script is loaded from Plausible Analytics.
			wp_delete_file( $local_file );
			wp_clear_scheduled_hook( Cron::TASK_NAME );
			delete_transient( 'plausible_analytics_js_download_attempt' );

			$this->assertEquals( 'https://plausible.io/js/pa-test-tracker-id.js', TestableHelpers::get_js_url( true ) );
			$this->assertNotFalse( wp_next_scheduled( Cron::TASK_NAME ) );

			// Once that attempt has run (and failed), no new one is scheduled until the backoff has expired.
			wp_clear_scheduled_hook( Cron::TASK_NAME );

			$this->assertEquals( 'https://plausible.io/js/pa-test-tracker-id.js', TestableHelpers::get_js_url( true ) );
			$this->assertFalse( wp_next_scheduled( Cron::TASK_NAME ) );

			file_put_contents( $local_file, '// test' );

			$url = TestableHelpers::get_js_url( true );

			$this->assertMatchesRegularExpression( '~http://example.org/wp-content/uploads/.*?/.*?.js~', $url );
		} finally {
			remove_filter( 'plausible_analytics_settings', [ $this, 'enableProxy' ] );
			wp_delete_file( $local_file ?? '' );
			wp_clear_scheduled_hook( Cron::TASK_NAME );
			delete_transient( 'plausible_analytics_js_download_attempt' );
		}

		try {
			add_filter( 'plausible_analytics_settings', [ $this, 'enableSelfHostedDomain' ] );

			$url = TestableHelpers::get_js_url();

			$this->assertEquals( 'https://self-hosted-test.org/js/pa-test-tracker-id.js', $url );
		} finally {
			remove_filter( 'plausible_analytics_settings', [ $this, 'enableSelfHostedDomain' ] );
		}
	}

	/**
	 * @see Helpers::get_settings()
	 *
	 * @return void
	 */
	public function testGetPostSettings() {
		$_POST['action']  = 'plausible_analytics_save_options';
		$_POST['options'] = wp_json_encode( [ [ 'name' => 'post_test', 'value' => 'post_test' ] ] );

		$settings = Helpers::get_settings();

		$this->assertArrayNotHasKey( 'post_test', $settings );
	}

	/**
	 * After moving the site to another host, domain or path, the cache directory's path and URL should follow the
	 * current uploads directory, not the (absolute) ones stored before 2.6.3.
	 *
	 * @see Helpers::get_proxy_resources()
	 * @return void
	 * @throws Exception
	 */
	public function testGetProxyResourcesAfterMovingTheSite() {
		$stored = new \ReflectionProperty( Helpers::class, 'stored_proxy_resources' );
		$stored->setAccessible( true );
		$backup = get_option( 'plausible_analytics_proxy_resources' );

		update_option(
			'plausible_analytics_proxy_resources',
			[
				'namespace' => 'abcdef',
				'base'      => 'abcd',
				'endpoint'  => 'abcdefgh',
				'cache_dir' => '/home/old-host/public_html/wp-content/uploads/0123456789/',
				'cache_url' => 'https://old-host.example/wp-content/uploads/0123456789/',
			]
		);
		$stored->setValue( null, null );

		try {
			$upload_dir = wp_get_upload_dir();

			$this->assertEquals( trailingslashit( $upload_dir['basedir'] ) . '0123456789/', Helpers::get_proxy_resource( 'cache_dir' ) );
			$this->assertEquals( trailingslashit( $upload_dir['baseurl'] ) . '0123456789/', Helpers::get_proxy_resource( 'cache_url' ) );
			// The REST route stays the same.
			$this->assertEquals( 'abcdef', Helpers::get_proxy_resource( 'namespace' ) );
		} finally {
			update_option( 'plausible_analytics_proxy_resources', $backup );
			$stored->setValue( null, null );
		}
	}

	/**
	 * @see Helpers::get_proxy_resource()
	 * @return void
	 * @throws Exception
	 */
	public function testGetProxyResource() {
		$namespace = Helpers::get_proxy_resource( 'namespace' );

		$this->assertMatchesRegularExpression( '/[a-z0-9]{6}/', $namespace );

		$base = Helpers::get_proxy_resource( 'base' );

		$this->assertMatchesRegularExpression( '/[a-z0-9]{4}/', $base );

		$endpoint = Helpers::get_proxy_resource( 'endpoint' );

		$this->assertMatchesRegularExpression( '/[a-z0-9]{8}/', $endpoint );

		$cache_dir  = Helpers::get_proxy_resource( 'cache_dir' );
		$upload_dir = wp_get_upload_dir()['basedir'];

		$this->assertMatchesRegularExpression( "~$upload_dir/[a-z0-9]{10}/~", $cache_dir );
		$this->assertTrue( is_dir( $cache_dir ) );

		$cache_url  = Helpers::get_proxy_resource( 'cache_url' );
		$upload_url = wp_get_upload_dir()['baseurl'];

		$this->assertMatchesRegularExpression( "~$upload_url/[a-z0-9]{10}/~", $cache_url );
	}

	/**
	 * @see Helpers::get_rest_endpoint()
	 * @return void
	 * @throws Exception
	 */
	public function testGetRestEndpoint() {
		$endpoint = Helpers::get_rest_endpoint( false );

		$this->assertMatchesRegularExpression( '~/wp-json/[0-9a-z]{6}/v1/[0-9a-z]{4}/[0-9a-z]{8}~', $endpoint );

		$endpoint = Helpers::get_rest_endpoint();

		$this->assertMatchesRegularExpression( '~http://example.org/index.php\?rest_route=/[0-9a-z]{6}/v1/[0-9a-z]{4}/[0-9a-z]{8}~', $endpoint );
	}

	/**
	 * @see Helpers::get_settings()
	 * @return void
	 */
	public function testGetSettingsNormalization() {
		$settings = [
			'domain_name' => 'example.com',
			'api_token'   => 'test-token',
			'shared_link' => 'https://plausible.io/share/example.com',
		];

		update_option( 'plausible_analytics_settings', $settings );

		$normalized_settings = Helpers::get_settings();

		$this->assertIsArray( $normalized_settings['api_token'] );
		$this->assertEquals( [ 'default' => 'test-token' ], $normalized_settings['api_token'] );

		$this->assertIsArray( $normalized_settings['shared_link'] );
		$this->assertEquals( [ 'default' => 'https://plausible.io/share/example.com' ], $normalized_settings['shared_link'] );
	}

	/**
	 * @see Helpers::update_setting()
	 * @return void
	 */
	public function testUpdateSetting() {
		Helpers::update_setting( 'test', true );

		$this->assertTrue( Helpers::get_settings()['test'] );
	}

	/**
	 * TranslatePress' "Multiple Domains" mappings should be reduced to [ language_code => domain ], excluding the
	 * default language and any language that isn't enabled or lacks a domain.
	 *
	 * @see Helpers::get_translatepress_language_domains()
	 * @return void
	 * @throws \ReflectionException
	 */
	public function testGetTranslatePressLanguageDomains() {
		update_option(
			'trp_settings',
			[
				'default-language'     => 'en_US',
				'trp-multiple-domains' => [
					// Default language: always excluded, it maps to the main WP domain.
					'en_US' => [ 'enabled' => '1', 'domain' => 'https://example.com' ],
					'fr_FR' => [ 'enabled' => '1', 'domain' => 'https://example.fr' ],
					'de_DE' => [ 'enabled' => '1', 'domain' => 'https://example.de' ],
					// Not enabled: excluded.
					'es_ES' => [ 'enabled' => '', 'domain' => 'https://example.es' ],
					// No domain: excluded.
					'nl_NL' => [ 'enabled' => '1', 'domain' => '' ],
				],
			]
		);

		$method = new \ReflectionMethod( Helpers::class, 'get_translatepress_language_domains' );
		$method->setAccessible( true );

		$domains = $method->invoke( null );

		$this->assertEquals(
			[
				'fr_FR' => 'https://example.fr',
				'de_DE' => 'https://example.de',
			],
			$domains
		);
	}

	/**
	 * When no "Multiple Domains" mappings are configured, no language domains should be returned.
	 *
	 * @see Helpers::get_translatepress_language_domains()
	 * @return void
	 * @throws \ReflectionException
	 */
	public function testGetTranslatePressLanguageDomainsEmpty() {
		update_option( 'trp_settings', [ 'default-language' => 'en_US', 'trp-multiple-domains' => [] ] );

		$method = new \ReflectionMethod( Helpers::class, 'get_translatepress_language_domains' );
		$method->setAccessible( true );

		$this->assertEquals( [], $method->invoke( null ) );
	}

	/**
	 * WPML's hidden languages should be left out, whether or not WPML lists them for the current user.
	 *
	 * @see Helpers::get_active_languages()
	 * @return void
	 */
	public function testGetActiveLanguagesWpmlSkipsHiddenLanguages() {
		$plugin = function () {
			return Helpers::MULTILANG_PLUGIN_WPML;
		};
		$active = function () {
			return [ 'en' => [], 'es' => [], 'de' => [] ];
		};
		$hidden = function ( $value, $setting ) {
			return $setting === 'hidden_languages' ? [ 'de' ] : $value;
		};

		add_filter( 'plausible_analytics_multilang_plugin', $plugin );
		add_filter( 'wpml_active_languages', $active );
		add_filter( 'wpml_setting', $hidden, 10, 2 );

		try {
			$this->assertEquals( [ 'en', 'es' ], Helpers::get_active_languages() );
		} finally {
			remove_filter( 'plausible_analytics_multilang_plugin', $plugin );
			remove_filter( 'wpml_active_languages', $active );
			remove_filter( 'wpml_setting', $hidden );
		}
	}

	/**
	 * TranslatePress' unpublished languages should be left out.
	 *
	 * @see Helpers::get_active_languages()
	 * @return void
	 */
	public function testGetActiveLanguagesTranslatePressSkipsUnpublishedLanguages() {
		$plugin = function () {
			return Helpers::MULTILANG_PLUGIN_TRANSLATEPRESS;
		};

		add_filter( 'plausible_analytics_multilang_plugin', $plugin );
		update_option(
			'trp_settings',
			[
				'translation-languages' => [ 'en_US', 'es_ES', 'nl_NL' ],
				'publish-languages'     => [ 'en_US', 'es_ES' ],
			]
		);

		try {
			$this->assertEquals( [ 'en_US', 'es_ES' ], Helpers::get_active_languages() );
		} finally {
			remove_filter( 'plausible_analytics_multilang_plugin', $plugin );
			delete_option( 'trp_settings' );
		}
	}

	/**
	 * A language's pinned WCML default currency should be used; anything else ("Keep", stored as false, 0 or '0')
	 * should fall back to the store's base currency setting, not the (filtered) currency of the current request.
	 *
	 * @see Helpers::get_currency_for_language()
	 * @return void
	 */
	public function testGetCurrencyForLanguage() {
		// Multicurrency plugins filter this to the currency of the current request.
		when( 'get_woocommerce_currency' )->justReturn( 'JPY' );
		update_option( 'woocommerce_currency', 'USD' );

		$plugin  = function () {
			return Helpers::MULTILANG_PLUGIN_WPML;
		};
		$default = function () {
			return 'en';
		};

		add_filter( 'plausible_analytics_integrations_edd', '__return_false' );
		add_filter( 'plausible_analytics_multilang_plugin', $plugin );
		add_filter( 'wpml_default_language', $default );
		update_option(
			'_wcml_settings',
			[
				'enable_multi_currency' => 2,
				'default_currencies'    => [ 'en' => 'GBP', 'es' => 'EUR', 'nl' => '0', 'de' => false, 'fr' => 0 ],
			]
		);

		try {
			$this->assertEquals( 'GBP', Helpers::get_currency_for_language() );
			$this->assertEquals( 'EUR', Helpers::get_currency_for_language( 'es' ) );
			$this->assertEquals( 'USD', Helpers::get_currency_for_language( 'nl' ) );
			$this->assertEquals( 'USD', Helpers::get_currency_for_language( 'de' ) );
			$this->assertEquals( 'USD', Helpers::get_currency_for_language( 'fr' ) );
			$this->assertEquals( 'USD', Helpers::get_currency_for_language( 'it' ) );

			// The per-language defaults don't apply when the currency follows the visitor's location.
			update_option( '_wcml_settings', array_merge( get_option( '_wcml_settings' ), [ 'currency_mode' => 'by_location' ] ) );

			$this->assertEquals( 'USD', Helpers::get_currency_for_language( 'es' ) );

			// A default currency per language is a WCML (i.e. WPML) concept only.
			remove_filter( 'plausible_analytics_multilang_plugin', $plugin );
			update_option( '_wcml_settings', array_merge( get_option( '_wcml_settings' ), [ 'currency_mode' => 'by_language' ] ) );

			$this->assertEquals( 'USD', Helpers::get_currency_for_language( 'es' ) );
		} finally {
			remove_filter( 'plausible_analytics_integrations_edd', '__return_false' );
			remove_filter( 'plausible_analytics_multilang_plugin', $plugin );
			remove_filter( 'wpml_default_language', $default );
			delete_option( '_wcml_settings' );
			delete_option( 'woocommerce_currency' );
		}
	}

	/**
	 * @see Helpers::get_home_relative_path()
	 * @return void
	 */
	public function testGetHomeRelativePath() {
		$home_url = function ( $url, $path ) {
			return 'https://example.com/site/' . ltrim( $path, '/' );
		};

		add_filter( 'home_url', $home_url, 10, 2 );

		try {
			$this->assertEquals( 'site', Helpers::get_home_path() );
			$this->assertEquals( 'product*', Helpers::get_home_relative_path( '/site/product*' ) );
			$this->assertEquals( 'es', Helpers::get_home_relative_path( 'https://example.com/site/es/' ) );
			$this->assertEquals( '', Helpers::get_home_relative_path( 'https://example.com/site/' ) );
			// A path outside the site's path is returned as-is.
			$this->assertEquals( 'product*', Helpers::get_home_relative_path( '/product*' ) );
			$this->assertEquals( 'sites/product*', Helpers::get_home_relative_path( '/sites/product*' ) );
		} finally {
			remove_filter( 'home_url', $home_url );
		}
	}
}
