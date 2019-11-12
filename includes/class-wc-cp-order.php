<?php
/**
 * Composite order filters and functions.
 *
 * @class 	WC_CP_Order
 * @version 3.0.6
 * @since   2.2.2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_CP_Order {

	public function __construct() {

		// Hide configurable configuration metadata in order line items
		add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'wc_cp_hide_order_item_meta' ) );
		
		add_filter( 'woocommerce_order_again_cart_item_data', array($this, 'wc_cp_order_again_cart_item'), 10, 3 );
		
		add_action( 'woocommerce_add_order_again_cart_item', array($this, 'wc_cp_order_again' ) );

	}

	/**
	 * Hides configurable metadata.
	 *
	 * @param  array $hidden
	 * @return array
	 */
	public function wc_cp_hide_order_item_meta( $hidden ) {
		
		return array_merge( $hidden, array( '_per_product_pricing' ) );
		
	}
	
	public function wc_cp_order_again_cart_item( $item_data, $item, $order ) {
		
		$product = $order->get_product_from_item( $item );
		
		if( $product->is_type('configurable') ) {
			
			$item_data['wc_cp_order_again_data'] = $product->get_item_variation_data( $item );
			$item_data['wc_cp_order_again_price'] = $item->get_total();
			
		}
		
		return $item_data;
		
	}
	
	/**
	 * Order again configurable product
	 *
	 * @param  int $order_id
	 */
	public function wc_cp_order_again( $cart_item_data ) {
		
		if( ! empty( $cart_item_data['wc_cp_order_again_data'] ) ) {
			
			$cart_item_data['variation'] = array_merge($cart_item_data['variation'], $cart_item_data['wc_cp_order_again_data']);
			
			add_filter('wc_cp_set_explicit_price', '__return_true'); 
			$cart_item_data['data']->set_price($cart_item_data['wc_cp_order_again_price']);
			remove_filter('wc_cp_set_explicit_price', '__return_true'); 
			
		}
		
		return $cart_item_data;
		
	}

}
