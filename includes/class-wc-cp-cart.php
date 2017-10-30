<?php
/**
 * Configurable cart filters and functions.
 * @class 	WC_CP_Cart
 * @version 3.1.0
 * @since  2.2.2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_CP_Cart {

	public function __construct() {

		// Modify cart item data for configurable products
		add_filter( 'woocommerce_add_cart_item', array( $this, 'wc_cp_add_cart_item_filter' ), 10, 2 );
		
		add_filter( 'woocommerce_add_cart_item_data', array($this, 'wc_cp_add_cart_item_data'), 10, 3 );

		// Preserve data in cart
		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'wc_cp_get_cart_data_from_session' ), 10, 2 );
		
		add_filter( 'woocommerce_get_item_data', array( $this, 'wc_cp_get_item_data' ), 10, 2 );
			
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

		if( $product->is_type('configurable') ) {
			
			$cart_item = $this->wc_cp_add_cart_item_data($cart_item, $product->get_id(), $request );
			
			$cart_item['data']->set_cart_item_data($cart_item);
			
		}		

		return $cart_item;

	}
	
	public function wc_cp_add_cart_item_data($cart_item, $product_id, $request = true) {
		
		$product = wc_get_product($product_id);
		
		if( $product->is_type('configurable') ) {
			
			$cart_item['configurable'] = array_merge(array(
				'product_id' => $product_id,
				'quantity' => $cart_item['quantity'],
				'price_incl_tax' => wc_get_price_including_tax( $product ),
				'price_excl_tax' => wc_get_price_excluding_tax( $product ),
				'product_sku' => $product->get_sku(),
				'display_weight' => $product->get_weight() . strtoupper( get_option('woocommerce_weight_unit' ) ),
				'weight' => $product->get_weight(),
				'selections' => []
			), ! empty( $cart_item['configurable'] ) ? $cart_item['configurable'] : [], $_REQUEST);
			
			$cart_item['variation'] = [];
			
			$cart_item['variation']['Weight'] = $cart_item['configurable']['display_weight'];
			$cart_item['variation']['SKU'] = $cart_item['configurable']['product_sku'];
				
			foreach($cart_item['configurable']['selections'] as $selection) {
				
				$key = $selection['title'];
				$i = 1;
				
				while( array_key_exists( $key, $cart_item['variation'] ) ) {
					
					$i++;
					
					$key = $selection['title'] . ( $i ? ' ' . $i : '' );
					
				}
				
				$cart_item['variation'][ $key ] = $selection['selected'];
				
			}
			
		}
		
		return $cart_item;
		
	}

	/**
	 * Load all configurable-related session data.
	 *
	 * @param  array 	$cart_item
	 * @param  array 	$item_session_values
	 * @return array	$cart_item
	 */
	public function wc_cp_get_cart_data_from_session( $cart_item, $item_session_values ) {
		
		$cart_item = $this->wc_cp_add_cart_item_filter( $cart_item, null, false );

		return $cart_item;
	}
	
	/**
	 * Load all configurable-related data to cart variation data
	 *
	 * @param  array 	$item_data
	 * @param  array 	cart_item
	 * @return array	item_data
	 */
	public function wc_cp_get_item_data( $item_data, $cart_item ) {
		
		if ( $cart_item['data']->is_type( 'configurable' ) && is_array( $cart_item['variation'] ) ) {
			foreach ( $cart_item['variation'] as $name => $value ) {
				$taxonomy = wc_attribute_taxonomy_name( str_replace( 'attribute_pa_', '', urldecode( $name ) ) );

				// If this is a term slug, get the term's nice name
				if ( taxonomy_exists( $taxonomy ) ) {
					$term = get_term_by( 'slug', $value, $taxonomy );
					if ( ! is_wp_error( $term ) && $term && $term->name ) {
						$value = $term->name;
					}
					$label = wc_attribute_label( $taxonomy );

				// If this is a custom option slug, get the options name.
				} else {
					$value = apply_filters( 'woocommerce_variation_option_name', $value );
					$label = wc_attribute_label( str_replace( 'attribute_', '', $name ), $cart_item['data'] );
				}

				// Check the nicename against the title.
				if ( '' === $value || wc_is_attribute_in_product_name( $value, $cart_item['data']->get_name() ) ) {
					continue;
				}

				$item_data[] = array(
					'key'   => $label,
					'value' => $value,
				);
			}
		}
		
		return $item_data;
		
	}

}
