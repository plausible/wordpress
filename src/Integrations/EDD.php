<?php
/**
 * Plausible Analytics | Integrations | EDD.
 *
 * @since      2.1.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Integrations;

use Plausible\Analytics\WP\Integrations;
use Plausible\Analytics\WP\Proxy;

/**
 * @codeCoverageIgnore Because all we'd be testing is the (external) API.
 */
class EDD {
	public $event_goals = [];

	/**
	 * Build class.
	 *
	 * @param $init
	 */
	public function __construct( $init = true ) {
		$uri = defined( 'EDD_SLUG' ) ? EDD_SLUG : 'downloads';

		if ( is_multisite() ) {
			$uri = get_blog_details()->path . $uri;
		} else {
			$uri = '/' . $uri;
		}

		$this->event_goals = [
			'view-product'     => sprintf( __( 'Visit %s*', 'plausible-analytics' ), $uri ),
			'add-to-cart'      => __( 'EDD Add to Cart', 'plausible-analytics' ),
			'remove-from-cart' => __( 'EDD Remove from Cart', 'plausible-analytics' ),
			'checkout'         => __( 'EDD Start Checkout', 'plausible-analytics' ),
			'purchase'         => __( 'EDD Complete Purchase', 'plausible-analytics' ),
		];

		$this->init( $init );
	}

	/**
	 * Action & filter hooks.
	 *
	 * @param $init
	 *
	 * @return void
	 */
	private function init( $init ) {
		if ( ! $init ) {
			return;
		}

		add_action( 'edd_post_add_to_cart', [ $this, 'track_add_to_cart' ], 10, 3 );
		add_action( 'edd_pre_remove_from_cart', [ $this, 'track_remove_cart_item' ], 10 );
		add_action( 'edd_before_purchase_form', [ $this, 'track_entered_checkout' ] );
		add_action( 'wp_head', [ $this, 'track_purchase' ] );
	}

	/**
	 * Tracks the "add to cart" event with relevant product and cart data.
	 *
	 * @param int   $download_id The ID of the product being added to the cart.
	 * @param array $options     Optional data associated with the product being added.
	 * @param array $items       The current items in the cart.
	 *
	 * @return void
	 */
	public function track_add_to_cart( $download_id, $options, $items ) {
		$download = new \EDD_Download( $download_id );

		if ( $download->ID === 0 ) {
			return;
		}

		$quantity = array_filter(
			$items,
			function ( $item ) use ( $download_id ) {
				return $item[ 'id' ] === $download_id;
			}
		);
		$quantity = reset( $quantity )[ 'quantity' ] ?? 1;

		$props = apply_filters(
			'plausible_analytics_edd_add_to_cart_custom_properties',
			[
				'product_name'     => edd_get_download_name( $download_id ),
				'product_id'       => $download_id,
				'quantity'         => $quantity,
				'price'            => edd_get_download_price( $download_id ),
				'tax_class'        => edd_get_cart_tax_rate(),
				'cart_total_items' => edd_get_cart_quantity(),
				'cart_total'       => edd_get_cart_total(),
			]
		);

		$proxy = new Proxy( false );

		$proxy->do_request( $this->event_goals[ 'add-to-cart' ], null, null, $props );
	}

	/**
	 * Tracks the removal of an item from the cart, updates cart contents and triggers an event to log this action.
	 *
	 * @param string|int $key The key of the item in the cart to be removed.
	 *
	 * @return void
	 */
	public function track_remove_cart_item( $key ) {
		$cart_contents          = edd_get_cart_contents();
		$item_removed_from_cart = $cart_contents[ $key ] ?? [];
		$product                = null;

		if ( empty( $item_removed_from_cart ) ) {
			return;
		}

		unset( $cart_contents[ $key ] );

		if ( isset( $item_removed_from_cart[ 'id' ] ) ) {
			$product = new \EDD_Download( $item_removed_from_cart[ 'id' ] );
		}

		if ( ! $product ) {
			return;
		}

		$total_removed_from_cart = edd_get_cart_total() - ( $product->get_price() * $item_removed_from_cart[ 'quantity' ] );

		$props = apply_filters(
			'plausible_analytics_edd_remove_cart_item_custom_properties',
			[
				'product_name'     => $product->get_name(),
				'product_id'       => $item_removed_from_cart[ 'id' ],
				'quantity'         => $item_removed_from_cart[ 'quantity' ],
				'cart_total_items' => count( $cart_contents ),
				'cart_total'       => $total_removed_from_cart,
			]
		);

		$proxy = new Proxy( false );

		$proxy->do_request( $this->event_goals[ 'remove-from-cart' ], null, null, $props );
	}

	/**
	 * Tracks the "entered checkout" event with relevant cart data.
	 *
	 * @return void
	 */
	public function track_entered_checkout() {
		// Just to make sure we're where we're supposed to be.
		if ( ! edd_is_checkout() ) {
			return;
		}

		$props = apply_filters(
			'plausible_analytics_edd_entered_checkout_custom_properties',
			[
				// @todo Add cart contents
				'subtotal' => edd_get_cart_subtotal(),
				'tax'      => edd_get_cart_tax(),
				'total'    => edd_get_cart_total(),
			]
		);

		$proxy = new Proxy( false );

		$proxy->do_request( $this->event_goals[ 'checkout' ], null, null, $props );
	}

	/**
	 * Tracks the "purchase" event with relevant payment data.
	 *
	 * We choose to render a JS script, instead of hooking into an action, because user-agent information
	 * gets lost when the user is first redirected to a Payment Provider.
	 *
	 * @return void
	 */
	public function track_purchase() {
		if ( ! edd_is_success_page() ) {
			return; // @codeCoverageIgnore
		}

		$session = edd_get_purchase_session();
		$order   = null;

		if ( ! empty( $session[ 'purchase_key' ] ) ) {
			$order = edd_get_order_by( 'payment_key', $session[ 'purchase_key' ] );
		}

		// Don't track on page reload.
		if ( ! $order || edd_get_order_meta( $order->id, Integrations::PURCHASE_TRACKED_META_KEY, true ) ) {
			return;
		}

		$props = wp_json_encode(
			apply_filters(
				'plausible_analytics_edd_purchase_custom_properties',
				[
					'revenue' => [
						'amount'   => $order->total,
						'currency' => $order->currency,
					],
				]
			)
		);
		$label = $this->event_goals[ 'purchase' ];

		echo sprintf( Integrations::SCRIPT_WRAPPER, "window.plausible( '$label', $props )" );

		edd_add_order_meta( $order->id, Integrations::PURCHASE_TRACKED_META_KEY, true );
	}
}
