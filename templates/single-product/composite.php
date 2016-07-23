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

<form method="post" enctype="multipart/form-data" class="js-composite-product-bind">

	<div class="component" rv-each-component="product:components">

		<h4 class="component_title product_title">
			
			<span rv-text="component:title"></span>
							
			<a class="toggle_component" href="#" rv-on-click="component.close"></a>
		
		</h4>
	
		<div class="component_inner">
	
			<p class="component_description" rv-if="component:description" rv-text="component:description"></p>
			
			<select class="component_options_select" rv-if="component:style | = 'dropdown'" rv-name="component:name" rv-sku-order="component:sku_order" rv-on-change="component.update_option">
							
				<option class="empty none" rv-value="0" rv-selected="component:selection | = 0" rv-text="component:empty_text"></option>
					
				<option rv-each-option="component:options" rv-value="option:id" rv-text="option:display" rv-selected="component:selection:id | = option.id" rv-disabled="option:available | !"></option>
					
			</select> 
			
			<ul rv-unless="component:style | = 'dropdown'">
				
				<li>
				
					<input type="radio" rv-name="component:name" rv-value="0" rv-checked="component:selection" />
				
					<label rv-text="component:empty_text"></label>
					
				</li>
				
				<li rv-each-option="component:options" rv-class-disabled="option:available | !">
				
					<input type="radio" rv-name="component:name" rv-on-click="component.update_option" rv-value="option:id" rv-checked="component:selection:id" rv-disabled="option:available | !" />
					
					<label rv-text="option:display">
					
				</li>
				
			</ul>
			
			<div class="component_content" rv-if="component:data" rv-html="component:data"></div>
			
		</div>
		
	</div>

</form>
