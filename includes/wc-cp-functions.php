<?php
/**
 * Configurable Products compatibility functions and conditional functions.
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

function is_configurable_product() {

	global $product;

	return function_exists( 'is_product' ) && is_product() && ! empty( $product ) && $product->get_type() === 'configurable' ? true : false;
}

function wc_cp_get_component( $the_component, $associations = false ) {
	
	try {
		
		if ( is_numeric( $the_component ) || ( is_object( $the_component ) || is_array( $the_component ) ) ) {
			
			$component = new WC_CP_Component( $the_component, $associations );
			
			if( ! $component->id ) {
				
				throw new Exception( 'Component could not be found', 422 );
				
			}
			
		} else {
			
			throw new Exception( 'Invalid component data', 422 );
			
		}

		return $component;

	} catch ( Exception $e ) {
		
		return false;
		
	}
	
}

function wc_cp_get_scenario( $the_scenario = false, $associations = false ) {
	
	try {
		
		if ( is_numeric( $the_scenario ) || ( is_object( $the_scenario ) || is_array( $the_scenario ) ) ) {
			
			$scenario = new WC_CP_Scenario( $the_scenario, $associations );
			
			if( ! $scenario->id ) {
				
				throw new Exception( 'Scenario could not be found', 422 );
				
			}
			
		} else {
			
			throw new Exception( 'Invalid scenario data', 422 );
			
		}

		return $scenario;

	} catch ( Exception $e ) {
		
		return false;
		
	}
	
}

