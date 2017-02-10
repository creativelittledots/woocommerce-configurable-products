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

/**
 * Get ther without khowing it's taxonomy. Not very nice, though.
 * 
 * @uses type $wpdb
 * @uses get_term()
 * @param int|object $term
 * @param string $output
 * @param string $filter
 */
function get_term_by_id($term, $output = OBJECT, $filter = 'raw') {
    global $wpdb;
    $null = null;

    if ( empty($term) ) {
        $error = new WP_Error('invalid_term', __('Empty Term'));
        return $error;
    }

    $_tax = $wpdb->get_row( $wpdb->prepare( "SELECT t.* FROM $wpdb->term_taxonomy AS t WHERE t.term_id = %s LIMIT 1", $term) );
    $taxonomy = $_tax->taxonomy;

    return get_term($term, $taxonomy, $output, $filter);

}
