<?php
/**
 * Admin filters and functions.
 *
 * @class 	WC_CP_Admin
 * @version 3.1.0
 * @since   2.2.2
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_CP_Admin {

	private $save_errors = array();

	public function __construct() {

		// Admin jquery
		add_action( 'admin_enqueue_scripts', array( $this, 'composite_admin_scripts' ) );

		// Creates the admin Components and Scenarios panel tabs
		add_action( 'woocommerce_product_write_panel_tabs', array( $this, 'composite_write_panel_tabs' ) );

		// Adds the base price options
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'composite_pricing_options' ) );

		// Creates the admin Components and Scenarios panels
		add_action( 'woocommerce_product_write_panels', array( $this, 'composite_write_panel' ) );

		// Allows the selection of the 'composite product' type
		add_filter( 'product_type_options', array( $this, 'add_composite_type_options' ) );

		// Processes and saves the necessary post metas from the selections made above
		add_action( 'woocommerce_process_product_meta_composite', array( $this, 'process_composite_meta' ) );

		// Allows the selection of the 'composite product' type
		add_filter( 'product_type_selector', array( $this, 'add_composite_type' ) );

		// Ajax save composite config
		add_action( 'wp_ajax_woocommerce_bto_composite_save', array( $this, 'ajax_composite_save' ) );

		// Ajax add component
		add_action( 'wp_ajax_woocommerce_add_composite_component', array( $this, 'ajax_add_component' ) );

		// Ajax add scenario
		add_action( 'wp_ajax_woocommerce_add_composite_scenario', array( $this, 'ajax_add_scenario' ) );

		// Ajax search default component id
		add_action( 'wp_ajax_woocommerce_json_search_default_component_option', array( $this, 'json_search_default_component_option' ) );

		// Ajax search products and variations in scenarios
		add_action( 'wp_ajax_woocommerce_json_search_component_options_in_scenario', array( $this, 'json_search_component_options_in_scenario' ) );

		// Template override scan path
		add_filter( 'woocommerce_template_overrides_scan_paths', array( $this, 'composite_template_scan_path' ) );
		
		// Add component html to metabox
		add_action( 'woocommerce_composite_component_admin_html', array($this, 'component_admin_html'), 10, 3 );

		// Basic component config options
		add_action( 'woocommerce_composite_component_admin_config_html', array( $this, 'component_layout_options_style' ), 5, 3 );
		add_action( 'woocommerce_composite_component_admin_config_html', array( $this, 'component_config_title' ), 10, 3 );
		add_action( 'woocommerce_composite_component_admin_config_html', array( $this, 'component_config_description' ), 15, 3 );
		add_action( 'woocommerce_composite_component_admin_config_html', array( $this, 'component_config_options' ), 25, 3 );
		add_action( 'woocommerce_composite_component_admin_config_html', array( $this, 'component_config_optional' ), 35, 3 );

		// Advanced component configuration
		add_action( 'woocommerce_composite_component_admin_advanced_html', array( $this, 'component_config_default_option' ), 5, 3 );
		add_action( 'woocommerce_composite_component_admin_advanced_html', array( $this, 'component_config_recommended_option' ), 6, 3 );
		add_action( 'woocommerce_composite_component_admin_advanced_html', array( $this, 'component_config_default_value' ), 7, 3 );
		add_action( 'woocommerce_composite_component_admin_advanced_html', array( $this, 'component_config_step_value' ), 8, 3 );
		add_action( 'woocommerce_composite_component_admin_advanced_html', array( $this, 'component_config_min_value' ), 9, 3 );
		add_action( 'woocommerce_composite_component_admin_advanced_html', array( $this, 'component_config_max_value' ), 10, 3 );
		add_action( 'woocommerce_composite_component_admin_advanced_html', array( $this, 'component_config_tag_numbers_option' ), 11, 3 );
		add_action( 'woocommerce_composite_component_admin_advanced_html', array( $this, 'component_config_sovereign_option' ), 12, 3 );
		
		add_action( 'woocommerce_composite_component_admin_sku_html', array( $this, 'component_sku_affect_sku' ), 5, 3 );
		add_action( 'woocommerce_composite_component_admin_sku_html', array( $this, 'component_sku_sku_order' ), 10, 3 );
		add_action( 'woocommerce_composite_component_admin_sku_html', array( $this, 'component_sku_sku_default' ), 11, 3 );
		add_action( 'woocommerce_composite_component_admin_sku_html', array( $this, 'component_sku_options' ), 15, 3 );
		add_action( 'woocommerce_composite_component_admin_price_html', array( $this, 'component_price_options' ), 5, 3 );

		// Scenario options
		add_action( 'woocommerce_composite_scenario_admin_info_html', array( $this, 'scenario_info' ), 10, 4 );
		add_action( 'woocommerce_composite_scenario_admin_config_html', array( $this, 'scenario_config' ), 10, 4 );
		add_action( 'woocommerce_composite_scenario_admin_actions_html', array( $this, 'scenario_actions' ), 10, 4 );

		// Delete component options query cache on product save
		add_action( 'woocommerce_delete_product_transients', array( $this, 'delete_cp_query_transients' ) );
		
		add_action( 'admin_post_export_scenarios', array($this, 'export_scenarios') ); // For Export Scenarios
		add_action( 'woocommerce_process_product_meta_composite', array($this, 'import_scenarios'), 15); // For Import Scenarios
		add_action( 'post_edit_form_tag', array($this, 'add_enctype_to_edit_form') ); // For Import Scenarios
		
		
        add_filter( 'woocommerce_product_data_tabs', array($this, 'composite_product_tabs') );
			
	}
	
	function composite_product_tabs( $tabs ) {

    	$tabs['inventory']['class'] = [];

    	return $tabs;
    	
    }

	/**
	 * Delete component options query cache on product save.
	 *
	 * @param  int   $post_id
	 * @return void
	 */
	function delete_cp_query_transients( $post_id ) {

		// Invalidate query cache
		if ( class_exists( 'WC_Cache_Helper' ) ) {
			WC_Cache_Helper::get_transient_version( 'wccp_q', true );
		}

		if ( ! wp_using_ext_object_cache() ) {

			global $wpdb;

			// Delete all query transients
			$wpdb->query( "DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_wccp_q_%') OR `option_name` LIKE ('_transient_timeout_wccp_q_%')" );
		}
	}

	/**
	 * Add scenario title and description options.
	 *
	 * @param  int    $id
	 * @param  array  $scenario_data
	 * @param  array  $composite_data
	 * @param  int    $product_id
	 * @return void
	 */
	function scenario_actions( $id, $scenario_data, $composite_data, $product_id ) {

		$defines_compat_group = isset( $scenario_data[ 'scenario_actions' ][ 'compat_group' ][ 'is_active' ] ) ? $scenario_data[ 'scenario_actions' ][ 'compat_group' ][ 'is_active' ] : 'yes';

		?>
		<div class="scenario_action_compat_group" >
			<div class="form-field">
				<label for="scenario_action_compat_group_<?php echo $id; ?>">
					<?php echo __( 'Active', 'woocommerce-composite-products' ); ?>
					<img class="help_tip" data-tip="<?php echo __( 'Toggle this Scenario on or off', 'woocommerce-composite-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
				</label>
				<input type="checkbox" class="checkbox"<?php echo ( $defines_compat_group == 'yes' ? ' checked="checked"' : '' ); ?> name="bto_scenario_data[<?php echo $id; ?>][scenario_actions][compat_group][is_active]" <?php echo ( $defines_compat_group == 'yes' ? ' value="1"' : '' ); ?> />
			</div>
		</div>
		<?php

	}

	/**
	 * Add scenario title and description options.
	 *
	 * @param  int    $id
	 * @param  array  $scenario_data
	 * @param  array  $composite_data
	 * @param  int    $product_id
	 * @return void
	 */
	function scenario_info( $id, $scenario_data, $composite_data, $product_id ) {

		$title       = isset( $scenario_data[ 'title' ] ) ? $scenario_data[ 'title' ] : '';
		$position    = isset( $scenario_data[ 'position' ] ) ? $scenario_data[ 'position' ] : $id;
		$description = isset( $scenario_data[ 'description' ] ) ? $scenario_data[ 'description' ] : '';

		?>
		<div class="scenario_title">
			<div class="form-field">
				<label><?php echo __( 'Scenario Name', 'woocommerce-composite-products' ); ?></label>
				<input type="text" class="scenario_title component_text_input" name="bto_scenario_data[<?php echo $id; ?>][title]" value="<?php echo $title; ?>"/>
				<input type="hidden" name="bto_scenario_data[<?php echo $id; ?>][position]" class="scenario_position" value="<?php echo $position; ?>"/>
			</div>
		</div>
		<div class="scenario_description">
			<div class="form-field">
				<label><?php echo __( 'Scenario Description', 'woocommerce-composite-products' ); ?></label>
				<textarea class="scenario_description" name="bto_scenario_data[<?php echo $id; ?>][description]" id="scenario_description_<?php echo $id; ?>" placeholder="" rows="2" cols="20"><?php echo esc_textarea( $description ); ?></textarea>
			</div>
		</div>
		<?php
	}

	/**
	 * Add scenario config options.
	 *
	 * @param  int    $id
	 * @param  array  $scenario_data
	 * @param  array  $composite_data
	 * @param  int    $product_id
	 * @return void
	 */
	function scenario_config( $id, $scenario_data, $composite_data, $product_id ) {

		global $woocommerce_composite_products;

		?><div class="scenario_config_group"><?php

			foreach ( $composite_data as $component_id => $component_data ) {

				$modifier = '';

				if ( isset( $scenario_data[ 'modifier' ][ $component_id ] ) ) {

					$modifier = $scenario_data[ 'modifier' ][ $component_id ];

				} else {

					$exclude = isset( $scenario_data[ 'exclude' ][ $component_id ] ) ? $scenario_data[ 'exclude' ][ $component_id ] : 'no';

					if ( $exclude === 'no' ) {
						$modifier = 'in';
					} elseif ( $exclude === 'masked' ) {
						$modifier = 'masked';
					} else {
						$modifier = 'not-in';
					}
				}

				?><div class="bto_scenario_selector">
					<div class="form-field">
						<label><?php
							echo apply_filters( 'woocommerce_composite_component_title', $component_data[ 'title' ], $component_id, $product_id ); ?>
						</label>
						<div class="bto_scenario_modifier_wrapper bto_scenario_exclude_wrapper">
							<select class="bto_scenario_modifier bto_scenario_exclude" name="bto_scenario_data[<?php echo $id; ?>][modifier][<?php echo $component_id; ?>]">
								<option <?php selected( $modifier, 'in', true ); ?> value="in"><?php echo __( 'selection is' ); ?></option>
								<option <?php selected( $modifier, 'not-in', true ); ?> value="not-in"><?php echo __( 'selection is not' ); ?></option>
								<option <?php selected( $modifier, 'masked', true ); ?> value="masked"><?php echo __( 'selection is masked' ); ?></option>
							</select>
						</div>
						<div class="bto_scenario_selector_inner" <?php echo $modifier === 'masked' ? 'style="display:none"' : ''; ?>><?php

							$component_options = $woocommerce_composite_products->api->get_component_options( $component_data );

							if ( count( $component_options ) < 30 ) {

								$scenario_options    = array();
								$scenario_selections = array();

								if ( $component_data[ 'optional' ] == 'yes' ) {

									if ( $woocommerce_composite_products->api->scenario_contains_product( $scenario_data, $component_id, -1 ) ) {
										$scenario_selections[] = -1;
									}

									$scenario_options[ -1 ] = __( 'None', 'woocommerce-composite-products' );
								}

								if ( $woocommerce_composite_products->api->scenario_contains_product( $scenario_data, $component_id, 0 ) ) {
									$scenario_selections[] = 0;
								}

								$scenario_options[ 0 ] = __( 'All Products and Variations', 'woocommerce-composite-products' );

								foreach ( $component_options as $item_id ) {

									$title = $woocommerce_composite_products->api->get_product_title( $item_id );

									if ( ! $title ) {
										continue;
									}

									// Get product type
									$terms        = get_the_terms( $item_id, 'product_type' );
									$product_type = ! empty( $terms ) && isset( current( $terms )->name ) ? sanitize_title( current( $terms )->name ) : 'simple';


									$product_title = $title;

									if ( $woocommerce_composite_products->api->scenario_contains_product( $scenario_data, $component_id, $item_id ) ) {

										$scenario_selections[] = $item_id;
									}

									$scenario_options[ $item_id ] = $product_title;

									if ( $product_type == 'variable' ) {

										if ( ! empty( $variation_descriptions ) ) {

											foreach ( $variation_descriptions as $variation_id => $description ) {

												if ( $woocommerce_composite_products->api->scenario_contains_product( $scenario_data, $component_id, $variation_id ) ) {
													$scenario_selections[] = $variation_id;
												}

												$scenario_options[ $variation_id ] = $description;
											}
										}
									}

								}

								$optional_tip = $component_data[ 'optional' ] === 'yes' ? sprintf( __( '<br/><strong>Pro Tip</strong>: Use the <strong>None</strong> option to control the <strong>Optional</strong> property of <strong>%s</strong> in this Scenario.', 'woocommerce-composite-products' ), apply_filters( 'woocommerce_composite_component_title', $component_data[ 'title' ], $component_id, $product_id ) ) : '';
								$select_tip   = sprintf( __( 'Select products and variations from <strong>%1$s</strong>.<br/><strong>Tip</strong>: Choose the <strong>All Products and Variations</strong> option to add all products and variations available under <strong>%1$s</strong> in this Scenario.%2$s', 'woocommerce-composite-products' ), apply_filters( 'woocommerce_composite_component_title', $component_data[ 'title' ], $component_id, $product_id ), $optional_tip );

								?><select id="bto_scenario_ids_<?php echo $id; ?>_<?php echo $component_id; ?>" name="bto_scenario_data[<?php echo $id; ?>][component_data][<?php echo $component_id; ?>][]" style="width: 75%;" class="wc-enhanced-select bto_scenario_ids" multiple="multiple" data-placeholder="<?php echo __( 'Select products &amp; variations&hellip;', 'woocommerce-composite-products' ); ?>"><?php

									foreach ( $scenario_options as $scenario_option_id => $scenario_option_description ) {
										$option_selected = in_array( $scenario_option_id, $scenario_selections ) ? 'selected="selected"' : '';
										echo '<option ' . $option_selected . 'value="' . $scenario_option_id . '">' . $scenario_option_description . '</option>';
									}

								?></select>
								<span class="bto_scenario_select tips" data-tip="<?php echo $select_tip; ?>"></span><?php

							} else {

								$selections_in_scenario = array();

								foreach ( $scenario_data[ 'component_data' ][ $component_id ] as $product_id_in_scenario ) {

									if ( $product_id_in_scenario == -1 ) {
										if ( $component_data[ 'optional' ] == 'yes' ) {
											$selections_in_scenario[ $product_id_in_scenario ] = __( 'None', 'woocommerce-composite-products' );
										}
									} elseif ( $product_id_in_scenario == 0 ) {
										$selections_in_scenario[ $product_id_in_scenario ] = __( 'All Products and Variations', 'woocommerce-composite-products' );
									} else {

										$product_in_scenario = wc_get_product( $product_id_in_scenario );

										if ( ! $product_in_scenario ) {
											continue;
										}

										if ( ! in_array( $product_in_scenario->id, $component_options ) ) {
											continue;
										}

										$selections_in_scenario[ $product_id_in_scenario ] = $woocommerce_composite_products->api->get_product_title( $product_in_scenario );
										
									}
								}

								$optional_tip = $component_data[ 'optional' ] === 'yes' ? sprintf( __( '<br/><strong>Pro Tip</strong>: The <strong>None</strong> option controls the <strong>Optional</strong> property of <strong>%s</strong> in this Scenario.', 'woocommerce-composite-products' ), apply_filters( 'woocommerce_composite_component_title', $component_data[ 'title' ], $component_id, $product_id ) ) : '';
								$search_tip   = sprintf( __( 'Search for products and variations from <strong>%1$s</strong>.<br/><strong>Tip</strong>: Choose the <strong>All Products and Variations</strong> option to add all products and variations available under <strong>%1$s</strong> in this Scenario.%2$s', 'woocommerce-composite-products' ), apply_filters( 'woocommerce_composite_component_title', $component_data[ 'title' ], $component_id, $product_id ), $optional_tip );

								?>
								
								<input type="hidden" id="bto_scenario_ids_<?php echo $id; ?>_<?php echo $component_id; ?>" name="bto_scenario_data[<?php echo $id; ?>][component_data][<?php echo $component_id; ?>]" class="wc-component-options-search" style="width: 75%;" data-component_optional="<?php echo $component_data[ 'optional' ]; ?>" data-component_id="<?php echo $component_id; ?>" data-placeholder="<?php _e( 'Search for products &amp; variations&hellip;', 'woocommerce-composite-products' ); ?>" data-action="woocommerce_json_search_component_options_in_scenario" data-multiple="true" data-selected="<?php

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
	 * Add component config title option.
	 *
	 * @param  int    $id
	 * @param  array  $data
	 * @param  int    $product_id
	 * @return void
	 */
	function component_config_title( $id, $data, $product_id ) {

		$title    = isset( $data[ 'title' ] ) ? $data[ 'title' ] : '';
		$position = isset( $data[ 'position' ] ) ? $data[ 'position' ] : $id;

		?>
		<div class="group_title">
			<div class="form-field">
				<label>
					<?php echo __( 'Component Name', 'woocommerce-composite-products' ); ?>
					<img class="help_tip" data-tip="<?php echo __( 'Name or title of this Component.', 'woocommerce-composite-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
				</label>
				<input type="text" class="group_title component_text_input" name="bto_data[<?php echo $id; ?>][title]" value="<?php echo $title; ?>"/>
				<input type="hidden" name="bto_data[<?php echo $id; ?>][position]" class="group_position" value="<?php echo $position; ?>" />
			</div>
		</div>
		<?php
	}

	/**
	 * Add component config description option.
	 *
	 * @param  int    $id
	 * @param  array  $data
	 * @param  int    $product_id
	 * @return void
	 */
	function component_config_description( $id, $data, $product_id ) {

		$description = isset( $data[ 'description' ] ) ? $data[ 'description' ] : '';

		?>
		<div class="group_description">
			<div class="form-field">
				<label>
					<?php echo __( 'Component Description', 'woocommerce-composite-products' ); ?>
					<img class="help_tip" data-tip="<?php echo __( 'Optional short description of this Component.', 'woocommerce-composite-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
				</label>
				<textarea class="group_description" name="bto_data[<?php echo $id; ?>][description]" id="group_description_<?php echo $id; ?>" placeholder="" rows="2" cols="20"><?php echo esc_textarea( $description ); ?></textarea>
			</div>
		</div>
		<?php
	}

	/**
	 * Add component config multi select products option.
	 *
	 * @param  int    $id
	 * @param  array  $data
	 * @param  int    $product_id
	 * @return void
	 */
	function component_config_options( $id, $data, $product_id ) {

		global $woocommerce_composite_products;

		?>
		<div class="group_config_options hide-if-option-number">
			<div class="form-field">
				<label>
					<?php echo __( 'Component Options', 'woocommerce-composite-products' ); ?>
					<img class="help_tip" data-tip="<?php echo __( 'Options (products) of component.', 'woocommerce-composite-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
				</label>
				
				<div class="bto_selector bto_query_type_selector bto_multiselect bto_query_type_product_ids">
					<?php
		
					$product_id_options = array();
	
					if ( ! empty( $data[ 'assigned_ids' ] ) ) {
	
						$item_ids = $data[ 'assigned_ids' ];
	
						foreach ( $item_ids as $item_id ) {
	
							$product_title = $woocommerce_composite_products->api->get_product_title( $item_id );
	
							if ( $product_title ) {
	
								$product_id_options[ $item_id ] = $product_title;
							}
						}
	
					}
	
					?>
					
					<input type="hidden" id="bto_ids_<?php echo $id; ?>" name="bto_data[<?php echo $id; ?>][assigned_ids]" class="wc-product-search" style="width: 75%;" data-placeholder="<?php _e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products" data-multiple="true" data-selected="<?php
	
						echo esc_attr( json_encode( $product_id_options ) );
	
					?>" value="<?php echo implode( ',', array_keys( $product_id_options ) ); ?>" />
					
				</div>
		
				<?php
		
				// Hook here to add your own custom query config options
				do_action( 'woocommerce_composite_component_admin_config_query_options', $id, $data, $product_id );
				
				?>
				
			</div>
			
		</div>
		
		<?php
	}

	/**
	 * Add component config default selection option.
	 *
	 * @param  int    $id
	 * @param  array  $data
	 * @param  int    $product_id
	 * @return void
	 */
	function component_config_default_option( $id, $data, $product_id ) {

		global $woocommerce_composite_products;

		?>
		<div class="default_selector hide-if-option-number">
			<div class="form-field">
				<label>
					<?php echo __( 'Default Option', 'woocommerce-composite-products' ); ?>
					<img class="help_tip" data-tip="<?php echo __( 'Select a product that you want to use as the default (pre-selected) Component Option. To use this option, you must first add some products in the <strong>Component Options</strong> field and then save your configuration.', 'woocommerce-composite-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
				</label><?php

				// Run query to get component option ids
				$item_ids = $woocommerce_composite_products->api->get_component_options( $data );

				if ( ! empty( $item_ids ) ) {

					// If < 30 show a dropdown, otherwise show an ajax chosen field
					if ( count( $item_ids ) < 30 ) {

						?><select id="group_default_<?php echo $id; ?>" name="bto_data[<?php echo $id; ?>][default_id]">
							<option value=""><?php echo __( 'No default option&hellip;', 'woocommerce-composite-products' ); ?></option><?php

							$selected_default = $data[ 'default_id' ];

							foreach ( $item_ids as $item_id ) {

								$product_title = $woocommerce_composite_products->api->get_product_title( $item_id );

								if ( $product_title ) {
									echo '<option value="' . $item_id . '" ' . selected( $selected_default, $item_id, false ) . '>'. $product_title . '</option>';
								}
							}

						?></select><?php

					} else {

						$selected_default = $data[ 'default_id' ];
						$product_title    = '';

						if ( $selected_default ) {

							$product_title = $woocommerce_composite_products->api->get_product_title( $selected_default );
						}

						?>
						
						<input type="hidden" id="group_default_<?php echo $id; ?>" name="bto_data[<?php echo $id; ?>][default_id]" class="wc-component-options-search" style="width: 75%;" data-component_id="<?php echo isset( $data[ 'component_id' ] ) ? $data[ 'component_id' ] : ''; ?>" data-placeholder="<?php _e( 'No default selected. Search for a product&hellip;', 'woocommerce-composite-products' ); ?>" data-allow_clear="true" data-action="woocommerce_json_search_default_component_option" data-multiple="false" data-selected="<?php

							echo esc_attr( $product_title ? $product_title : __( 'No default selected. Search for a product&hellip;', 'woocommerce-composite-products' ) );

						?>" value="<?php echo $product_title ? $selected_default : ''; ?>" />
						
						<?php
						
					}

				} else {

					?><div class="prompt"><em><?php _e( 'To choose a default product, you must first add some products in the Component Options field and then save your configuration&hellip;', 'woocommerce-composite-products' ); ?></em></div><?php
				}

			?></div>
		</div>
		<?php
	}

	/**
	 * Add component config default value option.
	 *
	 * @param  int    $id
	 * @param  array  $data
	 * @param  int    $product_id
	 * @return void
	 */
	function component_config_default_value( $id, $data, $product_id ) {
		
		$default = isset( $data[ 'default_value' ] ) ? $data[ 'default_value' ] : '';

		?>
		<div class="group_default_value show-if-option-number" >
			<div class="form-field">
				<label for="group_optional_<?php echo $id; ?>">
					<?php echo __( 'Default Value', 'woocommerce-composite-products' ); ?>
					<img class="help_tip" data-tip="<?php echo __( 'The default value of the number field.', 'woocommerce-composite-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
				</label>
				<input type="number" name="bto_data[<?php echo $id; ?>][default_value]" value="<?php echo $default; ?>" />
			</div>
		</div>
		<?php
		
	}
	
	/**
	 * Add component config min value option.
	 *
	 * @param  int    $id
	 * @param  array  $data
	 * @param  int    $product_id
	 * @return void
	 */
	function component_config_min_value( $id, $data, $product_id ) {
		
		$min = isset( $data[ 'min_value' ] ) ? $data[ 'min_value' ] : 0;

		?>
		<div class="group_min_value show-if-option-number" >
			<div class="form-field">
				<label for="group_optional_<?php echo $id; ?>">
					<?php echo __( 'Min Value', 'woocommerce-composite-products' ); ?>
					<img class="help_tip" data-tip="<?php echo __( 'The minimum value of the number field.', 'woocommerce-composite-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
				</label>
				<input type="number" name="bto_data[<?php echo $id; ?>][min_value]" value="<?php echo $min; ?>" />
			</div>
		</div>
		<?php
		
	}
	
	/**
	 * Add component config max value option.
	 *
	 * @param  int    $id
	 * @param  array  $data
	 * @param  int    $product_id
	 * @return void
	 */
	function component_config_max_value( $id, $data, $product_id ) {
		
		$max = isset( $data[ 'max_value' ] ) ? $data[ 'max_value' ] : '';

		?>
		<div class="group_max_value show-if-option-number" >
			<div class="form-field">
				<label for="group_optional_<?php echo $id; ?>">
					<?php echo __( 'Max Value', 'woocommerce-composite-products' ); ?>
					<img class="help_tip" data-tip="<?php echo __( 'The maximum value of the number field.', 'woocommerce-composite-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
				</label>
				<input type="number" name="bto_data[<?php echo $id; ?>][max_value]" value="<?php echo $max; ?>" />
			</div>
		</div>
		<?php
		
	}
	
	/**
	 * Add component config step option.
	 *
	 * @param  int    $id
	 * @param  array  $data
	 * @param  int    $product_id
	 * @return void
	 */
	function component_config_step_value( $id, $data, $product_id ) {
		
		$step = isset( $data[ 'step_value' ] ) ? $data[ 'step_value' ] : 0.01;

		?>
		<div class="group_step_value show-if-option-number">
			<div class="form-field">
				<label for="group_optional_<?php echo $id; ?>">
					<?php echo __( 'Step Value', 'woocommerce-composite-products' ); ?>
					<img class="help_tip" data-tip="<?php echo __( 'The increment in which the number field should increase.', 'woocommerce-composite-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
				</label>
				<input type="number" name="bto_data[<?php echo $id; ?>][step_value]" value="<?php echo $step; ?>" />
			</div>
		</div>
		<?php
		
	}
	
	/**
	 * Add component config optional option.
	 *
	 * @param  int    $id
	 * @param  array  $data
	 * @param  int    $product_id
	 * @return void
	 */
	function component_config_optional( $id, $data, $product_id ) {

		$optional = isset( $data[ 'optional' ] ) ? $data[ 'optional' ] : '';

		?>
		<div class="group_optional" >
			<div class="form-field">
				<label for="group_optional_<?php echo $id; ?>">
					<?php echo __( 'Optional', 'woocommerce-composite-products' ); ?>
					<img class="help_tip" data-tip="<?php echo __( 'Checking this option will allow customers to proceed without making any selection for this Component at all.', 'woocommerce-composite-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
				</label>
				<input type="checkbox" class="checkbox"<?php echo ( $optional == 'yes' ? ' checked="checked"' : '' ); ?> name="bto_data[<?php echo $id; ?>][optional]" <?php echo ( $optional == 'yes' ? ' value="1"' : '' ); ?> />
			</div>
		</div>
		<?php
	}

	/**
	 * Admin writepanel scripts.
	 *
	 * @return void
	 */
	function composite_admin_scripts( $hook ) {

		global $post, $woocommerce_composite_products;

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		$writepanel_dependency = 'wc-admin-meta-boxes';

		wp_register_script( 'wc_composite_writepanel', $woocommerce_composite_products->plugin_url() . '/assets/js/admin/wc-composite-write-panels' . $suffix . '.js', array( 'jquery', 'jquery-ui-datepicker', $writepanel_dependency ), $woocommerce_composite_products->version );
		wp_register_style( 'wc_composite_writepanel_css', $woocommerce_composite_products->plugin_url() . '/assets/css/admin/wc-composite-write-panels.css', array( 'woocommerce_admin_styles' ), $woocommerce_composite_products->version );
		wp_register_style( 'wc_composite_edit_order_css', $woocommerce_composite_products->plugin_url() . '/assets/css/admin/wc-composite-edit-order.css', array( 'woocommerce_admin_styles' ), $woocommerce_composite_products->version );

		// Get admin screen id
		$screen = get_current_screen();

		// WooCommerce admin pages
		if ( in_array( $screen->id, array( 'product' ) ) ) {
			wp_enqueue_script( 'wc_composite_writepanel' );

			$params = array(
				'save_composite_nonce'      => wp_create_nonce( 'wc_bto_save_composite' ),
				'add_component_nonce'       => wp_create_nonce( 'wc_bto_add_component' ),
				'add_scenario_nonce'        => wp_create_nonce( 'wc_bto_add_scenario' ),
				'i18n_no_default'           => __( 'No default option&hellip;', 'woocommerce-composite-products' ),
				'i18n_all'                  => __( 'All Products and Variations', 'woocommerce-composite-products' ),
				'i18n_none'                 => __( 'None', 'woocommerce-composite-products' ),
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
			);

			wp_localize_script( 'wc_composite_writepanel', 'wc_composite_admin_params', $params );
		}

		if ( in_array( $screen->id, array( 'edit-product', 'product' ) ) )
			wp_enqueue_style( 'wc_composite_writepanel_css' );

		if ( in_array( $screen->id, array( 'shop_order', 'edit-shop_order' ) ) )
			wp_enqueue_style( 'wc_composite_edit_order_css' );
		
	}

	/**
	 * Adds the Composite Product write panel tabs.
	 *
	 * @return string
	 */
	function composite_write_panel_tabs() {

		echo '<li class="bto_product_tab show_if_composite composite_product_options"><a href="#bto_product_data">'.__( 'Components', 'woocommerce-composite-products' ).'</a></li>';
		echo '<li class="bto_product_tab show_if_composite composite_scenarios"><a href="#bto_scenario_data">'.__( 'Scenarios', 'woocommerce-composite-products' ).'</a></li>';
	}

	/**
	 * Adds the base and sale price option writepanel options.
	 *
	 * @return void
	 */
	function composite_pricing_options() {

		echo '<div class="options_group bto_base_pricing show_if_composite">';

		// Price
		woocommerce_wp_text_input( array( 'id' => '_base_regular_price', 'class' => 'short', 'label' => __( 'Base Regular Price', 'woocommerce-composite-products' ) . ' (' . get_woocommerce_currency_symbol().')', 'data_type' => 'price' ) );

		// Sale Price
		woocommerce_wp_text_input( array( 'id' => '_base_sale_price', 'class' => 'short', 'label' => __( 'Base Sale Price', 'woocommerce-composite-products' ) . ' (' . get_woocommerce_currency_symbol() . ')', 'data_type' => 'price' ) );

		echo '</div>';
	}

	/**
	 * Components and Scenarios write panels.
	 *
	 * @return void
	 */
	function composite_write_panel() {

		global $woocommerce_composite_products, $post, $wpdb;

		$bto_data = get_post_meta( $post->ID, '_bto_data', true );

		?>
		<div id="bto_product_data" class="bto_panel panel woocommerce_options_panel wc-metaboxes-wrapper">

			<div class="options_group bundle_group bto_clearfix">
				<p class="form-field">
					<label class="bundle_group_label">
						<?php _e( 'Options Style', 'woocommerce-composite-products' ); ?>
						<img class="help_tip" data-tip="<?php echo __( $this->get_option_styles_descriptions(), 'woocommerce-composite-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
					</label>
					<select name="bto_selection_mode">
						<?php 
							$mode = get_post_meta( $post->ID, '_bto_selection_mode', true );
							foreach($this->get_option_styles() as $option_style_key => $option_style) { ?>
							<option <?php selected( $mode, $option_style_key); ?> value="<?php echo $option_style_key; ?>"><?php _e( $option_style['title'], 'woocommerce-composite-products' ); ?></option>
						<?php } ?>
					</select>
				</p>
				<p class="form-field">
					<label class="bundle_group_label">
						<?php _e( 'Build a SKU?', 'woocommerce-composite-products' ); ?>
						<img class="help_tip" data-tip="<?php echo __( 'Check this box if would like to build a SKU from the components', 'woocommerce-composite-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
					</label>
					<input type="checkbox" name="bto_extension[bto_build_sku]" value="yes" id="bto_build_sku" <?php checked( get_post_meta($post->ID, '_bto_build_sku', true), 'yes' ); ?> />
				</p>
				<p class="form-field group_affect_sku" <?php echo get_post_meta($post->ID, '_bto_build_sku', true) != 'yes' ? 'style="display: none;"' : ''; ?>>
					<label class="bundle_group_label">
						<?php _e( 'SKU Start', 'woocommerce-composite-products' ); ?>
						<img class="help_tip" data-tip="<?php echo __( 'Please enter a start SKU for use when building the SKU', 'woocommerce-composite-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
					</label>
					<input type="text" name="bto_extension[bto_sku_start]" value="<?php echo get_post_meta($post->ID, '_bto_sku_start', true); ?>" id="bto_sku_start" />
				</p>
			</div>
			<div class="options_group config_group bto_clearfix">
				<p class="toolbar">
					<a href="#" class="help_tip tips" data-tip="<?php echo __( 'Composite Products consist of building blocks, called <strong>Components</strong>. Each Component offers an assortment of <strong>Component Options</strong> that correspond to existing Simple products, Variable products or Product Bundles.', 'woocommerce-composite-products' ); ?>" >[?]</a>
					<a href="#" class="close_all"><?php _e( 'Close all', 'woocommerce' ); ?></a>
					<a href="#" class="expand_all"><?php _e( 'Expand all', 'woocommerce' ); ?></a>
				</p>

				<div id="bto_config_group_inner">

					<div class="bto_groups wc-metaboxes ui-sortable" data-count="">

						<?php

						if ( $bto_data ) {

							$i = 0;

							foreach ( $bto_data as $group_id => $data ) {

								if ( ! isset( $data[ 'component_id' ] ) ) {
									$data[ 'component_id' ] = $group_id;
								}

								do_action('woocommerce_composite_component_admin_html', $data, $post->ID);

								$i++;
							}
						}

					?></div>
				</div>

				<p class="toolbar borderless">
					<button type="button" class="button save_composition"><?php _e( 'Save Configuration', 'woocommerce-composite-products' ); ?></button>
					<button type="button" class="button button-primary add_bto_group"><?php _e( 'Add Component', 'woocommerce-composite-products' ); ?></button>
				</p>
			</div> <!-- options group -->
		</div>
		<?php

		$bto_scenarios = get_post_meta( $post->ID, '_bto_scenario_data', true );

		?>
		<div id="bto_scenario_data" class="bto_panel panel woocommerce_options_panel wc-metaboxes-wrapper">

			<div class="options_group">

				<div id="bto_scenarios_inner"><?php

					if ( $bto_data ) {

						?><div id="bto-scenarios-info" class="inline woocommerce-message">
							<span class="question"><?php
								$tip = '<a href="#" class="tips" data-tip="' . __( 'Scenarios are different possible configurations of a Composite product. By default, Scenarios can be used to introduce dependencies between Component Options. Developers may create custom actions and attach them to Scenarios using the Scenario Actions API. The action(s) associated with a Scenario are triggered at every step of the configuration if the previously selected products/variations are present in the Scenario.', 'woocommerce-composite-products' ) . '">' . __( 'help', 'woocommerce-composite-products' ) . '</a>';
								echo sprintf( __( 'Need %s to set up <strong>Scenarios</strong> ?', 'woocommerce-composite-products' ), $tip );
							?></span></br>
							<a class="button-primary" href="<?php echo 'http://docs.woothemes.com/document/composite-products'; ?>" target="_blank"><?php _e( 'Learn more', 'woocommerce' ); ?></a>
							
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
											<input type="hidden" name="bto_scenario_data[<?php echo $i; ?>][scenario_id]" class="scenario_id" value="<?php echo $scenario_id; ?>"/>
										</h3>
										<div class="bto_scenario_data wc-metabox-content">
											<div class="options_group">
												<h4><?php echo __( 'Scenario Name &amp; Description', 'woocommerce-composite-products' ); ?></h4><?php

												do_action( 'woocommerce_composite_scenario_admin_info_html', $i, $scenario_data, $bto_data, $post->ID );

												?><h4><?php echo __( 'Scenario Configuration', 'woocommerce-composite-products' ); ?></h4><?php

												do_action( 'woocommerce_composite_scenario_admin_config_html', $i, $scenario_data, $bto_data, $post->ID );

												?><h4><?php echo __( 'Scenario Actions', 'woocommerce-composite-products' ); ?></h4><?php

												do_action( 'woocommerce_composite_scenario_admin_actions_html', $i, $scenario_data, $bto_data, $post->ID );

											?></div>
										</div>
									</div><?php

									$i++;
								}
							}

						?></div>

						<p class="toolbar borderless">
							<input type="file" name="import_scenarios" class="hide" id="importScenarios" onChange="if(confirm('Are you sure you want to upload this import file? This action will replace all existing scenarios')){this.form.submit();}" />
							
							<a href="#" onClick="jQuery('#importScenarios').trigger('click'); return false;" class="button-primary"><?php _e('Import Scenarios', 'woocommerce-composite-products-extension'); ?></a>
							
							<a href="<?php echo add_query_arg(array('action' => 'export_scenarios', 'product_id' => $post->ID, '_wpnonce' => wp_create_nonce('export_scenarios')), admin_url('admin-post.php')); ?>" class="button-primary"><?php _e('Export Scenarios', 'woocommerce-composite-products-extension'); ?></a>
							<button type="button" class="button button-primary add_bto_scenario"><?php _e( 'Add Scenario', 'woocommerce-composite-products' ); ?></button>
						</p><?php

					} else {

						?><div id="bto-scenarios-message" class="inline woocommerce-message">
							<div class="squeezer">
								<p><?php _e( 'Scenarios can be defined only after creating and saving some Components from the <strong>Components</strong> tab.', 'woocommerce-composite-products' ); ?></p>
								<p class="submit"><a class="button-primary" href="<?php echo 'http://docs.woothemes.com/document/composite-products'; ?>" target="_blank"><?php _e( 'Learn more', 'woocommerce' ); ?></a></p>
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
	function add_composite_type_options( $options ) {

		$options[ 'per_product_pricing_bto' ] = array(
			'id'            => '_per_product_pricing_bto',
			'wrapper_class' => 'show_if_composite bto_per_item_pricing',
			'label'         => __( 'Per-Item Pricing', 'woocommerce-composite-products' ),
			'description'   => __( 'When <strong>Per-Item Pricing</strong> is checked, the Composite product will be priced according to the cost of its contents. To add a fixed amount to the Composite price when thr <strong>Per-Item Pricing</strong> option is checked, use the Base Price fields below.', 'woocommerce-composite-products' ),
			'default'       => 'no'
		);

		return $options;
	}

	/**
	 * Adds the 'composite product' type to the menu.
	 *
	 * @param  array 	$options
	 * @return array
	 */
	function add_composite_type( $options ) {

		$options[ 'composite' ] = __( 'Composite product', 'woocommerce-composite-products' );

		return $options;
	}

	/**
	 * Process, verify and save composite product data.
	 *
	 * @param  int 	$post_id
	 * @return void
	 */
	function process_composite_meta( $post_id ) {

		global $woocommerce_composite_products;

		// Per-Item Pricing

		if ( isset( $_POST[ '_per_product_pricing_bto' ] ) ) {

			update_post_meta( $post_id, '_per_product_pricing_bto', 'yes' );

			update_post_meta( $post_id, '_base_sale_price', $_POST[ '_base_sale_price' ] === '' ? '' : stripslashes( wc_format_decimal( $_POST[ '_base_sale_price' ] ) ) );
			update_post_meta( $post_id, '_base_regular_price', $_POST[ '_base_regular_price' ] === '' ? '' : stripslashes( wc_format_decimal( $_POST[ '_base_regular_price' ] ) ) );

			if ( $_POST[ '_base_sale_price' ] !== '' ) {
				update_post_meta( $post_id, '_base_price', stripslashes( wc_format_decimal( $_POST[ '_base_sale_price' ] ) ) );
			} else {
				update_post_meta( $post_id, '_base_price', stripslashes( wc_format_decimal( $_POST[ '_base_regular_price' ] ) ) );
			}

		} else {

			update_post_meta( $post_id, '_per_product_pricing_bto', 'no' );
		}


		// Shipping
		// Non-Bundled (per-item) Shipping

		if ( isset( $_POST[ '_per_product_shipping_bto' ] ) ) {
			update_post_meta( $post_id, '_per_product_shipping_bto', 'yes' );
			update_post_meta( $post_id, '_virtual', 'yes' );
			update_post_meta( $post_id, '_weight', '' );
			update_post_meta( $post_id, '_length', '' );
			update_post_meta( $post_id, '_width', '' );
			update_post_meta( $post_id, '_height', '' );
		} else {
			update_post_meta( $post_id, '_per_product_shipping_bto', 'no' );
			update_post_meta( $post_id, '_virtual', 'no' );
			update_post_meta( $post_id, '_weight', stripslashes( $_POST[ '_weight' ] ) );
			update_post_meta( $post_id, '_length', stripslashes( $_POST[ '_length' ] ) );
			update_post_meta( $post_id, '_width', stripslashes( $_POST[ '_width' ] ) );
			update_post_meta( $post_id, '_height', stripslashes( $_POST[ '_height' ] ) );
		}

		$this->save_composite_config( $post_id, $_POST );

	}

	/**
	 * Save components and scenarios.
	 *
	 * @param  int   $post_id
	 * @param  array $posted_composite_data
	 * @return boolean
	 */
	function save_composite_config( $post_id, $posted_composite_data ) {

		global $woocommerce_composite_products, $wpdb;

		// Composite style

		$composite_layout = 'single';

		if ( isset( $posted_composite_data[ 'bto_style' ] ) ) {
			$composite_layout = stripslashes( $posted_composite_data[ 'bto_style' ] );
		}

		update_post_meta( $post_id, '_bto_style', $composite_layout );

		// Composite selection mode

		$composite_options_style = 'dropdowns';

		if ( isset( $posted_composite_data[ 'bto_selection_mode' ] ) ) {
			$composite_options_style = stripslashes( $posted_composite_data[ 'bto_selection_mode' ] );
		}

		update_post_meta( $post_id, '_bto_selection_mode', $composite_options_style );

		// Process Composite Product Configuration
		$zero_product_item_exists = false;
		$component_options_count  = 0;
		$bto_data                 = get_post_meta( $post_id, '_bto_data', true );

		if ( ! $bto_data ) {
			$bto_data = array();
		}

		if ( isset( $posted_composite_data[ 'bto_data' ] ) ) {

			/* -------------------------- */
			/* Components
			/* -------------------------- */

			$counter  = 0;
			$ordering = array();

			foreach ( $posted_composite_data[ 'bto_data' ] as $row_id => $post_data ) {

				$bto_ids     = isset( $post_data[ 'assigned_ids' ] ) ? $post_data[ 'assigned_ids' ] : '';

				$group_id    = isset ( $post_data[ 'group_id' ] ) ? stripslashes( $post_data[ 'group_id' ] ) : ( current_time( 'timestamp' ) + $counter );
				$counter++;

				$bto_data[ $group_id ] = array();

				// Save component id
				$bto_data[ $group_id ][ 'component_id' ] = $group_id;

				if ( ! empty( $bto_ids ) ) {

					if ( is_array( $bto_ids ) ) {
						$bto_ids = array_map( 'intval', $post_data[ 'assigned_ids' ] );
					} else {
						$bto_ids = array_filter( array_map( 'intval', explode( ',', $post_data[ 'assigned_ids' ] ) ) );
					}

					foreach ( $bto_ids as $key => $id ) {

						// Get product type
						$terms        = get_the_terms( $id, 'product_type' );
						$product_type = ! empty( $terms ) && isset( current( $terms )->name ) ? sanitize_title( current( $terms )->name ) : 'simple';

						if ( $id && $id > 0 && in_array( $product_type, apply_filters( 'woocommerce_composite_products_supported_types', array( 'simple', 'variable', 'bundle' ) ) ) && $post_id != $id ) {

							// Check that product exists

							if ( ! get_post( $id ) ) {
								continue;
							}

							// Check Bundles version
							if ( $product_type == 'bundle' ) {

								global $woocommerce_bundles;

								if ( empty( $woocommerce_bundles ) || version_compare( $woocommerce_bundles->version, '4.8.7' ) < 0  ) {
									$this->save_errors[] = $this->add_admin_error( sprintf( __( 'To add Product Bundles to your Composites, please install Product Bundles version %s or newer.', 'woocommerce-composite-products' ), '4.8.7' ) );
									continue;
								}

							} else {

								$error = apply_filters( 'woocommerce_composite_products_custom_type_save_error', false, $id );

								if ( $error ) {
									$this->save_errors[] = $this->add_admin_error( $error );
									continue;
								}
							}

							// Save assigned ids
							$bto_data[ $group_id ][ 'assigned_ids' ][] = $id;
						}

					}

					if ( ! empty( $bto_data[ $group_id ][ 'assigned_ids' ] ) ) {
						$bto_data[ $group_id ][ 'assigned_ids' ] = array_unique( $bto_data[ $group_id ][ 'assigned_ids' ] );
					}

				}

				// Run query to get component option ids
				$component_options = $woocommerce_composite_products->api->get_component_options( $bto_data[ $group_id ] );

				// Add up options
				$component_options_count += count( $component_options );

				// Save default preferences
				if ( isset( $post_data[ 'default_id' ] ) && ! empty( $post_data[ 'default_id' ] ) && count( $component_options ) > 0 ) {

					if ( in_array( $post_data[ 'default_id' ], $component_options ) )

						$bto_data[ $group_id ][ 'default_id' ] = stripslashes( $post_data[ 'default_id' ] );

					else {

						$bto_data[ $group_id ][ 'default_id' ] = '';

						if ( ! empty( $post_data[ 'title' ] ) )
							$this->save_errors[] = $this->add_admin_error( sprintf( __( 'The default option that you selected for \'%s\' is inconsistent with the set of active Component Options. Always double-check your preferences before saving, and always save any changes made to the Component Options before choosing new defaults.', 'woocommerce-composite-products' ), strip_tags( stripslashes( $post_data[ 'title' ] ) ) ) );
					}

				} else {

					// If the component option is only one, set it as default
					if ( count( $component_options ) == 1 && ! isset( $post_data[ 'optional' ] ) )
						$bto_data[ $group_id ][ 'default_id' ] = $component_options[0];
					else
						$bto_data[ $group_id ][ 'default_id' ] = '';
				}

				// Save title preferences
				if ( isset( $post_data[ 'title' ] ) && ! empty( $post_data[ 'title' ] ) ) {
					$bto_data[ $group_id ][ 'title' ] = strip_tags ( stripslashes( $post_data[ 'title' ] ) );
				} else {
					$bto_data[ $group_id ][ 'title' ] = 'Untitled Component';
					$this->save_errors[] = $this->add_admin_error( __( 'Please give a valid Name to all Components before publishing.', 'woocommerce-composite-products' ) );

					if ( isset( $posted_composite_data[ 'post_status' ] ) && $posted_composite_data[ 'post_status' ] == 'publish' ) {
						global $wpdb;
						$wpdb->update( $wpdb->posts, array( 'post_status' => 'draft' ), array( 'ID' => $post_id ) );
					}
				}

				// Save description preferences
				if ( isset( $post_data[ 'description' ] ) && ! empty( $post_data[ 'description' ] ) ) {
					$bto_data[ $group_id ][ 'description' ] = wp_kses_post( stripslashes( $post_data[ 'description' ] ) );
				} else {
					$bto_data[ $group_id ][ 'description' ] = '';
				}

				// Save optional data
				if ( isset( $post_data[ 'optional' ] ) ) {
					$bto_data[ $group_id ][ 'optional' ] = 'yes';
				} else {
					$bto_data[ $group_id ][ 'optional' ] = 'no';
				}

				// Prepare position data
				if ( isset( $post_data[ 'position' ] ) ) {
					$ordering[ (int) $post_data[ 'position' ] ] = $group_id;
				} else {
					$ordering[ count( $ordering ) ] = $group_id;
				}

				// Process custom data - add custom errors via $woocommerce_composite_products->admin->add_error()
				$bto_data[ $group_id ] = apply_filters( 'woocommerce_composite_process_component_data', $bto_data[ $group_id ], $post_data, $group_id, $post_id );
				
				// Save default preferences
				if ( isset( $post_data[ 'recommended_id' ] ) && ! empty( $post_data[ 'recommended_id' ] ) ) {
		
					if ( in_array( $post_data[ 'recommended_id' ], $component_options ) )
		
						$bto_data[ $group_id ][ 'recommended_id' ] = stripslashes( $post_data[ 'recommended_id' ] );
		
					else {
		
						$bto_data[ 'recommended_id' ] = '';
		
						if ( ! empty( $post_data[ 'title' ] ) )
							$this->add_error( sprintf( __( 'The default option that you selected for \'%s\' is inconsistent with the set of active Component Options. Always double-check your preferences before saving, and always save any changes made to the Component Options before choosing new defaults.', 'woocommerce-composite-products' ), strip_tags( stripslashes( $post_data[ 'title' ] ) ) ) );
					}
		
				}
				
				if ( isset( $post_data[ 'option_style' ] ) ) {
					$bto_data[ $group_id ][ 'option_style' ] = stripslashes( $post_data[ 'option_style' ] );
				} else {
					$bto_data[ $group_id ][ 'option_style' ] = -1;
				}
				
				if ( isset( $post_data[ 'affect_sku' ] ) ) {
					$bto_data[ $group_id ][ 'affect_sku' ] = 'yes';
				} else {
					$bto_data[ $group_id ][ 'affect_sku' ] = 'no';
				}
				
				if ( isset( $post_data[ 'affect_sku_order' ] ) ) {
					$bto_data[ $group_id ][ 'affect_sku_order' ] = $post_data[ 'affect_sku_order' ];
				} else {
					$bto_data[ $group_id ][ 'affect_sku_order' ] = '';
				}
				
				if ( isset( $post_data[ 'affect_sku_default' ] ) ) {
					$bto_data[ $group_id ][ 'affect_sku_default' ] = $post_data[ 'affect_sku_default' ];
				} else {
					$bto_data[ $group_id ][ 'affect_sku_default' ] = '';
				}
				
				if( isset ( $post_data['sku_options'] ) ) {
					$bto_data[ $group_id ][ 'sku_options' ] = $post_data['sku_options'];
				} else {
					$bto_data[ $group_id ][ 'sku_options' ] = array();
				}	
				
				if( isset ( $post_data['price_options'] ) ) {
					$bto_data[ $group_id ][ 'price_options' ] = $post_data['price_options'];
				} else {
					$bto_data[ $group_id ][ 'price_options' ] = array();
				}
				
				if ( isset( $post_data[ 'default_value' ] ) ) {
					$bto_data[ $group_id ][ 'default_value' ] = $post_data[ 'default_value' ];
				} else {
					$bto_data[ $group_id ][ 'default_value' ] = '';
				}
				
				if ( isset( $post_data[ 'step_value' ] ) ) {
					$bto_data[ $group_id ][ 'step_value' ] = $post_data[ 'step_value' ];
				} else {
					$bto_data[ $group_id ][ 'step_value' ] = 0.01;
				}
				
				if ( isset( $post_data[ 'min_value' ] ) ) {
					$bto_data[ $group_id ][ 'min_value' ] = $post_data[ 'min_value' ];
				} else {
					$bto_data[ $group_id ][ 'min_value' ] = 0;
				}
				
				if ( isset( $post_data[ 'max_value' ] ) ) {
					$bto_data[ $group_id ][ 'max_value' ] = $post_data[ 'max_value' ];
				} else {
					$bto_data[ $group_id ][ 'max_value' ] = '';
				}
				
				if( isset ( $post_data['price_formula'] ) ) {
					$bto_data[ $group_id ][ 'price_formula' ] = $post_data['price_formula'];
				} else {
					$bto_data[ $group_id ][ 'price_formula' ] = '';
				}
				
				if( isset( $post_data['tag_numbers'] ) ) {
					$bto_data[ $group_id ][ 'tag_numbers' ] = 'yes';
				} else {
					$bto_data[ $group_id ][ 'tag_numbers' ] = 'no';
				}
				
				if( isset( $post_data['sovereign'] ) ) {
					$bto_data[ $group_id ][ 'sovereign' ] = 'yes';
				} else {
					$bto_data[ $group_id ][ 'sovereign' ] = 'no';
				}

				// Invalidate query cache
				if ( class_exists( 'WC_Cache_Helper' ) ) {
					WC_Cache_Helper::get_transient_version( 'wccp_q', true );
				}

				if ( ! wp_using_ext_object_cache() ) {
					// Delete query transients
					$wpdb->query( "DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_wccp_q_" . $group_id . "_%') OR `option_name` LIKE ('_transient_timeout_wccp_q_" . $group_id . "_%')" );
				}
			}

			ksort( $ordering );
			$ordered_bto_data = array();
			$ordering_loop    = 0;
			foreach ( $ordering as $group_id ) {
				$ordered_bto_data[ $group_id ]               = $bto_data[ $group_id ];
				$ordered_bto_data[ $group_id ][ 'position' ] = $ordering_loop;
				$ordering_loop++;
			}


			// Prompt user to activate the right options when a Composite includes a large number of Component Options
			if ( $component_options_count > 100 ) {

				$show_large_composite_prompt = false;
				$ppp_prompt                  = '';
				$dropdowns_prompt            = '';

				if ( isset( $_POST[ '_per_product_pricing_bto' ] ) ) {
					$show_large_composite_prompt = true;
					$ppp_prompt = __( ' To avoid placing a big load on your server, consider checking the Hide Price option, located in the General tab. This setting will bypass all min/max pricing calculations which typically happen during product load when the Per-Item Pricing option is checked.', 'woocommerce-composite-products' );
				}

				if ( isset( $posted_composite_data[ 'bto_selection_mode' ] ) && $posted_composite_data[ 'bto_selection_mode' ] === 'dropdowns' ) {
					$show_large_composite_prompt = true;
					$dropdowns_prompt = sprintf( __( ' %s consider switching the Options Style of your Composite to Product Thumbnails. This setting can be changed from the Components tab.', 'woocommerce-composite-products' ), $ppp_prompt === '' ? __( 'To reduce the load on your server and make the configuration process easier,', 'woocommerce-composite-products' ) : __( 'To further reduce the load on your server and make the configuration process easier,', 'woocommerce-composite-products' ) );
				}

				if ( $show_large_composite_prompt ) {
					$large_composite_prompt = sprintf( __( 'You have added a significant number of Component Options to this Composite.%1$s%2$s', 'woocommerce-composite-products' ), $ppp_prompt, $dropdowns_prompt );

					$this->save_errors[] = $this->add_admin_error( $large_composite_prompt );
				}
			}


			/* -------------------------- */
			/* Scenarios
			/* -------------------------- */

			// Convert posted data coming from select2 ajax inputs
			$compat_scenario_data = array();

			if ( isset( $posted_composite_data[ 'bto_scenario_data' ] ) ) {
				foreach ( $posted_composite_data[ 'bto_scenario_data' ] as $scenario_id => $scenario_post_data ) {

					$compat_scenario_data[ $scenario_id ] = $scenario_post_data;

					if ( isset( $scenario_post_data[ 'component_data' ] ) ) {
						foreach ( $scenario_post_data[ 'component_data' ] as $component_id => $products_in_scenario ) {

							if ( ! empty( $products_in_scenario ) ) {
								if ( is_array( $products_in_scenario ) ) {
									$compat_scenario_data[ $scenario_id ][ 'component_data' ][ $component_id ] = array_unique( array_map( 'intval', $products_in_scenario ) );
								} else {
									$compat_scenario_data[ $scenario_id ][ 'component_data' ][ $component_id ] = array_unique( array_map( 'intval', explode( ',', $products_in_scenario ) ) );
								}
							} else {
								$compat_scenario_data[ $scenario_id ][ 'component_data' ][ $component_id ] = array();
							}
						}
					}
				}

				$posted_composite_data[ 'bto_scenario_data' ] = $compat_scenario_data;
			}
			// End conversion

			// Start processing
			$bto_scenario_data          = array();
			$ordered_bto_scenario_data  = array();
			$compat_group_actions_exist = false;
			$masked_rules_exist         = false;

			if ( isset( $posted_composite_data[ 'bto_scenario_data' ] ) ) {

				$counter = 0;
				$scenario_ordering = array();

				foreach ( $posted_composite_data[ 'bto_scenario_data' ] as $scenario_id => $scenario_post_data ) {

					$scenario_id = isset ( $scenario_post_data[ 'scenario_id' ] ) ? stripslashes( $scenario_post_data[ 'scenario_id' ] ) : ( current_time( 'timestamp' ) + $counter );
					$counter++;

					$bto_scenario_data[ $scenario_id ] = array();

					// Save scenario title
					if ( isset( $scenario_post_data[ 'title' ] ) && ! empty( $scenario_post_data[ 'title' ] ) ) {
						$bto_scenario_data[ $scenario_id ][ 'title' ] = strip_tags ( stripslashes( $scenario_post_data[ 'title' ] ) );
					} else {
						unset( $bto_scenario_data[ $scenario_id ] );
						$this->save_errors[] = $this->add_admin_error( __( 'Please give a valid Name to all Scenarios before saving.', 'woocommerce-composite-products' ) );
						continue;
					}

					// Save scenario description
					if ( isset( $scenario_post_data[ 'description' ] ) && ! empty( $scenario_post_data[ 'description' ] ) ) {
						$bto_scenario_data[ $scenario_id ][ 'description' ] = wp_kses_post( stripslashes( $scenario_post_data[ 'description' ] ) );
					} else {
						$bto_scenario_data[ $scenario_id ][ 'description' ] = '';
					}

					// Prepare position data
					if ( isset( $scenario_post_data[ 'position' ] ) ) {
						$scenario_ordering[ ( int ) $scenario_post_data[ 'position' ] ] = $scenario_id;
					} else {
						$scenario_ordering[ count( $scenario_ordering ) ] = $scenario_id;
					}

					$bto_scenario_data[ $scenario_id ][ 'scenario_actions' ] = array();

					// Save scenario action(s)
					if ( isset( $scenario_post_data[ 'scenario_actions' ][ 'compat_group' ] ) ) {

						if ( ! empty( $scenario_post_data[ 'scenario_actions' ][ 'compat_group' ][ 'is_active' ] ) ) {
							$bto_scenario_data[ $scenario_id ][ 'scenario_actions' ][ 'compat_group' ][ 'is_active' ] = 'yes';
							$compat_group_actions_exist = true;
						}
					} else {
						$bto_scenario_data[ $scenario_id ][ 'scenario_actions' ][ 'compat_group' ][ 'is_active' ] = 'no';
					}

					// Save component options in scenario
					$bto_scenario_data[ $scenario_id ][ 'component_data' ] = array();

					foreach ( $ordered_bto_data as $group_id => $group_data ) {

						// Save modifier flag
						if ( isset( $scenario_post_data[ 'modifier' ][ $group_id ] ) && $scenario_post_data[ 'modifier' ][ $group_id ] === 'not-in' ) {

							if ( ! empty( $scenario_post_data[ 'component_data' ][ $group_id ] ) ) {

								if ( $woocommerce_composite_products->api->scenario_contains_product( $scenario_post_data, $group_id, 0 ) ) {
									$bto_scenario_data[ $scenario_id ][ 'modifier' ][ $group_id ] = 'in';
								} else {
									$bto_scenario_data[ $scenario_id ][ 'modifier' ][ $group_id ] = 'not-in';
								}
							} else {
								$bto_scenario_data[ $scenario_id ][ 'modifier' ][ $group_id ] = 'in';
							}

						} elseif ( isset( $scenario_post_data[ 'modifier' ][ $group_id ] ) && $scenario_post_data[ 'modifier' ][ $group_id ] === 'masked' ) {
							$bto_scenario_data[ $scenario_id ][ 'modifier' ][ $group_id ] = 'masked';

							$masked_rules_exist = true;

							if ( ! $woocommerce_composite_products->api->scenario_contains_product( $scenario_post_data, $group_id, 0 ) ) {
								$scenario_post_data[ 'component_data' ][ $group_id ][] = 0;
							}
						} else {
							$bto_scenario_data[ $scenario_id ][ 'modifier' ][ $group_id ] = 'in';
						}


						$all_active = false;

						if ( ! empty( $scenario_post_data[ 'component_data' ][ $group_id ] ) ) {

							$bto_scenario_data[ $scenario_id ][ 'component_data' ][ $group_id ] = array();

							if ( $woocommerce_composite_products->api->scenario_contains_product( $scenario_post_data, $group_id, 0 ) ) {

								$bto_scenario_data[ $scenario_id ][ 'component_data' ][ $group_id ][] = 0;
								$all_active = true;
							}

							if ( $all_active ) {
								continue;
							}

							if ( $woocommerce_composite_products->api->scenario_contains_product( $scenario_post_data, $group_id, -1 ) ) {
								$bto_scenario_data[ $scenario_id ][ 'component_data' ][ $group_id ][] = -1;
							}

							// Run query to get component option ids
							$component_options = $woocommerce_composite_products->api->get_component_options( $group_data );


							foreach ( $scenario_post_data[ 'component_data' ][ $group_id ] as $item_in_scenario ) {

								if ( (int) $item_in_scenario === -1 || (int) $item_in_scenario === 0 ) {
									continue;
								}

								// Get product
								$product_in_scenario = get_product( $item_in_scenario );

								if ( $product_in_scenario->product_type === 'variation' ) {

									$parent_id = $product_in_scenario->id;

									if ( $parent_id && in_array( $parent_id, $component_options ) && ! in_array( $parent_id, $scenario_post_data[ 'component_data' ][ $group_id ] ) ) {
										$bto_scenario_data[ $scenario_id ][ 'component_data' ][ $group_id ][] = $item_in_scenario;
									}

								} else {

									if ( in_array( $item_in_scenario, $component_options ) ) {
										$bto_scenario_data[ $scenario_id ][ 'component_data' ][ $group_id ][] = $item_in_scenario;
									}
								}
							}

						} else {

							$bto_scenario_data[ $scenario_id ][ 'component_data' ][ $group_id ]   = array();
							$bto_scenario_data[ $scenario_id ][ 'component_data' ][ $group_id ][] = 0;
						}

					}

					// Process custom data - add custom errors via $woocommerce_composite_products->admin->add_error()
					$bto_scenario_data[ $scenario_id ] = apply_filters( 'woocommerce_composite_process_scenario_data', $bto_scenario_data[ $scenario_id ], $scenario_post_data, $scenario_id, $ordered_bto_data, $post_id );
					
				}

				// Re-order and save position data
				ksort( $scenario_ordering );
				$ordering_loop = 0;
				foreach ( $scenario_ordering as $scenario_id ) {
					$ordered_bto_scenario_data[ $scenario_id ]               = $bto_scenario_data[ $scenario_id ];
					$ordered_bto_scenario_data[ $scenario_id ][ 'position' ] = $ordering_loop;
				    $ordering_loop++;
				}

			}

			// Save config
			update_post_meta( $post_id, '_bto_data', $ordered_bto_data );
			update_post_meta( $post_id, '_bto_scenario_data', $ordered_bto_scenario_data );
			
			if(isset($_REQUEST['bto_extension']) && is_array($_REQUEST['bto_extension'])) {
				foreach($_REQUEST['bto_extension'] as $key => $val) {
					update_post_meta($post_id, '_' . $key, $val);
				}
			}
			
			if( ! isset( $_REQUEST['bto_extension']['bto_build_sku'] ) ) {
    			
    			update_post_meta($post_id, '_bto_build_sku', '');
    			
			} 

			// Initialize and save price meta
			$composite = wc_get_product( $post_id );

		}

		if ( ! isset( $posted_composite_data[ 'bto_data' ] ) || count( $bto_data ) == 0 ) {

			delete_post_meta( $post_id, '_bto_data' );

			$this->save_errors[] = $this->add_admin_error( __( 'Please create at least one Component before publishing. To add a Component, go to the Components tab and click on the Add Component button.', 'woocommerce-composite-products' ) );

			if ( isset( $posted_composite_data[ 'post_status' ] ) && $posted_composite_data[ 'post_status' ] == 'publish' ) {
				global $wpdb;
				$wpdb->update( $wpdb->posts, array( 'post_status' => 'draft' ), array( 'ID' => $post_id ) );
			}

			return false;
		}

		return true;
	}

	/**
	 * Handles saving composite config via ajax.
	 *
	 * @return void
	 */
	function ajax_composite_save() {

		check_ajax_referer( 'wc_bto_save_composite', 'security' );

		parse_str( $_POST[ 'data' ], $posted_composite_data );

		$post_id = absint( $_POST[ 'post_id' ] );

		$this->save_composite_config( $post_id, $posted_composite_data );

		header( 'Content-Type: application/json; charset=utf-8' );
		echo json_encode( $this->save_errors );
		die();
	}
	
	/**
	 * Handles component html being loaded in action 'woocommerce_composite_component_admin_html'
	 *
	 * @return void
	 */
	function component_admin_html($data = array(), $post_id, $toggle = 'closed') {
		
		$data = array_merge(array(
			'title' => '',
			'component_id' => '',
			'position' => 1
		), $data);
		
		$tabs = $this->get_component_tabs();
		
		include('html-component-admin.php');
		
	}
	
	/**
	 * Handles getting component tabs for use in component_admin_html
	 *
	 * @return array()
	 */
	
	function get_component_tabs() {
		
		return apply_filters('woocommerce_composite_extension_admin_component_tabs', array(
			'basic' => array(
				'title' => 'Basic',
				'action' => 'woocommerce_composite_component_admin_config_html',
				'classes' => array(),
			),
			'advanced' => array(
				'title' => 'Advanced',
				'action' => 'woocommerce_composite_component_admin_advanced_html',
				'classes' => array(),
			),
			array(
				'title' => 'SKU',
				'condition' => 'hide-if-option-number',
				'action' => 'woocommerce_composite_component_admin_sku_html',
				'classes' => array(),
			),
			array(
				'title' => 'Price',
				'action' => 'woocommerce_composite_component_admin_price_html',
				'classes' => array(),
			)
		));
		
	}

	/**
	 * Handles adding components via ajax.
	 *
	 * @return void
	 */
	function ajax_add_component() {

		check_ajax_referer( 'wc_bto_add_component', 'security' );

		$id      = intval( $_POST[ 'id' ] );
		$post_id = intval( $_POST[ 'post_id' ] );
		
		$data = array(
			'title' => '',
			'position' => $id,
		);

		do_action('woocommerce_composite_component_admin_html', $data, $post_id, 'open');

		die();
	}

	/**
	 * Handles adding scenarios via ajax.
	 *
	 * @return void
	 */
	function ajax_add_scenario() {

		global $woocommerce_composite_products;

		check_ajax_referer( 'wc_bto_add_scenario', 'security' );

		$id      = intval( $_POST[ 'id' ] );
		$post_id = intval( $_POST[ 'post_id' ] );

		$composite_data = get_post_meta( $post_id, '_bto_data', true );
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

		global $woocommerce_composite_products;

		ob_start();

		check_ajax_referer( 'search-products', 'security' );

		$term         = (string) wc_clean( stripslashes( $_GET[ 'term' ] ) );
		$composite_id = $_GET[ 'composite_id' ];
		$component_id = $_GET[ 'component_id' ];

		if ( empty( $term ) || empty( $composite_id ) || empty( $component_id ) ) {
			die();
		}

		$composite_data = get_post_meta( $composite_id, '_bto_data', true );
		$component_data = isset( $composite_data[ $component_id ] ) ? $composite_data[ $component_id ] : false;

		if ( false == $composite_data || false == $component_data ) {
			die();
		}

		// Run query to get component option ids
		$component_options = $woocommerce_composite_products->api->get_component_options( $component_data );

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
					$found_products[ $post ] = $woocommerce_composite_products->api->get_product_variation_title( $product );
				} else {
					if ( $x == 'search_component_options_in_scenario' && $product->product_type === 'variable' ) {
						$found_products[ $post ] = $woocommerce_composite_products->api->get_product_title( $product ) . ' ' . __( '&mdash; All Variations', 'woocommerce-composite-products' );
					} else {
						$found_products[ $post ] = $woocommerce_composite_products->api->get_product_title( $product );
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
	function composite_template_scan_path( $paths ) {

		global $woocommerce_composite_products;

		$paths[ 'WooCommerce Composite Products' ] = $woocommerce_composite_products->plugin_path() . '/templates/';

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
			'dropdowns' => array(
				'title' => 'Dropdowns',
				'description' => 'Component Options are listed in dropdown menus without any pagination. Use this setting if your Components include just a few product options. Also recommended if you want to keep the layout as compact as possible.',
			),
			'radios' => array(
				'title' => 'Radio Buttons',
				'description' => 'Component Options are presented as radio buttons, all in a vertical list. Radio buttons are disabled when outside of scenarios.',
			),
			'checkboxes' => array(
				'title' => 'Checkboxes',
				'description' => 'Component Options are presented as checkboxes, where many products can be selected, all in a vertical list. Checkboxes are disabled when outside of scenarios.',
			),
			'number' => array(
				'title' => 'Number',
				'description' => 'Component Option is presented as number field, where the user can enter a number according to the step interval you set.',
			)
		);
		
		return apply_filters('woocommerce_composite_products_selection_modes', $styles);
		
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
		
		return apply_filters('woocommerce_composite_products_option_style_description', $description);
		
	}
	
	public function component_config_recommended_option($id, $data, $product_id) {
		
		global $woocommerce_composite_products;
		
		?>
		
		<div class="recommended_option hide-if-option-number">
	
			<div class="form-field">
				
				<label>
					
					<?php echo __( 'Recommended Option', 'woocommerce-composite-products' ); ?>
					
					<img class="help_tip" data-tip="<?php echo __( 'Select a product that you want to use as the recommended Component Option. To use this option, you must first add some products in the <strong>Component Options</strong> field and then save your configuration.', 'woocommerce-composite-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
					
				</label>
				
				<?php
			
				// Run query to get component option ids
				$item_ids = $woocommerce_composite_products->api->get_component_options( $data );
			
				if ( ! empty( $item_ids ) ) {
			
					// If < 30 show a dropdown, otherwise show an ajax chosen field
					if ( count( $item_ids ) < 30 ) {
			
						?>
						
						<select id="group_recommended_<?php echo $id; ?>" name="bto_data[<?php echo $id; ?>][recommended_id]">
							
							<option value=""><?php echo __( 'No recommended option&hellip;', 'woocommerce-composite-products' ); ?></option>
							
							<?php
			
								$selected_recommended = $data[ 'recommended_id' ];
				
								foreach ( $item_ids as $item_id ) {
				
									$product_title = $woocommerce_composite_products->api->get_product_title( $item_id );
				
									if ( $product_title ) {
										
										echo '<option value="' . $item_id . '" ' . selected( $selected_recommended, $item_id, false ) . '>'. $product_title . '</option>';
										
									}
								}
				
							?>
						
						</select>
						
						<?php
			
					} elseif(isset($data[ 'recommended_id' ])) {
			
						$selected_recommended = $data[ 'recommended_id' ];
						
						$product_title    = '';
			
						if ( $selected_recommended ) {
			
							$product_title = $woocommerce_composite_products->api->get_product_title( $selected_recommended );
						}
			
						?>
						
						<input type="hidden" id="group_recommended_<?php echo $id; ?>" name="bto_data[<?php echo $id; ?>][recommended_id]" class="wc-component-options-search" style="width: 75%;" data-component_id="<?php echo isset( $data[ 'component_id' ] ) ? $data[ 'component_id' ] : ''; ?>" data-placeholder="<?php _e( 'No default selected. Search for a product&hellip;', 'woocommerce-composite-products' ); ?>" data-allow_clear="true" data-action="woocommerce_json_search_default_component_option" data-multiple="false" data-selected="<?php
			
								echo esc_attr( $product_title ? $product_title : __( 'No default selected. Search for a product&hellip;', 'woocommerce-composite-products' ) );
			
							?>" value="<?php echo $product_title ? $selected_recommended : ''; ?>" />
							
						<?php
							
					}
			
				} else {
			
					?>
					
					<div class="prompt"><em><?php _e( 'To choose a recommended product, you must first add some products in the Component Options field and then save your configuration&hellip;', 'woocommerce-composite-products' ); ?></em></div>
					
					<?php
				}
			
			?>
			
			</div>
			
		</div>
		
		<?php
		
	}
	
	public function component_config_tag_numbers_option($id, $data, $product_id) {
    	
    	?>
		
		<div class="option_style">
			
			<div class="form-field">
		
				<label class="bundle_group_label">
					
					<?php _e( 'Use Tag Numbers', 'woocommerce-composite-products' ); ?>
					
					<img class="help_tip" data-tip="<?php echo __( 'Check this box if you would this component to display an input for tag numbers during configuration', 'woocommerce-composite-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
					
				</label>
				
				<input type="checkbox" class="checkbox tag_numbers" name="bto_data[<?php echo $id; ?>][tag_numbers]" <?php echo isset( $data[ 'tag_numbers' ] ) ? checked($data[ 'tag_numbers' ], 'yes', false) : ''; ?> />
				
			</div>
			
		</div>
		
		<?php
    	
	}
	
	public function component_config_sovereign_option($id, $data, $product_id) {
    	
    	?>
		
		<div class="option_style">
			
			<div class="form-field">
		
				<label class="bundle_group_label">
					
					<?php _e( 'Make Sovereign', 'woocommerce-composite-products' ); ?>
					
					<img class="help_tip" data-tip="<?php echo __( 'Check this box if you would this component\'s options to always be available in spite of scenarios', 'woocommerce-composite-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
					
				</label>
				
				<input type="checkbox" class="checkbox sovereign" name="bto_data[<?php echo $id; ?>][sovereign]" <?php echo isset( $data[ 'sovereign' ] ) ? checked($data[ 'sovereign' ], 'yes', false) : ''; ?> />
				
			</div>
			
		</div>
		
		<?php
    	
	}
	
	public function component_layout_options_style($id, $data, $product_id) {
		
		?>
		
		<div class="option_style">
			
			<div class="form-field">
		
				<label class="bundle_group_label">
					
					<?php _e( 'Options Style', 'woocommerce-composite-products' ); ?>
					
					<img class="help_tip" data-tip="<?php echo __( $this->get_option_styles_descriptions(), 'woocommerce-composite-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
					
				</label>
				
				<select name="bto_data[<?php echo $id; ?>][option_style]" class="option_style_select">
					
					<?php $mode = $data[ 'option_style' ]; ?>
					
					<option <?php selected( $mode, 0); ?> value="0"><?php _e( 'Same as Above', 'woocommerce-composite-products' ); ?></option>
					
					<?php foreach($this->get_option_styles() as $option_style_key => $option_style) : ?>
					
						<option <?php selected( $mode, $option_style_key); ?> value="<?php echo $option_style_key; ?>"><?php _e( $option_style['title'], 'woocommerce-composite-products' ); ?></option>
						
					<?php endforeach; ?>
					
				</select>
				
			</div>
			
		</div>
		
		<?php
		
	}
	
	public function component_sku_affect_sku($id, $data, $product_id) {
		
		?>
		
		<div class="group_affect_sku" <?php echo get_post_meta($product_id, '_bto_build_sku', true) != 'yes' ? 'style="display: none;"' : ''; ?>>
	
			<div class="form-field">
		
				<label class="bundle_group_label">
					
					<?php _e( 'Affect SKU?', 'woocommerce-composite-products' ); ?>
					
					<img class="help_tip" data-tip="<?php echo __( 'Check this box if you would this component to affect the SKU built over the configuration', 'woocommerce-composite-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
					
				</label>
				
				<input type="checkbox" class="checkbox affect_sku" name="bto_data[<?php echo $id; ?>][affect_sku]" <?php echo isset($data[ 'affect_sku' ]) ? checked($data[ 'affect_sku' ], 'yes', false) : ''; ?> />
				
			</div>
			
		</div>
		
		<?php
		
	}
	
	public function component_sku_sku_order($id, $data, $product_id) {
		
		?>
		
		<div class="group_affected_by_sku group_affect_sku_order" <?php echo get_post_meta($product_id, '_bto_build_sku', true) != 'yes' || ! isset( $data[ 'affect_sku' ] ) || $data[ 'affect_sku' ] != 'yes' ? 'style="display: none;"' : ''; ?>>
	
			<div class="form-field">
		
				<label class="bundle_group_label">
					
					<?php _e( 'SKU Order', 'woocommerce-composite-products' ); ?>
					
					<img class="help_tip" data-tip="<?php echo __( 'Enter an integer in this field to set the order in which the SKU should be built, in ascending order', 'woocommerce-composite-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
					
				</label>
				
				<input type="number" min="0" name="bto_data[<?php echo $id; ?>][affect_sku_order]" value="<?php echo isset($data['affect_sku_order']) ? $data['affect_sku_order'] : ''; ?>" />
				
			</div>
			
		</div>
		
		<?php
		
	}
	
	public function component_sku_sku_default($id, $data, $product_id) {
		
		?>
		
		<div class="group_affected_by_sku group_affect_sku_default" <?php echo get_post_meta($product_id, '_bto_build_sku', true) != 'yes' || ! isset( $data[ 'affect_sku' ] ) || $data[ 'affect_sku' ] != 'yes' ? 'style="display: none;"' : ''; ?>>
	
			<div class="form-field">
		
				<label class="bundle_group_label">
					
					<?php _e( 'SKU Default', 'woocommerce-composite-products' ); ?>
					
					<img class="help_tip" data-tip="<?php echo __( 'Enter an a default sku for this component', 'woocommerce-composite-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
					
				</label>
				
				<input type="text"  name="bto_data[<?php echo $id; ?>][affect_sku_default]" value="<?php echo isset($data['affect_sku_default']) ? $data['affect_sku_default'] : ''; ?>" />
				
			</div>
			
		</div>
		
		<?php
		
	}
	
	public function component_sku_options($id, $data, $product_id) {
		
		global $woocommerce_composite_products;
		
		?>
		
		<div class="group_affected_by_sku group_affect_sku_options" <?php echo !isset($data[ 'affect_sku' ]) || !checked($data[ 'affect_sku' ], 'yes', false) ? 'style="display: none;"' : ''; ?>>
	
			<div class="form-field">
		
				<label class="bundle_group_label">
					
					<?php _e( 'SKU Options', 'woocommerce-composite-products' ); ?>
					
					<img class="help_tip" data-tip="<?php echo __( 'Enter the SKU to append the SKU Build per component option.', 'woocommerce-composite-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
					
				</label>
				
				<?php
			
				// Run query to get component option ids
				$item_ids = $woocommerce_composite_products->api->get_component_options( $data );
			
				if ( ! empty( $item_ids ) ) : ?>
				
					<table>
						
						<thead>
							
							<tr>
								
								<td><strong><?php _e('Component Option', 'woocommerce-composite-products'); ?></strong></td>
								
								<td><strong><?php _e('SKU', 'woocommerce-composite-products'); ?></strong></td>
								
							</tr>
							
						</thead>
						
						<tbody>
							
							<?php if(isset($data['optional']) && $data['optional'] == 'yes') : ?>
							
								<tr>
										
									<td><?php _e('None', 'woocommerce-companies'); ?></td>
									
									<td>
										
										<input type="text" name="bto_data[<?php echo $id; ?>][sku_options][-1]" value="<?php echo isset($data[ 'sku_options' ][ '-1' ]) ? $data[ 'sku_options' ][ '-1' ] : get_post_meta($item_id, '_sku', true); ?>" />
										
									</td>
									
								</tr>
							
							<?php endif; ?>
							
							<?php foreach ( $item_ids as $item_id ) : $product_title = $woocommerce_composite_products->api->get_product_title( $item_id ); ?>
							
								<tr>
									
									<td>
										
										<?php echo $product_title; ?>
										
									</td>
									
									<td>
										
										<input type="text" name="bto_data[<?php echo $id; ?>][sku_options][<?php echo $item_id; ?>]" value="<?php echo isset( $data[ 'sku_options' ][ $item_id ] ) ? $data[ 'sku_options' ][ $item_id ] : ''; ?>" />
										
									</td>
									
								</tr>
								
							<?php endforeach; ?>
							
						</tbody>
						
					</table>
					
				<?php else : ?>
					
					<div class="prompt"><em><?php _e( 'To set SKU options, you must first add some products in the Component Options field and then save your configuration&hellip;', 'woocommerce-composite-products' ); ?></em></div>
					
				<?php endif; ?>
				
			</div>
			
		</div>
		
		<?php
		
	}
	
	public function component_price_options($id, $data, $product_id) {
		
		global $woocommerce_composite_products;
		
		?>
		
		<div class="group_affected_by_price group_affect_price_options">
	
			<div class="form-field">
		
				<label class="bundle_group_label">
				
					<?php $is_formula = ( ! empty( $data['option_style'] ) && $data['option_style'] == 'number' ); ?>
					
					<?php echo $is_formula ? __( 'Price Formula', 'woocommerce-composite-products' ) : __( 'Price Options', 'woocommerce-composite-products' ); ?>
					
					<img class="help_tip" data-tip="<?php echo $is_formula ? __( 'Enter the Price formula for this component. Leave formula blank to keep price unaffected by this component.', 'woocommerce-composite-products' ) : __( 'Enter the Price to per component option. Leave prices blank if you want price to be retrieved from respective products.', 'woocommerce-composite-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
					
				</label>
				
				<?php if( ! empty( $data['option_style'] ) && $data['option_style'] == 'number' ) : ?>
				
					<?php $formula = isset( $data[ 'price_formula' ] ) ? $data[ 'price_formula' ] : ''; ?>
					
					<div class="prompt mg-b-1"><em><?php _e( 'If you are expecting to see the price configuration for your Component Options then you may need to Save Configuration below to continue.', 'woocommerce-composite-products' ); ?></em></div>
					
					<input type="text" name="bto_data[<?php echo $id; ?>][price_formula]" value="<?php echo $formula; ?>" placeholder="{n}*2.75" />
						
					<div class="clear"></div>
					
					<small>{n} represents the value of the component</small>
				
				<?php else :
			
					// Run query to get component option ids
					$item_ids = $woocommerce_composite_products->api->get_component_options( $data );
				
					if ( ! empty( $item_ids ) ) : ?>
					
						<table>
							
							<thead>
								
								<tr>
									
									<td><strong><?php _e('Component Option', 'woocommerce-composite-products'); ?></strong></td>
									
									<td><strong><?php _e('Price (£)', 'woocommerce-composite-products'); ?></strong></td>
									
								</tr>
								
							</thead>
							
							<tbody>
								
								<?php if(isset($data['optional']) && $data['optional'] == 'yes') : ?>
								
									<tr>
											
										<td><?php _e('None', 'woocommerce-companies'); ?></td>
										
										<td>
											
											<input type="text" name="bto_data[<?php echo $id; ?>][price_options][-1]" value="<?php echo isset($data[ 'price_options' ][ '-1' ]) ? $data[ 'price_options' ][ '-1' ] : ''; ?>" />
											
										</td>
										
									</tr>
								
								<?php endif; ?>
								
								<?php foreach ( $item_ids as $item_id ) : $product_title = $woocommerce_composite_products->api->get_product_title( $item_id ); ?>
								
									<tr>
										
										<td>
											
											<?php echo $product_title; ?>
											
										</td>
										
										<td>
											
											<?php $item = wc_get_product($item_id); ?>
											
											<input type="text" name="bto_data[<?php echo $id; ?>][price_options][<?php echo $item_id; ?>]" value="<?php echo isset( $data[ 'price_options' ][ $item_id ] ) ? $data[ 'price_options' ][ $item_id ] : ''; ?>" />
											
										</td>
										
									</tr>
									
								<?php endforeach; ?>
								
							</tbody>
							
						</table>
						
					<?php else : ?>
						
						<div class="prompt"><em><?php _e( 'To set price options, you must first add some products in the Component Options field and then save your configuration&hellip;', 'woocommerce-composite-products' ); ?></em></div>
						
					<?php endif; 
						
				endif; ?>	
				
			</div>
			
		</div>
		
		<?php
		
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
							
							array_unshift($component_data['assigned_ids'], -1);
							
						}
						
						ksort($component_data['assigned_ids']);
						
						foreach($component_data['assigned_ids'] as $sku_product_id) {
							
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
					
					wp_die( __( 'You attempted to download the scenario template for either a non Composite Product, or a Composite Product with no Component Data, must have been a mistake? <a href="' . get_edit_post_link($_REQUEST['product_id']) . '">Go back to the product screen</a>' ) );
					
				}
				
				if($product->bto_scenario_data) {
					
					foreach ( $product->bto_scenario_data as $scenario_id => $scenario_data ) {
						
						$modifiers = $scenario_data['modifier'];
						
						$data[0][] = $scenario_data['title'];
						$data[1][] = $scenario_data['description'];
							
						foreach($scenario_data['component_data'] as $component_id => $component_ids) {
							
							$modifier = $modifiers[$component_id];
							
							$component_data = $product->bto_data[$component_id];
							
							if($component_data['optional'] == 'yes') {
							
								array_unshift($component_data['assigned_ids'], -1);
								
							}
						
							foreach($component_data['assigned_ids'] as $sku_product_id) {
									
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
			
			$bto_scenario_data = get_post_meta( $product_id, '_bto_scenario_data', true);
			
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
			
			update_post_meta($product_id, '_bto_scenario_data', $scenario_data);
			
		}
		
		return true;
		
	}
	
	public function add_enctype_to_edit_form($post) {
		
		if($post->post_type == 'product') {
			
			echo ' enctype="multipart/form-data"';
			
		}
		
	}
	
}
