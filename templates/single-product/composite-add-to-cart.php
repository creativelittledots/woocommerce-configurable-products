<?php
/**
 * Composite add-to-cart panel template.
 *
 * In paged mode, this template is displayed by hooking 'wc_cp_add_paged_mode_cart' on the 'woocommerce_composite_after_components' action.
 *
 * @version  3.0.0
 * @since  1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?><div id="composite_data_<?php echo $product->id; ?>" class="cart composite_data" data-item_id="review" data-button_behaviour="<?php echo esc_attr( apply_filters( 'woocommerce_composite_button_behaviour', 'new', $product ) ); ?>" data-nav_title="<?php echo esc_attr( __( 'Review &amp; Add to Cart', 'woocommerce-composite-products' ) ); ?>" data-bto_selection_mode="<?php echo $selection_mode; ?>" data-bto_style="<?php echo $navigation_style; ?>" data-bto_style_variation="<?php echo $navigation_style_variation; ?>" data-progression_style="<?php echo esc_attr( apply_filters( 'woocommerce_composite_progression_style', 'normal', $product ) ); ?>" data-scenario_data="<?php echo esc_attr( json_encode( $product->get_composite_scenario_data() ) ); ?>" data-price_data="<?php echo esc_attr( json_encode( $product->get_composite_price_data() ) ); ?>" data-container_id="<?php echo $product->id; ?>"><?php

	do_action( 'woocommerce_before_add_to_cart_button' );

	?><div class="composite_wrap" style="<?php echo apply_filters( 'woocommerce_composite_button_behaviour', 'new', $product ) == 'new' ? '' : 'display:none'; ?>">
		<div class="composite_price"></div><?php

			// Availability
			$availability = $product->get_availability();

			if ( $availability[ 'availability' ] )
				echo apply_filters( 'woocommerce_stock_html', '<p class="stock ' . $availability[ 'class' ] . '">' . $availability[ 'availability' ] . '</p>', $availability[ 'availability' ] );

		?><div class="composite_button"><?php

			foreach ( $components as $component_id => $component_data ) {

				?><div class="form_data form_data_<?php echo $component_id; ?>">
				</div><?php

			}

			do_action( 'woocommerce_composite_add_to_cart_button' );

			?><input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product->id ); ?>" />
		</div>
	</div><?php

	do_action( 'woocommerce_after_add_to_cart_button' );

?></div>

<?php do_action( 'woocommerce_after_add_to_cart_form' );
