<?php
	
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_CP_Option {
	
	var $id = null;
	var $component_id = null;
	var $option_id = null;
	var $source = 'simple-product';
	var $product_id = null;
	var $value = '';
	var $label = '';
	var $affect_stock = false;
	var $selected = false;
	var $recommended = false;
	var $sku = null;
	var $price = null;
	var $display_price_incl_tax = null;
	var $display_price_excl_tax = null;
	var $formula = null;
	var $display_formula = null;
	var $nested_options = false;
	var $position = 0;
	var $weight = 0;
	var $title = '';
	var $product_title = '';
	
	var $options = array();
	var $scenarios = array();
	var $product = null;
	var $component = null;
	
	private $errors = array();
	
	private $fetched_scenarios = false;
	
	public function __construct( $option = null, $associations = false, $component = null ) {
		
		if( is_numeric( $option ) ) {
				
			global $wpdb;
			
			$table = $this->get_table();
			
			$option = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %s", $option ), ARRAY_A ); 
			
		}
		
		if( $option ) {
			
			$this->populate( $option, $component );
			
			if( $associations ) {
				
				$this->load_options();
				
				$this->load_scenarios();
				
			}
			
		}
		
	}
	
	public function populate( $option = array(), $component = null ) {
		
		$this->id = ! empty( $option['id'] ) ? $option['id'] : $this->get_id();
		$this->component_id = ! empty( $option['component_id'] ) ? $option['component_id'] : $this->get_component_id();
		$this->option_id = ! empty( $option['option_id'] ) ? $option['option_id'] : $this->get_option_id();
		$this->source = ! empty( $option['source'] ) ? $option['source'] : $this->get_source();
		$this->product_id = ! empty( $option['product_id'] ) ? $option['product_id'] : $this->get_product_id();
		$this->value = ! empty( $option['value'] ) ? $option['value'] : $this->get_value();
		$this->label = ! empty( $option['label'] ) ? $option['label'] : $this->get_label();
		$this->affect_stock = ! empty( $option['affect_stock'] ) ? ( $option['affect_stock'] ? true : false ) : $this->affect_stock();
		$this->selected = ! empty( $option['selected'] ) ? ( $option['selected'] ? true : false ) : $this->is_selected();
		$this->recommended = ! empty( $option['recommended'] ) ? ( $option['recommended'] ? true : false ) : $this->is_recommended();
		$this->sku = ! empty( $option['sku'] ) ? $option['sku'] : $this->get_raw_sku();
		$this->price = isset( $option['price'] ) ? $option['price'] : $this->get_raw_price();
		$this->display_price_incl_tax = $this->get_product() ? wc_get_price_including_tax( $this->get_product(), [ 'price' => $this->get_price() ] ) : 0;
		$this->display_price_excl_tax = $this->get_product() ? wc_get_price_excluding_tax( $this->get_product(), [ 'price' => $this->get_price() ] ) : 0;
		$this->formula = ! empty( $option['formula'] ) ? $option['formula'] : $this->get_raw_formula();
		$this->display_formula = $this->get_formula();
		$this->nested_options = ! empty( $option['nested_options'] ) ? $option['nested_options'] : $this->has_nested_options();
		$this->position = isset( $option['position'] ) ? $option['position'] : $this->get_position();
		$this->weight = ! empty( $option['weight'] ) ? $option['weight'] : $this->get_weight();
		
		$this->product_title = $this->get_product_title();
		$this->title = $this->get_title();
		
		
		$this->component = $component;
		
		return $this;
		
	}
	
	protected function get_table() {
		
		global $wpdb;
		
		return "{$wpdb->prefix}woocommerce_component_options";
		
	}
	
	public function clean() {
		
		foreach($this->get_options() as &$option) {
			
			$option->clean();
			
		}
		
		$this->id = null;
		
	}
	
	// Get Associations
	
	public function get_options( $load = true ) {
		
		if( $load ) {
		
			$this->load_options();
			
		}
		
		return $this->options;
		
	}
	
	// Get Associations
	
	public function get_scenarios( $load = true ) {
		
		if( $load ) {
		
			$this->load_scenarios();
			
		}
		
		return $this->scenarios;
		
	}
	
	public function get_component( $load = true ) {
		
		if( $load ) {
			
			$this->load_component();
			
		}
		
		return $this->component;
		
	}
	
	public function get_product( $load = true ) {
		
		if( $load ) {
		
			$this->load_product();
			
		}
		
		return $this->product;
		
	}
	
	// Load Associations
	
	public function load_scenarios() {
		
		if( $this->get_id() && ! $this->fetched_scenarios() ) {
		
			global $wpdb;
			
			$inside = $wpdb->get_results( $wpdb->prepare( "SELECT sc.scenario_id FROM {$wpdb->prefix}woocommerce_scenario_components sc LEFT JOIN {$wpdb->prefix}woocommerce_scenario_component_options sco ON sc.id = sco.scenario_component_id WHERE sc.component_id = %d AND ( sc.allow_all = %d OR ( sco.option_id = %d AND sc.modifier = %s ) )", $this->component_id, 1, $this->id, 'in' ), ARRAY_A );
			
			$scenarios = wp_list_pluck( $inside, 'scenario_id' );
			
			$component = $this->get_component();
			
			$outside = $component->get_outsiders();
			
			$excluded = [];

			foreach($outside as $component) {
				
				$excluded[$component->scenario_id] = ! empty($excluded[$component->scenario_id]) ? $excluded[$component->scenario_id] : [];
				
				$excluded[$component->scenario_id][] = $component->option_id;
				
			}
			
			foreach($excluded as $scenario_id => $options) {
				
				if( ! in_array( $this->id, $options ) ) {
					
					$scenarios[] = $scenario_id;
					
				}
				
			}
			
			$this->scenarios = array_unique($scenarios);
			$this->fetch_scenarios = true;
			
		}
		
	}
	
	public function load_options() {
		
		if( $this->get_id() && ! $this->get_options( false ) ) {
		
			global $wpdb;
		
			$options = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_component_options WHERE option_id = %s", $this->get_id() ), ARRAY_A );
			
			$this->options = array_map(function($option) {
				
				return new self($option, true);
				
			}, $options);
			
		}
		
	}
	
	public function load_component() {
		
		if( $this->get_component_id() && ! $this->get_component( false ) ) {
			
			$this->component = wc_cp_get_component( $this->get_component_id() );
			
		}
		
	}
	
	public function load_product() {
		
		if( $this->get_product_id() && ! $this->get_product( false ) ) {
			
			$this->product = wc_get_product( $this->get_product_id() );
			
		}
		
	}
	
	public function fetched_scenarios() {
		
		return $this->fetched_scenarios;
		
	}
	
	// Save Associations
	
	public function save_options( $options = array() ) {
		
		if( ! $this->get_id() ) {
			
			return false;
			
		}
		
		$this->delete_options( $options );
		
		foreach($options as $option) {
			
			$option['option_id'] = $this->get_id();
			$option['component_id'] = $this->get_component_id();
			
			$object = new self( $option );
			
			$object->save_all();
			
		}
		
	}
	
	// Delete Associations
	
	public function delete_options( $options = array() ) {
		
		global $wpdb;
		
		$end = '';
		
		$table = $this->get_table();
		
		if( $options ) {
		
			$option_ids = implode( "', '", array_filter( array_map( function($option) {
				
				return ! empty( $option['id'] ) ? $option['id'] : null;
			
			}, $options ) ) );
			
			$end .= "AND id NOT IN ( '$option_ids' ) ";
			
		}
		
		$wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE option_id = %s $end", $this->get_id() ) );
		
	}
	
	// Save Methods
	
	public function save_all( $data = array() ) {
		
		if( $data ) {
			
			$this->populate( $data );
			
		}
		
		$this->save();
		
		$options = isset( $data[ 'options' ] ) ? $data[ 'options' ] : array();
				
		$this->save_options( $options );
		
	}
	
	public function save() {
		
		global $wpdb;
	
		$table = $this->get_table();
		
		$data = array(
			'id' => $this->get_id(),
			'component_id' => $this->get_component_id(),
			'option_id' => $this->get_option_id(),
			'source' => $this->get_source(),
			'product_id' => $this->get_product_id(),
			'value' => $this->get_value(),
			'label' => $this->get_label(),
			'affect_stock' => $this->affect_stock(),
			'selected' => $this->is_selected(),
			'recommended' => $this->is_recommended(),
			'sku' => $this->get_raw_sku(),
			'price' => $this->get_raw_price(),
			'formula' => $this->get_formula(),
			'nested_options' => $this->has_nested_options(),
			'position' => $this->get_position()
		);
		
		if( $this->get_id() && $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE id = %s", $this->get_id() ) ) ) {
			
			$wpdb->update( $table, $data, array(
				'id' => $this->get_id()
			) );
			
		} else {
			
			$wpdb->insert( $table, $data );
			
			$this->id = $wpdb->insert_id;
			
		}
		
		if ( in_array( $this->get_source(), array( 'simple-product', 'configurable-product' ) ) && get_post_type( $this->get_product_id() ) !== 'product' ) {
					
			$this->add_error( "Option id #{$this->id} of component id #{$this->component_id} does not have a valid product assigned" );
			
		}
		
	}
	
	// Helpers
	
	public function add_error( $message ) {
		
		$this->errors[] = $message;
		
	}
	
	public function get_errors() {
		
		return $this->errors;
		
	}
	
	// Get Properties
	
	public function get_product_id() {
		
		return $this->product_id;
		
	}
	
	public function get_title() {
		
		if( $this->get_label() ) {
			
			return html_entity_decode( $this->get_label() );
			
		}
		
		if( $title = $this->get_product_title() ) {
			
			return html_entity_decode( $title );
			
		}
		
		return '';
		
	}
	
	public function get_product_title() {
		
		if( $product_id = $this->get_product_id() ) {
			
			return get_the_title( $product_id );
			
		}
		
		return '';
		
	}
	
	public function get_sku() {
		
		if( $this->get_raw_sku() ) {
			
			return $this->get_raw_sku();
			
		}
		
		if( $product_id = $this->get_product_id() ) {
			
			return get_post_meta( $product_id, '_sku', true );
			
		}
		
		return false;
		
	}
	
	public function get_raw_sku() {
		
		return $this->sku;
		
	}
	
	public function get_component_id() {
		
		return $this->component_id;
		
	}
	
	public function get_option_id() {
		
		return $this->option_id;
		
	}
	
	public function get_id() {
		
		return $this->id;
		
	}
	
	public function get_source() {
		
		return $this->source;
		
	}
	
	public function get_weight() {
		
		if( $this->get_raw_weight() ) {
			
			return $this->get_raw_weight();
			
		}
		
		if( $product_id = $this->get_product_id() ) {
			
			return (float) get_post_meta( $product_id, '_weight', true );
			
		}
		
		return false;
		
	}
	
	public function get_raw_weight() {
		
		return $this->weight;
		
	}
	
	public function get_price() {
		
		$price = false;
		
		if( is_numeric( $this->get_raw_price() ) ) {
			
			$price = $this->get_raw_price();
			
		}
		
		elseif( $product_id = $this->get_product_id() ) {
			
			$price = get_post_meta( $product_id, '_price', true );
			
		}
		
		return $this->finalise_price($price);
		
	}
	
	public function get_regular_price() {
		
		if( $product_id = $this->get_product_id() ) {
			
			$price = get_post_meta( $product_id, '_regular_price', true );
			
		} else {
			
			$price = $this->get_price();
			
		}
		
		return $this->finalise_price($price);
		
	}
	
	protected function finalise_price($price) {
		
		if( $price ) {
			
			$price = apply_filters( 'woocommerce_get_price', $price, $this->get_product() );
			
		}
		
		return $price;
		
	}
	
	public function get_raw_price() {
		
		return $this->price;
		
	}
	
	public function get_formula() {
		
		return $this->formula ? $this->formula : '{p}';
		
	}
	
	public function get_raw_formula() {
		
		return $this->formula;
				
	}
	
	public function get_value() {
		
		return $this->value;
		
	}
	
	public function get_label() {
		
		return $this->label;
		
	}
	
	public function is_selected() {
		
		return $this->selected;
		
	}
	
	public function is_recommended() {
		
		return $this->recommended;
		
	}
	
	public function affect_stock() {
		
		return $this->affect_stock;
		
	}
	
	public function has_nested_options() {
		
		return $this->nested_options;
		
	}
	
	public function get_position() {
		
		return $this->position;
		
	}
	
	public function is_taxable() {
		
		return $this->get_tax_status() === 'taxable' && wc_tax_enabled();
		
	}
	
	public function get_tax_class() {
		
		return get_post_meta( $this->get_product_id(), '_tax_class', true );
		
	}
	
	public function get_tax_status() {
		
		return get_post_meta( $this->get_product_id(), '_tax_status', true );
		
	}
	
}