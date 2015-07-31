<?php
/**
 * Composited Product Quantity.
 *
 * @version  3.0.5
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

ob_start();

woocommerce_quantity_input( array(
	'input_name'  => 'wccp_component_quantity[' . $component_id . ']',
	'min_value'   => $quantity_min,
	'max_value'   => $quantity_max,
	'input_value' => isset( $_POST[ 'wccp_component_quantity' ][ $component_id ] ) ? $_POST[ 'wccp_component_quantity' ][ $component_id ] : apply_filters( 'woocommerce_composited_product_quantity', max( $quantity_min, 1 ), $quantity_min, $quantity_max, $product, $component_id, $composite_product )
), $product );

$quantity_input = ob_get_clean();

if ( $quantity_min == $quantity_max ) {
	echo str_replace( 'class="quantity"', 'class="quantity quantity_hidden"', $quantity_input );
} else {
 	echo $quantity_input;
}
