<?php
/**
 * Installation related functions and actions.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_CP_Install Class.
 */
class WC_CP_Install {
	
	/**
	 * Hook in tabs.
	 */
	public static function init() {
		
		if( ! is_admin() ) {
		
			add_action( 'init', array( __CLASS__, 'install' ), 20 );
			
		}
		
	}

	/**
	 * Install WC.
	 */
	public static function install() {

		//self::create_tables();
		
		//self::convert_composites();
		
		//self::convert_visibility();
		
	}
	
	private static function convert_visibility() {
		
		// make child products not visible
            
        $args = array(
            'type' => 'grouped',
            'showposts' => -1 
        );
        
        foreach(wc_get_products($args) as $product) {
            
            foreach($product->get_children() as $child_id) {
	            
	            $child = wc_get_product( $child_id );
	                      
	            $child->set_catalog_visibility( 'search' );
	            
	            $child->save();
	            
            }
            
        }
		
	}

	private static function create_tables() {
		
		global $wpdb;

		$wpdb->hide_errors();
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		dbDelta( self::get_schema() );
		
	}
	
	private static function convert_composites() {
		
		ini_set( 'memory_limit', '9999M' );
		
		set_time_limit(6000000);
		
		$args = array(
			'post_type' => 'product', 
			'tax_query' => [
				[
					'taxonomy' => 'product_type',
					'terms' => ['composite'],
					'field' => 'slug'
				]
			], 
			'showposts' => -1
		);
		
		foreach( get_posts($args) as $post ) {
			
			update_post_meta( $post->ID, '_old_composite', true );
			
			wp_set_object_terms( $post->ID, 'configurable', 'product_type' );
			
		}
		
		$args = array(
			'post_type' => 'product', 
			'meta_query' => [
				[
					'key' => '_old_composite',
					'value' => true
				]
			],
			'tax_query' => [
				[
					'taxonomy' => 'product_type',
					'terms' => ['configurable'],
					'field' => 'slug'
				]
			],
			'showposts' => -1
		);
		
		foreach( get_posts($args) as $post ) {
			
			$product = new WC_Product_Configurable( $post );

			$product->save_components( array_map( function($composite) use ($product) {
				
				$style = ! $composite['option_style'] ? $product->get_meta('_bto_selection_mode') : $composite['option_style'];
				$style = $style == 'dropdowns' ? 'dropdown' : $style;
				
				$component = array(
					'ref' => $product->get_id() . $composite['component_id'],
					'product_id' => $product->get_id(),
					'source' => 'default',
					'style' => $style,
					'title' => $composite['title'],
					'description' => $composite['description'],
					'optional' => $composite['optional'] === 'yes' ? 1 : 0,
					'sovereign' => ! empty( $composite['sovereign'] ) && $composite['sovereign'] === 'yes' ? 1 : 0,
					'affect_sku' => $composite['affect_sku'] === 'yes' ? 1 : 0,
					'sku_order' => $composite['affect_sku_order'],
					'sku_default' => ! empty( $composite['affect_sku_default'] ) ? $composite['affect_sku_default'] : null,
					'position' => $composite['position'],
					'options' => array_map(function($assigned_id) use ($composite) {
						
						return array(
							'source' => 'simple-product',
							'product_id' => $assigned_id,
							'affect_stock' => false,
							'selected' => ! empty( $composite['default_id'] ) && $composite['default_id'] == $assigned_id,
							'recommended' => ! empty( $composite['recommended_id'] ) && $composite['recommended_id'] == $assigned_id,
							'sku' => ! empty( $composite['sku_options'][$assigned_id] ) ? $composite['sku_options'][$assigned_id] : '',
							'price' => isset( $composite['price_options'][$assigned_id] ) ? $composite['price_options'][$assigned_id] : '',
							'formula' => ! empty( $composite['formula_options'][$assigned_id] ) ? $composite['formula_options'][$assigned_id] : '',
							'nested_options' => false,
							'position' => array_search($assigned_id, $composite['assigned_ids'])
						);
						
					}, ! empty( $composite['assigned_ids'] ) ? $composite['assigned_ids'] : [])
				);
				
				if( in_array( $composite['option_style'], array( 'text', 'number' ) ) ) {

					$component['field'] =  array(
						'label' => $composite['title'],
						'value' => ! empty( $composite['default_value'] ) ? $composite['default_value'] : null,
						'step' => $composite['step_value'],
						'min' => $composite['min_value'],
						'max' => $composite['max_value'],
						'suffix' => $composite['suffix'],
						'price_formula' => $composite['price_formula'],
					);
					
				}
				
				if( ! empty( $composite['tag_numbers'] ) && $composite['tag_numbers'] === 'yes' ) {
					
					$component = array(
						'product_id' => $product->get_id(),
						'source' => 'subcomponents',
						'title' => $composite['title'],
						'position' => $composite['position'],
						'components' => array(
							array_merge($component, array(
								'position' => 0
							)),
							array(
								'title' => 'Tag Numbers',
								'product_id' => $product->get_id(),
								'style' => 'text',
								'source' => 'default',
								'position' => 1,
								'optional' => true,
								'field' => array(
									'label' => 'Tag Number',
									'placeholder' => 'Please provide a tag number',
									'value' => $composite['default_value'],
									'step' => $composite['step_value'],
									'min' => $composite['min_value'],
									'max' => $composite['max_value']
								)
							)
						)
					);
					
				}
				
				return $component;
				
			}, $product->get_meta( '_bto_data', true ) ) );
			
			$product->save_scenarios( array_map( function($scenario) use ($product) {
				
				$scenario = array(
					'product_id' => $product->get_id(),
					'title' => $scenario['title'],
					'description' => $scenario['description'],
					'active' => $scenario['scenario_actions']['compat_group']['is_active'] === 'yes' ? 1 : 0,
					'position' => $scenario['position'],
					'components' => array_map(function($component_id, $component_options) use ($scenario, $product) {
						global $wpdb;
						$modifier = $scenario['modifier'][$component_id];
						$component_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}woocommerce_components WHERE ref = %s", $product->get_id() . $component_id ) );
						$allow_all = $modifier == 'in' && in_array( 0, $component_options );
						return array(
							'component_id' => $component_id,
							'modifier' => $modifier,
							'allow_all' => $allow_all,
							'options' => $allow_all ? array() : array_map(function($option_id) use($wpdb, $component_id)  {
								return $option_id > -1 ? $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}woocommerce_component_options WHERE product_id = %s AND component_id = %s", $option_id, $component_id ) ) : $option_id;
							}, $component_options)
						);
					}, array_keys($scenario['component_data']), $scenario['component_data'])
				);
				
				return $scenario;
				
			}, $product->get_meta( '_bto_scenario_data', true ) ) );
			
