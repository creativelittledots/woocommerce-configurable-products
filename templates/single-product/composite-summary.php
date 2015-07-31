<?php
/**
 * Composite paged mode Summary template.
 *
 * By default, this template is hooked on the 'woocommerce_before_add_to_cart_button' action, found inside the composite add-to-cart template (composite-add-to-cart.php).
 * Note that the latter one is displayed by hooking 'wc_cp_add_paged_mode_cart' on the 'woocommerce_composite_after_components' action.
 *
 * @version  3.1.0
 * @since  3.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$summary_elements = count( $components );
$summary_columns  = min( apply_filters( 'woocommerce_composite_component_summary_max_columns', 6, $product ), $summary_elements );
$summary_classes  = 'columns-' . $summary_columns;

if ( apply_filters( 'woocommerce_composite_summary_vertical_style', false, $product ) ) {
	$summary_classes .= ' force_vertical';
}

?><div id="composite_summary_<?php echo $product->id; ?>" class="composite_summary <?php echo $summary_classes; ?>" data-columns="<?php echo $summary_columns; ?>" <?php echo $hidden ? 'style="display:none;"' : ''; ?>><?php

	if ( $product->get_composite_layout_style_variation() === 'componentized' ) {

		?><h2 class="summary_title component_title"><?php
			echo __( 'Your Configuration', 'woocommerce-composite-products' );
		?></h2><?php

	} else {

		?><h2 class="summary_title component_title"><?php
			$final_step = count( $components ) + 1;
			$title      = __( 'Review &amp; Add to Cart', 'woocommerce-composite-products' );
			echo apply_filters( 'woocommerce_composite_component_step_title', sprintf( __( '<span class="step_index">%d</span> <span class="step_title">%s</span>', 'woocommerce-composite-products' ), $final_step, $title ), $title, $final_step, count( $components ), $product );
		?></h2>

		<p class="component_section_title">
			<label class="summary_label"><?php echo __( 'Your configuration:', 'woocommerce-composite-products' ); ?>
			</label>
		</p><?php
	}

	?><ul class="summary_elements cp_clearfix" style="list-style:none"><?php

		$summary_element_loop = 1;

		foreach ( $components as $component_id => $component_data ) {

			$summary_element_class = '';

			// Summary loop first/last class
			if ( ( ( $summary_element_loop - 1 ) % $summary_columns ) == 0 || $summary_columns == 1 ) {
				$summary_element_class = 'first';
			}

			if ( $summary_element_loop % $summary_columns == 0 ) {
				$summary_element_class = 'last';
			}

			$title = apply_filters( 'woocommerce_composite_component_title', $component_data[ 'title' ], $component_id, $product->id );

			?><li class="summary_element summary_element_<?php echo $component_id; ?> <?php echo $summary_element_class; ?>" data-item_id="<?php echo $component_id; ?>">
				<div class="summary_element_wrapper_outer">
					<div class="summary_element_wrapper summary_element_link cp_clearfix disabled">
						<div class="summary_element_wrapper_inner cp_clearfix">
							<a class="summary_element_tap" href="#" ></a>
							<div class="summary_element_title summary_element_data">
								<h3 class="title summary_element_content"><?php
									echo apply_filters( 'woocommerce_composite_component_step_title', sprintf( __( '<span class="step_index">%d</span> <span class="step_title">%s</span>', 'woocommerce-composite-products' ), $summary_element_loop, $title ), $title, $summary_element_loop, $summary_elements, $product );
								?></h3>
							</div>
							<div class="summary_element_image summary_element_data"><?php

								echo $product->get_component_image( $component_id );

							?></div>
							<div class="summary_element_selection summary_element_data">
							</div><?php

							if ( $product->is_priced_per_product() ) {

								?><div class="summary_element_price summary_element_data">
								</div><?php
							}

						?></div>
					</div>
				</div>
			</li><?php

			$summary_element_loop++;
		}
	?></ul>
</div>
