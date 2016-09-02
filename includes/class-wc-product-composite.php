<?php
/**
 * Composite Product Class
 *
 * @class 	WC_Product_Composite
 * @version 3.1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_Product_Composite extends WC_Product {

	private $composite_data = array();

	public $per_product_pricing;
	
	private $selections_style;

	private $composited_products = array();

	private $component_options               = array();

	public $min_price;

	public $base_price;
	public $base_regular_price;

	private $price_meta;

	public $min_composite_price;
	public $min_composite_regular_price;
	public $min_composite_price_incl_tax;
	public $min_composite_price_excl_tax;

	public function __construct( $product ) {

		$this->product_type = 'composite';

		parent::__construct( $product );

		$this->composite_data       = get_post_meta( $this->id, '_bto_data', true );
		$this->per_product_pricing  = get_post_meta( $this->id, '_per_product_pricing_bto', true );

		$this->selections_style     = get_post_meta( $this->id, '_bto_selection_mode', true );

		$this->min_price            = get_post_meta( $this->id, '_min_composite_price', true );

		$this->price_meta           = (double) get_post_meta( $this->id, '_price', true );

		$base_price                 = get_post_meta( $this->id, '_base_price', true );
		$base_regular_price         = get_post_meta( $this->id, '_base_regular_price', true );
		$base_sale_price            = get_post_meta( $this->id, '_base_sale_price', true );

		$this->base_price           = empty( $base_price ) ? 0.0 : (double) $base_price;
		$this->base_regular_price   = empty( $base_regular_price ) ? 0.0 : (double) $base_regular_price;
		$this->base_sale_price      = empty( $base_sale_price ) ? 0.0 : (double) $base_sale_price;
		$this->base_weight 			= $this->weight;

		if ( $this->is_priced_per_product() ) {
			$this->price = $this->get_base_price();
		}
	}

	/**
	 * Overrides get_price to return base price in static price mode.
	 * In per-product pricing mode, get_price() returns the base composite price.
	 *
	 * @return double
	 */
	public function get_price() {
		
		return (float) apply_filters( 'woocommerce_composite_get_price', $this->get_raw_price(), $this );
		
	}
	
	public function get_base_weight() {
		
		return apply_filters( 'woocommerce_composite_product_base_weight', apply_filters( 'woocommerce_composite_product_get_base_weight', $this->base_weight ? $this->base_weight : '' ), $this );
		
	}
	
	public function get_weight() {
		
		return apply_filters( 'woocommerce_composite_product_weight', apply_filters( 'woocommerce_composite_product_get_weight', $this->weight ? $this->weight : '' ), $this );
		
	}
	
	/**
	 * Overrides get_price to return base price in static price mode.
	 * In per-product pricing mode, get_price() returns the base composite price.
	 *
	 * @return double
	 */
	public function get_raw_price() {

		if ( $this->is_priced_per_product() ) {
			
			return $this->get_base_price();
			
		} else {
			
			return parent::get_price();
			
		}
		
	}

	/**
	 * Overrides get_regular_price to return base price in static price mode.
	 *
	 * @return double
	 */
	public function get_regular_price() {

		return (float) apply_filters( 'woocommerce_composite_get_regular_price', $this->get_raw_regular_price(), $this );
		
	}
	
	/**
	 * Overrides get_regular_price to return base price in static price mode.
	 *
	 * @return double
	 */
	public function get_raw_regular_price() {

		if ( $this->is_priced_per_product() ) {
    		
    		return $this->get_base_regular_price();
			
		} else {
			
			return parent::get_regular_price();
			
		}
		
	}

	/**
	 * Get composite base price.
	 *
	 * @return double
	 */
	public function get_base_price() {

		if ( $this->is_priced_per_product() ) {
			
			return (float) apply_filters( 'woocommerce_composite_get_base_price', get_query_var( 'wc_query' ) == 'product_query' ? max($this->min_price, $this->base_price) : $this->base_price, $this );
			
		} else {
			
			return false;
			
		}
	}
	
	/**
	 * Get composite base price including tax.
	 *
	 * @return double
	 */
	public function get_base_price_including_tax() {

        return $this->get_price_including_tax( 1, $this->get_base_price() );
		
	}

	/**
	 * Get composite base regular price.
	 *
	 * @return double
	 */
	public function get_base_regular_price() {

		if ( $this->is_priced_per_product() ) {
			
			return (float) apply_filters( 'woocommerce_composite_get_base_regular_price', get_query_var( 'wc_query' ) == 'product_query' ? max($this->min_price, $this->base_regular_price) : $this->base_regular_price, $this );
			
		} else {
			
			return false;
			
		}
	}
	
	/**
	 * Get composite base price.
	 *
	 * @return double
	 */
	public function get_build_sku() {

		return apply_filters( 'woocommerce_composite_get_build_sku', $this->bto_build_sku ? true : false, $this );
		
	}
	
	/**
	 * Get composite base sku.
	 *
	 * @return string
	 */
	public function get_base_sku() {

		return apply_filters( 'woocommerce_composite_get_base_sku', $this->bto_sku_start, $this );
		
	}
	
	/**
	 * True if the composite is in sync with its contents.
	 *
	 * @return boolean
	 */
	public function is_synced() {

		return $this->is_synced;
	}
	
	/**
	 * Calculates min prices based on the composited product data.
	 *
	 * @return void
	 */
	public function sync_composite() {

		global $woocommerce_composite_products;

		if ( empty( $this->composite_data ) )
			return false;

		// Initialize min price information
		$this->min_composite_price = $this->min_composite_regular_price = '';

		// Initialize component options
		foreach ( $this->get_composite_data() as $component_id => $component_data ) {

			$this->composited_products[ $component_id ] = array();

			// Do not pass any ordering args to speed up the query - ordering and filtering is done when calling get_current_component_options()
			$this->component_options[ $component_id ]   = $woocommerce_composite_products->api->get_component_options( $component_data );
		}

		if ( $this->is_priced_per_product() ) {

			foreach ( $this->get_composite_data() as $component_id => $component_data ) {

				$item_price = '';

				$item_regular_price = '';
				
				$item_price_incl = '';
				
				$item_price_excl = '';

				// No options available
				if ( empty( $component_data['options'] ) || $component_data['optional'] )
					continue;

				foreach ( $component_data['options'] as $option ) {

					// Update component prices
					$item_price = $item_price !== '' ? min( $item_price, $option['price'] ) : $option['price'];

					$item_regular_price = $item_regular_price !== '' ? min( $item_regular_price, $option['regular_price'] ) : $option['regular_price'];

					// Price incl tax
					$item_price_incl = $item_price_incl !== '' ? min( $item_price_incl, $option['price_incl_tax'] ) : $option['price_incl_tax'];
					
					// Price excl tax
					$item_price_excl = $item_price_excl !== '' ? min( $item_price_excl, $option['price_excl_tax'] ) : $option['price_excl_tax'];
					
				}

				$this->min_composite_price          = $this->min_composite_price + $item_price;
				$this->min_composite_regular_price  = $this->min_composite_regular_price + $item_regular_price;
				$this->min_composite_price_incl_tax = $this->min_composite_price_incl_tax + $item_price_incl;
				$this->min_composite_price_excl_tax = $this->min_composite_price_excl_tax + $item_price_excl;
				
			}
			
			$composite_price = $this->get_raw_price();
			$composite_regular_price = $this->get_raw_regular_price();
			$composite_price_incl = $this->get_price_including_tax( 1, $composite_price );
			$composite_price_excl = $this->get_price_excluding_tax( 1, $composite_price );

			$this->min_composite_price          = $composite_price + $this->min_composite_price;
			$this->min_composite_regular_price  = $composite_regular_price + $this->min_composite_regular_price;
			$this->min_composite_price_incl_tax = $composite_price_incl + $this->min_composite_price_incl_tax;
			$this->min_composite_price_excl_tax = $composite_price_excl + $this->min_composite_price_excl_tax;

			if ( $this->price_meta != $this->min_composite_price && ! is_admin() ) {
				update_post_meta( $this->id, '_price', $this->min_composite_price );
			}

		} else {

			$this->min_composite_price = $this->get_raw_price();
			$this->min_composite_regular_price = $this->get_raw_regular_price();
			$this->min_composite_price_incl_tax = $this->get_price_including_tax( 1, $this->min_composite_price );
			$this->min_composite_price_excl_tax = $this->get_price_excluding_tax( 1, $this->min_composite_price );

		}

		// Use these filters if get_hide_price_html() returns false but you still want to include the composite in price filter widget min results
		$this->min_composite_price = apply_filters( 'woocommerce_min_composite_price_meta', $this->min_composite_price, $this );

		// Save modified min price meta to include product in price filter widget results
		if ( $this->min_price != $this->min_composite_price ) {
			update_post_meta( $this->id, '_min_composite_price', $this->min_composite_price );
		}

		$this->is_synced = true;
		
	}
	
	public function get_min_price() {
    	
    	if ( ! $this->is_synced() )
			$this->sync_composite();
    	
    	return $this->min_composite_price;
    	
	}
	
	public function get_min_price_including_tax() {
    	
    	if ( ! $this->is_synced() )
			$this->sync_composite();
    	
    	return $this->min_composite_price_incl_tax;
    	
	}

	/**
	 * Returns range style html price string without min.
	 *
	 * @param  mixed    $price    default price
	 * @return string             overridden html price string (old style)
	 */
	public function get_price_html( $price = '' ) {

		global $woocommerce_composite_products;
		
		if ( ! $this->is_synced() )
			$this->sync_composite();

		if ( $this->is_priced_per_product() ) {

			// Get the price
			if ( $this->min_composite_price === '' ) {

				$price = apply_filters( 'woocommerce_composite_empty_price_html', wc_price( 
				$this->min_composite_price, 
				array(
					'price_format' => '%1$s<span rv-text="product:price">%2$s</span>'
				) ) . $this->get_price_suffix(), $this );

			} else {

				$price = apply_filters( 'woocommerce_composite_price_html', $this->get_price_html_from_text() . wc_price( 
				$this->min_composite_price, 
				array(
					'price_format' => '%1$s<span rv-text="product:price">%2$s</span>'
				) ) . 
				$this->get_price_suffix(), $this );

			}

			return apply_filters( 'woocommerce_get_price_html', $price, $this );

		} else {

			return parent::get_price_html();
		}

	}
	
	/**
     * Functions for getting parts of a price, in html, used by get_price_html.
     *
     * @return string
     */
    public function get_price_html_from_text() {
	    
        return '<span class="from" rv-show="product:errors">' . _x( 'From:', 'min_price', 'woocommerce' ) . ' </span>';
        
    }

	/**
	 * Component configuration array passed through 'woocommerce_composite_component_data' filter.
	 *
	 * @return array
	 */
	public function get_composite_data() {
		
		global $woocommerce_composite_products;

		if ( empty( $this->composite_data ) ) {
			return false;
		}

		$composite_data = array();
		
		$composite_scenario_meta = get_post_meta( $this->id, '_bto_scenario_data', true );
		$composite_scenario_meta = apply_filters( 'woocommerce_composite_scenario_meta', $composite_scenario_meta, $this );

		foreach ( $this->composite_data as $component_id => $component_data ) {
			
			$options = array();
			
			foreach($component_data['assigned_ids'] as $product_id) {
				
				if( $product = wc_get_product($product_id) ) {
					
					$terms        = get_the_terms( $product_id, 'product_type' );
					$product_type = ! empty( $terms ) && isset( current( $terms )->name ) ? sanitize_title( current( $terms )->name ) : 'simple';
                    
                    $price = (float) ! empty( $component_data['price_options'][$product_id] ) ? $component_data['price_options'][$product_id] : $product->get_price();
				
					$options[] = array(
						'id' => $product_id,
						'title' => $product->get_title(),
						'price' => $price,
						'regular_price' => (float) ! empty( $component_data['price_options'][$product_id] ) ? $price : $product->get_regular_price(),
						'price_incl_tax' => (float) $product->get_price_including_tax( 1, $price ),
						'price_excl_tax' => (float) $product->get_price_excluding_tax( 1, $price ),
						'sku' => ! empty( $component_data['sku_options'][$product_id] ) ? $component_data['sku_options'][$product_id] : $product->get_sku(),
						'weight' => (float) $product->get_weight(),
						'scenarios' => $woocommerce_composite_products->api->get_scenarios_for_product( $composite_scenario_meta, $component_id, $product_id, '', $product_type ),
					);
					
				}
				
			}
			
			$composite_data[] = apply_filters( 'woocommerce_composite_component_data', [
				'id' => $component_data['component_id'],
				'style' => ! empty( $component_data['option_style'] ) ? $component_data['option_style'] : $this->selections_style,
				'title' => $component_data['title'],
				'description' => htmlspecialchars_decode($component_data['description']),
				'optional' => $component_data['optional'] === 'yes' ? true : false,
				'default_id' => $component_data['default_id'] ? $component_data['default_id'] : 0,
				'recommended_id' => ! empty( $component_data['recommended_id'] ) ? $component_data['recommended_id'] : 0,
				'affect_sku' => $component_data['affect_sku'],
				'sku_order' => $component_data['affect_sku_order'],
				'sku_default' => $component_data['affect_sku_default'],
				'options' => $options,
				'assigned_ids' => $component_data['assigned_ids'],
				'use_tag_numbers' => $component_data['tag_numbers'] === 'yes' ? true : false,
				'sovereign' => $component_data['sovereign'] === 'yes' ? true : false
			], $component_id, $this );
			
		}

		return array_values($composite_data);
	}
	
	/**
	 * Get component options to display. Fetched using a WP Query wrapper to allow advanced component options filtering / ordering / pagination.
	 *
	 * @param  string $component_id
	 * @param  array  $args
	 * @return array
	 */
	public function get_current_component_options( $component_id, $args = array() ) {

		global $woocommerce_composite_products;

		$current_options = array();

		if ( isset( $this->current_component_options_query[ $component_id ] ) ) {

			$current_options = $this->current_component_options_query[ $component_id ]->get_component_options();

		} else {
			
			if ( ! $this->is_synced() )
				$this->sync_composite();

			$defaults = array(
				'load_page'       => 'selected',
				'per_page'        => false,
				'selected_option' => $this->get_component_default_option( $component_id ),
				'orderby'         => $this->get_component_default_ordering_option( $component_id ),
				'query_type'      => 'product_ids',
			);

			// Component option ids have already been queried without any pages / filters / sorting when initializing the product in 'sync_composite'.
			// This time, we can speed up our paged / filtered / sorted query by using the stored ids of the first "raw" query.

			$component_data                   = $this->get_component_data( $component_id );
			$component_data[ 'assigned_ids' ] = $this->get_component_options( $component_id );

			$current_args = apply_filters( 'woocommerce_composite_component_options_query_args_current', wp_parse_args( $args, $defaults ), $args, $component_id, $this );

			// Pass through query to apply filters / ordering
			$query = new WC_CP_Query( $component_data, $current_args );

			$this->current_component_options_query[ $component_id ] = $query;

			$current_options = $query->get_component_options();
		}

		// Append a value to the results
		if ( ! empty( $args[ 'append_option' ] ) && ! in_array( $args[ 'append_option' ], $current_options ) ) {

			$current_options[] = $args[ 'append_option' ];
		}

		return $current_options;
	}

	/**
	 * Scenario data arrays used by JS scripts.
	 *
	 * @return array
	 */
	public function get_composite_scenario_data() {

		global $woocommerce_composite_products;

		$composite_scenario_meta = get_post_meta( $this->id, '_bto_scenario_data', true );
		$composite_scenario_meta = apply_filters( 'woocommerce_composite_scenario_meta', $composite_scenario_meta, $this );

		$composite_data = $this->get_composite_data();

		foreach ( $composite_data as $component_id => &$component_data ) {

			$current_component_options = $this->get_current_component_options( $component_id );

			$component_data[ 'current_component_options' ] = $current_component_options;
		}

		$composite_scenario_data = $woocommerce_composite_products->api->build_scenarios( $composite_scenario_meta, $composite_data );

		return apply_filters( 'woocommerce_composite_initial_scenario_data', $composite_scenario_data, $composite_data, $composite_scenario_meta, $this );
	}

	/**
	 * Gets price data array. Contains localized strings and price data passed to JS.
	 *
	 * @return array
	 */
	public function get_composite_price_data() {
		
		if ( ! $this->is_synced() )
			$this->sync_composite();

		return $this->composite_price_data;
	}

	/**
	 * Get component data array by component id.
	 *
	 * @param  string $component_id
	 * @return array
	 */
	public function get_component_data( $component_id ) {

		if ( ! isset( $this->composite_data[ $component_id ] ) ) {
			
			return false;
		}

		return apply_filters( 'woocommerce_composite_component_data', $this->composite_data[ $component_id ], $component_id, $this );
		
	}

	/**
	 * True if the composite is priced per product.
	 *
	 * @return boolean
	 */
	public function is_priced_per_product() {

		$is_priced_per_product = $this->per_product_pricing === 'yes' ? true : false;

		return $is_priced_per_product;
	}

	/**
	 * Get the add to url used mainly in loops.
	 *
	 * @return  string
	 */
	public function add_to_cart_url() {

		return apply_filters( 'woocommerce_composite_add_to_cart_url', get_permalink( $this->id ), $this );
	}

	/**
	 * Get the add to cart button text
	 *
	 * @return  string
	 */
	public function add_to_cart_text() {

		$text = __( 'Configure & Buy', 'woocommerce' );

		return apply_filters( 'woocommerce_composite_add_to_cart_text', $text, $this );
		
	}
	
	/**
	 * Get the default option (product id) of a component.
	 *
	 * @param  string $component_id
	 * @return int
	 */
	public function get_component_default_option( $component_id ) {

		$component_data = $this->get_component_data( $component_id );

		if ( ! $component_data ) {
			return '';
		}

		$component_options = $this->get_component_options( $component_id );

		if ( isset( $_POST[ 'wccp_component_selection' ][ $component_id ] ) ) {
			$selected_value = $_POST[ 'wccp_component_selection' ][ $component_id ];
		} elseif ( $component_data[ 'optional' ] != 'yes' && count( $component_options ) == 1 ) {
			$selected_value = $component_options[0];
		} else {
			$selected_value = isset( $component_data[ 'default_id' ] ) && in_array( $component_data[ 'default_id' ], $this->get_component_options( $component_id ) ) ? $component_data[ 'default_id' ] : '';
		}

		return apply_filters( 'woocommerce_composite_component_default_option', $selected_value, $component_id, $this );
	}
	
	/**
	 * Get the default method to order the options of a component.
	 *
	 * @param  int    $component_id
	 * @return string
	 */
	public function get_component_default_ordering_option( $component_id ) {

		$default_orderby = apply_filters( 'woocommerce_composite_component_default_orderby', 'default', $component_id, $this );

		return $default_orderby;
	}
	
	/**
	 * Get all component options (product ids) available in a component.
	 *
	 * @param  string $component_id
	 * @return array
	 */
	public function get_component_options( $component_id ) {

		global $woocommerce_composite_products;
		
		if ( ! $this->is_synced() )
			$this->sync_composite();

		return $this->component_options[ $component_id ];
		
	}

}