			update_post_meta( $product->get_id(), '_wc_cp_per_product_pricing', $product->get_meta( '_per_product_pricing_bto' ) );
			update_post_meta( $product->get_id(), '_is_synced', false );
			update_post_meta( $product->get_id(), '_wc_cp_build_sku', $product->get_meta( '_bto_build_sku' ) );
			update_post_meta( $product->get_id(), '_wc_cp_sku_start', $product->get_meta( '_bto_sku_start' ) );
			
		}
		
	}

	/**
	 * Get Table schema.
	 * https://github.com/woothemes/woocommerce/wiki/Database-Description/
	 * @return string
	 */
	private static function get_schema() {
		
		global $wpdb;

		$tables = "
			CREATE TABLE {$wpdb->prefix}woocommerce_components (
				id bigint(20) NOT NULL auto_increment,
				ref varchar(200) NULL,
				component_id bigint(20) NULL,
				source varchar(255) NOT NULL DEFAULT 'default',
				product_id bigint(20) NOT NULL,
				style varchar(200) NULL,
				title varchar(200) NOT NULL,
				description longtext NULL,
				optional tinyint(1) NOT NULL DEFAULT 0,
				sovereign tinyint(1) NOT NULL DEFAULT 0,
				affect_sku tinyint(1) NOT NULL DEFAULT 0,
				sku_order tinyint(1) NOT NULL DEFAULT 0,
				sku_default tinyint(1) NULL,
				position int(11) NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				FOREIGN KEY (component_id) REFERENCES {$wpdb->prefix}woocommerce_components(component_id),
				FOREIGN KEY (product_id) REFERENCES {$wpdb->posts}(product_id)
			);
			CREATE TABLE {$wpdb->prefix}woocommerce_component_field (
				id bigint(20) NOT NULL auto_increment,
				component_id bigint(20) NOT NULL,
				label varchar(255) NULL,
				placeholder varchar(255) NULL,
				price_formula varchar(255) NULL,
				value varchar(255) NULL,
				step bigint(20) NOT NULL DEFAULT '1',
				min bigint(20) NOT NULL DEFAULT '0',
				max bigint(20) NULL,
				suffix varchar(255) NULL,
				PRIMARY KEY  (id),
				FOREIGN KEY (component_id) REFERENCES {$wpdb->prefix}woocommerce_components(component_id)
			);
			CREATE TABLE {$wpdb->prefix}woocommerce_component_options (
				id bigint(20) NOT NULL auto_increment,
				component_id bigint(20) NULL,
				option_id bigint(20) NULL,
				source varchar(255) NOT NULL DEFAULT 'simple-product',
				product_id bigint(20) NULL,
				value varchar(200) NOT NULL,
				label varchar(200) NOT NULL,
				affect_stock tinyint(1) NOT NULL DEFAULT 0,
				selected tinyint(1) NOT NULL DEFAULT 0,
				recommended tinyint(1) NOT NULL DEFAULT 0,
				sku varchar(200) NULL,
				price varchar(200) NULL,
				formula varchar(200) NULL,
				nested_options tinyint(1) NOT NULL DEFAULT 0,
				position int(11) NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				FOREIGN KEY (component_id) REFERENCES {$wpdb->prefix}woocommerce_components(component_id),
				FOREIGN KEY (option_id) REFERENCES {$wpdb->prefix}woocommerce_component_options(option_id),
				FOREIGN KEY (product_id) REFERENCES {$wpdb->posts}(product_id)
			);
			CREATE TABLE {$wpdb->prefix}woocommerce_scenarios (
				id bigint(20) NOT NULL auto_increment,
				product_id bigint(20) NOT NULL,
				title varchar(200) NOT NULL,
				description longtext NULL,
				active tinyint(1) NOT NULL DEFAULT 0,
				position int(11) NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				FOREIGN KEY (product_id) REFERENCES {$wpdb->posts}(product_id)
			);
			CREATE TABLE {$wpdb->prefix}woocommerce_scenario_components (
				id bigint(20) NOT NULL auto_increment,
				component_id bigint(20) NOT NULL,
				scenario_id bigint(20) NOT NULL,
				allow_all tinyint(1) NOT NULL DEFAULT 0,
				allow_field tinyint(1) NOT NULL DEFAULT 0,
				modifier varchar(200) NOT NULL DEFAULT 'in',
				PRIMARY KEY  (id),
				FOREIGN KEY (component_id) REFERENCES {$wpdb->prefix}woocommerce_components(component_id),
				FOREIGN KEY (scenario_id) REFERENCES {$wpdb->prefix}woocommerce_scenarios(scenario_id)
			);
			CREATE TABLE {$wpdb->prefix}woocommerce_scenario_component_options (
				id bigint(20) NOT NULL auto_increment,
				scenario_component_id bigint(20) NOT NULL,
				option_id bigint(20) NOT NULL,
				PRIMARY KEY  (id),
				FOREIGN KEY (scenario_component_id) REFERENCES {$wpdb->prefix}woocommerce_scenario_components(scenario_component_id)
			);
		";

		return $tables;
		
	}

}

WC_CP_Install::init();
