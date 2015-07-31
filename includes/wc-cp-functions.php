<?php
/**
 * Composite Products compatibility functions and conditional functions.
 *
 * @version 3.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

function wc_composite_get_template( $file, $data, $empty, $path ) {

	return wc_get_template( $file, $data, $empty, $path );
}

function wc_composite_get_product_terms( $product_id, $attribute_name, $args ) {

	if ( WC_CP_Core_Compatibility::is_wc_version_gte_2_3() ) {

		return wc_get_product_terms( $product_id, $attribute_name, $args );

	} else {

		$orderby = wc_attribute_orderby( sanitize_title( $attribute_name ) );

		switch ( $orderby ) {
			case 'name' :
				$args = array( 'orderby' => 'name', 'hide_empty' => false, 'menu_order' => false );
			break;
			case 'id' :
				$args = array( 'orderby' => 'id', 'order' => 'ASC', 'menu_order' => false );
			break;
			case 'menu_order' :
				$args = array( 'menu_order' => 'ASC' );
			break;
		}

		$terms = get_terms( sanitize_title( $attribute_name ), $args );

		return $terms;
	}
}

function is_composite_product() {

	global $product;

	return function_exists( 'is_product' ) && is_product() && ! empty( $product ) && $product->product_type === 'composite' ? true : false;
}
