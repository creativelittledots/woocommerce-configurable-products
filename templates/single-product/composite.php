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

	<div class="component" rv-each-component="product:components">

		<h4 class="component_title product_title">
			
			<span rv-text="component:title"></span>
							
			<a class="toggle_component" href="#" rv-on-click="component.close"></a>
		
		</h4>
	
		<div class="component_inner">
	
			<p class="component_description" rv-if="component:description" rv-text="component:description"></p>
			
			<select class="component_options_select" rv-if="component:style | = 'dropdowns'" rv-name="component:id" rv-sku-order="component:sku_order" rv-on-change="component.select" rv-required="component:optional | !" rv-value="component:first">
							
				<option class="empty none" value="0" rv-text="component:empty_text"></option>
					
				<option rv-each-option="component:options" rv-value="option:id" rv-text="option:title | append option:formatted_price" rv-disabled="option:available | !"></option>
					
			</select> 
			
			<ul rv-if="component:style | = 'radios'" class="js-component-options">
				
				<li rv-if="component:optional">
				
					<input type="radio" rv-id="'component_' | append component:id | append '_option_' | append '_0'" rv-name="component:id" rv-value="0" rv-on-change="component.deselect" rv-checked="component:selected" />
				
					<label rv-text="component:empty_text" rv-for="'component_' | append component:id | append '_option_' | append '_0'"></label>
					
				</li>
				
				<li rv-each-option="component:options" rv-class-disabled="option:available | !">
				
					<input type="radio" rv-id="'component_' | append component:id | append '_option_' | append option.id" rv-name="component:id" rv-on-change="option.select" rv-value="option:id" rv-checked="component:selected" rv-disabled="option:available | !" />
					
					<label rv-html="option:display" rv-for="'component_' | append component:id | append '_option_' | append option.id"></label>
					
				</li>
				
			</ul>
			
			<ul rv-if="component:style | = 'checkboxes'" class="js-component-options">
				
				<li rv-each-option="component:options" rv-class-disabled="option:available | !">
				
					<input type="checkbox" rv-id="'component_' | append component:id | append '_option_' | append option.id" rv-name="component:id" rv-on-change="option.select" rv-value="option:id" rv-checked="option:selected" rv-disabled="option:available | !" />
					
					<label rv-html="option:display" rv-for="'component_' | append component:id | append '_option_' | append option.id"></label>
					
				</li>
				
			</ul>
			
			<div rv-if="component:style | = 'number'" rv-class-error="component:error">
			
				<input type="number" rv-min="component:min_value" rv-max="component:max_value" rv-step="component:step_value" rv-value="component:price_value" rv-placeholder="component:placeholder" rv-required="component:optional | !" rv-disabled="component:available | !" class="js-cnfg-number-field" />
				
				<small class="error">The value must be between { component:min_value } and { component:max_value }</small>
				
			</div>
						
			<div rv-if="component:show_tag_number_field">
				
				<label for="'component_' | append component:id | append '_tag_number'">Tag Number</label>
			
				<input type="text" id="'component_' | append component:id | append '_tag_number'" rv-value="component:tag_number" placeholder="Please provide a tag number" required />
				
				<small class="error">A tag number is required for this components</small>
				
				
			</div>
			
		</div>
		
	</div>

</form>
