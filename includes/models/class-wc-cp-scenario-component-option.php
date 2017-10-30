<?php
	
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_CP_Scenario_Component_Option {
	
	var $id = null;
	var $scenario_component_id = null;
	var $option_id = null;
	
	public function __construct( $option = null ) {
		
		if( is_numeric( $option ) ) {
				
			global $wpdb;
			
			$table = $this->get_table();
			
			$option = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %s", $option ), ARRAY_A ); 
			
		}
		
		if( $option ) {
			
			$this->populate( $option );
			
		}
		
	}
	
	public function populate( $option = array() ) {
		
		$this->id = ! empty( $option['id'] ) ? $option['id'] : $this->get_id();
		$this->scenario_component_id = ! empty( $option['scenario_component_id'] ) ? $option['scenario_component_id'] : $this->get_scenario_component_id();
		$this->option_id = ! empty( $option['option_id'] ) ? $option['option_id'] : $this->get_option_id();
		
		return $this;
		
	}
	
	protected function get_table() {
		
		global $wpdb;
		
		return "{$wpdb->prefix}woocommerce_scenario_component_options";
		
	}
	
	// Save Methods
	
	public function save() {
		
		global $wpdb;
		
		$table = $this->get_table();
		
		$data = array(
			'id' => $this->get_id(),
			'scenario_component_id' => $this->get_scenario_component_id(),
			'option_id' => $this->get_option_id(),
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
	
	// Get Properties
	
	public function get_id() {
		
		return $this->id;
		
	}
	
	public function get_scenario_component_id() {
		
		return $this->scenario_component_id;
		
	}
	
	public function get_option_id() {
		
		return $this->option_id;
		
	}
	
}