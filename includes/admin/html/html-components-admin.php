<div id="configuration_components" class="js-cp-product-bind wc_cp_panel woocommerce_options_panel panel wc-metaboxes-wrapper">

	<div class="options_group">
		
<!--
		
		<p class="form-field wc_cp_field">
			
			<label class="wc_cp_label">
			
				<?php _e( 'Style', 'woocommerce-configurable-products' ); ?>
				
				<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'Choose the style of this configurable product', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
				
			</label>
			

			<select rv-value="product.attributes.style">
				
				<option value="stacked"><?php _e( 'Stacked', 'woocommerce-configurable-products' ); ?></option>
				
				<option value="progressive"><?php _e( 'Progressive', 'woocommerce-configurable-products' ); ?></option>
				
				<option value="stepped"><?php _e( 'Stepped', 'woocommerce-configurable-products' ); ?></option>
				
				<option value="componentised"><?php _e( 'Componentised', 'woocommerce-configurable-products' ); ?></option>
				
			</select>
			
		</p>
		
-->
		
		<p class="form-field wc_cp_field">
			
			<label class="wc_cp_label">
			
				<?php _e( 'Build a SKU?', 'woocommerce-configurable-products' ); ?>
				
				<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'Check this box if would like to build a SKU from the components', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
				
			</label>
			
			<input type="checkbox" rv-checked="product.attributes.build_sku" value="1" />
			
		</p>
		
		<p class="form-field wc_cp_field component_affect_sku" rv-if="product.attributes.build_sku">
			
			<label class="wc_cp_label">
			
				<?php _e( 'SKU Start', 'woocommerce-configurable-products' ); ?>
				
				<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'Please enter a start SKU for use when building the SKU', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
				
			</label>
			
			<input type="text" rv-value="product.attributes.base_sku" />
			
		</p>
		
	</div>
	
	<div class="wc_cp_group options_group" rv-if="product.attributes.id">
		
		<p class="toolbar">
			
			<a href="#" class="wc_cp_help_tip help_tip tips" tip="<?php echo __( 'Configurable Products consist of building blocks, called <strong>Components</strong>. Each Component offers an assortment of <strong>Component Options</strong> that correspond to existing Simple products, Variable products or Product Bundles.', 'woocommerce-configurable-products' ); ?>" >[?]</a>
			
			<a href="#" class="close_all"><?php _e( 'Close all', 'woocommerce' ); ?></a>
			
			<a href="#" class="expand_all"><?php _e( 'Expand all', 'woocommerce' ); ?></a>
			
		</p>

		<div class="wc_cp_group-inner">

			<ul class="wc-metaboxes js-sortable ui-sortable">
				
				<li class="wc_cp_metabox wc-metabox" rv-each-component="product.attributes.components.models" rv-data-position="component.attributes.position">
					
					<component component="component" product="product" class="wc-metabox"></component>
					
				</li>

			</ul>
			
		</div>

		<p class="toolbar borderless">
			
			<button type="button" class="button js-save-configuration" rv-on-click="product.saveConfiguration"><?php _e( 'Save Configuration', 'woocommerce-configurable-products' ); ?></button>
			
			<input type="file" name="import_components" class="hide" id="importComponents" onChange="if(confirm('Are you sure you want to upload this import file? This action will replace all existing components')){this.form.submit();}" />
				
			<a href="#" onClick="jQuery('#importComponents').trigger('click'); return false;" class="button-primary"><?php _e('Import Components', 'woocommerce-configurable-products-extension'); ?></a>
			
			<a href="<?php echo add_query_arg(array('action' => 'export_components', 'product_id' => $post->ID, '_wpnonce' => wp_create_nonce('export_components', 'product_id')), admin_url('admin-post.php')); ?>" class="button-primary"><?php _e('Export Components', 'woocommerce-configurable-products-extension'); ?></a>
			
			<button type="button" class="button button-primary wc_cp_right" rv-on-click="product.addComponent"><?php _e( 'Add Component', 'woocommerce-configurable-products' ); ?></button>
			
		</p>
		
	</div> <!-- options group -->
	
	<p rv-unless="product.attributes.id">Please save this Configurable Product before adding Components.</p>
	
</div>

