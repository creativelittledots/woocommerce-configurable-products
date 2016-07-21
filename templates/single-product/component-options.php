<?php
/**
 * Component Options Template.
 *
 * @version 3.0.6
 * @since  1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $woocommerce_composite_products, $woocommerce_composite_products_extension;

$is_static      = $product->is_component_static( $component_id );
$is_optional    = $component_data[ 'optional' ] === 'yes';
$quantity_min   = $component_data[ 'quantity_min' ];
$quantity_max   = $component_data[ 'quantity_max' ];
$recommended_id = isset($component_data[ 'recommended_id' ]) ? $component_data[ 'recommended_id' ] : null;
$affect_sku		= isset($component_data[ 'affect_sku' ]) ? $component_data[ 'affect_sku' ] : false;
$affect_sku_order = isset($component_data[ 'affect_sku_order' ]) ? $component_data[ 'affect_sku_order' ] : false;
$sku_options = isset($component_data[ 'sku_options' ]) ? $component_data[ 'sku_options' ] : array();
$selection_mode = isset($component_data[ 'option_style' ]) && $component_data[ 'option_style' ] ? $component_data[ 'option_style' ] : $product->get_composite_selections_style();
$is_multiple = apply_filters('woocommerce_composite_product_extension_dropdown_is_multiple', $selection_mode == 'checkboxes', $component_data);

$args = array_merge($args, compact('is_static', 'is_optional', 'quantity_min', 'quantity_max', 'selection_mode', 'recommended_id', 'affect_sku', 'affect_sku_order', 'sku_options', 'is_multiple'));

?>

<div class="component_options" style="<?php echo $is_static ? 'display:none;' : ''; ?>">
	
	<div class="component_options_inner cp_clearfix">

		<p class="component_section_title">
			
			<label class="select_label"><?php echo __( 'Select an option&hellip;', 'woocommerce-composite-products' ); ?></label>
			
		</p>
		
		<?php 

			// Thumbnails template
			if ( $selection_mode == 'thumbnails' ) {
				wc_get_template( 'single-product/component-option-thumbnails.php', $args, '', $woocommerce_composite_products->plugin_path() . '/templates/' );
			}
			
			elseif( $selection_mode != 'dropdowns' ) {
				wc_get_template( 'single-product/component-option-' . $selection_mode . '.php', $args, '', $woocommerce_composite_products_extension->plugin_path() . '/templates/' );
			}
			
			wc_get_template( 'single-product/component-option-dropdowns.php', $args, '', $woocommerce_composite_products_extension->plugin_path() . '/templates/' ); 
			
		?>

		<div class="cp_clearfix"></div>
		
	</div>
	
</div>