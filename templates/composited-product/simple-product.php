<?php
/**
 * Composited Simple Product Template.
 *
 * @version  3.0.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

global $woocommerce_composite_products;

// Current selection title
if ( $hide_product_title != 'yes' ) {

	if ( $show_selection_ui ) {
		?><p class="component_section_title">
			<label class="selected_option_label"><?php echo __( 'Your selection:', 'woocommerce-composite-products' ); ?>
			</label>
		</p><?php
	}

	wc_get_template( 'composited-product/title.php', array(
		'title'      => $product->get_title(),
		'product_id' => $product->id,
		'quantity'   => $quantity_min == $quantity_max && $quantity_min > 1 && $product->sold_individually !== 'yes' ? $quantity_min : ''
	), '', $woocommerce_composite_products->plugin_path() . '/templates/' );
}

// Clear current selection
if ( $show_selection_ui ) {
	?><p class="component_section_title clear_component_options_wrapper">
		<a class="clear_component_options" href="#clear_component"><?php
			echo __( 'Clear selection', 'woocommerce-composite-products' );
		?></a>
	</p><?php
}

// Current selection thumbnail
if ( $hide_product_thumbnail != 'yes' ) {
	wc_get_template( 'composited-product/image.php', array(
		'product_id' => $product->id
	), '', $woocommerce_composite_products->plugin_path() . '/templates/' );
}

?><div class="details component_data" data-component_set="true" data-price="<?php echo $data['price_data']['price']; ?>" data-regular_price="<?php echo $data['price_data']['regular_price']; ?>" data-product_type="simple" data-custom="<?php echo esc_attr( json_encode( $data[ 'custom_data' ] ) ); ?>"><?php

	if ( $hide_product_description != 'yes' )
		wc_get_template( 'composited-product/excerpt.php', array(
			'product_description' => $product->post->post_excerpt,
			'product_id'          => $product->id
		), '', $woocommerce_composite_products->plugin_path() . '/templates/' );

	?><div class="component_wrap"><?php

			if ( $per_product_pricing && $product->get_price() !== '' ) {
				wc_get_template( 'composited-product/price.php', array(
					'product' => $product
				), '', $woocommerce_composite_products->plugin_path() . '/templates/' );
			}

			// Add-ons
			do_action( 'woocommerce_composite_product_add_to_cart', $product->id, $component_id, $product );

			// Availability
			$availability = $woocommerce_composite_products->api->get_composited_item_availability( $product, $quantity_min );

			if ( $availability[ 'availability' ] ) {
				echo apply_filters( 'woocommerce_stock_html', '<p class="stock ' . esc_attr( $availability[ 'class' ] ) . '">' . esc_html( $availability[ 'availability' ] ) . '</p>', $availability[ 'availability' ] );
		    }

			if ( $product->is_in_stock() ) {

				?><div class="quantity_button"><?php

			 		wc_get_template( 'composited-product/quantity.php', array(
						'quantity_min'      => $quantity_min,
						'quantity_max'      => $quantity_max,
						'component_id'      => $component_id,
						'product'           => $product,
						'composite_product' => $composite_product
					), '', $woocommerce_composite_products->plugin_path() . '/templates/' );

			 	?></div><?php
			}
	?></div>
</div>