<script type="text/html" id="component">
					
	<h3 rv-on-click="component.open">
		
		<a class="button wc_cp_right" rv-if="parent" rv-on-click="parent.removeComponent" rv-component_cid="component.cid" ><?php _e( 'Remove', 'woocommerce' ); ?></a>
		
		<a class="button wc_cp_right" rv-unless="parent" rv-on-click="product.removeComponent" rv-component_cid="component.cid"><?php _e( 'Remove', 'woocommerce' ); ?></a>
		
		<div class="handlediv" title="<?php _e( 'Click to toggle', 'woocommerce' ); ?>"></div>
		
		<strong class="component_name" rv-html="component.attributes.title"></strong>
		
		<input type="hidden" rv-value="component.attributes.id" />
		
	</h3>
	
	<div class="wc-metabox-content wc_cp_component_data js-component js-sortable-item-content" rv-class-hide="component.attributes.closed">
		
		<div class="flex">
		
			<ul class="subsubsub wc_cp_inline-list js-component-list" rv-if="component.attributes.source | = 'default'">
				
				<li><a href="#" data-tab="settings" rv-class-current="component.attributes.tab | = 'settings'" rv-on-click="component.tab">Settings</a></li>
				
				<li rv-if="component.attributes.style | IN 'text number'">| <a href="#" data-tab="field-settings" rv-class-current="component.attributes.tab | = 'field-settings'" rv-on-click="component.tab">Field Settings</a></li>
				
				<li rv-unless="component.attributes.style | IN 'text number'">| <a href="#" data-tab="options" rv-class-current="component.attributes.tab | = 'options'" rv-on-click="component.tab">Options</a></li>

			</ul>
			
		</div>
		
		<div class="wc_cp_group options_group js-component-page js-component-page-settings" rv-class-hide="component.attributes.tab | != 'settings'">
			
			<div class="component_title">
				
				<div class="wc_cp_field">
					
					<label>
					
						<?php echo __( 'Title', 'woocommerce-configurable-products' ); ?>
						
						<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'Name or title of this Component.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
						
					</label>
					
					<input type="text" class="component_title component_text_input" rv-value="component.attributes.title"/>
					
					<input type="hidden" class="js-item-position" rv-input-match="component.attributes.position" />
					
				</div>
				
			</div>
			
			<div class="component_source">
	
				<div class="wc_cp_field">
			
					<label class="wc_cp_label">
						
						<?php _e( 'Data Source', 'woocommerce-configurable-products' ); ?>
						
						<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'Choose the source data to feed this Component', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
						
					</label>
					
					<select rv-value="component.attributes.source">
						
						<option value="default"><?php _e( 'Default', 'woocommerce-configurable-products' ); ?></option>
									
						<option value="subcomponents"><?php _e( 'Sub Components', 'woocommerce-configurable-products' ); ?></option>
						
