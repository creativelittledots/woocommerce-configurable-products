<select id="component_options_<?php echo $component_id; ?>" class="component_options_select" name="wccp_component_selection[<?php echo $component_id; ?>]<?php echo ($selection_mode == 'checkboxes') ? '[]' : ''; ?>" <?php echo $is_multiple ? 'multiple="true"' : ''; ?> style="<?php echo $selection_mode != 'dropdowns' ? 'display:none;' : ''; ?>" data-sku-order="<?php echo $affect_sku == 'yes' && $affect_sku_order ? $affect_sku_order : null; ?>"><?php
		
	if ( ! $is_static ) {
		?><option class="empty none" data-title="<?php echo __( 'None', 'woocommerce-composite-products' ); ?>" value="" <?php echo $is_multiple && !$selected_option ? 'selected="selected"' : ''; ?>><?php echo $is_optional ? _x( 'Select an option&hellip;', 'select option dropdown text - optional component', 'woocommerce-composite-products' ) : _x( 'Select an option&hellip;', 'select option dropdown text - mandatory component', 'woocommerce-composite-products' ); ?></option><?php
	}
	
	// In thumbnails mode, always add the current selection to the (hidden) dropdown
	if ( $selection_mode === 'thumbnails' && $selected_option && ! in_array( $selected_option, $component_options ) ) {
		$component_options[] = $selected_option;
	}
	
	foreach ( $component_options as $product_id ) {
	
		$composited_product = $product->get_composited_product( $component_id, $product_id );
	
		if ( ! $composited_product )
			continue;
	
		if ( has_post_thumbnail( $product_id ) ) {
			$attachment_id = get_post_thumbnail_id( $product_id );
			$attachment    = wp_get_attachment_image_src( $attachment_id, apply_filters( 'woocommerce_composite_component_option_image_size', 'shop_catalog' ) );
			$image_src     = $attachment ? current( $attachment ) : false;
		} else {
			$image_src = '';
		}
	
		?>
		
		<option data-title="<?php echo esc_attr( get_the_title( $product_id ) ); ?>" data-image_src="<?php echo esc_attr( $image_src ); ?>" data-product-sku="<?php echo $composited_product->get_product()->get_sku(); ?>" data-product-weight="<?php echo $composited_product->get_product()->get_weight(); ?>" data-sku-build="<?php echo isset($sku_options[$product_id]) ? $sku_options[$product_id] : ''; ?>" value="<?php echo $product_id; ?>" <?php echo in_array($product_id, is_array($selected_option) ? $selected_option : array($selected_option)) ? 'selected="selected"' : ''; ?> data-title="<?php echo $composited_product->get_product()->get_title(); ?>">
			
		<?php
	
			if ( $quantity_min == $quantity_max && $quantity_min > 1 ) {
				$quantity = ' &times; ' . $quantity_min;
			} else {
				$quantity = '';
			}
	
			echo $composited_product->get_product()->get_title() . $quantity;
	
			echo $composited_product->get_price_string();
			
			if($recommended_id == $composited_product->get_product()->id) {
				
				?>
				
				<em> <?php echo ' ' . __(apply_filters('woocommerce_composite_products_extension_recommended_text', 'Recommended'), 'woocommerce'); ?></em>
				
				<?php
				
			}
	
		?>
		
		</option>
		
	<?php } ?>
		
</select> 