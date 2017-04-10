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
		
		add_filter( 'woocommerce_add_cart_item_data', array($this, 'wc_cp_add_cart_item_data'), 10, 3 );
		
		add_filter( 'woocommerce_get_item_data', array($this, 'wc_cp_display_cart_item_data'), 10, 2 );

		// Preserve data in cart
		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'wc_cp_get_cart_data_from_session' ), 10, 2 );
			
	}
	
	public function wc_cp_display_cart_item_data($item_data, $cart_item) {
		
		if ( $cart_item['data']->is_type( 'composite' ) ) {
			
			foreach ( $cart_item['variation'] as $name => $value ) {

                if ( '' === $value )
                    continue;

                $taxonomy = wc_attribute_taxonomy_name( str_replace( 'attribute_pa_', '', urldecode( $name ) ) );

                // If this is a term slug, get the term's nice name
                if ( taxonomy_exists( $taxonomy ) ) {
                    $term = get_term_by( 'slug', $value, $taxonomy );
                    if ( ! is_wp_error( $term ) && $term && $term->name ) {
                        $value = $term->name;
                    }
                    $label = wc_attribute_label( $taxonomy );

                // If this is a custom option slug, get the options name
                } else {
                    $value              = apply_filters( 'woocommerce_variation_option_name', $value );
                    $product_attributes = $cart_item['data']->get_attributes();
                    if ( isset( $product_attributes[ str_replace( 'attribute_', '', $name ) ] ) ) {
                        $label = wc_attribute_label( $product_attributes[ str_replace( 'attribute_', '', $name ) ]['name'] );
                    } else {
                        $label = $name;
                    }
                }

                $item_data[] = array(
                    'key'   => $label,
                    'value' => $value
                );
            }	
			
		}
		
		return $item_data;
		
	}
	
	/**
	 * Modifies cart item data - important for the first calculation of totals only.
	 *
	 * @param  array $cart_item
	 * @return array
	 */
	public function wc_cp_add_cart_item_filter( $cart_item, $cart_item_key, $request = true ) {

		// Get product type
		$product = $cart_item['data'];

		if( $product->is_type('composite') ) {
			
			$cart_item = $this->wc_cp_add_cart_item_data($cart_item, $product->id, $product->variation_id, $request );
			
			$cart_item['data']->set_cart_item_data($cart_item);
			
		}		

		return $cart_item;

	}
	
	public function wc_cp_add_cart_item_data($cart_item, $product_id, $variation_id, $request = true) {
		
		$product = wc_get_product($product_id);
		
		if( $product->is_type('composite') ) {
		
			$cart_item['composite']['price'] = str_replace( ',', '', ! empty( $cart_item['composite']['price'] ) ? $cart_item['composite']['price'] : ( ! empty( $_REQUEST['product_price'] ) && $request ? $_REQUEST['product_price'] : $product->price ) );
			
			$cart_item['composite']['sku'] = str_replace( ',', '', ! empty( $cart_item['composite']['sku'] ) ? $cart_item['composite']['sku'] : ( ! empty( $_REQUEST['product_sku'] ) && $request ? $_REQUEST['product_sku'] : $product->sku ) );
			
			$cart_item['variation']['Weight'] = str_replace( ',', '', ! empty( $cart_item['variation']['Weight'] ) ? $cart_item['variation']['Weight'] : ( ! empty( $_REQUEST['product_weight'] ) && $request ? $_REQUEST['product_weight'] : ( $product->weight . strtoupper( get_option('woocommerce_weight_unit' ) ) ) ) );
			
			$cart_item['composite']['weight'] = str_replace( ',', '', ! empty( $cart_item['composite']['weight'] ) && abs($cart_item['composite']['weight']) ? $cart_item['composite']['weight'] : ( ! empty( $_REQUEST['product_weight_clean'] ) && $request ? $_REQUEST['product_weight_clean'] : str_replace( strtoupper( get_option('woocommerce_weight_unit' ) ), '', $cart_item['variation']['Weight'] ) ) );
			
			$cart_item['variation']['SKU'] = ! empty( $cart_item['variation']['SKU'] ) ? $cart_item['variation']['SKU'] : $cart_item['composite']['sku'];
			
			if( $request && ! empty( $_REQUEST['selections'] ) && is_array( $_REQUEST['selections'] ) ) {
				
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

	/**
	 * Load all composite-related session data.
	 *
	 * @param  array 	$cart_item
	 * @param  array 	$item_session_values
	 * @return array	$cart_item
	 */
	public function wc_cp_get_cart_data_from_session( $cart_item, $item_session_values ) {
		
		$cart_item = $this->wc_cp_add_cart_item_filter( $cart_item, null, false );

		return $cart_item;
	}

}