<!-- 						<option value="configurable-product"><?php _e( 'Configurable Product', 'woocommerce-configurable-products' ); ?></option> -->
						
					</select>
					
				</div>
				
			</div>
			
			<div class="component_description" rv-if="component.attributes.source | != 'subcomponents'">
				
				<div class="wc_cp_field">
					
					<label>
					
						<?php echo __( 'Description', 'woocommerce-configurable-products' ); ?>
						
						<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'Optional short description of this Component.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
						
					</label>
					
					<textarea class="component_description js-wc-cp-editor" rv-id="'component_description_' | append component.cid" placeholder="" rows="2" cols="20" rv-html="component.attributes.description | esc_textarea" rv-input-match="component.attributes.description"></textarea>
					
				</div>
				
			</div>
			
			<div class="component_image" rv-if="product.attributes.style | = 'componentised'">
					
				<div class="wc_cp_field">
					
					<label>
					
						<?php echo __( 'Image', 'woocommerce-configurable-products' ); ?>
						
						<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'Upload the image to display for this component when in componentised mode', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
						
					</label>
					
					<a href="#" class="button js-wc-cp-upload-image">Upload Image</a>
					
				</div>
				
			</div>
			
			<div rv-if="component.attributes.source | = 'default'">
				
				<div class="component_style">

					<div class="wc_cp_field">
				
						<label class="wc_cp_label">
							
							<?php _e( 'Style', 'woocommerce-configurable-products' ); ?>
							
							<img class="wc_cp_help_tip help_tip" tip="<?php echo __( $this->get_option_styles_descriptions(), 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
							
						</label>
						
						<select rv-value="component.attributes.style">
							
							<?php foreach($this->get_option_styles() as $option_style_key => $option_style) : ?>
							
								<option rv-checked="component.attributes.style" value="<?php echo $option_style_key; ?>"><?php _e( $option_style['title'], 'woocommerce-configurable-products' ); ?></option>
								
							<?php endforeach; ?>
							
						</select>
						
					</div>
					
				</div>
			
				<div class="component_optional">
					
					<div class="wc_cp_field">
						
						<label>
						
							<?php echo __( 'Optional', 'woocommerce-configurable-products' ); ?>
							
							<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'Checking this option will allow customers to proceed without making any selection for this Component at all.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
							
						</label>
						
						<input type="checkbox" class="checkbox" rv-checked="component.attributes.optional" value="1" />
						
					</div>
					
				</div>
				
				<div class="component_sovereign" rv-if="product.attributes.style | = 'stacked'">
					
					<div class="wc_cp_field">
						
						<label>
						
							<?php echo __( 'Sovereign', 'woocommerce-configurable-products' ); ?>
							
							<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'Check this box if you would this component\'s options to always be available in spite of scenarios when in stacked mode', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
							
						</label>
						
						<input type="checkbox" class="checkbox" rv-checked="component.attributes.sovereign" value="1" />
						
					</div>
					
				</div>
				
				<div rv-if="product.attributes.build_sku">
				
					<div class="component_affect_sku" rv-if="product.attributes.build_sku">
						
						<div class="wc_cp_field">
							
							<label>
							
								<?php echo __( 'Affect SKU', 'woocommerce-configurable-products' ); ?>
								
								<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'Check this box if you would this component to affect the SKU built over the configuration', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
								
							</label>
							
							<input type="checkbox" class="checkbox" rv-checked="component.attributes.affect_sku" value="1" />
							
						</div>
						
					</div>
					
					<div class="component_sku_order" rv-if="component.attributes.affect_sku">
						
						<div class="wc_cp_field">
							
							<label>
							
								<?php echo __( 'SKU Order', 'woocommerce-configurable-products' ); ?>
								
								<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'Enter an integer in this field to set the order in which the SKU should be built, in ascending order', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
								
							</label>
							
							<input type="number" min="0" rv-value="component.attributes.sku_order" />
							
						</div>
						
					</div>
					
					<div class="component_sku_default" rv-if="component.attributes.affect_sku">
						
						<div class="wc_cp_field">
							
							<label>
							
								<?php echo __( 'SKU Default', 'woocommerce-configurable-products' ); ?>
								
								<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'Enter an a default sku for this component', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
								
							</label>
							
							<input type="text" rv-value="component.attributes.sku_default" />
							
						</div>
						
					</div>
					
				</div>
				
			</div>
			
			<div rv-if="component.attributes.source | = 'subcomponents'">
				
				<div class="wc_cp_group-inner">
					
					<ul class="wc-metaboxes js-sortable ui-sortable">

						<li class="wc_cp_metabox wc-metabox" rv-each-child="component.attributes.components.models" rv-data-position="child.attributes.position">
							
							<component parent="component" component="child" product="product"></component>
							
						</li>
						
					</ul>
					
				</div>
				
				<p class="toolbar borderless">
					
					<button type="button" class="button button-primary wc_cp_right" rv-on-click="component.addComponent"><?php _e( 'Add Component', 'woocommerce-configurable-products' ); ?></button>
					
				</p>
				
			</div>
			
			<div rv-if="component.attributes.source | = 'configurable-product'">
				
				<div class="wc_cp_field">
						
					<label>
					
						<?php echo __( 'Configurable Product', 'woocommerce-configurable-products' ); ?>
						
						<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'Choose the Configurable Product to feed this Component.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
						
					</label>
					
					<div>
						
						<select class="js-wc-product-search" data-placeholder="<?php _e( 'Search for a product&hellip;', 'woocommerce' ); ?>" product_type="configurable"></select>
						
					</div>
					
				</div>
				
			</div>
			
		</div>
		
		<div class="wc_cp_group options_group wc_cp_group js-component-page js-component-page-field-settings" rv-class-hide="component.attributes.tab | != 'field-settings'">
			
			<div class="field_label">
				
				<div class="wc_cp_field">
					
					<label>
					
						<?php echo __( 'Label', 'woocommerce-configurable-products' ); ?>
						
						<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'The label of this field.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
						
					</label>
					
					<input type="text" rv-value="component.attributes.field:label" />
					
				</div>
				
			</div>
			
			<div class="field_label">
				
				<div class="wc_cp_field">
					
					<label>
					
						<?php echo __( 'Placeholder', 'woocommerce-configurable-products' ); ?>
						
						<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'The placeholder of this field.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
						
					</label>
					
					<input type="text" rv-value="component.attributes.field:placeholder" />
					
				</div>
				
			</div>
			
			<div class="field_default_value">
				
				<div class="wc_cp_field">
					
					<label>
					
						<?php echo __( 'Default Value', 'woocommerce-configurable-products' ); ?>
						
						<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'The default value of the number field.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
						
					</label>
					
					<input rv-type="component.attributes.style" rv-value="component.attributes.field:value" />
					
				</div>
				
			</div>
			
			<div class="field_step_value" rv-if="component.attributes.style | = 'number'">
				
				<div class="wc_cp_field">
					
					<label>
					
						<?php echo __( 'Step Value', 'woocommerce-configurable-products' ); ?>
						
						<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'The increment in which the number field should increase.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
						
					</label>
					
					<input type="number" rv-value="component.attributes.field:step" />
					
				</div>
				
			</div>
			
			<div class="field_min_value" rv-if="component.attributes.style | = 'number'">
				
				<div class="wc_cp_field">
					
					<label>
					
						<?php echo __( 'Min Value', 'woocommerce-configurable-products' ); ?>
						
						<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'The minimum value of the number field.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
						
					</label>
					
					<input type="number" rv-value="component.attributes.field:min" />
					
				</div>
				
			</div>
			
			<div class="field_max_value" rv-if="component.attributes.style | = 'number'">
				
				<div class="wc_cp_field">
					
					<label>
					
						<?php echo __( 'Max Value', 'woocommerce-configurable-products' ); ?>
						
						<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'The maximum value of the number field.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
						
					</label>
					
					<input type="number" rv-value="component.attributes.field:max" />
					
				</div>
				
			</div>
			
			<div class="field_suffix">
				
				<div class="wc_cp_field">
					
					<label>
					
						<?php echo __( 'Suffix', 'woocommerce-configurable-products' ); ?>
						
						<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'The suffix adjacent to the value on the sidebar.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
						
					</label>
					
					<input type="text" rv-value="component.attributes.field:suffix" />
					
				</div>
				
			</div>
			
			<div class="field_price_formula" rv-if="component.attributes.style | = 'number'">
			
				<div class="wc_cp_field">
					
					<label>
					
						<?php echo __( 'Price Formula', 'woocommerce-configurable-products' ); ?>
						
						<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'Enter the Price Formula for this Field. Leave the formula blank to keep price unaffected.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
						
					</label>
					
					<input type="text" placeholder="{n}*2.75" rv-value="component.attributes.field:price_formula" />
					
				</div>
				
			</div>
			
		</div>
		
		<div class="wc_cp_group bg-grey pd-1p5 options_group wc_cp_group js-component-page js-component-page-options" rv-class-hide="component.attributes.tab | != 'options'">
			
			<h4 class="wc_cp_title">Component Options</h4>
			
			<ul class="wc-metaboxes js-sortable ui-sortable mg-b-1">
			
				<li class="wc_cp_metabox wc-metabox bg-transparent" rv-each-option="component.attributes.options.models" rv-data-position="option.attributes.position">
					
					<selection option="option" depth="0" component="component" product="product"></selection>
						
				</li>
				
			</ul>
			
			<div class="flex align-right">
			
				<a href="#" class="button button-primary" rv-on-click="component.addOption"><?php _e( 'Add Option', '' ); ?></a>
				
			</div>
			
		</div>
		 
		<span class="wc_cp_group wc_cp_entity_id" rv-if="component.attributes.id" rv-html="'#component_id | ' | append component.attributes.id"></span>
			
	</div>
	
