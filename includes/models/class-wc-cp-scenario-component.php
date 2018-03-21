<?php
	
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_CP_Scenario_Component {
	
	var $id = null;
	var $scenario_id = null;
	var $component_id = null;
	var $modifier = 'in';
	var $allow_all = false;
	var $allow_field = false;
	var $closed = true;
	
	var $options = array();
	var $component = null;
	
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
				
			}
			
		}
		
	}
	
	public function populate( $component = array() ) {
		
		$this->id = ! empty( $component['id'] ) ? $component['id'] : $this->get_id();
		$this->scenario_id = ! empty( $component['scenario_id'] ) ? $component['scenario_id'] : $this->get_scenario_id();
		$this->component_id = ! empty( $component['component_id'] ) ? $component['component_id'] : $this->get_component_id();
		$this->modifier = ! empty( $component['modifier'] ) ? $component['modifier'] : $this->get_modifier();
		$this->allow_all = ! empty( $component['allow_all'] ) ? $component['allow_all'] : $this->allow_all();
		$this->allow_field = ! empty( $component['allow_field'] ) ? $component['allow_field'] : $this->allow_field();
		
		return $this;
		
	}
	
	protected function get_table() {
		
		global $wpdb;
		
		return "{$wpdb->prefix}woocommerce_scenario_components";
		
	}
	
	public function clean() {
		
		foreach($this->get_options() as &$option) {
			
			$option->clean();
			
		}
		
		$this->id = null;
		
	}
	
	// Load Associations
	
	public function load_options() {
		
		if( $this->get_id() && ! $this->get_options( false ) ) {
					
			global $wpdb;
		
			$options = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_scenario_component_options WHERE scenario_component_id = %s", $this->get_id() ), ARRAY_A );
			
			$this->options = array_map(function($option) {
				
				return $option['option_id'];
				
			}, $options);
			
		}
		
	}
	
	public function load_component() {
		
		if( $this->get_component_id() && ! $this->get_component( false ) ) {
			
			$this->component = wc_cp_get_component( $this->get_component_id() );
			
		}
		
	}
	
	// Save Associations
	
	public function save_options( $options = array() ) {
		
		if( ! $this->get_id() ) {
			
			return false;
			
		}
		
		global $wpdb;
		
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_scenario_component_options WHERE scenario_component_id = %s", $this->get_id() ) );
		
		foreach($options as $option) {
			
			if( is_numeric( $option ) ) {
				
				$option = array(
					'option_id' => $option
				);
				
			}
			
			$option['scenario_component_id'] = $this->get_id();
			
			$object = new WC_CP_Scenario_Component_Option( $option );
			
			$object->save();
			
		}
		
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
			'scenario_id' => $this->get_scenario_id(),
			'component_id' => $this->get_component_id(),
			'modifier' => $this->get_modifier(),
			'allow_all' => $this->allow_all(),
			'allow_field' => $this->allow_field()
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
	
	//Helpers
		
	public function is_modifier( $modifier ) {
		
		return $this->get_modifier() === $modifier;
		
	}
	
	public function is_allowed( $option_id ) {
		
		return $this->allow_all() || $this->in_scenario( $option_id ) || $this->out_scenario( $option_id );
		
	}
	
	public function in_scenario( $option_id ) {
		
		return $this->is_modifier( 'in' ) && $this->has_option_id( $option_id );
		
	}
	
	public function out_scenario( $option_id ) {
		
		return ! $this->is_modifier( 'in' ) && ! $this->has_option_id( $option_id );
		
	}
	
	public function has_option_id( $option_id ) {
		
		return in_array( $option_id, $this->get_options() );
		
	}
	
	public function has_optional_option() {
		
		return in_array( -1, $this->get_options() );
		
	}
	
	// Get Properties
	
	public function get_id() {
		
		return $this->id;
		
	}
	
	public function get_scenario_id() {
		
		return $this->scenario_id;
		
	}
	
	public function get_component_id() {
		
		return $this->component_id;
		
	}
	
	public function get_options( $load = true ) {
		
		if( $load ) {
			
			$this->load_options();
			
		}
		
		return $this->options;
		
	}
	
	public function get_component( $load = true ) {
		
		if( $load ) {
		
			$this->load_component();
			
		}
		
		return $this->component;
		
	}
	
	public function allow_all() {
		
		return $this->allow_all;
		
	}
	
	public function allow_field() {
		
		return $this->allow_field;
		
	}
	
	public function get_modifier() {
		
		return $this->modifier;
		
	}
	
}