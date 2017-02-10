<?php
/**
 * Admin filters and functions.
 *
 * @class 	WC_CP_Admin
 * @version 3.1.0
 * @since   2.2.2
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Option {
	
	var $id = null;
	var $component_id = null;
	var $source = 'product';
	var $entity_id = null;
	var $attribute_id = null;
	var $selected = false;
	var $recommended = false;
	var $sku = null;
	var $price = null;
	var $formula = null;
	var $position = 0;
	
	public function __construct( $option ) {
		
		$this->component_id = ! empty( $option['component_id'] ) ? $option['component_id'] : $this->component_id;
		$this->source = ! empty( $option['source'] ) ? $option['source'] : $this->source;
		$this->entity_id = ! empty( $option['entity_id'] ) ? $option['entity_id'] : $this->entity_id;
		$this->attribute_id = ! empty( $option['attribute_id'] ) ? $option['attribute_id'] : $this->attribute_id;
		$this->selected = ! empty( $option['selected'] ) ? $option['selected'] : $this->selected;
		$this->recommended = ! empty( $option['recommended'] ) ? $option['recommended'] : $this->recommended;
		$this->sku = ! empty( $option['sku'] ) ? $option['sku'] : $this->sku;
		$this->price = ! empty( $option['price'] ) ? $option['price'] : $this->price;
		$this->formula = ! empty( $option['formula'] ) ? $option['formula'] : $this->formula;
		$this->position = ! empty( $option['position'] ) ? $option['position'] : $this->position;
		
	}
	
	public function save() {
		
		try {
		
			$table = "{$wpdb->prefix}woocommerce_component_options";
			
			if ( $this->source !== 'attribute' || ! wc_attribute_taxonomy_name_by_id( $this->attribute_id ) ) {
				
				$this->attribute_id = null;
				
			}
			
			if ( in_array( $this->source, array( 'simple_product', 'configurable_product' ) ) && ! wc_get_product( $this->entity_id ) ) {
						
				throw new Exception( 'This is not a valid Product ID' );
				
			}
			
			if ( $this->source === 'attribute' && ! get_term_by_id( $id ) ) {
				
				throw new Exception( 'This is not a valid Attribute Term ID' );
				
			}
			
			$data = array(
				'component_id' => $this->component_id,
				'product_id' => $this->product_id,
				'style' => $this->style,
				'title' => $this->title,
				'description' => $this->description,
				'optional' => $this->optional,
				'sovereign' => $this->sovereign,
				'nest_attributes' => $this->nest_attributes,
				'affect_sku' => $this->affect_sku,
				'sku_order' => $this->sku_order,
				'sku_default' => $this->sku_default,
				'position' => $this->position
			);
			
			if( $this->id ) {
				
				$wpdb->update( $table, $data, array(
					'id' => $this->id
				) );
				
			} else {
				
				$wpdb->insert( $table, $data );
				
				$this->id = $wpdb->last_insert_id;
				
			}
			
		} catch(Exception $e) {
			
			
			
		}
		
	}
	
}

class Component {
	
	var $id = null;
	var $component_id = null;
	var $product_id = null;
	var $style = -1;
	var $title = 'Untitled Component';
	var $description = '';
	var $optional = false;
	var $sovereign = false;
	var $affect_sku = false;
	var $sku_order = '';
	var $sku_default = '';
	var $position = 0;
	
	public function __construct( $component ) {
		
		$this->component_id = ! empty( $component['component_id'] ) ? $component['component_id'] : $this->component_id;
		$this->product_id = ! empty( $component['product_id'] ) ? $component['product_id'] : $this->product_id;
		$this->style = ! empty( $component['style'] ) ? $component['style'] : $this->style;
		$this->title = ! empty( $component['title'] ) ? strip_tags ( stripslashes( $component['title'] ) ) : $this->title;
		$this->description = ! empty( $component['description'] ) ? wp_kses_post( stripslashes( $component['description'] ) ) : $this->description;
		$this->optional = ! empty( $component['optional'] ) ? $component['optional'] : $this->optional;
		$this->sovereign = ! empty( $component['sovereign'] ) ? $component['sovereign'] : $this->sovereign;
		$this->affect_sku = ! empty( $component['affect_sku'] ) ? $component['affect_sku'] : $this->affect_sku;
		$this->sku_order = ! empty( $component['sku_order'] ) ? $component['sku_order'] : $this->sku_order;
		$this->sku_default = ! empty( $component['sku_default'] ) ? $component['sku_default'] : $this->sku_default;
		$this->position = ! empty( $component['position'] ) ? $component['position'] : $this->position;
		
	}
	
	public function save() {
		
		$table = "{$wpdb->prefix}woocommerce_component_attributes";
		
		$data = array(
			'component_id' => $this->component_id,
			'product_id' => $this->product_id,
			'style' => $this->style,
			'title' => $this->title,
			'description' => $this->description,
			'optional' => $this->optional,
			'sovereign' => $this->sovereign,
			'nest_attributes' => $this->nest_attributes,
			'affect_sku' => $this->affect_sku,
			'sku_order' => $this->sku_order,
			'sku_default' => $this->sku_default,
			'position' => $this->position
		);
		
		if( $this->id ) {
			
			$wpdb->update( $table, $data, array(
				'id' => $this->id
			) );
			
		} else {
			
			$wpdb->insert( $table, $data );
			
			$this->id = $wpdb->last_insert_id;
			
		}
		
	}
	
	public function saveOptions($options) {
		
		global $wpdb;
		
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_component_options WHERE component_id = {$this->id}" );
		
		foreach($options as $option) {
			
			$option = new Option($option);
			
			$option->save();
			
		}
		
	}
	
}

class Scenario {
	
	public function __construct() {
		
		
	}
	
	public function saveAll() {
		
		$scenario_id = isset ( $scenario_post_data[ 'scenario_id' ] ) ? stripslashes( $scenario_post_data[ 'scenario_id' ] ) : ( current_time( 'timestamp' ) + $counter );
		$counter++;

		$scenarios[ $scenario_id ] = array();

		// Save scenario title
		if ( isset( $scenario_post_data[ 'title' ] ) && ! empty( $scenario_post_data[ 'title' ] ) ) {
			$scenarios[ $scenario_id ][ 'title' ] = strip_tags ( stripslashes( $scenario_post_data[ 'title' ] ) );
		} else {
			unset( $scenarios[ $scenario_id ] );
			$this->save_errors[] = $this->add_admin_error( __( 'Please give a valid Name to all Scenarios before saving.', 'woocommerce-configurable-products' ) );
			continue;
		}

		// Save scenario description
		if ( isset( $scenario_post_data[ 'description' ] ) && ! empty( $scenario_post_data[ 'description' ] ) ) {
			$scenarios[ $scenario_id ][ 'description' ] = wp_kses_post( stripslashes( $scenario_post_data[ 'description' ] ) );
		} else {
			$scenarios[ $scenario_id ][ 'description' ] = '';
		}

		// Prepare position data
		if ( isset( $scenario_post_data[ 'position' ] ) ) {
			$scenario_ordering[ ( int ) $scenario_post_data[ 'position' ] ] = $scenario_id;
		} else {
			$scenario_ordering[ count( $scenario_ordering ) ] = $scenario_id;
		}

		$scenarios[ $scenario_id ][ 'scenario_actions' ] = array();

		// Save scenario action(s)
		if ( isset( $scenario_post_data[ 'scenario_actions' ][ 'compat_group' ] ) ) {

			if ( ! empty( $scenario_post_data[ 'scenario_actions' ][ 'compat_group' ][ 'is_active' ] ) ) {
				$scenarios[ $scenario_id ][ 'scenario_actions' ][ 'compat_group' ][ 'is_active' ] = 'yes';
				$compat_group_actions_exist = true;
			}
		} else {
			$scenarios[ $scenario_id ][ 'scenario_actions' ][ 'compat_group' ][ 'is_active' ] = 'no';
		}

		// Save component options in scenario
		$scenarios[ $scenario_id ][ 'component_data' ] = array();

		foreach ( $ordered_bto_data as $component_id => $group_data ) {

			// Save modifier flag
			if ( isset( $scenario_post_data[ 'modifier' ][ $component_id ] ) && $scenario_post_data[ 'modifier' ][ $component_id ] === 'not-in' ) {

				if ( ! empty( $scenario_post_data[ 'component_data' ][ $component_id ] ) ) {

					if ( $woocommerce_configurable_products->api->scenario_contains_product( $scenario_post_data, $component_id, 0 ) ) {
						$scenarios[ $scenario_id ][ 'modifier' ][ $component_id ] = 'in';
					} else {
						$scenarios[ $scenario_id ][ 'modifier' ][ $component_id ] = 'not-in';
					}
				} else {
					$scenarios[ $scenario_id ][ 'modifier' ][ $component_id ] = 'in';
				}

			} else {
				$scenarios[ $scenario_id ][ 'modifier' ][ $component_id ] = 'in';
			}


			$all_active = false;

			if ( ! empty( $scenario_post_data[ 'component_data' ][ $component_id ] ) ) {

				$scenarios[ $scenario_id ][ 'component_data' ][ $component_id ] = array();

				if ( $woocommerce_configurable_products->api->scenario_contains_product( $scenario_post_data, $component_id, 0 ) ) {

					$scenarios[ $scenario_id ][ 'component_data' ][ $component_id ][] = 0;
					$all_active = true;
				}

				if ( $all_active ) {
					continue;
				}

				if ( $woocommerce_configurable_products->api->scenario_contains_product( $scenario_post_data, $component_id, -1 ) ) {
					$scenarios[ $scenario_id ][ 'component_data' ][ $component_id ][] = -1;
				}

				// Run query to get component option ids
				$component_options = $woocommerce_configurable_products->api->get_component_options( $group_data );


				foreach ( $scenario_post_data[ 'component_data' ][ $component_id ] as $item_in_scenario ) {

					if ( (int) $item_in_scenario === -1 || (int) $item_in_scenario === 0 ) {
						continue;
					}

					// Get product
					$product_in_scenario = get_product( $item_in_scenario );

					if ( $product_in_scenario->product_type === 'variation' ) {

						$parent_id = $product_in_scenario->id;

						if ( $parent_id && in_array( $parent_id, $component_options ) && ! in_array( $parent_id, $scenario_post_data[ 'component_data' ][ $component_id ] ) ) {
							$scenarios[ $scenario_id ][ 'component_data' ][ $component_id ][] = $item_in_scenario;
						}

					} else {

						if ( in_array( $item_in_scenario, $component_options ) ) {
							$scenarios[ $scenario_id ][ 'component_data' ][ $component_id ][] = $item_in_scenario;
						}
					}
				}

			} else {

				$scenarios[ $scenario_id ][ 'component_data' ][ $component_id ]   = array();
				$scenarios[ $scenario_id ][ 'component_data' ][ $component_id ][] = 0;
			}

		}

		// Process custom data - add custom errors via $woocommerce_configurable_products->admin->add_error()
		$scenarios[ $scenario_id ] = apply_filters( 'woocommerce_configuration_process_scenario_data', $scenarios[ $scenario_id ], $scenario_post_data, $scenario_id, $ordered_bto_data, $post_id );
		
	}
	
}

class WC_CP_Admin {

	private $save_errors = array();

	public function __construct() {

		// Admin jquery
		add_action( 'admin_enqueue_scripts', array( $this, 'configuration_admin_scripts' ) );

		// Creates the admin Components and Scenarios panel tabs
		add_action( 'woocommerce_product_write_panel_tabs', array( $this, 'configuration_write_panel_tabs' ) );

		// Adds the base price options
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'configuration_pricing_options' ) );

		// Creates the admin Components and Scenarios panels
		add_action( 'woocommerce_product_write_panels', array( $this, 'configuration_write_panel' ) );

		// Allows the selection of the 'configuration product' type
		add_filter( 'product_type_options', array( $this, 'add_configuration_type_options' ) );

		// Processes and saves the necessary post metas from the selections made above
		add_action( 'woocommerce_process_product_meta_configuration', array( $this, 'process_configuration_meta' ) );

		// Allows the selection of the 'configuration product' type
		add_filter( 'product_type_selector', array( $this, 'add_configuration_type' ) );

		// Ajax save configuration config
		add_action( 'wp_ajax_woocommerce_bto_configuration_save', array( $this, 'ajax_configuration_save' ) );

		// Ajax add scenario
		add_action( 'wp_ajax_woocommerce_add_configuration_scenario', array( $this, 'ajax_add_scenario' ) );

		// Ajax search default component id
		add_action( 'wp_ajax_woocommerce_json_search_default_component_option', array( $this, 'json_search_default_component_option' ) );
		
		// Ajax search attributes
		add_action( 'wp_ajax_woocommerce_json_search_attributes', array($this, 'json_search_attributes' ) );
		
		// Ajax search attribute terms
		add_action( 'wp_ajax_woocommerce_json_search_attribute_terms', array($this, 'json_search_attribute_terms' ) );

		// Ajax search products and variations in scenarios
		add_action( 'wp_ajax_woocommerce_json_search_component_options_in_scenario', array( $this, 'json_search_component_options_in_scenario' ) );

		// Template override scan path
		add_filter( 'woocommerce_template_overrides_scan_paths', array( $this, 'configuration_template_scan_path' ) );

		// Scenario options
		add_action( 'woocommerce_configuration_scenario_admin_info_html', array( $this, 'scenario_info' ), 10, 4 );
		add_action( 'woocommerce_configuration_scenario_admin_config_html', array( $this, 'scenario_config' ), 10, 4 );
		add_action( 'woocommerce_configuration_scenario_admin_actions_html', array( $this, 'scenario_actions' ), 10, 4 );
		
		add_action( 'admin_post_export_scenarios', array($this, 'export_scenarios') ); // For Export Scenarios
		add_action( 'woocommerce_process_product_meta_configuration', array($this, 'import_scenarios'), 15); // For Import Scenarios
		add_action( 'post_edit_form_tag', array($this, 'add_enctype_to_edit_form') ); // For Import Scenarios
		
		
        add_filter( 'woocommerce_product_data_tabs', array($this, 'configuration_product_tabs') );
			
	}
	
	function configuration_product_tabs( $tabs ) {

    	$tabs['inventory']['class'] = [];

    	return $tabs;
    	
    }

	/**
	 * Add scenario title and description options.
	 *
	 * @param  int    $id
	 * @param  array  $scenario_data
	 * @param  array  $configuration_data
	 * @param  int    $product_id
	 * @return void
	 */
	function scenario_actions( $id, $scenario_data, $configuration_data, $product_id ) {

		$defines_compat_group = isset( $scenario_data[ 'scenario_actions' ][ 'compat_group' ][ 'is_active' ] ) ? $scenario_data[ 'scenario_actions' ][ 'compat_group' ][ 'is_active' ] : 'yes';

		?>
		<div class="scenario_action_compat_group" >
			<div class="form-field">
				<label for="scenario_action_compat_group_<?php echo $id; ?>">
					<?php echo __( 'Active', 'woocommerce-configurable-products' ); ?>
					<img class="wc_cp_help_tip" data-tip="<?php echo __( 'Toggle this Scenario on or off', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
				</label>
				<input type="checkbox" class="checkbox"<?php echo ( $defines_compat_group == 'yes' ? ' checked="checked"' : '' ); ?> name="scenarios[<?php echo $id; ?>][scenario_actions][compat_group][is_active]" <?php echo ( $defines_compat_group == 'yes' ? ' value="1"' : '' ); ?> />
			</div>
		</div>
		<?php

	}

	/**
	 * Add scenario title and description options.
	 *
	 * @param  int    $id
	 * @param  array  $scenario_data
	 * @param  array  $configuration_data
	 * @param  int    $product_id
	 * @return void
	 */
	function scenario_info( $id, $scenario_data, $configuration_data, $product_id ) {

		$title       = isset( $scenario_data[ 'title' ] ) ? $scenario_data[ 'title' ] : '';
		$position    = isset( $scenario_data[ 'position' ] ) ? $scenario_data[ 'position' ] : $id;
		$description = isset( $scenario_data[ 'description' ] ) ? $scenario_data[ 'description' ] : '';

		?>
		<div class="scenario_title">
			<div class="form-field">
				<label><?php echo __( 'Scenario Name', 'woocommerce-configurable-products' ); ?></label>
				<input type="text" class="scenario_title wc_cp_text_input" name="scenarios[<?php echo $id; ?>][title]" value="<?php echo $title; ?>"/>
				<input type="hidden" name="scenarios[<?php echo $id; ?>][position]" class="scenario_position" value="<?php echo $position; ?>"/>
			</div>
		</div>
		<div class="scenario_description">
			<div class="form-field">
				<label><?php echo __( 'Scenario Description', 'woocommerce-configurable-products' ); ?></label>
				<textarea class="scenario_description" name="scenarios[<?php echo $id; ?>][description]" id="scenario_description_<?php echo $id; ?>" placeholder="" rows="2" cols="20"><?php echo esc_textarea( $description ); ?></textarea>
			</div>
		</div>
		<?php
	}

	/**
	 * Add scenario config options.
	 *
	 * @param  int    $id
	 * @param  array  $scenario_data
	 * @param  array  $configuration_data
	 * @param  int    $product_id
	 * @return void
	 */
	function scenario_config( $id, $scenario_data, $configuration_data, $product_id ) {

		global $woocommerce_configurable_products;

		?><div class="scenario_config_group"><?php

			foreach ( $configuration_data as $component_id => $component_data ) {

				$modifier = '';

				if ( isset( $scenario_data[ 'modifier' ][ $component_id ] ) ) {

					$modifier = $scenario_data[ 'modifier' ][ $component_id ];

				} else {

					$exclude = isset( $scenario_data[ 'exclude' ][ $component_id ] ) ? $scenario_data[ 'exclude' ][ $component_id ] : 'no';

					if ( $exclude === 'no' ) {
						$modifier = 'in';
					} else {
						$modifier = 'not-in';
					}
				}

				?><div class="bto_scenario_selector">
					<div class="form-field">
						<label><?php
							echo apply_filters( 'woocommerce_configuration_component_title', $component_data[ 'title' ], $component_id, $product_id ); ?>
						</label>
						<div class="bto_scenario_modifier_wrapper bto_scenario_exclude_wrapper">
							<select class="bto_scenario_modifier bto_scenario_exclude" name="scenarios[<?php echo $id; ?>][modifier][<?php echo $component_id; ?>]">
								<option <?php selected( $modifier, 'in', true ); ?> value="in"><?php echo __( 'selection is' ); ?></option>
								<option <?php selected( $modifier, 'not-in', true ); ?> value="not-in"><?php echo __( 'selection is not' ); ?></option>
							</select>
						</div>
						<div class="wc_cp_panel-inner"><?php

							$component_options = $woocommerce_configurable_products->api->get_component_options( $component_data );

							if ( count( $component_options ) < 30 ) {

								$scenario_options    = array();
								$scenario_selections = array();

								if ( $component_data[ 'optional' ] == 'yes' ) {

									if ( $woocommerce_configurable_products->api->scenario_contains_product( $scenario_data, $component_id, -1 ) ) {
										$scenario_selections[] = -1;
									}

									$scenario_options[ -1 ] = __( 'None', 'woocommerce-configurable-products' );
								}

								if ( $woocommerce_configurable_products->api->scenario_contains_product( $scenario_data, $component_id, 0 ) ) {
									$scenario_selections[] = 0;
								}

								$scenario_options[ 0 ] = __( 'All Products and Variations', 'woocommerce-configurable-products' );

								foreach ( $component_options as $item_id ) {

									$title = $woocommerce_configurable_products->api->get_product_title( $item_id );

									if ( ! $title ) {
										continue;
									}

									// Get product type
									$terms        = get_the_terms( $item_id, 'product_type' );
									$product_type = ! empty( $terms ) && isset( current( $terms )->name ) ? sanitize_title( current( $terms )->name ) : 'simple';


									$product_title = $title;

									if ( $woocommerce_configurable_products->api->scenario_contains_product( $scenario_data, $component_id, $item_id ) ) {

										$scenario_selections[] = $item_id;
									}

									$scenario_options[ $item_id ] = $product_title;

									if ( $product_type == 'variable' ) {

										if ( ! empty( $variation_descriptions ) ) {

											foreach ( $variation_descriptions as $variation_id => $description ) {

												if ( $woocommerce_configurable_products->api->scenario_contains_product( $scenario_data, $component_id, $variation_id ) ) {
													$scenario_selections[] = $variation_id;
												}

												$scenario_options[ $variation_id ] = $description;
											}
										}
									}

								}

								$optional_tip = $component_data[ 'optional' ] === 'yes' ? sprintf( __( '<br/><strong>Pro Tip</strong>: Use the <strong>None</strong> option to control the <strong>Optional</strong> property of <strong>%s</strong> in this Scenario.', 'woocommerce-configurable-products' ), apply_filters( 'woocommerce_configuration_component_title', $component_data[ 'title' ], $component_id, $product_id ) ) : '';
								$select_tip   = sprintf( __( 'Select products and variations from <strong>%1$s</strong>.<br/><strong>Tip</strong>: Choose the <strong>All Products and Variations</strong> option to add all products and variations available under <strong>%1$s</strong> in this Scenario.%2$s', 'woocommerce-configurable-products' ), apply_filters( 'woocommerce_configuration_component_title', $component_data[ 'title' ], $component_id, $product_id ), $optional_tip );

								?><select id="bto_scenario_ids_<?php echo $id; ?>_<?php echo $component_id; ?>" name="scenarios[<?php echo $id; ?>][component_data][<?php echo $component_id; ?>][]" style="width: 75%;" class="wc-enhanced-select bto_scenario_ids" multiple="multiple" data-placeholder="<?php echo __( 'Select products &amp; variations&hellip;', 'woocommerce-configurable-products' ); ?>"><?php

									foreach ( $scenario_options as $scenario_option_id => $scenario_option_description ) {
										$option_selected = in_array( $scenario_option_id, $scenario_selections ) ? 'selected="selected"' : '';
										echo '<option ' . $option_selected . 'value="' . $scenario_option_id . '">' . $scenario_option_description . '</option>';
									}

								?></select>
								<span class="wc_cp_panel-tips" data-tip="<?php echo $select_tip; ?>"></span><?php

							} else {

								$selections_in_scenario = array();

								foreach ( $scenario_data[ 'component_data' ][ $component_id ] as $product_id_in_scenario ) {

									if ( $product_id_in_scenario == -1 ) {
										if ( $component_data[ 'optional' ] == 'yes' ) {
											$selections_in_scenario[ $product_id_in_scenario ] = __( 'None', 'woocommerce-configurable-products' );
										}
									} elseif ( $product_id_in_scenario == 0 ) {
										$selections_in_scenario[ $product_id_in_scenario ] = __( 'All Products and Variations', 'woocommerce-configurable-products' );
									} else {

										$product_in_scenario = wc_get_product( $product_id_in_scenario );

										if ( ! $product_in_scenario ) {
											continue;
										}

										if ( ! in_array( $product_in_scenario->id, $component_options ) ) {
											continue;
										}

										$selections_in_scenario[ $product_id_in_scenario ] = $woocommerce_configurable_products->api->get_product_title( $product_in_scenario );
										
									}
								}

								$optional_tip = $component_data[ 'optional' ] === 'yes' ? sprintf( __( '<br/><strong>Pro Tip</strong>: The <strong>None</strong> option controls the <strong>Optional</strong> property of <strong>%s</strong> in this Scenario.', 'woocommerce-configurable-products' ), apply_filters( 'woocommerce_configuration_component_title', $component_data[ 'title' ], $component_id, $product_id ) ) : '';
								$search_tip   = sprintf( __( 'Search for products and variations from <strong>%1$s</strong>.<br/><strong>Tip</strong>: Choose the <strong>All Products and Variations</strong> option to add all products and variations available under <strong>%1$s</strong> in this Scenario.%2$s', 'woocommerce-configurable-products' ), apply_filters( 'woocommerce_configuration_component_title', $component_data[ 'title' ], $component_id, $product_id ), $optional_tip );

								?>
								
								<input type="hidden" id="bto_scenario_ids_<?php echo $id; ?>_<?php echo $component_id; ?>" name="scenarios[<?php echo $id; ?>][component_data][<?php echo $component_id; ?>]" class="wc-component-options-search" style="width: 75%;" data-component_optional="<?php echo $component_data[ 'optional' ]; ?>" data-component_id="<?php echo $component_id; ?>" data-placeholder="<?php _e( 'Search for products &amp; variations&hellip;', 'woocommerce-configurable-products' ); ?>" data-action="woocommerce_json_search_component_options_in_scenario" data-multiple="true" data-selected="<?php

										echo esc_attr( json_encode( $selections_in_scenario ) );

									?>" value="<?php echo implode( ',', array_keys( $selections_in_scenario ) ); ?>" />
									<span class="bto_scenario_search tips" data-tip="<?php echo $search_tip; ?>"></span><?php
								
							}

						?></div>
					</div>
				</div><?php
			}

		?></div><?php
	}

	/**
	 * Admin writepanel scripts.
	 *
	 * @return void
	 */
	function configuration_admin_scripts( $hook ) {

		global $post, $woocommerce_configurable_products;

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		
		wp_register_script( 'backbone', $woocommerce_configurable_products->plugin_url() . '/assets/js/vendor/backbone.min.js', array( 'underscore' ), $woocommerce_configurable_products->version );
		
		wp_register_script( 'rivets', $woocommerce_configurable_products->plugin_url() . '/assets/js/vendor/rivets.bundled.min.js', array(), $woocommerce_configurable_products->version );
		
		wp_register_script( 'rivets-formatters', $woocommerce_configurable_products->plugin_url() . '/assets/js/vendor/rivets.formatters.min.js', array( 'rivets' ), $woocommerce_configurable_products->version );
		
		wp_register_script( 'rivets-backbone', $woocommerce_configurable_products->plugin_url() . '/assets/js/vendor/rivets.backbone.min.js', array( 'rivets', 'backbone', 'rivets-formatters' ), $woocommerce_configurable_products->version );

		wp_register_script( 'wc_cp_edit_product', $woocommerce_configurable_products->plugin_url() . '/assets/js/admin/wc-cp-edit-product' . $suffix . '.js', array( 'jquery', 'jquery-ui-datepicker', 'wc-admin-meta-boxes', 'rivets-backbone' ), $woocommerce_configurable_products->version );
		
		wp_register_style( 'wc_cp_edit_product', $woocommerce_configurable_products->plugin_url() . '/assets/css/admin/wc-cp-edit-product.css', array( 'woocommerce_admin_styles' ), $woocommerce_configurable_products->version );
		// Get admin screen id
		$screen = get_current_screen();

		// WooCommerce admin pages
		if ( in_array( $screen->id, array( 'product' ) ) ) {
			
			wp_enqueue_script( 'wc_cp_edit_product' );

			$params = array(
				'save_configuration_nonce'      => wp_create_nonce( 'wc_bto_save_configuration' ),
				'add_component_nonce'       => wp_create_nonce( 'wc_bto_add_component' ),
				'add_scenario_nonce'        => wp_create_nonce( 'wc_bto_add_scenario' ),
				'i18n_no_default'           => __( 'No default option&hellip;', 'woocommerce-configurable-products' ),
				'i18n_all'                  => __( 'All Products and Variations', 'woocommerce-configurable-products' ),
				'i18n_none'                 => __( 'None', 'woocommerce-configurable-products' ),
				'is_wc_version_gte_2_3'     => 'yes',
				'i18n_matches_1'            => _x( 'One result is available, press enter to select it.', 'enhanced select', 'woocommerce' ),
				'i18n_matches_n'            => _x( '%qty% results are available, use up and down arrow keys to navigate.', 'enhanced select', 'woocommerce' ),
				'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'woocommerce' ),
				'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'woocommerce' ),
				'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'woocommerce' ),
				'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'woocommerce' ),
				'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'woocommerce' ),
				'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'woocommerce' ),
				'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'woocommerce' ),
				'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'woocommerce' ),
				'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'woocommerce' ),
				'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'woocommerce' ),
				'product'					=> null
			);

			wp_localize_script( 'wc_cp_edit_product', 'wc_cp_admin_params', $params );
			
		}

		if ( in_array( $screen->id, array( 'edit-product', 'product' ) ) ) {
			
			wp_enqueue_style( 'wc_cp_edit_product' );
			
		}
		
	}

	/**
	 * Adds the configuration Product write panel tabs.
	 *
	 * @return string
	 */
	function configuration_write_panel_tabs() {

		echo '<li class="show_if_configurable configuration_components"><a href="#configuration_components">'.__( 'Components', 'woocommerce-configurable-products' ).'</a></li>';
		echo '<li class="show_if_configurable configuration_scenarios"><a href="#configuration_scenarios">'.__( 'Scenarios', 'woocommerce-configurable-products' ).'</a></li>';
	}

	/**
	 * Adds the base and sale price option writepanel options.
	 *
	 * @return void
	 */
	function configuration_pricing_options() {

		echo '<div class="options_group show_if_configurable">';

		// Price
		woocommerce_wp_text_input( array( 'id' => '_base_regular_price', 'class' => 'short', 'label' => __( 'Base Regular Price', 'woocommerce-configurable-products' ) . ' (' . get_woocommerce_currency_symbol().')', 'data_type' => 'price' ) );

		// Sale Price
		woocommerce_wp_text_input( array( 'id' => '_base_sale_price', 'class' => 'short', 'label' => __( 'Base Sale Price', 'woocommerce-configurable-products' ) . ' (' . get_woocommerce_currency_symbol() . ')', 'data_type' => 'price' ) );

		echo '</div>';
	}

	/**
	 * Components and Scenarios write panels.
	 *
	 * @return void
	 */
	function configuration_write_panel() {

		global $woocommerce_configurable_products, $post, $wpdb;
		
		include( 'html-components-admin.php' );

		$bto_scenarios = get_post_meta( $post->ID, '_scenarios', true );

		?>
		
		<div id="configuration_scenarios" class="panel wc-metaboxes-wrapper">

			<div class="options_group">

				<div id="bto_scenarios_inner"><?php

					if ( $bto_data ) {

						?><div class="wc_cp_panel-info inline woocommerce-message">
							<span class="wc_cp_panel-question"><?php
								$tip = '<a href="#" class="tips" data-tip="' . __( 'Scenarios are different possible configurations of a configuration product. By default, Scenarios can be used to introduce dependencies between Component Options. Developers may create custom actions and attach them to Scenarios using the Scenario Actions API. The action(s) associated with a Scenario are triggered at every step of the configuration if the previously selected products/variations are present in the Scenario.', 'woocommerce-configurable-products' ) . '">' . __( 'help', 'woocommerce-configurable-products' ) . '</a>';
								echo sprintf( __( 'Need %s to set up <strong>Scenarios</strong> ?', 'woocommerce-configurable-products' ), $tip );
							?></span></br>
							<a class="button-primary" href="<?php echo 'http://docs.woothemes.com/document/configurable-products'; ?>" target="_blank"><?php _e( 'Learn more', 'woocommerce' ); ?></a>
							
						</div>
						<p class="toolbar">
							<a href="#" class="close_all"><?php _e( 'Close all', 'woocommerce' ); ?></a>
							<a href="#" class="expand_all"><?php _e( 'Expand all', 'woocommerce' ); ?></a>
						</p>

						<div class="bto_scenarios wc-metaboxes"><?php

							if ( $bto_scenarios ) {

								$i = 0;

								foreach ( $bto_scenarios as $scenario_id => $scenario_data ) {

									$scenario_data[ 'scenario_id' ] = $scenario_id;

									?><div class="bto_scenario bto_scenario_<?php echo $i; ?> wc-metabox closed" rel="<?php echo $scenario_data[ 'position' ]; ?>">
										<h3>
											<button type="button" class="remove_row button"><?php echo __( 'Remove', 'woocommerce' ); ?></button>
											<div class="handlediv" title="<?php echo __( 'Click to toggle', 'woocommerce' ); ?>"></div>
											<strong class="scenario_name"><?php echo $scenario_data[ 'title' ]; ?></strong>
											<input type="hidden" name="scenarios[<?php echo $i; ?>][scenario_id]" class="scenario_id" value="<?php echo $scenario_id; ?>"/>
										</h3>
										<div class="scenarios wc-metabox-content">
											<div class="options_group">
												<h4><?php echo __( 'Scenario Name &amp; Description', 'woocommerce-configurable-products' ); ?></h4><?php

												do_action( 'woocommerce_configuration_scenario_admin_info_html', $i, $scenario_data, $bto_data, $post->ID );

												?><h4><?php echo __( 'Scenario Configuration', 'woocommerce-configurable-products' ); ?></h4><?php

												do_action( 'woocommerce_configuration_scenario_admin_config_html', $i, $scenario_data, $bto_data, $post->ID );

												?><h4><?php echo __( 'Scenario Actions', 'woocommerce-configurable-products' ); ?></h4><?php

												do_action( 'woocommerce_configuration_scenario_admin_actions_html', $i, $scenario_data, $bto_data, $post->ID );

											?></div>
										</div>
									</div><?php

									$i++;
								}
							}

						?></div>

						<p class="toolbar borderless">
							<input type="file" name="import_scenarios" class="hide" id="importScenarios" onChange="if(confirm('Are you sure you want to upload this import file? This action will replace all existing scenarios')){this.form.submit();}" />
							
							<a href="#" onClick="jQuery('#importScenarios').trigger('click'); return false;" class="button-primary"><?php _e('Import Scenarios', 'woocommerce-configurable-products-extension'); ?></a>
							
							<a href="<?php echo add_query_arg(array('action' => 'export_scenarios', 'product_id' => $post->ID, '_wpnonce' => wp_create_nonce('export_scenarios')), admin_url('admin-post.php')); ?>" class="button-primary"><?php _e('Export Scenarios', 'woocommerce-configurable-products-extension'); ?></a>
							<button type="button" class="button button-primary wc_cp_right"><?php _e( 'Add Scenario', 'woocommerce-configurable-products' ); ?></button>
						</p><?php

					} else {

						?><div id="bto-scenarios-message" class="wc_cp_panel-message inline woocommerce-message">
							<div class="squeezer">
								<p><?php _e( 'Scenarios can be defined only after creating and saving some Components from the <strong>Components</strong> tab.', 'woocommerce-configurable-products' ); ?></p>
								<p class="submit"><a class="button-primary" href="<?php echo 'http://docs.woothemes.com/document/configurable-products'; ?>" target="_blank"><?php _e( 'Learn more', 'woocommerce' ); ?></a></p>
							</div>
						</div><?php
					}

				?></div>
			</div>
		</div><?php
	}

	/**
	 * Product options for post-1.6.2 product data section.
	 *
	 * @param  array $options
	 * @return array
	 */
	function add_configuration_type_options( $options ) {

		$options[ 'wc_cp_per_product_pricing' ] = array(
			'id'            => '_wc_cp_per_product_pricing',
			'wrapper_class' => 'show_if_configurable',
			'label'         => __( 'Per-Item Pricing', 'woocommerce-configurable-products' ),
			'description'   => __( 'When <strong>Per-Item Pricing</strong> is checked, the configurable product will be priced according to the cost of its contents. To add a fixed amount to the configuration price when thr <strong>Per-Item Pricing</strong> option is checked, use the Base Price fields below.', 'woocommerce-configurable-products' ),
			'default'       => 'no'
		);

		return $options;
	}

	/**
	 * Adds the 'configuration product' type to the menu.
	 *
	 * @param  array 	$options
	 * @return array
	 */
	function add_configuration_type( $options ) {

		$options[ 'configurable' ] = __( 'Configurable product', 'woocommerce-configurable-products' );

		return $options;
	}

	/**
	 * Process, verify and save configuration product data.
	 *
	 * @param  int 	$post_id
	 * @return void
	 */
	function process_configuration_meta( $post_id ) {

		global $woocommerce_configurable_products;

		// Per-Item Pricing

		if ( isset( $_POST[ '_wc_cp_per_product_pricing' ] ) ) {

			update_post_meta( $post_id, '_wc_cp_per_product_pricing', 'yes' );

			update_post_meta( $post_id, '_base_sale_price', $_POST[ '_base_sale_price' ] === '' ? '' : stripslashes( wc_format_decimal( $_POST[ '_base_sale_price' ] ) ) );
			
			update_post_meta( $post_id, '_base_regular_price', $_POST[ '_base_regular_price' ] === '' ? '' : stripslashes( wc_format_decimal( $_POST[ '_base_regular_price' ] ) ) );

			if ( $_POST[ '_base_sale_price' ] !== '' ) {
				
				update_post_meta( $post_id, '_base_price', stripslashes( wc_format_decimal( $_POST[ '_base_sale_price' ] ) ) );
				
			} else {
				
				update_post_meta( $post_id, '_base_price', stripslashes( wc_format_decimal( $_POST[ '_base_regular_price' ] ) ) );
				
			}

		} else {

			update_post_meta( $post_id, '_wc_cp_per_product_pricing', 'no' );
			
		}


		// Shipping
		// Non-Bundled (per-item) Shipping

		if ( isset( $_POST[ '_wc_cp_per_product_shipping' ] ) ) {
			
			update_post_meta( $post_id, '_wc_cp_per_product_shipping', 'yes' );
			update_post_meta( $post_id, '_virtual', 'yes' );
			update_post_meta( $post_id, '_weight', '' );
			update_post_meta( $post_id, '_length', '' );
			update_post_meta( $post_id, '_width', '' );
			update_post_meta( $post_id, '_height', '' );
			
		} else {
			
			update_post_meta( $post_id, '_wc_cp_per_product_shipping', 'no' );
			update_post_meta( $post_id, '_virtual', 'no' );
			update_post_meta( $post_id, '_weight', stripslashes( $_POST[ '_weight' ] ) );
			update_post_meta( $post_id, '_length', stripslashes( $_POST[ '_length' ] ) );
			update_post_meta( $post_id, '_width', stripslashes( $_POST[ '_width' ] ) );
			update_post_meta( $post_id, '_height', stripslashes( $_POST[ '_height' ] ) );
			
		}

		$this->save_configuration( $post_id, $_POST );

	}

	/**
	 * Save components and scenarios.
	 *
	 * @param  int   $post_id
	 * @param  array $configuration
	 * @return boolean
	 */
	function save_configuration( $post_id, $configuration ) {

		// configuration selection mode

		update_post_meta( $post_id, '_wc_cp_style', isset( $configuration[ 'style' ] ) ? stripslashes( $configuration[ 'style' ] ) : 'dropdown' );
		update_post_meta( $post_id, '_wc_cp_build_sku', ! empty( $configuration[ 'build_sku' ] ) ? true : false );
		update_post_meta( $post_id, '_wc_cp_sku_start', isset( $configuration[ 'sku_start' ] ) ? $configuration[ 'sku_start' ] : '' );

		if ( isset( $configuration[ 'components' ] ) ) {

			/* -------------------------- */
			/* Components
			/* -------------------------- */

			foreach ( $configuration[ 'components' ] as $component ) {
				
				$options = isset( $component[ 'options' ] ) ? $component[ 'options' ] : array();
				
				$component = new Component( $component );
				
				$component->save();	
				
				$component->saveOptions($options);		
				
			}


			/* -------------------------- */
			/* Scenarios
			/* -------------------------- */

			if ( isset( $configuration[ 'scenarios' ] ) ) {

				foreach ( $configuration[ 'scenarios' ] as $scenario ) {

					$scenario = new Component( $scenario );
				
					$scenario->saveAll();
					
				}

			}

		}

		if ( ! isset( $configuration[ 'components' ] ) || count( $configuration[ 'components' ] ) == 0 ) {

			$this->save_errors[] = $this->add_admin_error( __( 'Please create at least one Component before publishing. To add a Component, go to the Components tab and click on the Add Component button.', 'woocommerce-configurable-products' ) );

			if ( isset( $configuration[ 'post_status' ] ) && $configuration[ 'post_status' ] == 'publish' ) {
				
				global $wpdb;
				
				$wpdb->update( $wpdb->posts, array( 'post_status' => 'draft' ), array( 'ID' => $post_id ) );
				
			}

			return false;
		}

		return true;
		
	}

	/**
	 * Handles saving configuration config via ajax.
	 *
	 * @return void
	 */
	function ajax_configuration_save() {

		check_ajax_referer( 'wc_cp_save_configuration', 'security' );

		parse_str( $_POST[ 'data' ], $configuration );

		$post_id = absint( $_POST[ 'post_id' ] );

		$this->save_configuration( $post_id, $configuration );

		header( 'Content-Type: application/json; charset=utf-8' );
		
		echo json_encode( $this->save_errors );
		
		die();
	}

	/**
	 * Handles adding scenarios via ajax.
	 *
	 * @return void
	 */
	function ajax_add_scenario() {

		global $woocommerce_configurable_products;

		check_ajax_referer( 'wc_cp_add_scenario', 'security' );

		$id      = intval( $_POST[ 'id' ] );
		$post_id = intval( $_POST[ 'post_id' ] );

		$scenario_data  = array();

		include( 'html-scenario-admin.php' );

		die();
	}

	/**
	 * Search for default component option and echo json.
	 *
	 * @return void
	 */
	public function json_search_default_component_option() {
		$this->json_search_component_options();
	}
	
	/**
	 * Search for attributes and echo json.
	 *
	 * @return void
	 */
	public function json_search_attributes() {
		
		global $wpdb;
		
		$term	= (string) wc_clean( stripslashes( $_GET[ 'term' ] ) );
		
		if ( empty( $term ) ) {
			die();
		}
		
		wp_send_json( wp_list_pluck( $wpdb->get_results( "SELECT attribute_id, attribute_label FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_name LIKE '%" . $term  . "%' OR attribute_label LIKE '%" . $term  . "%' ORDER BY attribute_label ASC;", ARRAY_A ), 'attribute_label', 'attribute_id' ) );
		
	}
	
	/**
	 * Search for attribute terms and echo json.
	 *
	 * @return void
	 */
	public function json_search_attribute_terms() {
		
		global $wpdb;
		
		$term	= (string) wc_clean( stripslashes( $_GET[ 'term' ] ) );
		$configuration_id = $_GET[ 'configuration_id' ];
		$component_id = $_GET[ 'component_id' ];
		
		if ( empty( $term ) || empty( $configuration_id ) || empty( $component_id ) ) {
			die();
		}
		
		$configuration_data = get_post_meta( $configuration_id, '_bto_data', true );
		$component_data = isset( $configuration_data[ $component_id ] ) ? $configuration_data[ $component_id ] : false;

		if ( false == $configuration_data || false == $component_data ) {
			die();
		}
		
		$attribute_ids = ! empty( $component_data['attribute_ids'] ) ? $component_data['attribute_ids'] : array();
		
		if( ! $attribute_ids ) {
			die();
		}
		
		$attributes = $wpdb->get_results( "SELECT attribute_name FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_id IN (" . implode( ', ', array_map( 'intval', $attribute_ids ) ) . ") ORDER BY attribute_name ASC;", ARRAY_A );
		
		$attributes = array_map( 'wc_attribute_taxonomy_name', wp_list_pluck( $attributes, 'attribute_name' ));
		
		wp_send_json( get_terms(array(
			'taxonomy' => $attributes,
			'fields' => 'id=>name',
			'orderby' => 'name',
			'hide_empty' => false,
			'search' => $term
		)) );
		
	}

	/**
	 * Search for default component option and echo json.
	 *
	 * @return void
	 */
	public function json_search_component_options_in_scenario() {
		$this->json_search_component_options( 'search_component_options_in_scenario', $post_types = array( 'product', 'product_variation' ) );
	}

	/**
	 * Search for component options and echo json.
	 *
	 * @param   string $x (default: '')
	 * @param   string $post_types (default: array('product'))
	 * @return  void
	 */
	public function json_search_component_options( $x = 'default', $post_types = array( 'product' ) ) {

		global $woocommerce_configurable_products;

		ob_start();

		check_ajax_referer( 'search-products', 'security' );

		$term         = (string) wc_clean( stripslashes( $_GET[ 'term' ] ) );
		$configuration_id = $_GET[ 'configuration_id' ];
		$component_id = $_GET[ 'component_id' ];

		if ( empty( $term ) || empty( $configuration_id ) || empty( $component_id ) ) {
			die();
		}

		$configuration_data = get_post_meta( $configuration_id, '_bto_data', true );
		$component_data = isset( $configuration_data[ $component_id ] ) ? $configuration_data[ $component_id ] : false;

		if ( false == $configuration_data || false == $component_data ) {
			die();
		}

		// Run query to get component option ids
		$component_options = $woocommerce_configurable_products->api->get_component_options( $component_data );

		// Add variation ids to component option ids
		if ( $x == 'search_component_options_in_scenario' ) {
			$variations_args = array(
				'post_type'      => array( 'product_variation' ),
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'post_parent'    => array_merge( array( '0' ), $component_options ),
				'fields'         => 'ids'
			);

			$component_options_variations = get_posts( $variations_args );

			$component_options = array_merge( $component_options, $component_options_variations );
		}

		if ( is_numeric( $term ) ) {

			$args = array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'post__in'       => array(0, $term),
				'fields'         => 'ids'
			);

			$args2 = array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_query'     => array(
					array(
						'key'     => '_sku',
						'value'   => $term,
						'compare' => 'LIKE'
					)
				),
				'fields'         => 'ids'
			);

			$args3 = array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'post_parent'    => $term,
				'fields'         => 'ids'
			);

			$posts = array_unique( array_intersect( $component_options, array_merge( get_posts( $args ), get_posts( $args2 ), get_posts( $args3 ) ) ) );

		} else {

			$args = array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				's'              => $term,
				'fields'         => 'ids'
			);

			$args2 = array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_query'     => array(
					array(
					'key'     => '_sku',
					'value'   => $term,
					'compare' => 'LIKE'
					)
				),
				'fields'         => 'ids'
			);

			$posts = array_unique( array_intersect( $component_options, array_merge( get_posts( $args ), get_posts( $args2 ) ) ) );

		}

		$found_products = array();
		$loop           = 0;

		if ( $posts ) {
			foreach ( $posts as $post ) {

				if ( $loop > 1000 ) {
					continue;
				}

				$product = wc_get_product( $post );

				if ( $product->product_type === 'variation' ) {
					$found_products[ $post ] = $woocommerce_configurable_products->api->get_product_variation_title( $product );
				} else {
					if ( $x == 'search_component_options_in_scenario' && $product->product_type === 'variable' ) {
						$found_products[ $post ] = $woocommerce_configurable_products->api->get_product_title( $product ) . ' ' . __( '&mdash; All Variations', 'woocommerce-configurable-products' );
					} else {
						$found_products[ $post ] = $woocommerce_configurable_products->api->get_product_title( $product );
					}
				}

				$loop++;
			}
		}

		wp_send_json( $found_products );
	}

	/**
	 * Support scanning for template overrides in extension.
	 *
	 * @param  array   $paths paths to check
	 * @return array          modified paths to check
	 */
	function configuration_template_scan_path( $paths ) {

		global $woocommerce_configurable_products;

		$paths[ 'WooCommerce configuration Products' ] = $woocommerce_configurable_products->plugin_path() . '/templates/';

		return $paths;
	}

	/**
	 * Add and return admin errors.
	 *
	 * @param  string $error
	 * @return string
	 */
	private function add_admin_error( $error ) {

		WC_Admin_Meta_Boxes::add_error( $error );

		return $error;
	}

	/**
	 * Add custom save errors via filters.
	 *
	 * @param string $error
	 */
	function add_error( $error ) {

		$this->save_errors[] = $this->add_admin_error( $error );
	}
	
	/**
	 * Retreieve option styles for option styles
	 *
	 * @return array()
	 */
	function get_option_styles() {
		
		$styles = array(
			'dropdown' => array(
				'title' => 'Dropdown',
				'description' => 'Component Options are listed in dropdown menus without any pagination. Use this setting if your Components include just a few product options. Also recommended if you want to keep the layout as compact as possible.',
			),
			'radios' => array(
				'title' => 'Radio Buttons',
				'description' => 'Component Options are presented as radio buttons, all in list. Radio buttons are disabled when outside of scenarios.',
			),
			'checkboxes' => array(
				'title' => 'Checkboxes',
				'description' => 'Component Options are presented as checkboxes, where many products can be selected, all in a vertical list. Checkboxes are disabled when outside of scenarios.',
			),
			'thumbnails' => array(
				'title' => 'Thumbnails',
				'description' => 'Component Options are presented as thumbnails, all in a list. Thumbnails are disabled when outside of scenarios.',
			),
			'number' => array(
				'title' => 'Number Field',
				'description' => 'Component Option is presented as number field, where the user can enter a number according to the step interval you set.',
			),
			'text' => array(
				'title' => 'Text Field',
				'description' => 'Component Option is presented as text field, where the user can enter a string.',
			)
		);
		
		return apply_filters('woocommerce_configuration_products_selection_modes', $styles);
		
	}
	
	/**
	 * Retreieve option style description for option styles
	 *
	 * @return array()
	 */
	function get_option_styles_descriptions() {
		
		$description = '';
		
		$i = 1;
		
		$option_styles = $this->get_option_styles();
		
		foreach($option_styles as $style) {
			
			$description .= '<strong>' . $style['title'] . '</strong>:</br>' . $style['description'];
			
			if(count($option_styles) > $i) {
				
				$description .= '</br></br>';
				
			}
			
			$i++;
			
		}
		
		return apply_filters('woocommerce_configuration_products_option_style_description', $description);
		
	}
	
	public function export_scenarios() {
		
		if(isset($_REQUEST['_wpnonce']) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'export_scenarios')) {
		
			if(isset($_REQUEST['product_id'])) {
				
				$product = new WC_Product($_REQUEST['product_id']);
				
				$data = array(
					array('Component ID', 'Component Title', 'Product SKU', 'Product Title'),
					array('Description', ' ', ' ', ' ')
				);
				
				if($product && $product->bto_data) {
					
					$component_datum = get_post_meta( $product_id, '_bto_data', true );
					
					foreach ( $product->bto_data as $row => $component_data ) {
						
						$component_id = $component_data['component_id'];
						
						$i = 0;
						
						if($component_data['optional'] == 'yes') {
							
							array_unshift($component_data['product_ids'], -1);
							array_unshift($component_data['attribute_term_ids'], -1);
							
						}
						
						ksort($component_data['product_ids']);
						ksort($component_data['attribute_term_ids']);
						
						foreach($component_data['product_ids'] as $sku_product_id) {
							
							$sku_product = $sku_product_id > 0 ? new WC_Product($sku_product_id) : null;
							
							$sku_product_title = $sku_product_id > 0 ? $sku_product->get_title() : 'None';
							
							$sku = $sku_product_id > 0 ? $sku_product->get_sku() : -1;
							
							if($i)
								$data[$component_id + $sku_product_id] = array('', '', "\"" . $sku . "\"", str_replace(',', '', $sku_product_title));
							else
								$data[$component_id + $sku_product_id] = array($component_data['component_id'], $component_data['title'], "\"" . $sku . "\"", str_replace(',', '', $sku_product_title));
							
							$i++;	
							
						}
						
					}
					
				} else {
					
					wp_die( __( 'You attempted to download the scenario template for either a non configuration Product, or a configuration Product with no Component Data, must have been a mistake? <a href="' . get_edit_post_link($_REQUEST['product_id']) . '">Go back to the product screen</a>' ) );
					
				}
				
				if($product->scenarios) {
					
					foreach ( $product->scenarios as $scenario_id => $scenario_data ) {
						
						$modifiers = $scenario_data['modifier'];
						
						$data[0][] = $scenario_data['title'];
						$data[1][] = $scenario_data['description'];
							
						foreach($scenario_data['component_data'] as $component_id => $component_ids) {
							
							$modifier = $modifiers[$component_id];
							
							$component_data = $product->bto_data[$component_id];
							
							if($component_data['optional'] == 'yes') {
							
								array_unshift($component_data['product_ids'], -1);
								array_unshift($component_data['attribute_term_ids'], -1);
								
							}
						
							foreach($component_data['product_ids'] as $sku_product_id) {
									
								if( ($modifier === 'in' && ( in_array($sku_product_id, $component_ids) || in_array(0, $component_ids) ) ) || ($modifier === 'not-in' && ( !in_array($sku_product_id, $component_ids) && ! in_array(0, $component_ids) ) ) )
									$data[$component_id + $sku_product_id][] = 'X';
									
								else
									$data[$component_id + $sku_product_id][] = '';
									
							}
							
						}
													
					} 
					
				}
				
				$filename = "Scenarios exported for " . $product->get_title() . ".csv";
            
				$this->outputCsv($filename, $data);
				
			} else {
			
				wp_die( __( 'You did not provide a product id in your request, must have been a mistake? <a href="' . admin_url('edit.php?post_type=product') . '">Go back to the products screen</a>' ) );	
				
			}	
				
		} else {
		
			wp_die( __( 'There was a security error, please try again. <a href="' . (isset($_REQUEST['product_id']) ? get_edit_post_link($_REQUEST['product_id']) : admin_url('edit.php?post_type=product')) . '">Go back</a>' ) );	
			
		}
		
	}
	
	private function outputCsv($filename, $data) {
		ob_clean();
		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Cache-Control: private', false);
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment;filename=' . $filename);    
		$output = fopen("php://output", "w");
	   foreach ($data as $row) {
	      fputcsv($output, $row); // here you can change delimiter/enclosure
	   }
	   fclose($output);
		ob_flush();
	}

	public function import_scenarios($product_id) {
		
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
			return;
		
		if(isset($_FILES['import_scenarios']) && $_FILES['import_scenarios']['size']) {
			
			global $wpdb;
			
			$data = file_get_contents($_FILES['import_scenarios']['tmp_name']);
			
			$rows = array_map('str_getcsv', explode("\n", $data));
			
			$headers = $rows[0];
			unset($rows[0]);
			
			$descriptions = $rows[1];
			unset($rows[1]);
			
			$rows = array_values($rows);
			
			$scenario_data = array();
			
			$scenarios = get_post_meta( $product_id, '_scenarios', true);
			
			$component_id = '';
			$component_title = '';
			
			foreach($rows as $i => $row) {
				
				$component_id = $row[0] ? $row[0] : $component_id;
				$component_title = $row[1] ? $row[1] : $component_title;
				$product_sku = str_replace('"', '', $row[2]);
				$product_title = $orw[3];
				$timestamp = current_time( 'timestamp' );
				
				$component_product_id = $product_sku == -1 ? -1 : $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $product_sku ) );
				
				unset($row[0]);
				unset($row[1]);
				unset($row[2]);
				unset($row[3]);
				
				$x = 0;
				
				foreach($row as $y => $scenario_value) {
					
					$scenario_id =  $timestamp + $y;
					$position = $x;
					$title = $headers[$y];
					$description = $descriptions[$y];
					
					if($scenario_value)
						$scenario_data[$scenario_id]["component_data"][$component_id][] = $component_product_id;
						
					else
						$scenario_data[$scenario_id]["component_excluded"][$component_id][] = $component_product_id;
						
					$scenario_data[$scenario_id]["scenario_id"] = $scenario_id;
					$scenario_data[$scenario_id]["modifier"][$component_id] = "in";				
					$scenario_data[$scenario_id]["position"] = $position;
					$scenario_data[$scenario_id]["title"] = $title;
					$scenario_data[$scenario_id]["description"] = $description;
					$scenario_data[$scenario_id]["scenario_actions"] = array(
																		"compat_group" => array(
																				"is_active" => "yes"
																			)
																		);
					
					$x++;
					
				}
				
			}
			
			foreach($scenario_data as $scenario_id => &$scenario) {
				
				foreach($scenario["component_data"] as $component_id => &$product_ids) {
					
					if(count($scenario["component_excluded"][$component_id]) === 0) {
						
						// All are included so just set as 0 'All Product & Variations'
					
						$product_ids = array(0);
						
					} 
					
					elseif(count($product_ids) > count($scenario["component_excluded"][$component_id])) {
						
						// There are less products in the exclusion than inclusion - better to store it as an exclusive
						
						$product_ids = $scenario["component_excluded"][$component_id];
						
						$scenario["modifier"][$component_id] = "not-in";	
						
					}
					
				}
				
				unset($scenario["component_excluded"]);
				
			}
			
			update_post_meta($product_id, '_scenarios', $scenario_data);
			
		}
		
		return true;
		
	}
	
	public function add_enctype_to_edit_form($post) {
		
		if($post->post_type == 'product') {
			
			echo ' enctype="multipart/form-data"';
			
		}
		
	}
	
}