</script>

<script type="text/html" id="selection">
	
	<h3 rv-on-click="option.open">
		
		<a class="button wc_cp_right" rv-if="parent" rv-on-click="parent.removeOption" rv-option_cid="option.cid"><?php _e( 'Remove', 'woocommerce' ); ?></a>
		
		<a class="button wc_cp_right" rv-unless="parent" rv-on-click="component.removeOption" rv-option_cid="option.cid"><?php _e( 'Remove', 'woocommerce' ); ?></a>
		
		<div class="handlediv" title="<?php _e( 'Click to toggle', 'woocommerce' ); ?>"></div>
		
		<strong rv-html="option.attributes.title" class="js-wc-product-search-title"></strong>
		
		<input type="hidden" rv-value="option.attributes.id" />
		
		<input type="hidden" class="js-item-position" rv-input-match="option.attributes.position" />
		
	</h3>
	
	<div class="wc-metabox-content js-option js-sortable-item-content wc_cp_option" rv-class-hide="option.attributes.closed">
	
		<div class="option_type">
	
		<div class="wc_cp_field">
	
			<label class="wc_cp_label">
				
				<?php _e( 'Type', 'woocommerce-configurable-products' ); ?>
				
				<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'Choose the data to drive the Option', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
				
			</label>
			
			<select rv-value="option.attributes.source" rv-on-change="option.updateSource">
				
				<option value="simple-product"><?php _e( 'Simple Product', 'woocommerce-configurable-products' ); ?></option>
