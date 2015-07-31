<?php
/**
 * Composited Variable Product Template.
 *
 * @version  3.0.1
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

?><div class="details component_data" data-component_set="" data-price="0" data-regular_price="0" data-product_type="variable" data-product_variations="<?php echo esc_attr( json_encode( $data[ 'product_variations' ] ) ); ?>" data-custom="<?php echo esc_attr( json_encode( $data[ 'custom_data' ] ) ); ?>"><?php

	if ( $hide_product_description != 'yes' ) {
		wc_get_template( 'composited-product/excerpt.php', array(
			'product_description' => $product->post->post_excerpt,
			'product_id'          => $product->id
		), '', $woocommerce_composite_products->plugin_path() . '/templates/' );
	}

	$attributes          = $product->get_variation_attributes();
	$selected_attributes = $product->get_variation_default_attributes();

	?><table class="variations" cellspacing="0">
		<tbody><?php
		$loop = 0;
		foreach ( $attributes as $name => $options ) {
			$loop++;
			?><tr class="attribute-options" data-attribute_label="<?php echo wc_attribute_label( $name ); ?>">
				<td class="label">
					<label for="<?php echo sanitize_title( $name ); ?>"><?php echo wc_attribute_label( $name ); ?> <abbr class="required" title="required">*</abbr></label>
				</td>
				<td class="value">
					<select id="<?php echo esc_attr( sanitize_title( $name ) ); ?>" name="attribute_<?php echo sanitize_title( $name ); ?>">
						<option value=""><?php echo __( 'Choose an option', 'woocommerce' ) ?>&hellip;</option>
						<?php
						if ( is_array( $options ) ) {

							$selected_value = '';

							if ( isset( $_POST[ 'wccp_attribute_' . sanitize_title( $name ) ][ $component_id ] ) && $_POST[ 'wccp_attribute_' . sanitize_title( $name ) ][ $component_id ] !== '' ) {
								$selected_value = $_POST[ 'wccp_attribute_' . sanitize_title( $name ) ][ $component_id ];
							} else {
								$selected_value = ( isset( $selected_attributes[ sanitize_title( $name ) ] ) ) ? $selected_attributes[ sanitize_title( $name ) ] : '';
							}

							// Get terms if this is a taxonomy - ordered
							if ( taxonomy_exists( $name ) ) {

								$terms = wc_composite_get_product_terms( $product->id, $name, array( 'fields' => 'all' ) );

								foreach ( $terms as $term ) {

									if ( ! in_array( $term->slug, $options ) ) {
										continue;
									}

									echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( $selected_value, $term->slug, false ) . '>' . apply_filters( 'woocommerce_variation_option_name', $term->name ) . '</option>';
								}
							} else {

								foreach ( $options as $option ) {
									echo '<option value="' . esc_attr( sanitize_title( $option ) ) . '" ' . selected( sanitize_title( $selected_value ), sanitize_title( $option ), false ) . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option ) ) . '</option>';
								}
							}
						}
					?></select><?php
					if ( sizeof( $attributes ) == $loop ) {
						echo '<a class="reset_variations" href="#reset">' . __( 'Clear selection', 'woocommerce' ) . '</a>';
					}
				?></td>
			</tr><?php
			}
		?></tbody>
	</table><?php

	// Add-ons
	do_action( 'woocommerce_composite_product_add_to_cart', $product->id, $component_id, $product );

	?><div class="single_variation_wrap component_wrap" style="display:none;">

		<div class="single_variation"></div>
		<div class="variations_button">
			<input type="hidden" name="variation_id" value="" /><?php

		 		wc_get_template( 'composited-product/quantity.php', array(
					'quantity_min'      => $quantity_min,
					'quantity_max'      => $quantity_max,
					'component_id'      => $component_id,
					'product'           => $product,
					'composite_product' => $composite_product
				), '', $woocommerce_composite_products->plugin_path() . '/templates/' );

		 ?></div>
	</div>
</div>
