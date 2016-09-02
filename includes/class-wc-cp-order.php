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
