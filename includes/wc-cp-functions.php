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

	return wc_get_product_terms( $product_id, $attribute_name, $args );

}

function is_composite_product() {

	global $product;

	return function_exists( 'is_product' ) && is_product() && ! empty( $product ) && $product->product_type === 'composite' ? true : false;
}
