<div id="configuration_components" class="js-cp-product-bind wc_cp_panel woocommerce_options_panel panel wc-metaboxes-wrapper">

	<div class="options_group">
		
		<p class="form-field wc_cp_field">
			
			<label class="wc_cp_label">
			
				<?php _e( 'Options Style', 'woocommerce-configurable-products' ); ?>
				
				<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( $this->get_option_styles_descriptions(), 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
				
			</label>
			
			<select name="wc_configuration[style]" rv-value="product:style">
				
				<?php foreach($this->get_option_styles() as $option_style_key => $option_style) : ?>
				
					<option <?php selected( $style, $option_style_key); ?> value="<?php echo $option_style_key; ?>"><?php _e( $option_style['title'], 'woocommerce-configurable-products' ); ?></option>
					
				<?php endforeach; ?>
				
			</select>
			
		</p>
		
		<p class="form-field wc_cp_field">
			
			<label class="wc_cp_label">
			
				<?php _e( 'Build a SKU?', 'woocommerce-configurable-products' ); ?>
				
				<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( 'Check this box if would like to build a SKU from the components', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
				
			</label>
			
			<input type="checkbox" rv-checked="product:build_sku" name="wc_configuration[build_sku]" value="1" />
			
		</p>
		
		<p class="form-field wc_cp_field group_affect_sku" rv-if="product:build_sku">
			
			<label class="wc_cp_label">
			
				<?php _e( 'SKU Start', 'woocommerce-configurable-products' ); ?>
				
				<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( 'Please enter a start SKU for use when building the SKU', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
				
			</label>
			
			<input type="text" name="wc_configuration[sku_start]" rv-value="product:sku_start" />
			
		</p>
		
	</div>
	
	<div class="wc_cp_group options_group">
		
		<p class="toolbar">
			
			<a href="#" class="wc_cp_help_tip help_tip tips" data-tip="<?php echo __( 'Configurable Products consist of building blocks, called <strong>Components</strong>. Each Component offers an assortment of <strong>Component Options</strong> that correspond to existing Simple products, Variable products or Product Bundles.', 'woocommerce-configurable-products' ); ?>" >[?]</a>
			
			<a href="#" class="close_all"><?php _e( 'Close all', 'woocommerce' ); ?></a>
			
			<a href="#" class="expand_all"><?php _e( 'Expand all', 'woocommerce' ); ?></a>
			
		</p>

		<div class="wc_cp_group-inner">

			<div class="wc-metaboxes ui-sortable" data-count="">
				
				<div class="wc_cp_metabox wc-metabox" rv-each-component="product:components" rv-class-closed="component:closed" rv-data-position="component:position">
					
					<h3>
						
						<button type="button" class="button"><?php _e( 'Remove', 'woocommerce' ); ?></button>
						
						<div class="handlediv" title="<?php _e( 'Click to toggle', 'woocommerce' ); ?>"></div>
						
						<strong class="group_name" rv-html="component:title"></strong>
						
						<input type="hidden" rv-value="component:id" />
						
					</h3>
					
					<div class="wc-metabox-content wc_cp_group_data js-component">
						
						<ul class="subsubsub wc_cp_inline-list js-component-list" rv-if="component:source | = 'default'">
							
							<li><a href="#" data-tab="settings" class="current">Settings</a></li>
							
							<li rv-if="component:style | IN 'text number'">| <a href="#" data-tab="field-settings">Field Settings</a></li>
							
							<li rv-unless="component:style | IN 'text number'">| <a href="#" data-tab="options">Options</a></li>

						</ul>
						
						<div class="wc_cp_group options_group wc_cp_group js-component-page js-component-page-settings">
							
							<div class="component_title">
								
								<div class="wc_cp_field">
									
									<label>
									
										<?php echo __( 'Title', 'woocommerce-configurable-products' ); ?>
										
										<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( 'Name or title of this Component.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
										
									</label>
									
									<input type="text" class="group_title component_text_input" rv-value="component:title"/>
									
									<input type="hidden" class="group_position" rv-value="component:position" />
									
								</div>
								
							</div>
							
							<div class="component_description">
								
								<div class="wc_cp_field">
									
									<label>
									
										<?php echo __( 'Description', 'woocommerce-configurable-products' ); ?>
										
										<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( 'Optional short description of this Component.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
										
									</label>
									
									<textarea class="group_description" placeholder="" rows="2" cols="20" rv-html="component:description | esc_textarea"></textarea>
									
								</div>
								
							</div>
							
							<div class="component_source">
					
								<div class="wc_cp_field">
							
									<label class="wc_cp_label">
										
										<?php _e( 'Data Source', 'woocommerce-configurable-products' ); ?>
										
										<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( 'Choose the source data to feed this Component', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
										
									</label>
									
									<select rv-value="component:source">
										
										<option value="default"><?php _e( 'Default', 'woocommerce-configurable-products' ); ?></option>
													
										<option value="subcomponents"><?php _e( 'Sub Components', 'woocommerce-configurable-products' ); ?></option>
										
										<option value="configurable-product"><?php _e( 'Configurable Product', 'woocommerce-configurable-products' ); ?></option>
										
									</select>
									
								</div>
								
							</div>
							
							<div rv-if="component:source | = 'default'">
								
								<div class="component_style">
			
									<div class="wc_cp_field">
								
										<label class="wc_cp_label">
											
											<?php _e( 'Style', 'woocommerce-configurable-products' ); ?>
											
											<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( $this->get_option_styles_descriptions(), 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
											
										</label>
										
										<select rv-value="component:style">
											
											<option rv-checked="component:style" value="0"><?php _e( 'Same as Above', 'woocommerce-configurable-products' ); ?></option>
											
											<?php foreach($this->get_option_styles() as $option_style_key => $option_style) : ?>
											
												<option rv-checked="component:style" value="<?php echo $option_style_key; ?>"><?php _e( $option_style['title'], 'woocommerce-configurable-products' ); ?></option>
												
											<?php endforeach; ?>
											
										</select>
										
									</div>
									
								</div>
							
								<div class="component_optional">
									
									<div class="wc_cp_field">
										
										<label>
										
											<?php echo __( 'Optional', 'woocommerce-configurable-products' ); ?>
											
											<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( 'Checking this option will allow customers to proceed without making any selection for this Component at all.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
											
										</label>
										
										<input type="checkbox" class="checkbox" rv-checked="component:optional" value="1" />
										
									</div>
									
								</div>
								
								<div class="component_sovereign">
									
									<div class="wc_cp_field">
										
										<label>
										
											<?php echo __( 'Sovereign', 'woocommerce-configurable-products' ); ?>
											
											<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( 'Check this box if you would this component\'s options to always be available in spite of scenarios', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
											
										</label>
										
										<input type="checkbox" class="checkbox" rv-checked="component:sovereign" value="1" />
										
									</div>
									
								</div>
								
								<div rv-if="product:build_sku">
								
									<div class="component_affect_sku">
										
										<div class="wc_cp_field">
											
											<label>
											
												<?php echo __( 'Affect SKU', 'woocommerce-configurable-products' ); ?>
												
												<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( 'Check this box if you would this component to affect the SKU built over the configuration', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
												
											</label>
											
											<input type="checkbox" class="checkbox" rv-checked="component:affect_sku" value="1" />
											
										</div>
										
									</div>
									
									<div class="component_sku_order" rv-if="component:affect_sku">
										
										<div class="wc_cp_field">
											
											<label>
											
												<?php echo __( 'SKU Order', 'woocommerce-configurable-products' ); ?>
												
												<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( 'Enter an integer in this field to set the order in which the SKU should be built, in ascending order', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
												
											</label>
											
											<input type="number" min="0" rv-value="component:sku_order" />
											
										</div>
										
									</div>
									
									<div class="component_sku_default" rv-if="component:affect_sku">
										
										<div class="wc_cp_field">
											
											<label>
											
												<?php echo __( 'SKU Default', 'woocommerce-configurable-products' ); ?>
												
												<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( 'Enter an a default sku for this component', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
												
											</label>
											
											<input type="text" rv-value="component:sku_default" />
											
										</div>
										
									</div>
									
								</div>
								
							</div>
							
							<div rv-if="component:source | = 'subcomponents'">
								
								<p>Loop template here</p>
								
							</div>
							
							<div rv-if="component:source | = 'configurable-product'">
								
								<div class="wc_cp_field">
										
									<label>
									
										<?php echo __( 'Configurable Product', 'woocommerce-configurable-products' ); ?>
										
										<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( 'Choose the Configurable Product to feed this Component.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
										
									</label>
									
									<div>
										
										<input type="hidden" data-placeholder="<?php _e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products" data-multiple="true" />
										
									</div>
									
								</div>
								
							</div>
							
						</div>
						
						<div class="wc_cp_group options_group wc_cp_group js-component-page js-component-page-field-settings hide">
							
							<div class="field_label">
								
								<div class="wc_cp_field">
									
									<label>
									
										<?php echo __( 'Label', 'woocommerce-configurable-products' ); ?>
										
										<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( 'The label of this field.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
										
									</label>
									
									<input type="number" rv-value="component:default_value" />
									
								</div>
								
							</div>
							
							<div class="field_default_value">
								
								<div class="wc_cp_field">
									
									<label>
									
										<?php echo __( 'Default Value', 'woocommerce-configurable-products' ); ?>
										
										<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( 'The default value of the number field.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
										
									</label>
									
									<input type="number" rv-value="component:default_value" />
									
								</div>
								
							</div>
							
							<div class="field_step_value">
								
								<div class="wc_cp_field">
									
									<label>
									
										<?php echo __( 'Step Value', 'woocommerce-configurable-products' ); ?>
										
										<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( 'The increment in which the number field should increase.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
										
									</label>
									
									<input type="number" rv-value="component:step_value" />
									
								</div>
								
							</div>
							
							<div class="field_min_value">
								
								<div class="wc_cp_field">
									
									<label>
									
										<?php echo __( 'Min Value', 'woocommerce-configurable-products' ); ?>
										
										<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( 'The minimum value of the number field.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
										
									</label>
									
									<input type="number" rv-value="component:min_value" />
									
								</div>
								
							</div>
							
							<div class="field_max_value">
								
								<div class="wc_cp_field">
									
									<label>
									
										<?php echo __( 'Max Value', 'woocommerce-configurable-products' ); ?>
										
										<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( 'The maximum value of the number field.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
										
									</label>
									
									<input type="number" rv-value="component:max_value" />
									
								</div>
								
							</div>
							
							<div class="field_suffix">
								
								<div class="wc_cp_field">
									
									<label>
									
										<?php echo __( 'Suffix', 'woocommerce-configurable-products' ); ?>
										
										<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( 'The suffix adjacent to the value on the sidebar.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
										
									</label>
									
									<input type="number" rv-value="component:suffi" />
									
								</div>
								
							</div>
							
						</div>
						
						<div class="wc_cp_group options_group wc_cp_group js-component-page js-component-page-options hide">
							
							<div rv-each-option="component:options">
								
								<div class="option_type">
			
									<div class="wc_cp_field">
								
										<label class="wc_cp_label">
											
											<?php _e( 'Type', 'woocommerce-configurable-products' ); ?>
											
											<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( 'Choose the data to drive the Option', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
											
										</label>
										
										<select rv-value="option:type">
											
											<option value="simple-product"><?php _e( 'Simple Product', 'woocommerce-configurable-products' ); ?></option>
											
											<option value="configurable-product"><?php _e( 'Configurable Product', 'woocommerce-configurable-products' ); ?></option>
											
											<option value="attribute"><?php _e( 'Attribute', 'woocommerce-configurable-products' ); ?></option>
											
										</select>
										
									</div>
									
								</div>
								
								<div class="option_attributes">
									
									<div class="wc_cp_field">
										
										<label>
										
											<?php echo __( 'Attributes', 'woocommerce-configurable-products' ); ?>
											
											<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( 'Options (attributes) of component.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
											
										</label>
										
										<div>
											
											<input type="hidden" class="wc-enhanced-search" data-placeholder="<?php _e( 'Search for an attribute&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_attributes" data-multiple="true" />
											
										</div>
										
									</div>
									
								</div>
								
								<div class="option_attribute_term">
									
									<div class="wc_cp_field">
										
										<label>
										
											<?php echo __( 'Attribute Term', 'woocommerce-configurable-products' ); ?>
											
											<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( 'Options (attribute terms) of component.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
											
										</label>
										
										<div>
											
											<input type="hidden" data-placeholder="<?php _e( 'Search for an attribute term&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_attribute_terms" data-multiple="true" />
											
										</div>
										
									</div>
									
								</div>
								
								<div class="option_product">
									
									<div class="wc_cp_field">
										
										<label>
										
											<?php echo __( 'Product', 'woocommerce-configurable-products' ); ?>
											
											<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( 'Options (products) of component.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
											
										</label>
										
										<div>
											
											<input type="hidden" data-placeholder="<?php _e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products" data-multiple="true" />
											
										</div>
										
									</div>
									
								</div>
								
								<div class="option_selected">
									
									<div class="wc_cp_field">
										
										<label>
										
											<?php echo __( 'Selected', 'woocommerce-configurable-products' ); ?>
											
											<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( 'Checking this option will select this option by default. If the option style is Dropdown the first selectable option will be selected.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
											
										</label>
										
										<input type="checkbox" class="checkbox" rv-checked="component:selected" value="1" />
										
									</div>
									
								</div>
								
								<div class="option_recommended">
									
									<div class="wc_cp_field">
										
										<label>
										
											<?php echo __( 'Recommended', 'woocommerce-configurable-products' ); ?>
											
											<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( 'Checking this option will recommend this option by default.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
											
										</label>
										
										<input type="checkbox" class="checkbox" rv-checked="component:recommended" value="1" />
										
									</div>
									
								</div>
								
								<div class="option_sku">
									
									<div class="wc_cp_field">
										
										<label>
										
											<?php echo __( 'SKU', 'woocommerce-configurable-products' ); ?>
											
											<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( 'Enter the SKU to append the SKU Build for this Option. Leave blank to use the default SKU for the Product / Attribute', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
											
										</label>
										
										<input type="text" rv-value="option:sku" />
										
									</div>
									
								</div>
								
								<div class="option_price">
									
									<div class="wc_cp_field">
										
										<label>
										
											<?php echo __( 'Price', 'woocommerce-configurable-products' ); ?>
											
											<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( 'Enter an Price for this Option, leave blank to use the default price for the Product / Attribute.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
											
										</label>
										
										<input type="number" min="0" step="0.01" rv-value="option:price" />
										
									</div>
									
								</div>
								
								<div class="option_formula">
									
									<div class="wc_cp_field">
										
										<label>
										
											<?php echo __( 'Formula', 'woocommerce-configurable-products' ); ?>
											
											<img class="wc_cp_help_tip help_tip" data-tip="<?php echo __( 'Enter the Price Formula for this Option. Leave the formula blank to keep price unaffected.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
											
										</label>
										
										<input type="text" placeholder="{n}*2.75" rv-value="option:formula" />
										
									</div>
									
								</div>
								
							</div>
							
							<a href="#"><?php _e( 'Add Option', '' ); ?></a>
							
						</div>
						 
						<span class="wc_cp_group options_group_id" rv-if="component:id" rv-html="'#id' | component:id"></span>
							
					</div>
					
				</div>

			</div>
			
		</div>

		<p class="toolbar borderless">
			
			<button type="button" class="button save_composition"><?php _e( 'Save Configuration', 'woocommerce-configurable-products' ); ?></button>
			
			<button type="button" class="button button-primary wc_cp_right" rv-on-click="product.addComponent"><?php _e( 'Add Component', 'woocommerce-configurable-products' ); ?></button>
			
		</p>
		
	</div> <!-- options group -->
	
</div>