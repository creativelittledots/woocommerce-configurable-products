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
		
		add_action( 'woocommerce_ordered_again', array($this, 'wc_cp_order_again') );

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
	
	/**
	 * Order again composite product
	 *
	 * @param  int $order_id
	 */
	public function wc_cp_order_again( $order_id ) {
		
		$order = wc_get_order( $order_id );
		
		// Copy products from the order to the cart
		foreach ( $order->get_items() as $item ) {
			
			// Load all product info including variation data
			$product_id   = (int) apply_filters( 'woocommerce_add_to_cart_product_id', $item['product_id'] );
			
			$product = wc_get_product( $product_id );
			
			if( $product->is_type( 'composite' ) ) { 
			
				$quantity     = (int) $item['qty'];
				$variations   = array();
				$cart_item_data = apply_filters( 'woocommerce_order_again_cart_item_data', array(), $item, $order );
	
				foreach ( $item['item_meta'] as $meta_name => $meta_value ) {
					
					// Skip hidden core fields 
                    if ( in_array( $meta_name, apply_filters( 'woocommerce_hidden_order_itemmeta', array( 
						'_qty',  
						'_tax_class',  
						'_product_id',  
						'_variation_id',  
						'_line_subtotal',  
						'_line_subtotal_tax',  
						'_line_total',  
						'_line_tax',  
						'_line_tax_data'
					) ) ) ) { 
						
                        continue; 
                        
                	} 
					
					$variations[ $meta_name ] = $meta_value[0];
					
				}
				
				$cart_item_data['composite']['price'] = $item['item_meta']['_line_subtotal'][0];
	
				// Add to cart validation
				if ( ! apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variations, $cart_item_data ) ) {
					continue;
				}
	
				WC()->cart->add_to_cart( $product_id, $quantity, null, $variations, $cart_item_data );
				
			}
			
		}
		
	}

}
