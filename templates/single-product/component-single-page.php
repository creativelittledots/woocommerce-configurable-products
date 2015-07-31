<?php
/**
 * Single Page Component Template.
 *
 * @version 3.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $woocommerce_composite_products;

$component_classes = $product->get_component_classes( $component_id );

?><div id="component_<?php echo $component_id; ?>" class="<?php echo esc_attr( implode( ' ', $component_classes ) ); ?>" data-nav_title="<?php echo esc_attr( apply_filters( 'woocommerce_composite_component_title', $component_data[ 'title' ], $component_id, $product->id ) ); ?>" data-item_id="<?php echo $component_id; ?>">

	<div class="component_title_wrapper"><?php

		$title = apply_filters( 'woocommerce_composite_component_title', $component_data[ 'title' ], $component_id, $product->id );

		wc_get_template( 'single-product/component-title.php', array(
			'title'   => $title,
			'toggled' => in_array( 'toggled', $component_classes ),
		), '', $woocommerce_composite_products->plugin_path() . '/templates/' );

	?></div>

	<div class="component_inner" <?php echo in_array( 'toggled', $component_classes ) && in_array( 'closed', $component_classes ) ? 'style="display:none;"' : ''; ?>>
		<div class="component_description_wrapper"><?php

			if ( $component_data[ 'description' ] != '' )
				wc_get_template('single-product/component-description.php', array(
					'description' => apply_filters( 'woocommerce_composite_component_description', $component_data[ 'description' ], $component_id, $product->id )
				), '', $woocommerce_composite_products->plugin_path() . '/templates/' );

		?></div>
		<div class="component_selections"><?php

			/**
			 * woocommerce_composite_component_selections_single hook (single-page)
			 *
			 * @hooked WC_CP_Display::wc_cp_add_sorting - 15
			 * @hooked WC_CP_Display::wc_cp_add_filtering - 20
			 * @hooked WC_CP_Display::wc_cp_add_component_options - 25
			 * @hooked WC_CP_Display::wc_cp_add_component_options_pagination - 26
			 * @hooked WC_CP_Display::wc_cp_add_current_selection_details - 35
			 */
			do_action( 'woocommerce_composite_component_selections_single', $component_id, $product );

		?></div>
	</div>
</div>
