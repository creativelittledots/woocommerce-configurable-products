<ul class="<?php echo implode(' ', apply_filters('woocommerce_composite_products_extension_radio_list_classes', array('component_options_radios'))); ?>">
	
	<?php
	
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
		
		<li>
		
			<input type="checkbox" name="wccp_component_checkbox[<?php echo $component_id; ?>][]" class="component_options_checkbox" id="component_option_<?php echo $product_id; ?>" data-title="<?php echo esc_attr( get_the_title( $product_id ) ); ?>" data-image_src="<?php echo esc_attr( $image_src ); ?>" value="<?php echo $product_id; ?>" <?php echo in_array($product_id, is_array($selected_option) ? $selected_option : array($selected_option)) ? 'checked="checked"' : ''; ?> />
			
			<label for="component_option_<?php echo $product_id; ?>">
			
				<?php
			
					if ( $quantity_min == $quantity_max && $quantity_min > 1 ) {
						$quantity = ' &times; ' . $quantity_min;
					} else {
						$quantity = '';
					}
			
					echo $composited_product->get_product()->get_title() . $quantity;
			
					echo $composited_product->get_price_string();
					
					if(isset($component_data['recommended_id']) && $component_data['recommended_id'] == $composited_product->get_product()->id) {
				
						?>
						
						<em> <?php echo ' ' . __(apply_filters('woocommerce_composite_products_extension_recommended_text', 'Recommended'), 'woocommerce'); ?></em>
						
						<?php
						
					}
			
				?>
				
			</label>
			
		</li>
		
	<?php } ?>
		
</ul>