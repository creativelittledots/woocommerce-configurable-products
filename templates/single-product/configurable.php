<?php
/**
 * Configurable Product Template.
 *
 * @version 3.0.0
 * @since  2.4.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<form method="post" class="js-configurable-product-form js-configurable-product-bind pd-v-1" data-abide="ajax">

	<div rv-each-component="product:components">
		
		<component component="component"></component>
		
	</div>

</form>

<script type="text/html" class="js-component-component">
	
	<div class="component">

		<h4 class="component_title product_title">
			
			<span rv-text="component:title"></span>
							
			<a class="toggle_component" href="#" rv-on-click="component.close"></a>
		
		</h4>
	
		<div class="component_inner">
			
			<component_inner component="component" options="component:options"></component_inner>
			
		</div>
		
	</div>
	
</script>

<script type="text/html" class="js-component-component_inner">
	
	<div rv-if="component:source | = 'default'">
		
		<p class="component_description" rv-if="component:description" rv-text="component:description"></p>
		
		<select rv-if="component:style | = 'dropdown'" rv-name="component:id" rv-sku-order="component:sku_order" rv-required="component:optional | !" rv-value="component:subselected">
									
			<option class="empty none" value="0" rv-text="component:empty_text"></option>
				
			<option rv-each-option="options" rv-value="option:id" rv-text="option:title | append ' ' | append option:formatted_price" rv-disabled="option:available | !"></option>
				
		</select> 
		
		<selection component="component" depth="0" options="options" parent="component"></selection>
		
		<div rv-if="component:style | = 'number'" rv-class-error="component:error">
		
			<input type="number" rv-min="component:field:min" rv-max="component:field:max" rv-step="component:field:step" rv-value="component:field:value" rv-placeholder="component:field:placeholder" rv-required="component:optional | !" rv-disabled="component:available | !" class="js-cnfg-number-field" />
			
			<small class="error">The value must be between { component:field:min } and { component:field:max }</small>
			
		</div>
		
		<div rv-if="component:style | = 'text'" rv-class-error="component:error">
		
			<input type="text" rv-value="component:field:value" rv-placeholder="component:field:placeholder" rv-required="component:optional | !" rv-disabled="component:available | !" />
			
			<small class="error">This field is required</small>
			
		</div>
		
	</div>
	
	<div rv-if="component:source | = 'subcomponents'">
		
		<component_inner rv-each-subcomponent="component:components" component="subcomponent" options="subcomponent:options"></component_inner>
		
	</div>
			
</script>

<script type="text/html" class="js-component-selection">
	
	<ul rv-if="component:style | IN 'radios thumbnails'">
		
		<li rv-if="component:optional">
		
			<input type="radio" rv-id="'component_' | append component:id | append '_option_' | append '_0'" rv-name="component:id | append '_' | append depth" rv-value="0" rv-checked="component:subselected" />
		
			<label rv-for="'component_' | append component:id | append '_option_' | append '_0'"><span rv-text="component:empty_text"></span></label>
			
		</li>
		
		<li rv-each-option="options" rv-class-disabled="option:available | !">
		
			<input type="radio" rv-id="'component_' | append component:id | append '_option_' | append option.id" rv-name="component:id | append '_' | append depth" rv-value="option:id" rv-checked="parent:subselected" rv-disabled="option:available | !" />
			
			<label rv-for="'component_' | append component:id | append '_option_' | append option.id"><span rv-html="option:display"></span></label>
			
			<div rv-if="option:options | length" rv-show="option:selected">
				
				<selection component="component" depth="depth" options="option:options" parent="option"></selection>
				
			</div>
			
		</li>
		
	</ul>
	
	<ul rv-if="component:style | = 'checkboxes'">
		
		<li rv-each-option="options" rv-class-disabled="option:available | !">
		
			<input type="checkbox" rv-id="'component_' | append component:id | append '_option_' | append option.id" rv-name="component:id | append '_' | append depth" rv-value="option:id" rv-checked="option:selected" rv-disabled="option:available | !" />
			
			<label rv-for="'component_' | append component:id | append '_option_' | append option.id"><span rv-html="option:display"></span></label>
			
			<div rv-if="option:options | length" rv-show="option:selected">
				
				<selection component="component" depth="depth" options="option:options" parent="option"></selection>
				
			</div>
			
		</li>
		
	</ul>
	
</script>