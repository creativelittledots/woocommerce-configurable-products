<?php
	
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_CP_Component_Field {
	
	var $id = null;
	var $component_id = null;
	var $label = '';
	var $placeholder = '';
	var $price_formula = '';
	var $value = '';
	var $step = 1;
	var $min = 0;
	var $max = null;
	var $suffix = '';
	
	var $scenarios = array();
	
	private $fetched_scenarios = false;
	
	public function __construct( $field = null, $associations = false ) {
		
		if( is_numeric( $field ) ) {
				
			global $wpdb;
			
			$table = $this->get_table();
			
			$field = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %s", $field ), ARRAY_A ); 
			
		}
		
		if( $field ) {
			
			$this->populate( $field );
			
			if( $associations ) {
				
				$this->load_scenarios();
				
			}
			
		}
		
	}
	
	public function populate( $field = array() ) {
		
		$this->id = ! empty( $field['id'] ) ? $field['id'] : $this->get_id();
		$this->component_id = ! empty( $field['component_id'] ) ? $field['component_id'] : $this->get_component_id();
		$this->label = ! empty( $field['label'] ) ? $field['label'] : $this->get_label();
		$this->placeholder = ! empty( $field['placeholder'] ) ? $field['placeholder'] : $this->get_placeholder();
		$this->price_formula = ! empty( $field['price_formula'] ) ? $field['price_formula'] : $this->get_price_formula();
		$this->value = ! empty( $field['value'] ) ? $field['value'] : $this->get_value();
		$this->step = ! empty( $field['step'] ) ? $field['step'] : $this->get_step();
		$this->min = ! empty( $field['min'] ) ? $field['min'] : $this->get_min();
		$this->max = ! empty( $field['max'] ) ? $field['max'] : $this->get_max();
		$this->suffix = ! empty( $field['suffix'] ) ? $field['suffix'] : $this->get_suffix();
		
		return $this;
		
	}
	
	protected function get_table() {
		
		global $wpdb;
		
		return "{$wpdb->prefix}woocommerce_component_field";
		
	}
	
	public function clean() {

		$this->id = null;
		
	}
	
	// Save Methods
	
	public function save() {
		
		global $wpdb;
		
		$table = $this->get_table();
		
		$data = array(
			'id' => $this->get_id(),
			'component_id' => $this->get_component_id(),
			'label' => $this->get_label(),
			'placeholder' => $this->get_placeholder(),
			'price_formula' => $this->get_price_formula(),
			'value' => $this->get_value(),
			'step' => $this->get_step(),
			'min' => $this->get_min(),
			'max' => $this->get_max(),
			'suffix' => $this->get_suffix()
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
	
	// Load Associations
	
	public function load_scenarios() {
		
		if( $this->get_id() && ! $this->fetched_scenarios() ) {
		
			global $wpdb;
			
			$inside = $wpdb->get_results( $wpdb->prepare( "SELECT sc.scenario_id FROM {$wpdb->prefix}woocommerce_scenario_components sc LEFT JOIN {$wpdb->prefix}woocommerce_scenario_component_options sco ON sc.id = sco.scenario_component_id WHERE sc.component_id = %d AND sc.allow_field = %d", $this->component_id, 1 ), ARRAY_A );
			
			$scenarios = wp_list_pluck( $inside, 'scenario_id' );

			$this->scenarios = array_unique($scenarios);
			$this->fetch_scenarios = true;
			
		}
		
	}
	
	public function fetched_scenarios() {
		
		return $this->fetched_scenarios;
		
	}
	
	// Get Properties
	
	public function get_id() {
		
		return $this->id;
		
	}
	
	public function get_component_id() {
		
		return $this->component_id;
		
	}
	
	public function get_label() {
		
		return $this->label;
		
	}
	
	public function get_placeholder() {
		
		return $this->placeholder;
		
	}
	
	public function get_price_formula() {
		
		return $this->price_formula;
		
	}
	
	public function get_value() {
		
		return $this->value;
		
	}
	
	public function get_step() {
		
		return $this->step;
		
	}
	
	public function get_min() {
		
		return $this->min;
		
	}
	
	public function get_max() {
		
		return $this->max;
		
	}
	
	public function get_suffix() {
		
		return $this->suffix;
		
	}
	
}