<!--
				
				<option value="variable-product"><?php _e( 'Variable Product', 'woocommerce-configurable-products' ); ?></option>
-->
				
<!-- 			<option value="configurable-product"><?php _e( 'Configurable Product', 'woocommerce-configurable-products' ); ?></option> -->
				
<!-- 			option value="component"><?php _e( 'Component', 'woocommerce-configurable-products' ); ?></option> -->

				<option value="static"><?php _e( 'Static', 'woocommerce-configurable-products' ); ?></option>
				
			</select>
			
		</div>
		
	</div>
		
		<div class="option_product" rv-unless="option.attributes.source | = 'static'">
			
			<div class="wc_cp_field">
				
				<label>
				
					<?php echo __( 'Product', 'woocommerce-configurable-products' ); ?>
					
					<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'Please select the Product / Component for this Option.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
					
				</label>
				
				<div>
					
					<select class="js-wc-product-search" data-placeholder="<?php _e( 'Search for a product&hellip;', 'woocommerce' ); ?>" rv-value="option.attributes.product_id">
						
						<option rv-if="option.attributes.id | > 0" rv-value="option.attributes.product_id" rv-html="option.attributes.product_title" selected="true"></option>
						
					</select>
					
				</div>
				
			</div>
			
		</div>
		
		<div class="option_value" rv-if="option.attributes.source | = 'static'">
				
			<div class="wc_cp_field">
				
				<label>
				
					<?php echo __( 'Value', 'woocommerce-configurable-products' ); ?>
					
					<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'Value of this option', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
					
				</label>
				
				<input type="text" class="option_value component_text_input" rv-value="option.attributes.value"/>
				
			</div>
			
		</div>
		
		<div class="option_label" rv-if="option.attributes.source | = 'static'">
				
			<div class="wc_cp_field">
				
				<label>
				
					<?php echo __( 'Label', 'woocommerce-configurable-products' ); ?>
					
					<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'Label of this option', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
					
				</label>
				
				<input type="text" class="option_label component_text_input" rv-value="option.attributes.label" rv-on-keyup="option.updateLabel" />
				
			</div>
			
		</div>
		
		<div class="option_thumbnail" rv-if="component.attributes.style | = 'thumbnails'">
					
			<div class="wc_cp_field">
				
				<label>
				
					<?php echo __( 'Thumbnail', 'woocommerce-configurable-products' ); ?>
					
					<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'Upload a thumbnail to display for this option when in thumbnail mode. Please leave blank to use default Product / Component featured image', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
					
				</label>
				
				<a href="#" class="button js-wc-cp-upload-image">Upload Thumbnail</a>
				
			</div>
			
		</div>
		
