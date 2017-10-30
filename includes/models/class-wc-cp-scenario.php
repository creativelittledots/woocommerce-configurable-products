<?php
	
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_CP_Scenario {
	
	var $id = null;
	var $product_id = null;
	var $title = 'Untitled Scenario';
	var $description = '';
	var $active = false;
	var $position = 0;
	
	var $components = array();
	
	public function __construct( $scenario = null, $associations = false ) {
		
		if( is_numeric( $scenario ) ) {
				
			global $wpdb;
			
			$table = $this->get_table();
			
			$scenario = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %s", $scenario ), ARRAY_A ); 
			
		}
		
		if( $scenario ) {
			
			$this->populate( $scenario );
			
			if( $associations ) {
				
				$this->load_components();
				
			}
			
		}
		
	}
	
	public function populate( $scenario = array() ) {
		
		$this->id = ! empty( $scenario['id'] ) ? $scenario['id'] : $this->get_id();
		$this->product_id = ! empty( $scenario['product_id'] ) ? $scenario['product_id'] : $this->get_product_id();
		$this->title = ! empty( $scenario['title'] ) ? strip_tags ( stripslashes( $scenario['title'] ) ) : $this->get_title();
		$this->description = ! empty( $scenario['description'] ) ? wp_kses_post( stripslashes( $scenario['description'] ) ) : $this->get_description();
		$this->active = ! empty( $scenario['active'] ) ? $scenario['active'] : $this->is_active();
		$this->position = ! empty( $scenario['position'] ) ? $scenario['position'] : $this->get_position();
		
		return $this;
		
	}
	
	protected function get_table() {
		
		global $wpdb;
		
		return "{$wpdb->prefix}woocommerce_scenarios";
		
	}
	
	// Load Associations
	
	public function load_components() {
		
		if( $this->get_id() && ! $this->get_components( false ) ) {
					
			global $wpdb;
		
			$components = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_scenario_components WHERE scenario_id = %s", $this->get_id() ), ARRAY_A );
			
			$this->components = array_map(function($component) {
				
				return new WC_CP_Scenario_Component($component, true);
				
			}, $components);
			
		}
		
	}
	
	// Save Associations
	
	public function save_components( $components = array() ) {
		
		if( ! $this->get_id() ) {
			
			return false;
			
		}
		
		global $wpdb;
		
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_scenario_components WHERE scenario_id = %s", $this->get_id() ) );
		
		foreach($components as $component) {
			
			$component = $this->inverse( $component );
			
			$component['scenario_id'] = $this->get_id();
			
			$object = new WC_CP_Scenario_Component( $component );
			
			$object->save_all( $component );
			
		}
		
	}
	
	// Save Methods
	
	public function save_all( $data = array() ) {
		
		if( $data ) {
			
			$this->populate( $data );
			
		}
		
		$this->save();
				
		$components = isset( $data[ 'components' ] ) ? $data[ 'components' ] : array();
			
		$this->save_components( $components );
		
	}
	
	public function save() {
		
		global $wpdb;
		
		$table = $this->get_table();
		
		$data = array(
			'id' => $this->get_id(),
			'product_id' => $this->get_product_id(),
			'title' => $this->get_title(),
			'description' => $this->get_description(),
			'active' => $this->is_active(),
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
	
	public function inverse( $data ) {
		
		if( empty( $data['allow_all'] ) ) {
		
			if( ! empty( $data['component_id'] ) ) {
				
				$component = new WC_CP_Component( $data['component_id'] );
				
				foreach( $component->get_options() as $option ) {
					
					if( ! in_array( $option->id, $data['options'] ) ) {
						
						$data['excluded'][] = $option->id;
						
					}
					
				}
				
			}
			
			if( empty( $data['excluded'] ) || count( $data['excluded'] ) === 0 ) {
					
				// All are included so just set as 'All Product & Variations'
			
				$data['options'] = array();
				$data['allow_all'] = true;
				
			} else if( count( $data['options'] ) > count( $data['excluded'] ) ) {
				
				// There are less products in the exclusion than inclusion - better to store it as an exclusive
				
				$data['modifier'] = 'not-in';
				$data['options'] = $data['excluded'];
				
			}
				
			unset( $data['excluded'] );
			
		} else {
			
			$data['options'] = array();
			
		}
		
		return $data;
		
	}
	
	// Get Properties
	
	public function get_id() {
		
		return $this->id;
		
	}
	
	public function get_product_id() {
		
		return $this->product_id;
		
	}
	
	public function get_title() {
		
		return $this->title;
		
	}
	
	public function is_active() {
		
		return $this->active;
		
	}
	
	public function get_position() {
		
		return $this->position;
		
	}
	
	public function get_description() {
		
		return $this->description;
		
	}
	
	public function get_components( $load = true ) {
		
		if( $load ) {
		
			$this->load_components();
			
		}
		
		return $this->components;
		
	}
	
}