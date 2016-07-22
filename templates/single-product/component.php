<?php
/**
 * Single Page Progressive Component Template.
 *
 * @version 3.1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $woocommerce_composite_products;

$component_classes = $product->get_component_classes( $component_id );
$title = apply_filters( 'woocommerce_composite_component_title', $component_data[ 'title' ], $component_id, $product->id );
$title = apply_filters( 'woocommerce_composite_component_step_title', sprintf( __( '<span class="step_index">%d</span> <span class="step_title">%s</span>', 'woocommerce-composite-products' ), $step, $title ), $title, $step, $steps, $product );
$toggled = in_array( 'toggled', $component_classes );
		
?><div id="component_<?php echo $component_id; ?>" class="<?php echo esc_attr( implode( ' ', $component_classes ) ); ?>" data-nav_title="<?php echo esc_attr( apply_filters( 'woocommerce_composite_component_title', $component_data[ 'title' ], $component_id, $product->id ) ); ?>" data-item_id="<?php echo $component_id; ?>">

	<div class="component_title_wrapper">
	<h4 class="component_title product_title"><?php

		echo $title;
	
		if ( isset( $toggled ) && $toggled ) {
				?><span class="toggle_component_wrapper">
					<a class="toggle_component" href="#">
						<span class="toggle_component_text"><?php
							echo __( 'Toggle', 'woocommerce-composite-products' );
						?></span>
					</a>
				</span><?php
			}
		
		?>
	
	</h4>
	
	</div>

	<div class="component_inner pd-v-1" <?php echo in_array( 'toggled', $component_classes ) && in_array( 'closed', $component_classes ) ? 'style="display:none;"' : ''; ?>>

		<div class="block_component"></div>

		<div class="component_description_wrapper"><?php

			if ( $component_data[ 'description' ] != '' ) : ?>
			<p class="component_description"><?php
				echo apply_filters( 'woocommerce_composite_component_description', $component_data[ 'description' ], $component_id, $product->id );
			?></p>
			<?php endif; ?>
		</div>
		<div class="component_selections">
			
			<?php
				
				$selected_option = $product->get_component_default_option( $component_id );
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
							if( $selection_mode != 'dropdowns' ) {
								wc_get_template( 'single-product/component-option-' . $selection_mode . '.php', $args, '', $woocommerce_composite_products->plugin_path() . '/templates/' );
							}
							
							wc_get_template( 'single-product/component-option-dropdowns.php', $args, '', $woocommerce_composite_products->plugin_path() . '/templates/' ); 
							
						?>
				
						<div class="cp_clearfix"></div>
						
					</div>
					
				</div>
				
				<div class="component_content" data-product_id="<?php echo $component_id; ?>">
					<div class="component_summary cp_clearfix">
						<div class="product content">
							<?php echo $woocommerce_composite_products->api->show_composited_product( is_array($selected_option) ? $selected_option : array($selected_option), $component_id, $product ); ?>
						</div>
					</div>
				</div>
			
		</div>
	</div>
</div>
