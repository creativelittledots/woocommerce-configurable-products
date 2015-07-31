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

global $woocommerce_composite_products;

$is_static      = $product->is_component_static( $component_id );
$is_optional    = $component_data[ 'optional' ] === 'yes';
$quantity_min   = $component_data[ 'quantity_min' ];
$quantity_max   = $component_data[ 'quantity_max' ];
$selection_mode = $product->get_composite_selections_style();

?><div class="component_options" style="<?php echo $is_static ? 'display:none;' : ''; ?>"><?php

	?><div class="component_options_inner cp_clearfix">

		<p class="component_section_title">
			<label class="select_label"><?php echo __( 'Select an option&hellip;', 'woocommerce-composite-products' ); ?>
			</label>
		</p><?php

		// Thumbnails template
		if ( $selection_mode === 'thumbnails' ) {
			wc_get_template( 'single-product/component-option-thumbnails.php', array(
				'product'           => $product,
				'component_id'      => $component_id,
				'quantity_min'      => $quantity_min,
				'quantity_max'      => $quantity_max,
				'component_options' => $component_options,
				'selected_option'   => $selected_option,
			), '', $woocommerce_composite_products->plugin_path() . '/templates/' );
		}

		?><select id="component_options_<?php echo $component_id; ?>" class="component_options_select" name="wccp_component_selection[<?php echo $component_id; ?>]" style="<?php echo $selection_mode === 'thumbnails' ? 'display:none;' : ''; ?>"><?php

			if ( ! $is_static ) {
				?><option class="empty none" data-title="<?php echo __( 'None', 'woocommerce-composite-products' ); ?>" value=""><?php echo $is_optional ? _x( 'Select an option&hellip;', 'select option dropdown text - optional component', 'woocommerce-composite-products' ) : _x( 'Select an option&hellip;', 'select option dropdown text - mandatory component', 'woocommerce-composite-products' ); ?></option><?php
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

				?><option data-title="<?php echo esc_attr( get_the_title( $product_id ) ); ?>" data-image_src="<?php echo esc_attr( $image_src ); ?>" value="<?php echo $product_id; ?>" <?php echo selected( $selected_option, $product_id, false ); ?>><?php

					if ( $quantity_min == $quantity_max && $quantity_min > 1 ) {
						$quantity = ' &times; ' . $quantity_min;
					} else {
						$quantity = '';
					}

					echo $composited_product->get_product()->get_title() . $quantity;

					echo $composited_product->get_price_string();

				?></option><?php
			}
		?></select>

		<div class="cp_clearfix"></div>
	</div><?php

?></div><?php
