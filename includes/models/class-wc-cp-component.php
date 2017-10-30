<?php
	
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_CP_Component {
	
	var $id = null;
	var $ref = null;
	var $component_id = null;
	var $product_id = null;
	var $source = 'default';
	var $style = 'dropdown';
	var $title = 'Untitled Component';
	var $description = '';
	var $optional = false;
	var $sovereign = false;
	var $affect_sku = false;
	var $sku_order = '';
	var $sku_default = '';
	var $position = 0;
	
	var $options = array();
	var $components = array();
	var $outsiders = array();
	var $field = null;
	
	var $parent = null;
	
	var $fetched_options = false;
	var $fetched_outsiders = false;
	
	private $errors = array();
	
	public function __construct( $component = null, $associations = false ) {
		
		if( is_numeric( $component ) ) {
				
			global $wpdb;
			
			$table = $this->get_table();
			
			$component = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %s", $component ), ARRAY_A ); 
			
		}
		
		if( $component ) {
			
			$this->populate( $component );
			
			if( $associations ) {
				
				$this->load_options();
				
				$this->load_components();
				
				$this->load_field();
				
				$this->load_outsiders();
				
			}
			
		}
		
	}
	
	public function populate( $component = array() ) {
		
		$this->id = ! empty( $component['id'] ) ? $component['id'] : $this->get_id();
		$this->ref = ! empty( $component['ref'] ) ? $component['ref'] : $this->get_ref();
		$this->component_id = ! empty( $component['component_id'] ) ? $component['component_id'] : $this->get_component_id();
		$this->source = ! empty( $component['source'] ) ? $component['source'] : $this->get_source();
		$this->product_id = ! empty( $component['product_id'] ) ? $component['product_id'] : $this->get_product_id();
		$this->style = ! empty( $component['style'] ) ? $component['style'] : $this->get_style();
		$this->title = ! empty( $component['title'] ) ? strip_tags ( stripslashes( $component['title'] ) ) : $this->get_title();
		$this->description = ! empty( $component['description'] ) ? wp_kses_post( stripslashes( $component['description'] ) ) : $this->get_description();
		$this->optional = ! empty( $component['optional'] ) ? $component['optional'] : $this->is_optional();
		$this->sovereign = ! empty( $component['sovereign'] ) ? $component['sovereign'] : $this->is_sovereign();
		$this->affect_sku = ! empty( $component['affect_sku'] ) ? $component['affect_sku'] : $this->affect_sku();
		$this->sku_order = ! empty( $component['sku_order'] ) ? $component['sku_order'] : $this->get_sku_order();
		$this->sku_default = ! empty( $component['sku_default'] ) ? $component['sku_default'] : $this->get_sku_default();
		$this->position = isset( $component['position'] ) ? $component['position'] : $this->get_position();
		
		return $this;
		
	}
	
	protected function get_table() {
		
		global $wpdb;
		
		return "{$wpdb->prefix}woocommerce_components";
		
	}
	
	// Get Associations
	
	public function get_options( $load = true ) {
		
		if( $load ) {
		
			$this->load_options();
			
		}
		
		return $this->options;
		
	}
	
	public function get_parent( $load = true ) {
		
		if( $load ) {
		
			$this->load_parent();
			
		}
		
		return $this->parent;
		
	}
	
	public function get_components( $load = true ) {
		
		if( $load ) {
		
			$this->load_components();
			
		}
		
		return $this->components;
		
	}
	
	public function get_field( $load = true ) {
		
		if( $load ) {
		
			$this->load_field();
			
		}
		
		return $this->field;
		
	}
	
	public function get_outsiders( $load = true ) {
		
		if( $load ) {
			
			$this->load_outsiders();
			
		}
		
		return $this->outsiders;
		
	}
	
	// Load Associations
	
	public function load_options() {
		
		if( $this->get_id() && ! $this->fetched_options() ) {
					
			global $wpdb;
		
			$options = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_component_options WHERE component_id = %s AND option_id IS NULL ORDER BY position ASC", $this->get_id() ), ARRAY_A );
			
			$this->load_outsiders();
			
			$this->options = array_map(function($option) {
				
				return new WC_CP_Option($option, true, clone $this);
				
			}, $options);
			
			$this->fetched_options = true;
			
		}
		
	}
	
	public function fetched_options() {
		
		return $this->fetched_options;
		
	}
	
	public function load_parent() {
		
		if( $this->component_id && ! $this->get_parent( false ) ) {
			
			$this->parent = wc_cp_get_component( $this->get_component_id() );
			
		}
		
	}
	
	public function load_components() {
		
		if( $this->get_id() && ! $this->get_components( false ) ) {
		
			global $wpdb;
		
			$components = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_components WHERE component_id = %s ORDER BY position ASC", $this->get_id() ), ARRAY_A );
			
			$this->components = array_map(function($component) {
				
				return new self($component, true);
				
			}, $components);
			
		}
		
	}
	
	public function load_field() {
		
		if( $this->get_id() && ! $this->get_field( false ) ) {
		
			global $wpdb;
		
			$field = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_component_field WHERE component_id = %s", $this->get_id() ), ARRAY_A );
			
			$this->field = new WC_CP_Component_Field( $field, true );
			
		}
		
	}
	
	public function load_outsiders() {
		
		if( $this->get_id() && ! $this->fetched_outsiders() ) {
			
			global $wpdb;
			
			$this->outsiders = $wpdb->get_results( $wpdb->prepare( "SELECT sc.scenario_id, sco.option_id FROM {$wpdb->prefix}woocommerce_scenario_components sc INNER JOIN {$wpdb->prefix}woocommerce_scenario_component_options sco ON sc.id = sco.scenario_component_id WHERE sc.component_id = %d AND sc.allow_all = %d AND sc.modifier = %s AND sco.option_id != %d", $this->get_id(), 0, 'not-in', -1 ) );
			
			$this->fetched_outsiders = true;
			
		}
		
	}
	
	public function fetched_outsiders() {
		
		return $this->fetched_outsiders;
		
	}
	
	// Save Associations
	
	public function save_options( $options = array() ) {
		
		if( ! $this->get_id() ) {
			
			return false;
			
		}
		
		$this->delete_options( $options );
		
		foreach($options as $option) {
			
			$option['component_id'] = $this->get_id();
			
			$object = new WC_CP_Option( $option );
			
			$object->save_all( $option );
			
			foreach( $object->get_errors() as $error ) {
				
				$this->add_error( $error );
				
			}
			
		}
		
	}
	
	public function save_field( $field = array() ) {
		
		if( ! $this->get_id() ) {
			
			return false;
			
		}
		
		$this->delete_field( $field );
		
		$field['component_id'] = $this->get_id();
		
		$object = new WC_CP_Component_Field( $field );
			
		$object->save();
		
	}
	
	public function save_components( $components = array() ) {
		
		if( ! $this->get_id() ) {
			
			return false;
			
		}
		
		$this->delete_components( $components );
		
		foreach($components as $component) {
			
			$component['component_id'] = $this->get_id();
			$component['product_id'] = $this->get_product_id();
			
			$object = new self( $component );
			
			$object->save_all( $component );
			
		}
		
	}
	
	// Delete Associations
	
	public function delete_options( $options = array() ) {
		
		global $wpdb;
		
		$end = '';
		
		if( $options ) {
		
			$option_ids = implode( "', '", array_filter( array_map( function($option) {
				
				return ! empty( $option['id'] ) ? $option['id'] : null;
			
			}, $options ) ) );
			
			$end .= "AND id NOT IN ( '$option_ids' ) ";
			
		}
		
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_component_options WHERE component_id = %s $end", $this->get_id() ) );
		
	}
	
	public function delete_field( $field = array() ) {
		
		global $wpdb;
		
		$end = '';
		
		if( $field ) {
		
			$field_id = ! empty( $field['id'] ) ? $field['id'] : null;
			
			$end .= "AND id NOT IN ( '$field_id' ) ";
			
		}
		
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_component_field WHERE component_id = %s $end", $this->get_id() ) );
		
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
		
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_components WHERE component_id = %s $end", $this->get_id() ) );
		
	}
	
	// Save Methods
	
	public function save_all( $data = array() ) {
		
		if( $data ) {
			
			$this->populate( $data );
			
		}
		
		$this->save();
				
		switch( $this->get_source() ) {
			
			case 'subcomponents' :
			
				$components = isset( $data[ 'components' ] ) ? $data[ 'components' ] : array();
			
				$this->save_components( $components );
				
			break;
			
			default :
			
				$options = isset( $data[ 'options' ] ) ? $data[ 'options' ] : array();
			
				$this->save_options( $options );	
				
				if( in_array( $this->get_style(), array( 'number', 'text') ) ) {
					
					$field = isset( $data[ 'field' ] ) ? $data[ 'field' ] : array();
					
					$this->save_field( $field );
					
				}
				
			break;
			
		}
		
	}
	
	public function save() {
		
		global $wpdb;
		
		$table = $this->get_table();
		
		$data = array(
			'id' => $this->get_id(),
			'ref' => $this->get_ref(),
			'component_id' => $this->get_component_id(),
			'product_id' => $this->get_product_id(),
			'source' => $this->get_source(),
			'style' => $this->get_style(),
			'title' => $this->get_title(),
			'description' => $this->get_description(),
			'optional' => $this->is_optional(),
			'sovereign' => $this->is_sovereign(),
			'affect_sku' => $this->affect_sku(),
			'sku_order' => $this->get_sku_order(),
			'sku_default' => $this->get_sku_default(),
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
		
	}
	
	// Helpers
	
	public function add_error( $message ) {
		
		$this->errors[] = $message;
		
	}
	
	public function get_errors() {
		
		return $this->errors;
		
	}
	
	public function has_source( $source ) {
		
		return $this->get_source() === $source;
		
	}
	
	// Get Properties
	
	public function get_id() {
		
		return $this->id;
		
	}
	
	public function get_component_id() {
		
		return $this->component_id;
		
	}
	
	public function get_ref() {
		
		return $this->ref;
		
	}
	
	public function get_product_id() {
		
		return $this->product_id;
		
	}
	
	public function get_title() {
		
		return $this->title;
		
	}
	
	public function get_style() {
		
		return $this->style;
		
	}
	
	public function get_description() {
		
		return $this->description;
		
	}
	
	public function is_optional() {
		
		return $this->optional;
		
	}
	
	public function is_sovereign() {
		
		return $this->sovereign;
		
	}
	
	public function affect_sku() {
		
		return $this->affect_sku;
		
	}
	
	public function get_sku_order() {
		
		return $this->sku_order;
		
	}
	
	public function get_sku_default() {
		
		return $this->sku_default;
		
	}
	
	public function get_source() {
		
		return $this->source;
		
	}
	
	public function get_position() {
		
		return $this->position;
		
	}
	
}