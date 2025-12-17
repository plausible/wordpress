<?php
/**
 * @package Plausible Analytics Integration Tests - Helpers
 */

namespace Plausible\Analytics\Tests\Integration;

use Exception;
use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\Cron;
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
	 * @throws Exception
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

		add_filter( 'plausible_analytics_settings', [ $this, 'enableRevenue' ] );
		add_filter( 'plausible_analytics_integrations_woocommerce', '__return_true' );

		$filename = Helpers::get_filename();

		remove_filter( 'plausible_analytics_settings', [ $this, 'enableRevenue' ] );
		remove_filter( 'plausible_analytics_integrations_woocommerce', '__return_true' );

		$this->assertEquals( 'plausible.revenue.tagged-events', $filename );

		add_filter( 'plausible_analytics_settings', [ $this, 'enableSearch' ] );

		global $wp_query;

		$wp_query = new \WP_Query();
		$wp_query->query( 's=test' );

		$filename = Helpers::get_filename();

		$this->assertEquals( 'plausible.pageview-props.manual', $filename );

		remove_filter( 'plausible_analytics_settings', [ $this, 'enableSearch' ] );
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
	 * Enable Enhanced Measurements > Search Queries
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function enableSearch( $settings ) {
		$settings[ 'enhanced_measurements' ] = [ 'search' ];

		return $settings;
	}

	/**
	 * @see Helpers::get_settings()
	 *
	 * @return void
	 */
	public function testGetPostSettings() {
		$_POST[ 'action' ]  = 'plausible_analytics_save_options';
		$_POST[ 'options' ] = wp_json_encode( [ [ 'name' => 'post_test', 'value' => 'post_test' ] ] );

		$settings = Helpers::get_settings();

		$this->assertArrayHasKey( 'post_test', $settings );
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
	 * @throws Exception
	 */
	public function testGetJsPath() {
		add_filter( 'plausible_analytics_settings', [ $this, 'enableProxy' ] );

		$path = Helpers::get_js_path();

		remove_filter( 'plausible_analytics_settings', [ $this, 'enableProxy' ] );

		$upload_dir = wp_get_upload_dir()[ 'basedir' ];

		$this->assertMatchesRegularExpression( "~$upload_dir/[a-z0-9]{10}/[a-z0-9]{8}\.js~", $path );
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
	 * @throws Exception
	 */
	public function testGetRestEndpoint() {
		$endpoint = Helpers::get_rest_endpoint( false );

		$this->assertMatchesRegularExpression( '~/wp-json/[0-9a-z]{6}/v1/[0-9a-z]{4}/[0-9a-z]{8}~', $endpoint );

		$endpoint = Helpers::get_rest_endpoint();

		$this->assertMatchesRegularExpression( '~http://example.org/index.php\?rest_route=/[0-9a-z]{6}/v1/[0-9a-z]{4}/[0-9a-z]{8}~', $endpoint );
	}
}
