<?php
/**
 * @package Plausible Analytics Integration Tests - Helpers
 */

namespace Plausible\Analytics\Tests\Integration;

use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\Helpers;

class HelpersTest extends TestCase {
	/**
	 * @see Helpers::get_js_url()
	 */
	public function testGetJsUrl() {
		$url = Helpers::get_js_url();

		$this->assertEquals( 'https://plausible.io/js/plausible.js', $url );

		add_filter( 'plausible_analytics_settings', [ $this, 'enableProxy' ] );

		$url = Helpers::get_js_url( true );

		remove_filter( 'plausible_analytics_settings', [ $this, 'enableProxy' ] );

		$this->assertMatchesRegularExpression( '~http://example.org/wp-content/uploads/.*?/.*?.js~', $url );

		add_filter( 'plausible_analytics_settings', [ $this, 'enableSelfHostedDomain' ] );

		$url = Helpers::get_js_url();

		remove_filter( 'plausible_analytics_settings', [ $this, 'enableSelfHostedDomain' ] );

		$this->assertEquals( 'https://self-hosted-test.org/js/plausible.js', $url );
	}

	/**
	 * Enable the proxy.
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function enableProxy( $settings ) {
		$settings[ 'proxy_enabled' ] = 'on';

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
		$settings[ 'self_hosted_domain' ] = 'self-hosted-test.org';

		return $settings;
	}

	/**
	 * @see Helpers::get_filename()
	 */
	public function testGetFilename() {
		add_filter( 'plausible_analytics_settings', [ $this, 'addExcludedPages' ] );

		$filename = Helpers::get_filename();

		remove_filter( 'plausible_analytics_settings', [ $this, 'addExcludedPages' ] );

		$this->assertEquals( 'plausible.exclusions', $filename );

		add_filter( 'plausible_analytics_settings', [ $this, 'enableProxy' ] );

		$filename = Helpers::get_filename( true );

		remove_filter( 'plausible_analytics_settings', [ $this, 'enableProxy' ] );

		$this->assertMatchesRegularExpression( '~[a-z0-9]{8}~', $filename );

		add_filter( 'plausible_analytics_settings', [ $this, 'enableOutboundLinks' ] );

		$filename = Helpers::get_filename();

		remove_filter( 'plausible_analytics_settings', [ $this, 'enableOutboundLinks' ] );

		$this->assertEquals( 'plausible.outbound-links', $filename );
	}

	/**
	 * Enable excluded pages option.
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function addExcludedPages( $settings ) {
		$settings[ 'excluded_pages' ] = 'test';

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
		$settings[ 'enhanced_measurements' ] = [ 'outbound-links' ];

		return $settings;
	}

	/**
	 * @see Helpers::get_proxy_resource()
	 * @return void
	 * @throws \Exception
	 */
	public function testGetProxyResource() {
		$namespace = Helpers::get_proxy_resource( 'namespace' );

		$this->assertMatchesRegularExpression( '/[a-z0-9]{6}/', $namespace );

		$base = Helpers::get_proxy_resource( 'base' );

		$this->assertMatchesRegularExpression( '/[a-z0-9]{4}/', $base );

		$endpoint = Helpers::get_proxy_resource( 'endpoint' );

		$this->assertMatchesRegularExpression( '/[a-z0-9]{8}/', $endpoint );

		$cache_dir  = Helpers::get_proxy_resource( 'cache_dir' );
		$upload_dir = wp_get_upload_dir()[ 'basedir' ];

		$this->assertMatchesRegularExpression( "~$upload_dir/[a-z0-9]{10}/~", $cache_dir );
		$this->assertTrue( is_dir( $cache_dir ) );

		$cache_url  = Helpers::get_proxy_resource( 'cache_url' );
		$upload_url = wp_get_upload_dir()[ 'baseurl' ];

		$this->assertMatchesRegularExpression( "~$upload_url/[a-z0-9]{10}/~", $cache_url );

		$file_alias = Helpers::get_proxy_resource( 'file_alias' );

		$this->assertMatchesRegularExpression( '/[a-z0-9]{8}/', $file_alias );
	}

	/**
	 * @see Helpers::update_setting()
	 * @return void
	 */
	public function testUpdateSetting() {
		Helpers::update_setting( 'test', true );

		$this->assertTrue( Helpers::get_settings()[ 'test' ] );
	}

	/**
	 * @see Helpers::get_js_path()
	 * @return void
	 * @throws \Exception
	 */
	public function testGetJsPath() {
		add_filter( 'plausible_analytics_settings', [ $this, 'enableProxy' ] );

		$path = Helpers::get_js_path();

		remove_filter( 'plausible_analytics_settings', [ $this, 'enableProxy' ] );

		$upload_dir = wp_get_upload_dir()[ 'basedir' ];

		$this->assertMatchesRegularExpression( "~$upload_dir/[a-z0-9]{10}/[a-z0-9]{8}\.js~", $path );
	}

	/**
	 * @see Helpers::download_file()
	 * @return void
	 * @throws \Exception
	 */
	public function testDownloadFile() {
		Helpers::download_file( 'https://plausible.io/js/plausible.js', wp_get_upload_dir()[ 'basedir' ] . '/test.js' );

		$this->assertFileExists( wp_get_upload_dir()[ 'basedir' ] . '/test.js' );
	}

	/**
	 * @see Helpers::get_domain()
	 * @return void
	 */
	public function testGetDomain() {
		$domain = Helpers::get_domain();

		$this->assertEquals( 'example.org', $domain );

		add_filter( 'plausible_analytics_settings', [ $this, 'setDomain' ] );

		$domain = Helpers::get_domain();

		remove_filter( 'plausible_analytics_settings', [ $this, 'setDomain' ] );

		$this->assertEquals( 'test.dev', $domain );
	}

	/**
	 * Set domain_name option.
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function setDomain( $settings ) {
		$settings[ 'domain_name' ] = 'test.dev';

		return $settings;
	}

	/**
	 * @see Helpers::get_data_api_url()
	 * @return void
	 */
	public function testGetDataApiUrl() {
		$url = Helpers::get_data_api_url();

		$this->assertEquals( 'https://plausible.io/api/event', $url );

		add_filter( 'plausible_analytics_settings', [ $this, 'enableProxy' ] );

		$url = Helpers::get_data_api_url();

		remove_filter( 'plausible_analytics_settings', [ $this, 'enableProxy' ] );

		$this->assertMatchesRegularExpression( '~http://example.org/index.php\?rest_route=/[0-9a-z]{6}/v1/[0-9a-z]{4}/[0-9a-z]{8}~', $url );

		add_filter( 'plausible_analytics_settings', [ $this, 'enableSelfHostedDomain' ] );

		$url = Helpers::get_data_api_url();

		remove_filter( 'plausible_analytics_settings', [ $this, 'enableSelfHostedDomain' ] );

		$this->assertEquals( 'https://self-hosted-test.org/api/event', $url );
	}

	/**
	 * @see Helpers::get_rest_endpoint()
	 * @return void
	 * @throws \Exception
	 */
	public function testGetRestEndpoint() {
		$endpoint = Helpers::get_rest_endpoint( false );

		$this->assertMatchesRegularExpression( '~/wp-json/[0-9a-z]{6}/v1/[0-9a-z]{4}/[0-9a-z]{8}~', $endpoint );

		$endpoint = Helpers::get_rest_endpoint();

		$this->assertMatchesRegularExpression( '~http://example.org/index.php\?rest_route=/[0-9a-z]{6}/v1/[0-9a-z]{4}/[0-9a-z]{8}~', $endpoint );
	}
}
