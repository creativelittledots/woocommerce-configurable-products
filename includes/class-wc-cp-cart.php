<?php
/**
 * Composite cart filters and functions.
 * @class 	WC_CP_Cart
 * @version 3.1.0
 * @since  2.2.2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_CP_Cart {

	public function __construct() {

		// Modify cart item data for composite products
		add_filter( 'woocommerce_add_cart_item', array( $this, 'wc_cp_add_cart_item_filter' ), 10, 2 );

		// Preserve data in cart
		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'wc_cp_get_cart_data_from_session' ), 10, 2 );
		
		add_filter( 'woocommerce_composite_get_price', array($this, 'wc_cp_get_price'), 10, 2 );
		
		add_filter( 'woocommerce_composite_product_weight', array($this, 'wc_cp_get_weight'), 10, 2 );
			
	}

	/**
	 * Modifies cart item data - important for the first calculation of totals only.
	 *
	 * @param  array $cart_item
	 * @param  string $cart_item_key
	 * @return array
	 */
	public function wc_cp_add_cart_item_filter( $cart_item, $cart_item_key ) {

		// Get product type
		$product = $cart_item['data'];

		if( $product->is_type('composite') ) {
			
			$cart_item['data']->variation_id = 999;
			
			$cart_item['composite']['price'] = str_replace( ',', '', ! empty( $cart_item['composite']['price'] ) ? $cart_item['composite']['price'] : ( ! empty( $_REQUEST['product_price'] ) ? $_REQUEST['product_price'] : $item['data']->price ) );
			
			$cart_item['variation']['Weight'] = str_replace( ',', '', ! empty( $cart_item['variation']['Weight'] ) ? $cart_item['variation']['Weight'] : ( ! empty( $_REQUEST['product_weight'] ) ? $_REQUEST['product_weight'] : $item['data']->weight ) );
			$cart_item['variation']['SKU'] = ! empty( $cart_item['variation']['SKU'] ) ? $cart_item['variation']['SKU'] : ( ! empty( $_REQUEST['product_sku'] ) ? $_REQUEST['product_sku'] : '' );
			
			if( ! empty( $_REQUEST['selections'] ) && is_array( $_REQUEST['selections'] ) ) {
				
				foreach($_REQUEST['selections'] as $selections) {
					
					foreach($selections as $i => $selection) {
						
						$key = $selection['title'];
						
						$key .= count($selections) > 1 ? ' #' . ($i+1) : '';
						
						$cart_item['variation'][ $key ] = $selection['selected'];
						
					}
					
				}
				
			}
			
		}		

		return $cart_item;

	}
	
	public function wc_cp_get_price($price, $product) {
		
		foreach(WC()->cart->cart_contents as $item) {
		
			if( $product->id == $item['data']->id ) {
				
				$price = ! empty( $item['composite']['price'] ) ? $item['composite']['price'] : $price;
				
			}
			
		}
		
		return $price;
		
	}
	
	public function wc_cp_get_weight($weight, $product) {
		
		foreach(WC()->cart->cart_contents as $item) {
		
			if( $product->id == $item['data']->id ) {
				
				return $weight = ! empty( $item['variation']['Weight'] ) ? $item['variation']['Weight'] : $weight;
				
			}
			
		}
		
		return $weight;
		
	}

	/**
	 * Load all composite-related session data.
	 *
	 * @param  array 	$cart_item
	 * @param  array 	$item_session_values
	 * @return array	$cart_item
	 */
	public function wc_cp_get_cart_data_from_session( $cart_item, $item_session_values ) {
		
		$cart_item = $this->wc_cp_add_cart_item_filter( $cart_item, null );

		return $cart_item;
	}

}
