<?php
/**
 * @package Plausible Analytics Integration Tests - Helpers
 */

namespace Plausible\Analytics\Tests\Integration;

use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\InitOptions;

class InitOptionsTest extends TestCase {
	/**
	 * @see InitOptions::maybe_add_pageview_props()
	 * @return void
	 */
	public function testAddPageviewProps() {
		try {
			global $post, $wp_query;
			$old_post  = $post;
			$old_query = $wp_query;

			$post_id             = wp_insert_post(
				[
					'id'           => 1,
					'post_author'  => 1,
					'post_title'   => 'Test',
					'post_content' => 'Test',
				]
			);
			$test_post           = get_post( $post_id );
			$post                = $test_post;
			$wp_query            = new \WP_Query();
			$wp_query->is_single = true;

			add_filter( 'plausible_analytics_settings', [ $this, 'enablePageviewProps' ] );

			/** @var InitOptions $class */
			$class   = $this->getMockBuilder( InitOptions::class )->disableOriginalConstructor()->onlyMethods( [] )->getMock();
			$options = $class->maybe_add_pageview_props();

			$this->assertArrayHasKey( 'customProperties', $options );
			$this->assertArrayHasKey( 'author', $options['customProperties'] );
			$this->assertEquals( 'admin', $options['customProperties']['author'] );
			$this->assertArrayHasKey( 'category', $options['customProperties'] );
			$this->assertEquals( 'Uncategorized', $options['customProperties']['category'] );
		} finally {
			$post     = $old_post;
			$wp_query = $old_query;
			remove_filter( 'plausible_analytics_settings', [ $this, 'enablePageviewProps' ] );
		}
	}

	/**
	 * @see InitOptions::maybe_add_proxy_options()
	 * @return void
	 * @throws \Exception
	 */
	public function testAddProxyOptions() {
		try {
			add_filter( 'plausible_analytics_settings', [ $this, 'enableProxy' ] );

			/** @var InitOptions $class */
			$class   = $this->getMockBuilder( InitOptions::class )->disableOriginalConstructor()->onlyMethods( [] )->getMock();
			$options = $class->maybe_add_proxy_options();

			$this->assertArrayHasKey( 'endpoint', $options );
		} finally {
			remove_filter( 'plausible_analytics_settings', [ $this, 'enableProxy' ] );
		}
	}

	/**
	 * @see InitOptions::maybe_exclude_pageview()
	 * @return void
	 */
	public function testExcludePageview() {
		/**
		 * Normal behavior.
		 */
		try {
			add_filter( 'plausible_analytics_settings', [ $this, 'setExcludePageview' ] );

			$class = $this->getMockBuilder( InitOptions::class )->disableOriginalConstructor()->onlyMethods( [
				'get_current_request',
			] )->getMock();

			$class->method( 'get_current_request' )->willReturn( 'http://example.com/category/test' );

			$options = $class->maybe_exclude_pageview();

			$this->assertArrayNotHasKey( 'transformRequest', $options );

			$class = $this->getMockBuilder( InitOptions::class )->disableOriginalConstructor()->onlyMethods( [
				'get_current_request',
			] )->getMock();

			$class->method( 'get_current_request' )->willReturn( 'http://test.example.com/test' );

			$options = $class->maybe_exclude_pageview();

			$this->assertArrayHasKey( 'transformRequest', $options );
		} finally {
			remove_filter( 'plausible_analytics_settings', [ $this, 'setExcludePageview' ] );
		}

		/**
		 * An asterisk should match everything.
		 */
		try {
			add_filter( 'plausible_analytics_settings', [ $this, 'setExcludePageviewEdgeCaseAsterisk' ] );

			$class = $this->getMockBuilder( InitOptions::class )->disableOriginalConstructor()->onlyMethods( [
				'get_current_request',
			] )->getMock();

			$class->method( 'get_current_request' )->willReturn( 'http://example.com/test' );

			$options = $class->maybe_exclude_pageview();

			$this->assertArrayHasKey( 'transformRequest', $options );
		} finally {
			remove_filter( 'plausible_analytics_settings', [ $this, 'setExcludePageviewEdgeCaseAsterisk' ] );
		}

		/**
		 * If a user accidentally entered a space in the option, it shouldn't match anything.
		 */
		try {
			add_filter( 'plausible_analytics_settings', [ $this, 'setExcludePageviewEdgeCaseSpace' ] );

			$class = $this->getMockBuilder( InitOptions::class )->disableOriginalConstructor()->onlyMethods( [
				'get_current_request',
			] )->getMock();

			$class->method( 'get_current_request' )->willReturn( 'http://example.com/test' );

			$options = $class->maybe_exclude_pageview();

			$this->assertArrayNotHasKey( 'transformRequest', $options );
		} finally {
			remove_filter( 'plausible_analytics_settings', [ $this, 'setExcludePageviewEdgeCaseSpace' ] );
		}
	}

	/**
	 * @see InitOptions::maybe_track_logged_in_users()
	 * @return void
	 */
	public function testTrackLoggedInUsers() {
		try {
			/** @var InitOptions $class */
			$class = $this->getMockBuilder( InitOptions::class )->disableOriginalConstructor()->onlyMethods( [] )->getMock();

			add_filter( 'plausible_analytics_settings', [ $this, 'enablePageviewProps' ] );

			$params = $class->maybe_track_logged_in_users();

			$this->assertArrayHasKey( 'customProperties', $params );
			$this->assertArrayHasKey( 'user_logged_in', $params['customProperties'] );

			global $current_user;

			$user         = new \WP_User();
			$user->ID     = 1;
			$user->roles  = [ 'test' ];
			$current_user = $user;

			$params = $class->maybe_track_logged_in_users();

			$this->assertArrayHasKey( 'customProperties', $params );
			$this->assertArrayHasKey( 'user_logged_in', $params['customProperties'] );
			$this->assertEquals( 'test', $params['customProperties']['user_logged_in'] );
		} finally {
			remove_filter( 'plausible_analytics_settings', [ $this, 'enablePageviewProps' ] );
		}
	}
}
