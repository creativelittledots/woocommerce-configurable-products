<?php
/**
 * Composite Product Template.
 *
 * @version 3.0.0
 * @since  2.4.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $woocommerce_composite_products;

?>

<form method="post" class="js-composite-product-form js-composite-product-bind pd-v-1" data-abide="ajax">

	<div rv-each-component="product:components">
		
		<component component="component"></component>
		
	</div>

</form>

<script class="js-component-component">
	
	<div class="component">

		<h4 class="component_title product_title">
			
			<span rv-text="component:title"></span>
							
			<a class="toggle_component" href="#" rv-on-click="component.close"></a>
		
		</h4>
	
		<div class="component_inner">
	
			<p class="component_description" rv-if="component:description" rv-text="component:description"></p>
			
			<div rv-if="component:data_source | = 'products'">
				
				<component_inner component="component" options="component:options"></component_inner>
				
			</div>
			
			<div rv-if="component:data_source | = 'attributes'">
				
				<ul rv-if="component:nest_attributes">
					
					<li rv-each-attribute="component:attributes">
					
						<strong rv-html="attribute:name"></strong>
						
						<component_inner component="component" options="attribute:options"></component_inner>
						
					</li>
					
				</li>
				
				<div rv-if="component:nest_attributes | !">
					
					<component_inner component="component" options="component:options"></component_inner>
					
				</div>
				
			</div>
			
		</div>
		
	</div>
	
</script>

<script class="js-component-component_inner">
	
	<select rv-if="component:style | = 'dropdowns'" rv-name="component:id" rv-sku-order="component:sku_order" rv-on-change="component.select" rv-required="component:optional | !" rv-value="component:first">
							
		<option class="empty none" value="0" rv-text="component:empty_text"></option>
			
		<option rv-each-option="options" rv-value="option:id" rv-text="option:title | append option:formatted_price" rv-disabled="option:available | !"></option>
			
	</select> 
	
	<ul rv-if="component:style | IN ['radios', 'thumbnails']">
		
		<li rv-if="component:optional">
		
			<input type="radio" rv-id="'component_' | append component:id | append '_option_' | append '_0'" rv-name="component:id" rv-value="0" rv-on-change="component.deselect" rv-checked="component:selected" />
		
			<label rv-text="component:empty_text" rv-for="'component_' | append component:id | append '_option_' | append '_0'"></label>
			
		</li>
		
		<li rv-each-option="options" rv-class-disabled="option:available | !">
		
			<input type="radio" rv-id="'component_' | append component:id | append '_option_' | append option.id" rv-name="component:id" rv-on-change="option.select" rv-value="option:id" rv-checked="component:selected" rv-disabled="option:available | !" />
			
			<label rv-html="option:display" rv-for="'component_' | append component:id | append '_option_' | append option.id"></label>
			
		</li>
		
	</ul>
	
	<ul rv-if="component:style | = 'checkboxes'">
		
		<li rv-each-option="options" rv-class-disabled="option:available | !">
		
			<input type="checkbox" rv-id="'component_' | append component:id | append '_option_' | append option.id" rv-name="component:id" rv-on-change="option.select" rv-value="option:id" rv-checked="option:selected" rv-disabled="option:available | !" />
			
			<label rv-html="option:display" rv-for="'component_' | append component:id | append '_option_' | append option.id"></label>
			
		</li>
		
	</ul>
	
	<div rv-if="component:style | = 'number'" rv-class-error="component:error">
	
		<input type="number" rv-min="component:min_value" rv-max="component:max_value" rv-step="component:step_value" rv-value="component:static_value" rv-placeholder="component:placeholder" rv-required="component:optional | !" rv-disabled="component:available | !" class="js-cnfg-number-field" />
		
		<small class="error">The value must be between { component:min_value } and { component:max_value }</small>
		
	</div>
	
	<div rv-if="component:style | = 'text'" rv-class-error="component:error">
	
		<input type="text" rv-value="component:default_value" rv-value="component:static_value" rv-placeholder="component:placeholder" rv-required="component:optional | !" rv-disabled="component:available | !" />
		
		<small class="error">This field is required</small>
		
	</div>
	
	<div rv-each-selection="components:selections">
		
		<div rv-if="selection:type | 'composite'">
			
			<div rv-each-component="selection:components">

				<component component="component"></component>
				
			</div>
			
		</div>
		
	</div>
			
</script>