<?php
/**
 * Admin Add Scenario markup.
 * @version 3.1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;
?>


<div id="configuration_scenarios" class="js-cp-product-bind wc_cp_panel panel woocommerce_options_panel wc-metaboxes-wrapper">

	<div class="wc_cp_group options_group" rv-if="product.attributes.id">
			
		<div rv-if="product.attributes.components | length">
			
			<div rv-class-options_group="product.attributes.scenarios | length">
			
				<p class="toolbar">
					
					<a href="#" class="close_all"><?php _e( 'Close all', 'woocommerce' ); ?></a>
					
					<a href="#" class="expand_all"><?php _e( 'Expand all', 'woocommerce' ); ?></a>
					
				</p>
				
			</div>

			<div class="wc_cp_group-inner">
				
				<ul class="wc-metaboxes js-sortable ui-sortable" data-count="">
				
					<li rv-each-scenario="product.attributes.scenarios.models" class="bto_scenario wc-metabox js-metabox" rv-rel="scenario.attributes.position">
					
						<input type="hidden" class="js-item-position" rv-input-match="scenario.attributes.position" />
					
						<h3 rv-on-click="scenario.open">
							
							<a class="button wc_cp_right" rv-on-click="product.removeScenario" rv-scenario_cid="scenario.cid" rv-scenario_id="scenario.id"><?php echo __( 'Remove', 'woocommerce' ); ?></a>
							
							<div class="handlediv" title="<?php echo __( 'Click to toggle', 'woocommerce' ); ?>"></div>
							
							<strong class="scenario_name" rv-html="scenario.attributes.title"></strong>
							
							<input type="hidden"class="scenario_id" rv-value="scenario.attributes.id" />
							
						</h3>
						
						<div class="wc-metabox-content wc_cp_scenario_data js-scenario js-sortable-item-content" rv-class-hide="scenario.attributes.closed">
							
							<div class="wc_cp_group options_group">
								
								<div class="scenario_title">
					
									<div class="wc_cp_field">
										
										<label>
										
											<?php echo __( 'Title', 'woocommerce-configurable-products' ); ?>
											
											<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'Name or title of this Scenario.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
											
										</label>
										
										<input type="text" class="scenario_title component_text_input" rv-value="scenario.attributes.title"/>
										
									</div>
									
								</div>
								
								<div class="scenario_description">
									
									<div class="wc_cp_field">
										
										<label>
										
											<?php echo __( 'Description', 'woocommerce-configurable-products' ); ?>
											
											<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'Optional short description of this Scenario.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
											
										</label>
										
										<textarea class="scenario_description_ js-wc-cp-editor" rv-id="'scenario_description_' | append component.cid" placeholder="" rows="2" cols="20" rv-html="scenario.attributes.description | esc_textarea" rv-input-match="component.attributes.description"></textarea>
										
									</div>
									
								</div>
								
								<div class="scenario_active">
		
									<div class="wc_cp_field">
			
										<label>
											
											<?php echo __( 'Active', 'woocommerce-configurable-products' ); ?>
											
											<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( 'Toggle this Scenario on or off', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
											
										</label>
										
										<input type="checkbox" class="checkbox" value="1" rv-checked="scenario.attributes.active" />
									
									</div>
									
								</div>
								
								<div class="wc_cp_group bg-grey pd-1p5 options_group wc_cp_group">
								
									<h4 class="wc_cp_title"><?php echo __( 'Scenario Configuration', 'woocommerce-configurable-products' ); ?></h4>
	
									<div class="wc-metaboxes b-1 b-t-0">
										
										<div rv-each-component="product.attributes.components.models">
										
											<div class="wc_cp_metabox wc-metabox closed">
												
												<scenario_component depth="0" scenario="scenario" component="component"></scenario_component>
									
											</div>
											
										</div>
										
									</div>
									
								</div>
								
							</div>
							
							<span class="wc_cp_group wc_cp_entity_id" rv-if="scenario.attributes.id" rv-html="'#scenario_id | ' | append scenario.attributes.id"></span>
								
						</div>
								
					</li>
					
				</ul>
						
			</div>

			<p class="toolbar borderless">
				
				<button type="button" class="button save_composition" rv-on-click="product.saveConfiguration"><?php _e( 'Save Configuration', 'woocommerce-configurable-products' ); ?></button>
				
				<input type="file" name="import_scenarios" class="hide" id="importScenarios" onChange="if(confirm('Are you sure you want to upload this import file? This action will replace all existing scenarios')){this.form.submit();}" />
				
				<a href="#" onClick="jQuery('#importScenarios').trigger('click'); return false;" class="button-primary"><?php _e('Import Scenarios', 'woocommerce-configurable-products-extension'); ?></a>
				
				<a href="<?php echo add_query_arg(array('action' => 'export_scenarios', 'product_id' => $post->ID, '_wpnonce' => wp_create_nonce('export_scenarios')), admin_url('admin-post.php')); ?>" class="button-primary"><?php _e('Export Scenarios', 'woocommerce-configurable-products-extension'); ?></a>
				
				<a type="button" class="button button-primary wc_cp_right" rv-on-click="product.addScenario"><?php _e( 'Add Scenario', 'woocommerce-configurable-products' ); ?></a>
				
			</p>
			
		</div>

		<div rv-unless="product.attributes.components | length" id="bto-scenarios-message" class="wc_cp_panel-message inline woocommerce-message">
			
			<div class="squeezer">
				
				<p><?php _e( 'Scenarios can only be defined after creating and saving some Components from the <strong>Components</strong> tab.', 'woocommerce-configurable-products' ); ?></p>
				
			</div>
			
		</div>
		
	</div>
	
	<p rv-unless="product.attributes.id">Please save this Configurable Product before adding Scenarios.</p>
	
</div>

<script type="text/html" id="scenario_component">
	
	<h3 rv-on-click="scenario_component.open">
											
		<div class="handlediv" title="<?php _e( 'Click to toggle', 'woocommerce' ); ?>"></div>
		
		<strong class="component_name" rv-html="component.attributes.title"></strong>
		
	</h3>
	
	<div class="wc-metabox-content wc_cp_component_data" rv-class-hide="scenario_component.attributes.closed">
		
		<div class="wc_cp_group options_group">
			
			<div rv-if="component.attributes.source | = 'default'">
				
				<div rv-if="component.attributes.style | NOTIN 'text number'">

					<div class="wc_cp_field">
						
						<label>
						
							<?php echo __( 'Allow All', 'woocommerce-configurable-products' ); ?>
							
							<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( 'Check this box to allow all options to be selected for this Component in this Scenario', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
							
						</label>
						
						<input type="checkbox" class="checkbox" value="1" rv-checked="scenario_component.attributes.allow_all" />
						
					</div>
					
					<div class="wc_cp_field" rv-hide="scenario_component.attributes.allow_all">
					
						<label>
						
							<?php echo __( 'Selection is', 'woocommerce-configurable-products' ); ?>
							
							<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( 'Select if the Scenario should let the selection of this Component be inside our outside of the below Options', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
		
						
						</label>
							
						<select rv-value="scenario_component.attributes.modifier">
							
							<option value="in"><?php echo __( 'In' ); ?></option>
							
							<option value="not-in"><?php echo __( 'Not In' ); ?></option>
							
						</select>
						
					</div>
					
					<div class="wc_cp_field" rv-hide="scenario_component.attributes.allow_all">
						
						<label>
						
							<?php echo __( 'Options', 'woocommerce-configurable-products' ); ?>
							
							<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( 'Select the Options that are allowed in this Scenario', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
							
						</label>
						
						<select class="js-wc-product-select" rv-value="scenario_component.attributes.options" multiple>
							
							<optgroup rv-if="component.attributes.optional">
							
								<option rv-if="'-1' | IN scenario_component.attributes.options" value="-1" selected="selected">None</option>
								
								<option rv-unless="'-1' | IN scenario_component.attributes.options" value="-1">None</option>
								
							</optgroup>
							
							<optgroup rv-each-option="component.attributes.options.models">
							
								<option rv-if="option.attributes.id | IN scenario_component.attributes.options" rv-value="option.attributes.id" rv-html="option.attributes.title" selected="selected"></option>
			
								<option rv-unless="option.attributes.id | IN scenario_component.attributes.options" rv-value="option.attributes.id" rv-html="option.attributes.title"></option>
								
								<option rv-each-suboption="option:options" rv-value="suboption.attributes.id" rv-html="'- ' | append suboption.attributes.title" rv-selected="suboption.attributes.id | IN scenario_component.attributes.options"></option>
								
							</optgroup>
						
						</select>
						
					</div>
					
				</div>
				
				<div rv-if="component.attributes.style | IN 'text number'">

					<div class="wc_cp_field">
						
						<label>
						
							<?php echo __( 'Allow Field', 'woocommerce-configurable-products' ); ?>
							
							<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( 'Check this box to allow the field to be filled for this Component in this Scenario', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
							
						</label>
						
						<input type="checkbox" class="checkbox" value="1" rv-checked="scenario_component.attributes.allow_field" />
						
					</div>
					
				</div>
				
			</div>
			
		</div>
		
		<div rv-if="component.attributes.source | = 'subcomponents'">
			
			<div rv-each-subcomponent="component.attributes.components.models" rv-class-bg-grey="depth | odd" class="wc-metabox">
				
				<scenario_component scenario="scenario" depth="depth" component="subcomponent"></scenario_component>
				
			</div>
			
		</div>
		
		<span class="wc_cp_group wc_cp_entity_id" rv-if="scenario_component.attributes.id" rv-html="'#scenario_component_id | ' | append scenario_component.attributes.id"></span>
		
	</div>
	
</script>