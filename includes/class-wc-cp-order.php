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

		// Hide composite configuration metadata in order line items
		add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'wc_cp_hide_order_item_meta' ) );
		
		add_action( 'woocommerce_order_again_cart_item_data', array($this, 'wc_cp_order_again_cart_item_data'), 10, 3 );
		
		add_action( 'woocommerce_add_to_cart', array($this, 'wc_cp_order_again' ), 10, 6 );

	}

	/**
	 * Hides composite metadata.
	 *
	 * @param  array $hidden
	 * @return array
	 */
	public function wc_cp_hide_order_item_meta( $hidden ) {
		
		return array_merge( $hidden, array( '_per_product_pricing' ) );
		
	}
	
	public function wc_cp_order_again_cart_item_data( $item_data, $item, $order ) {
		
		$product = $order->get_product_from_item( $item );
		
		if( $product->is_type('composite') ) {
			
			$item_data['wc_cp_order_again_data'] = $product->get_item_variation_data( $item );
			
		}
		
		return $item_data;
		
	}
	
	/**
	 * Order again composite product
	 *
	 * @param  int $order_id
	 */
	public function wc_cp_order_again( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
		
		if( ! empty( $cart_item_data['wc_cp_order_again_data'] ) ) {
			
			$variation = array_merge($variation, $cart_item_data['wc_cp_order_again_data']);
			
			wc()->cart->cart_contents[ $cart_item_key ]['variation'] = $variation;
			
		}
		
	}

}