<!--
		<div class="option_affect_stock" rv-unless="option.attributes.source | = 'static'">
			
			<div class="wc_cp_field">
				
				<label>
				
					<?php echo __( 'Affect Stock', 'woocommerce-configurable-products' ); ?>
					
					<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'Checking this option will reduce stock levels of the Product / Component after each Order. If the Product / Component is not set to Manage Stock this will have no affect. If the Product / Component is not in stock this Option will be displayed as disabled.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
					
				</label>
				
				<input type="checkbox" class="checkbox" rv-checked="option.attributes.affect_stock" value="1" />
				
			</div>
			
		</div>
-->
		
		<div class="option_selected">
			
			<div class="wc_cp_field">
				
				<label>
				
					<?php echo __( 'Selected', 'woocommerce-configurable-products' ); ?>
					
					<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'Checking this option will select this option by default. If the option style is Dropdown the first selectable option will be selected.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
					
				</label>
				
				<input type="checkbox" class="checkbox" rv-checked="option.attributes.selected" value="1" />
				
			</div>
			
		</div>
		
		<div class="option_recommended">
			
			<div class="wc_cp_field">
				
				<label>
				
					<?php echo __( 'Recommended', 'woocommerce-configurable-products' ); ?>
					
					<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'Checking this option will recommend this option by default.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
					
				</label>
				
				<input type="checkbox" class="checkbox" rv-checked="option.attributes.recommended" value="1" />
				
			</div>
			
		</div>
		
		<div class="option_sku" rv-if="product.attributes.build_sku | AND component.attributes.affect_sku">
				
			<div class="wc_cp_field">
				
				<label>
				
					<?php echo __( 'SKU', 'woocommerce-configurable-products' ); ?>
					
					<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'Enter the SKU to append the SKU Build for this Option. Leave blank to use the default SKU for the Product / Component', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
					
				</label>
				
				<input type="text" rv-value="option.attributes.sku" />
				
			</div>
			
		</div>
			
		<div class="option_price" rv-if="option.attributes.source | != 'configurable-product'">
			
			<div class="wc_cp_field">
				
				<label>
				
					<?php echo __( 'Price', 'woocommerce-configurable-products' ); ?>
					
					<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'Enter an Price for this Option, leave blank to use the default price for the Product / Component.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
					
				</label>
				
				<input type="number" min="0" step="0.01" rv-value="option.attributes.price" />
				
			</div>
			
		</div>
		
		<div class="option_formula" rv-if="option.attributes.source | != 'configurable-product'">
			
			<div class="wc_cp_field">
				
				<label>
				
					<?php echo __( 'Formula', 'woocommerce-configurable-products' ); ?>
					
					<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'Enter the Price Formula for this Option. Leave the formula blank to keep price unaffected.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
					
				</label>
				
				<input type="text" placeholder="{n}*2.75" rv-value="option.attributes.formula" />
				
			</div>
			
		</div>
		
		<div class="option_nested_options" rv-if="option.attributes.source | != 'configurable-product'">
			
			<div class="wc_cp_field">
				
				<label>
				
					<?php echo __( 'Nested Options', 'woocommerce-configurable-products' ); ?>
					
					<img class="wc_cp_help_tip help_tip" tip="<?php echo __( 'Checking this option will allow you to add nested options.', 'woocommerce-configurable-products' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" />
					
				</label>
				
				<input type="checkbox" class="checkbox" rv-checked="option.attributes.nested_options" value="1" />
				
			</div>
			
			<div class="wc_cp_group pd-1 b-1 mg-b-2" rv-class-bg-white="depth | odd" rv-class-bg-grey="depth | even" rv-if="option.attributes.nested_options">
			
				<h4 class="wc_cp_title">Nested Options</h4>
				
				<div class="wc-metaboxes js-sortable ui-sortable mg-b-1">
				
					<div class="wc_cp_metabox wc-metabox bg-transparent" rv-each-child="option.attributes.options.models" rv-data-position="child.attributes.position">
						
						<selection option="child" depth="depth" parent="option" component="component" product="product"></selection>
							
					</div>
					
				</div>
				
				<div class="flex align-right">
				
					<a href="#" class="button button-primary" rv-on-click="option.addOption"><?php _e( 'Add Option', '' ); ?></a>
					
				</div>
				
			</div>
			
		</div>
		
		<span class="wc_cp_group wc_cp_entity_id" rv-if="option.attributes.id" rv-html="'#option_id | ' | append option.attributes.id"></span>
		
	</div>
	
</script>