<?php
/**
 * @package Plausible Analytics Integration Tests - Helpers
 */

namespace Plausible\Analytics\Tests\Integration;

use Exception;
use Plausible\Analytics\Tests\TestableHelpers;
use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\Helpers;

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
	 * @see Helpers::get_js_url()
	 */
	public function testGetJsUrl() {
		$url = TestableHelpers::get_js_url();

		$this->assertEquals( 'https://plausible.io/js/pa-test-tracker-id.js', $url );

		try {
			add_filter( 'plausible_analytics_settings', [ $this, 'enableProxy' ] );

			$url = TestableHelpers::get_js_url( true );

			$this->assertMatchesRegularExpression( '~http://example.org/wp-content/uploads/.*?/.*?.js~', $url );
		} finally {
			remove_filter( 'plausible_analytics_settings', [ $this, 'enableProxy' ] );
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
	 * @see Helpers::update_setting()
	 * @return void
	 */
	public function testUpdateSetting() {
		Helpers::update_setting( 'test', true );

		$this->assertTrue( Helpers::get_settings()['test'] );
	}
}
