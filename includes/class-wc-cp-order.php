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

		// Composite containers should not affect order status
		add_filter( 'woocommerce_order_item_needs_processing', array( $this, 'wc_cp_container_items_need_no_processing' ), 10, 3 );

		// Hide composite configuration metadata in order line items
		add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'wc_cp_hide_order_item_meta' ) );

	}

	/**
	 * Composite Containers should not affect order status - let it be decided by composited items only.
	 *
	 * @param  bool 		$is_needed
	 * @param  WC_Product 	$product
	 * @param  int 			$order_id
	 * @return bool
	 */
	function wc_cp_container_items_need_no_processing( $is_needed, $product, $order_id ) {

		if ( $product->is_type( 'composite' ) )
			return false;

		return $is_needed;
	}

	/**
	 * Hides composite metadata.
	 *
	 * @param  array $hidden
	 * @return array
	 */
	function wc_cp_hide_order_item_meta( $hidden ) {
		
		return array_merge( $hidden, array( '_per_product_pricing' ) );
		
	}

}
