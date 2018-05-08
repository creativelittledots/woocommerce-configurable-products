<?php
/**
 * Configurable Product Class
 *
 * @class 	WC_Product_Composite
 * @version 3.1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_Product_Configurable extends WC_Product {

	private $configuration = null;
	private $cart_item_data = array();
	private $explicit_price = null;
	
	public $per_product_pricing;
	
	public $base_price;
	public $base_regular_price;
	public $base_weight;
	
	public $min_price;
	
	private $is_synced = false;
	
	private $errors = array();
	private $components = array();

	public function __construct( $product ) {

		parent::__construct( $product );

		$this->per_product_pricing  = $this->get_meta('_wc_cp_per_product_pricing') === 'yes';

		$this->min_price            = $this->get_meta('_min_configurable_price');

		$base_price                 = $this->get_meta('_base_price');
		$base_regular_price         = $this->get_meta('_base_regular_price');

		$this->base_price           = empty( $base_price ) ? 0.0 : (double) $base_price;
		$this->base_regular_price   = empty( $base_regular_price ) ? 0.0 : (double) $base_regular_price;
		$this->base_weight 			= $this->get_weight();
		
		$this->is_synced 			= $this->get_meta('_is_synced');

		if ( $this->is_priced_per_product() ) {
			
			$this->price = $this->get_base_price();
			
		}
		
		$this->price_inc_tax = wc_get_price_including_tax($this);;
		
	}
	
	public function get_type() {
		
		return 'configurable';
		
	}
	
	public function set_price($price) {
		
		if( apply_filters('wc_cp_set_explicit_price', false, $this) ) {
		
			$this->explicit_price = $price;
			
		}
		
		return parent::set_price($price);
		
	}
	
	public function get_explicit_price() {
		
		return $this->explicit_price;
		
	}

	/**
	 * Overrides get_price to return base price in static price mode.
	 * In per-product pricing mode, get_price() returns the base configurable price.
	 *
	 * @return double
	 */
	public function get_price( $context = 'view' ) {
		
		if( ! ( $price = $this->get_explicit_price() ) && ! ( $price = $this->get_cart_price() ) ) {
			
			$price = $this->get_raw_price();
			
		}
		
		return apply_filters( 'woocommerce_configurable_get_price', apply_filters( 'woocommerce_get_price', $price, $this ), $this );
		
	}
	
	public function get_sku( $context = 'view' ) {
		
		if( $sku = $this->get_cart_sku() ) {
			
			return $sku;
			
		}
		
		return parent::get_sku();
		
	}
	
	public function get_base_weight( $context = 'view' ) {
		
		return (float) apply_filters( 'woocommerce_configurable_product_base_weight', apply_filters( 'woocommerce_configurable_product_get_base_weight', $this->base_weight ? $this->base_weight : '' ), $this );
		
	}
	
	public function get_weight( $context = 'view' ) {
		
		if( $weight = $this->get_cart_weight() ) {
			
			return (float) $weight;
			
		}
		
		return (float) apply_filters( 'woocommerce_configurable_product_weight', apply_filters( 'woocommerce_configurable_product_get_weight', parent::get_weight() ? parent::get_weight() : '' ), $this );
		
	}
	
	/**
	 * Overrides get_price to return base price in static price mode.
	 * In per-product pricing mode, get_price() returns the base configurable price.
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
	
	public function set_cart_item_data($cart_item_data) {
		
		$this->cart_item_data = $cart_item_data;
		
	}
	
	public function get_cart_item_data() {
		
		return $this->cart_item_data;
		
	}
	
	public function get_cart_sku() {
		
		return isset( $this->cart_item_data['configurable']['sku'] ) ? $this->cart_item_data['configurable']['sku'] : null;
		
	}
	
	public function get_cart_price() {
		
		return wc_prices_include_tax() ? $this->get_cart_price_incl_tax() : $this->get_cart_price_excl_tax();
		
	}
	
	public function get_cart_price_incl_tax() {
		
		return isset( $this->cart_item_data['configurable']['price_incl_tax'] ) ? (float) $this->cart_item_data['configurable']['price_incl_tax'] : null;
		
	}
	
	public function get_cart_price_excl_tax() {
		
		return isset( $this->cart_item_data['configurable']['price_excl_tax'] ) ? (float) $this->cart_item_data['configurable']['price_excl_tax'] : null;
		
	}
	
	public function get_cart_weight() {
		
		return isset( $this->cart_item_data['configurable']['weight'] ) ? (float) $this->cart_item_data['configurable']['weight'] : null;
		
	}

	/**
	 * Overrides get_regular_price to return base price in static price mode.
	 *
	 * @return double
	 */
	public function get_regular_price( $context = 'view' ) {

		return (float) apply_filters( 'woocommerce_configurable_get_regular_price', $this->get_raw_regular_price(), $this );
		
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
	 * Get configurable base price.
	 *
	 * @return double
	 */
	public function get_base_price() {

		if ( $this->is_priced_per_product() ) {
			
			return (float) apply_filters( 'woocommerce_configurable_get_base_price', apply_filters( 'woocommerce_get_price', get_query_var( 'wc_query' ) == 'product_query' ? max( $this->min_price, $this->base_price ) : $this->base_price, $this ), $this );
			
		} else {
			
			return false;
			
		}
	}
	
	/**
	 * Get configurable base price including tax.
	 *
	 * @return double
	 */
	public function get_base_price_including_tax() {

        return $this->get_price_including_tax( 1, $this->get_base_price() );
		
	}
	
	/**
	 * Get configurable base price including tax.
	 *
	 * @return double
	 */
	public function get_base_price_excluding_tax() {

        return $this->get_price_excluding_tax( 1, $this->get_base_price() );
		
	}

	/**
	 * Get configurable base regular price.
	 *
	 * @return double
	 */
	public function get_base_regular_price() {

		if ( $this->is_priced_per_product() ) {
			
			return (float) apply_filters( 'woocommerce_configurable_get_base_regular_price', get_query_var( 'wc_query' ) == 'product_query' ? max( $this->min_price, $this->base_regular_price ) : $this->base_regular_price, $this );
			
		} else {
			
			return false;
			
		}
	}
	
	/**
	 * Get configurable base price.
	 *
	 * @return double
	 */
	public function get_build_sku() {

		return apply_filters( 'woocommerce_configurable_get_build_sku', $this->get_meta('_wc_cp_build_sku') ? true : false, $this );
		
	}
	
	/**
	 * Get configurable base sku.
	 *
	 * @return string
	 */
	public function get_base_sku() {

		return apply_filters( 'woocommerce_configurable_get_base_sku', $this->get_meta('_wc_cp_sku_start'), $this );
		
	}
	
	/**
	 * True if the configurable is in sync with its contents.
	 *
	 * @return boolean
	 */
	public function is_synced() {

		return $this->is_synced;
	}
	
	/**
	 * Calculates min prices based on the configured product data.
	 *
	 * @return void
	 */
	public function sync_configurable($force = false) {

		global $woocommerce_configurable_products;

		// Initialize min price information
		$min_configurable_price = $min_configurable_regular_price = 0;

		if ( $this->is_priced_per_product() || $force ) {

			foreach ( $this->get_components() as $component ) {

				$item_price = '';

				$item_regular_price = '';
				
				$item_price_incl = '';
				
				$item_price_excl = '';

				// No options available
				if ( empty( $component->get_options() ) || $component->is_optional() )
					continue;

				foreach ( $component->get_options() as $option ) {

					// Update component prices
					$item_price = $item_price !== '' ? min( $item_price, $option->get_price() ) : $option->get_price();

					$item_regular_price = $item_regular_price !== '' ? min( $item_regular_price, $option->get_regular_price() ) : $option->get_regular_price();
					
				}

				$min_configurable_price          = $min_configurable_price + $item_price;
				$min_configurable_regular_price  = $min_configurable_regular_price + $item_regular_price;
				
			}

			$min_configurable_price          = $this->base_price + $min_configurable_price;
			$min_configurable_regular_price  = $this->base_regular_price + $min_configurable_regular_price;

			if ( $this->get_meta( 'price' ) != $min_configurable_price && ! is_admin() ) {
				
				update_post_meta( $this->get_id(), '_price', $min_configurable_price );
				
			}

		} else {

			$min_configurable_price = $this->base_price;
			$min_configurable_regular_price = $this->base_regular_price;

		}

		// Use these filters if get_hide_price_html() returns false but you still want to include the configurable in price filter widget min results
		$min_configurable_price = apply_filters( 'woocommerce_min_configurable_price_meta', $min_configurable_price, $this );

		// Save modified min price meta to include product in price filter widget results
		if ( $this->min_price != $min_configurable_price ) {
			
			$this->min_price = $min_configurable_price;
			
			update_post_meta( $this->get_id(), '_min_configurable_price', $min_configurable_price );
			
		}

		update_post_meta( $this->get_id(), '_is_synced', true );
		
		$this->is_synced = true;
		
	}
	
	public function get_min_price() {
    	
    	if ( ! $this->is_synced() )
			$this->sync_configurable();
    	
    	return apply_filters( 'woocommerce_get_price', $this->min_price, $this );
    	
	}
	
	public function get_min_price_including_tax() {
    	
    	return $this->get_price_including_tax( 1, $this->get_min_price() );
    	
	}
	
	public function get_min_price_excluding_tax() {
    	
    	return $this->get_price_excluding_tax( 1, $this->get_min_price() );
    	
	}

	/**
	 * Returns range style html price string without min.
	 *
	 * @param  mixed    $price    default price
	 * @return string             overridden html price string (old style)
	 */
	public function get_price_html( $price = '' ) {

		global $woocommerce_configurable_products;
		
		if ( ! $this->is_synced() )
			$this->sync_configurable();

		if ( $this->is_priced_per_product() ) {
			
			$price_type = wc_prices_include_tax() ? 'price_incl_tax' : 'price_excl_tax';

			// Get the price
			if ( $this->get_min_price() === '' ) {
				
				$args = is_singular('product') ? array(
					'price_format' => '%1$s<span rv-text="product:' . $price_type . '">%2$s</span>'
				) : array();

				$price = apply_filters( 'woocommerce_configurable_empty_price_html', wc_price( 
				$this->get_min_price(), $args ) . $this->get_price_suffix(), $this );

			} else {
				
				$args = is_singular('product') ? array(
					'price_format' => '%1$s<span rv-text="product:' . $price_type . '">%2$s</span>'
				) : array();

				$price = apply_filters( 'woocommerce_configurable_price_html', $this->get_price_html_from_text() . wc_price( 
				$this->get_min_price(), $args ) . 
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
	    
        return '<span class="from" rv-show="product:no_of_errors">' . _x( 'From:', 'min_price', 'woocommerce' ) . ' </span>';
        
    }

	/**
	 * Product configuration
	 *
	 * @return stdClass
	 */
	public function get_configuration() {
		
		global $woocommerce_configurable_products;

		if ( ! $this->configuration ) {
			
			$configuration = new stdClass();
			
			$configuration->id = $this->get_id();
			$configuration->min_price_excl_tax = $this->get_min_price_excluding_tax();
			$configuration->min_price_incl_tax = $this->get_min_price_including_tax();
			$configuration->base_price_incl_tax = $this->get_base_price_including_tax();
			$configuration->base_price_excl_tax = $this->get_base_price_excluding_tax();
			$configuration->base_sku = $this->get_base_sku();
			$configuration->weight_unit = strtoupper( get_option('woocommerce_weight_unit') );
			$configuration->base_weight = $this->get_base_weight();
			$configuration->build_sku = $this->get_build_sku();
			$configuration->sku = $this->get_sku();
			$configuration->components = $this->get_components();
			$configuration->scenarios = $this->get_scenarios();
			$configuration->priced_per_product = $this->is_priced_per_product();
			
			$this->configuration = $configuration;
			
		}

		return $this->configuration;
	}
	
	public function get_raw_configuration() {
		
		$configuration = $this->get_configuration();
		
		foreach($configuration->components as &$component) {
			
			$component->clean();
			
		}
		
		foreach($configuration->scenarios as &$scenario) {
			
			$scenario->clean();
			
		}
		
		$configuration->id = null;
		
		return json_decode(json_encode($configuration), true);
		
	}
	
	/**
	 * Get Product Components for Export
	 *
	 * @return array
	 */
	public function get_component_data_for_export( $all_columns = false ) {
		
		$headers = array(
			'Component ID', 
			'Component Parent', 
			'Component Parent ID', 
			'Component Title', 
			'Component Source',
			'Style', 
			'Description', 
			'Optional', 
			'Sovereign'
		);
		
		if( $this->get_build_sku() || $all_columns ) {
			
			$headers = array_merge($headers, array(
				'Affect SKU',
				'SKU Order',
				'SKU Default'
			));
			
		}
		
		$field_headers = array(
			'Field ID', 
			'Field Label', 
			'Field Placeholder', 
			'Field Price Formula', 
			'Field Value', 
			'Field Step', 
			'Field Min', 
			'Field Max', 
			'Field Suffix'
		);
		
		$headers = array_merge($headers, $field_headers);
		
		$option_headers = array(
			'Option ID', 
			'Option Parent ID', 
			'Source', 
			'Option Product ID', 
			'Option Product Title', 
			'Option Product SKU', 
			'Option Product Price', 
			'Formula', 
			'Value', 
			'Label', 
			'Selected', 
			'Recommended', 
			'Affect Stock'
		);
		
		$headers = array_merge($headers, $option_headers);
		
		$data = array($headers);
		
		if( $components = $this->get_components() ) {
		
			foreach( $components as $component ) {
				
				$parent = $component->get_parent();
				
				$row = array(
					$component->get_id(),
					$parent ? $parent->get_title() : '',
					$parent ? $parent->get_id() : 0,
					$component->get_title(),
					$component->get_source(),
					$component->get_style(),
					$component->get_description(),
					$component->is_optional() ? 1 : 0,
					$component->is_sovereign() ? 1 : 0
				);
				
				if( $this->get_build_sku() || $all_columns ) {
					
					$row = array_merge($row, array(
						$component->affect_sku() ? 1 : 0,
						$component->get_sku_order(),
						$component->get_sku_default()
					));
					
				}
				
				if( $field = $component->get_field() ) {
					
					$row = array_merge($row, array(
						$field->get_id(),
						$field->get_label(),
						$field->get_placeholder(),
						$field->get_price_formula(),
						$field->get_value(),
						$field->get_step(),
						$field->get_min(),
						$field->get_max(),
						$field->get_suffix()
					));
					
					
				} else {
					
					$row = array_merge($row, array_map(function() {
						return '';
					}, range(1, count($field_headers))));
					
				}
				
				if( $options = $component->get_options() ) {
				
					foreach($options as $option ) {
						
						$option_row = array_merge($row, array(
							$option->get_id(),
							$option->get_option_id(),
							$option->get_source(),
							$option->get_product_id(),
							$option->get_product_title(),
							"\"" . $option->get_sku() . "\"",
							$option->get_price(),
							$option->get_formula(),
							$option->get_value(),
							$option->get_label(),
							$option->is_selected() ? 1 : 0,
							$option->is_recommended() ? 1 : 0,
							$option->affect_stock() ? 1 : 0
						));
						
						$data[] = $option_row;
						
					}
					
				} else {
					
					$row = array_merge($row, array_map(function() {
						return '';
					}, range(1, count($option_headers))));
					
					$data[] = $row;
					
				}
				
			}
			
		}
		
		return $data;
		
	}
	
	public function prepare_scenarios_for_import( $rows = array() ) {
		
		$scenarios = array();
		
		$component_id = '';
		$component_title = '';
		$allow_field = false;
		
		foreach($rows as $i => $row) {
			
			$component_id = $row[0] ? $row[0] : $component_id;
			$component_title = $row[1] ? $row[1] : $component_title;
			$allow_field = $row[2] ? $row[2] : $allow_field;
			
			$product_sku = str_replace('"', '', $row[3]);
			$product_title = $row[4];
			$timestamp = current_time( 'timestamp' );
			
			$component_product_id = $product_sku == -1 ? -1 : $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $product_sku ) );
			$component_option_id = $component_product_id == -1 ? -1 : $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}woocommerce_component_options WHERE component_id = %s AND product_id = %s LIMIT 1", $component_id, $component_product_id ) );
			
			unset($row[0]);
			unset($row[1]);
			unset($row[2]);
			unset($row[3]);
			
			$x = 0;
			
			foreach($headers as $y => $header) {
				
				$scenario_id =  $timestamp + $y;
				
				$scenarios[$scenario_id] = ! empty( $scenarios[$scenario_id] ) ? $scenarios[$scenario_id] : array();
				
				$scenario = &$scenarios[$scenario_id];
				
				$scenario['product_id'] = $product->get_id();
				$scenario['title'] = $header;
				$scenario['description'] = $descriptions[$y];
				$scenario['position'] = $x;
				$scenario['active'] = true;
				
				$scenario['components'] = ! empty( $scenario['components'] ) ? $scenario['components'] : array();
				$scenario['components'][$component_id] = ! empty( $scenario['components'][$component_id] ) ? $scenario['components'][$component_id] : array();
				
				$component = &$scenario['components'][$component_id];
				
				$component['component_id'] = $component_id;
				$component['modifier'] = "in";	
				$component['allow_all'] = false;
				$component['allow_field'] = $allow_field;	
				
				$value = ! empty( $row[$y] ) ? $row[$y] : false;

				if( $value ) {
					
					$component['options'] = ! empty( $component['options'] ) ? $component['options'] : array();
					
					$component['options'][] = $component_option_id;
					
				}	
				
				$x++;
				
			}
			
		}
		
		return $scenarios;
		
	}
	
	public function prepare_components_for_import( $rows = array() ) {
		
		$components = array();
		
		foreach($rows as $i => $full_row) {
			
			$cached_identifier = isset( $cached_identifier ) ? $cached_identifier : false;
			$x = isset( $x ) ? $x : 0;
			
			$identifier = ! empty( $full_row['component_id'] ) ? $full_row['component_id'] : $full_row['component_title'];
			
			$parent_identifier = ! empty( $full_row['component_parent_id'] ) ? $full_row['component_parent_id'] : $full_row['component_parent'];
			
			$components[$identifier] = ! empty( $components[$identifier] ) ? $components[$identifier] : array(
				'id' => ! empty( $full_row['component_id'] ) ? $full_row['component_id'] : null,
				'component_id' => ! empty( $full_row['component_parent_id'] ) ? $full_row['component_parent_id'] : null,
				'product_id' => $this->get_id(),
				'title' => $full_row['component_title'],
				'style' => $full_row['style'],
				'description' => $full_row['description'],
				'optional' => $full_row['optional'] ? 1 : 0,
				'sovereign' => $full_row['sovereign'] ? 1 : 0,
				'affect_sku' => $full_row['affect_sku'] ? 1 : 0,
				'sku_order' => $full_row['sku_order'],
				'sku_default' => $full_row['sku_default'],
				'source' => $full_row['component_source'],
			);
			
			$components[$identifier]['options'] = ! empty( $components[$identifier]['options'] ) ? $components[$identifier]['options'] : array();
			$components[$identifier]['position'] = array_search( $identifier, array_keys( $components ) );
			
			$option_product = ! empty( $full_row['option_product_id'] ) ? wc_get_product( $full_row['option_product_id'] ) : ( ! empty( $full_row['option_product_sku'] ) ? wc_get_product( wc_get_product_id_by_sku( $full_row['option_product_sku'] ) ) : false );
			
			if( ! empty( $full_row['field_label'] ) ) {
			
				$components[$identifier]['field'] = [
					'id' => $full_row['field_id'],
					'label' => $full_row['field_label'],
					'placeholder' => $full_row['field_placeholder'],
					'price_formula' => $full_row['field_price_formula'],
					'value' => $full_row['field_value'],
					'step' => $full_row['field_step'],
					'min' => $full_row['field_min'],
					'max' => $full_row['field_max'],
					'suffix' => $full_row['field_suffix'],
				];
				
			}
			
			$components[$identifier]['options'][] = array(
				'id' => ! empty( $full_row['option_id'] ) ? $full_row['option_id'] : null,
				'option_id' => ! empty( $full_row['option_parent_id'] ) ? $full_row['option_parent_id'] : null,
				'source' => $full_row['source'],
				'product_id' => $option_product ? $option_product->get_id() : null,
				'affect_stock' => $full_row['affect_stock'] ? 1 : 0,
				'selected' => $full_row['selected'] ? 1 : 0,
				'recommended' => $full_row['recommended'] ? 1 : 0,
				'sku' => ! $option_product ? $full_row['option_product_sku'] : ( $option_product->get_sku() == $full_row['option_product_sku'] ? '' : $full_row['option_product_sku'] ),
				'price' => ! $option_product ? $full_row['option_product_price'] : ( $option_product->get_price() == $full_row['option_product_price'] ? '' : $full_row['option_product_price'] ),
				'formula' => $full_row['formula'],
				'nested_options' => false,
				'position' => $x
			);
			
			if( $parent_identifier && ! empty( $components[$parent_identifier] ) ) {
				
				$components[$parent_identifier]['components'] = ! empty( $components[$parent_identifier]['components'] ) ? $components[$parent_identifier]['components'] : array();
				
				$components[$parent_identifier]['components'][$identifier] = $components[$identifier];
				
			} 
			
			if( $i === 0 || $identifier == $cached_identifier ) {
				
				$x++;
				
			} else {
				
				$x = 0;
				
			}
			
			$cached_identifier = $identifier;
			
		} 
		
		return $components;
		
	}
	
	private function prepare_scenario_component_for_export( &$data, $component ) {
		
		if( $subcomponents = $component->get_components() ) {
					
			foreach( $subcomponents as $subcomponent ) {
				
				$this->prepare_scenario_component( $data, $subcomponent );
				
			}
			
		}
		
		if( $options = $component->get_options() ) {
		
			if( $component->is_optional() ) {
				
				$data[$component->id] = array(
					$component->id, 
					$component->get_title(), 
					$component->get_field() ? 'X' : '',
					-1, 
					'None'
				);
				
			}
			
			foreach($component->get_options() as $i => $option) {
				
				if( $i || $component->is_optional() ) {
					
					$data[$option->id] = array(
						'', 
						'',
						'',
						"\"" . $option->get_sku() . "\"", 
						str_replace(',', '', $option->get_title())
					);
					
				} else {
					
					$data[$option->id] = array(
						$component->id,
						$component->get_title(), 
						$component->get_field() ? 'X' : '',
						"\"" . $option->get_sku() . "\"", 
						str_replace(',', '', $option->get_title())
					);
					
				}
				
			}
		
		}
		
		return $data;
		
	}
	
	/**
	 * Get Product Scenarios for Export
	 *
	 * @return array
	 */
	public function get_scenario_data_for_export() {
		
		$data = array(
			array('Component ID', 'Component Title', 'Allow Field', 'Option SKU', 'Option Title'),
			array('Description', ' ', ' ', ' ')
		);
		
		if( $components = $this->get_components() ) {
			
			foreach ( $components as $component ) {
				
				$this->prepare_scenario_component_for_export( $data, $component );
				
			}
			
		}
		
		if( $scenarios = $this->get_scenarios() ) {
			
			foreach ( $scenarios as $scenario ) {
				
				$data[0][] = $scenario->get_title();
				$data[1][] = $scenario->get_description();
					
				foreach( $scenario->get_components() as $scenario_component ) {
					
					$component = $scenario_component->get_component();
					
					if( $options = $component->get_options() ) {
			
						foreach ( $options as $option ) {
							
							if( $scenario_component->is_allowed( $option->id ) ) {
								
								$data[$option->id][] = 'X';
								
							} else {
								
								$data[$option->id][] = '';
								
							}
							
						}
						
					}
					
					if( $scenario_component->has_optional_option() ) {
						
						$data[$component->id][] = 'X';
						
					}
					
				}
											
			} 
			
		}
		
		return $data;
		
	}
	
	/**
	 * Product Components
	 *
	 * @return array
	 */
	public function get_components( $force = false ) {
		
		if( ! $this->components || $force ) {
		
			global $wpdb;
			
			$table = "{$wpdb->prefix}woocommerce_components";
			
			$components = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_components WHERE product_id = %s AND component_id IS NULL ORDER BY position ASC", $this->get_id() ), ARRAY_A );
			
			$this->components = array_map(function($component) {
				
				return wc_cp_get_component( $component, true );
				
			}, $components);
			
		}
		
		return $this->components;
		
	}
	
	/**
	 * Product Scenarios
	 *
	 * @return array
	 */
	public function get_scenarios( $force = false ) {
		
		if( ! $this->scenarios || $force ) {
		
			global $wpdb;
			
			$table = "{$wpdb->prefix}woocommerce_scenarios";
			
			$scenarios = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_scenarios WHERE product_id = %s ORDER BY position ASC", $this->get_id() ), ARRAY_A );
			
			$this->scenarios = array_map(function($scenario) {
				
				return wc_cp_get_scenario( $scenario, true );
				
			}, $scenarios);
			
		}
		
		return $this->scenarios;
		
	}

	/**
	 * True if the configurable is priced per product.
	 *
	 * @return boolean
	 */
	public function is_priced_per_product() {

		return $this->per_product_pricing;
	}

	/**
	 * Get the add to url used mainly in loops.
	 *
	 * @return  string
	 */
	public function add_to_cart_url() {

		return apply_filters( 'woocommerce_configurable_add_to_cart_url', get_permalink( $this->get_id() ), $this );
	}

	/**
	 * Get the add to cart button text
	 *
	 * @return  string
	 */
	public function add_to_cart_text() {

		$text = __( 'Configure & Buy', 'woocommerce' );

		return apply_filters( 'woocommerce_configurable_add_to_cart_text', $text, $this );
		
	}	
	
	public function get_item_variation_data($item) {
		
		$variations = array();
		
		foreach ( $item['item_meta'] as $meta_name => $meta_value ) {
			
			// Skip hidden core fields 
            if ( in_array( $meta_name, apply_filters( 'woocommerce_hidden_order_itemmeta', array( 
				'_qty',  
				'_tax_class',  
				'_product_id',  
				'_variation_id',  
				'_line_subtotal',  
				'_line_subtotal_tax',  
				'_line_total',  
				'_line_tax',  
				'_line_tax_data'
			) ) ) ) { 
				
                continue; 
                
        	} 
			
			$variations[ $meta_name ] = $meta_value;
			
		}
		
		return $variations;
		
	}
	
	public function order_again($item) {
		
		$quantity     = (int) $item['qty'];
		$variations   = array();
		$cart_item_data = apply_filters( 'woocommerce_order_again_cart_item_data', array(), $item, $order );

		foreach ( $item['item_meta'] as $meta_name => $meta_value ) {
			
			// Skip hidden core fields 
            if ( in_array( $meta_name, apply_filters( 'woocommerce_hidden_order_itemmeta', array( 
				'_qty',  
				'_tax_class',  
				'_product_id',  
				'_variation_id',  
				'_line_subtotal',  
				'_line_subtotal_tax',  
				'_line_total',  
				'_line_tax',  
				'_line_tax_data'
			) ) ) ) { 
				
                continue; 
                
        	} 
			
			$variations[ $meta_name ] = $meta_value[0];
			
		}
		
		if( $quantity ) {
			            
            if( get_option( 'woocommerce_prices_include_tax', 'no' ) == 'yes' ) {
                
                $cart_item_data['configurable']['price'] = ($item['line_subtotal']+$item['line_subtotal_tax'])/ $quantity;
                
            } else {
                
                $cart_item_data['configurable']['price'] = ($item['line_subtotal'])/ $quantity;
                
            }
            
        }

		// Add to cart validation
		if ( ! apply_filters( 'woocommerce_add_to_cart_validation', true, $this->get_id(), $quantity, $variation_id, $variations, $cart_item_data ) ) {
			
			return;
			
		}

		WC()->cart->add_to_cart( $this->get_id(), $quantity, null, $variations, $cart_item_data );
		
	}
	
	public function save_configuration( $configuration = array() ) {
		
		if ( isset( $configuration[ 'components' ] ) ) {
			
			$this->save_components( $configuration[ 'components' ] );
			
		}

		/* -------------------------- */
		/* Scenarios
		/* -------------------------- */

		if ( isset( $configuration[ 'scenarios' ] ) ) {
			
			$this->save_scenarios( $configuration[ 'scenarios' ] );

		}
		
	}
	
	public function save_components( $components = array() ) {
		
		$this->delete_components( $components );
		
		if( $components ) {
			
			$needs_configuration = false;
		
			/* -------------------------- */
			/* Components
			/* -------------------------- */
	
			foreach ( $components as $component ) {
				
				if( $component_id = ! empty( $component['id'] ) ? $component['id'] : null ) {
				
					$object = wc_cp_get_component( $component_id );
					
				} else {
									
					$object = new WC_CP_Component( $component );
					
				}
				
				$component['product_id'] = $this->get_id();
				
				$object->save_all( $component );
				
				foreach( $object->get_errors() as $error ) {
				
					$this->add_error( $error );
					
				}
				
				if( $object->needs_configuration() ) {
					
					$needs_configuration = true;
					
				}
				
			}
			
			update_post_meta( $this->get_id(), '_needs_configuration', $needs_configuration);
			
			return $this->get_components( true );
			
		}
		
	}
	
	public function save_scenarios( $scenarios = array() ) {
		
		$this->delete_scenarios( $scenarios );
		
		if( $scenarios ) {
	
			foreach ( $scenarios as $scenario ) {

				if( $scenario_id = ! empty( $scenario['id'] ) ? $scenario['id'] : null ) {
			
					$object = wc_cp_get_scenario( $scenario_id ); 
					
				} else {
				
					$object = new WC_CP_Scenario( $scenario );	
					
				}
				
				$scenario['product_id'] = $this->get_id();
			
				$object->save_all( $scenario );
				
			}
			
			return $this->get_scenarios( true );
			
		}
		
	}
	
	public function delete_scenarios( $scenarios = array() ) {
		
		global $wpdb;
		
		$end = '';
		
		if( $scenarios ) {
		
			$scenario_ids = implode( "', '", array_filter( array_map( function($scenario) {
				
				return ! empty( $scenario['id'] ) ? $scenario['id'] : null;
			
			}, $scenarios ) ) );
			
			
			$end .= "AND id NOT IN ( '$scenario_ids' )";
			
		}
		
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_scenarios WHERE product_id = %s $end", $this->get_id() ) );
		
	}
	
	public function delete_components( $components = array() ) {
		
		global $wpdb;
		
		$end = '';
		
		if( $components ) {
		
			$component_ids = implode( "', '", array_filter( array_map( function($component) {
				
				return ! empty( $component['id'] ) ? $component['id'] : null;
			
			}, $components ) ) );
			
			$end .= "AND id NOT IN ( '$component_ids' ) ";
			
		}
		
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_scenario_component_options WHERE scenario_component_id IN ( SELECT id FROM {$wpdb->prefix}woocommerce_scenario_components WHERE component_id IN ( SELECT id FROM {$wpdb->prefix}woocommerce_components WHERE product_id = %s $end) )", $this->get_id() ) );
		
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_scenario_components WHERE component_id IN ( SELECT id FROM {$wpdb->prefix}woocommerce_components WHERE product_id = %s $end)", $this->get_id() ) );
		
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_component_options WHERE component_id IN ( SELECT id FROM {$wpdb->prefix}woocommerce_components WHERE product_id = %s $end)", $this->get_id() ) );
		
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_component_field WHERE component_id IN ( SELECT id FROM {$wpdb->prefix}woocommerce_components WHERE product_id = %s $end)", $this->get_id() ) );
		
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_components WHERE product_id = %s $end", $this->get_id() ) );
		
	}
	
	public function add_error( $message ) {
		
		$this->errors[] = $message;
		
	}
	
	public function get_errors() {
		
		return $this->errors;
		
	}

}

