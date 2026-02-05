<?php
/**
 * @package Plausible Analytics Integration Tests - Integrations
 */

namespace Plausible\Analytics\Tests\Integration;

use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\Integrations;
use function Brain\Monkey\Functions\when;

class IntegrationsTest extends TestCase {
	public function testInit() {
		try {
		add_filter( 'plausible_analytics_integrations_woocommerce', '__return_true' );
		add_filter( 'plausible_analytics_integrations_edd', '__return_true' );
		add_filter( 'plausible_analytics_integrations_form_submit', '__return_true' );

		when( 'wc_get_permalink_structure' )->justReturn( [ 'product_base' => 'product' ] );

		new Integrations();

		$this->assertTrue( class_exists( '\Plausible\Analytics\WP\Integrations\WooCommerce' ) );
		$this->assertTrue( class_exists( '\Plausible\Analytics\WP\Integrations\EDD' ) );
		$this->assertTrue( class_exists( '\Plausible\Analytics\WP\Integrations\FormSubmit' ) );
		} finally {
			remove_filter( 'plausible_analytics_integrations_woocommerce', '__return_true' );
			remove_filter( 'plausible_analytics_integrations_edd', '__return_true' );
			remove_filter( 'plausible_analytics_integrations_form_submit', '__return_true' );
		}
	}

	/**
	 * Tests whether the WooCommerce integration is currently active.
	 */
	public function testIsWcActive() {
		try {
			add_filter( 'plausible_analytics_integrations_woocommerce', '__return_true' );

			// WC is already mocked.
			$this->assertTrue( Integrations::is_wc_active() );
		} finally {
			remove_filter( 'plausible_analytics_integrations_woocommerce', '__return_true' );
		}
	}

	/**
	 * Tests if the Easy Digital Downloads (EDD) integration is active.
	 */
	public function testIsEddActive() {
		try {
			add_filter( 'plausible_analytics_integrations_edd', '__return_true' );

			$this->assertTrue( Integrations::is_edd_active() );
		} finally {
			remove_filter( 'plausible_analytics_integrations_edd', '__return_true' );
		}
	}
}
