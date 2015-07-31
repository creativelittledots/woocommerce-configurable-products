<?php
/**
 * Component Option Thumbnails Template.
 *
 * @version 3.1.0
 * @since  2.2.7
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// By default, the component options loop has 1 column less than the main shop loop
$thumbnail_columns = apply_filters( 'woocommerce_composite_component_loop_columns', max( apply_filters( 'loop_shop_columns', 4 ) - 1, 1 ), $component_id, $product );

?><div id="component_option_thumbnails_<?php echo $component_id; ?>" class="component_option_thumbnails columns-<?php echo $thumbnail_columns; ?>" data-columns="<?php echo $thumbnail_columns; ?>"><?php

	if ( ! empty( $component_options ) ) {

		?><ul class="component_option_thumbnails_container cp_clearfix" style="list-style:none"><?php

			$thumbnail_loop = 0;

			foreach ( $component_options as $product_id ) {

				$thumbnail_class    = '';
				$composited_product = $product->get_composited_product( $component_id, $product_id );

				if ( ! $composited_product ) {
					continue;
				}

				$thumbnail_loop++;

				// Quantity suffix
				if ( $quantity_min == $quantity_max && $quantity_min > 1 ) {
					$quantity = ' &times; ' . $quantity_min;
				} else {
					$quantity = '';
				}

				// Product loop first/last class
				if ( ( ( $thumbnail_loop - 1 ) % $thumbnail_columns ) == 0 || $thumbnail_columns == 1 ) {
			        $thumbnail_class = 'first';
				}

				if ( $thumbnail_loop % $thumbnail_columns == 0 ) {
					$thumbnail_class = 'last';
				}

				$selected = $selected_option == $product_id ? 'selected' : '';

				?><li class="component_option_thumbnail_container <?php echo $thumbnail_class; ?>">
					<div id="component_option_thumbnail_<?php echo $product_id; ?>" class="cp_clearfix component_option_thumbnail disabled <?php echo $selected; ?>" data-val="<?php echo $product_id; ?>">
						<a class="component_option_thumbnail_tap" href="#" ></a>
						<div class="image thumbnail_image" title="<?php echo get_the_title( $product_id ) . $quantity . $composited_product->get_price_string(); ?>"><?php

							if ( has_post_thumbnail( $product_id ) ) {
								echo get_the_post_thumbnail( $product_id, apply_filters( 'woocommerce_composite_component_option_image_size', 'shop_catalog' ) );
							} else {
								echo apply_filters( 'woocommerce_composite_component_option_image_placeholder', sprintf( '<img src="%s" alt="%s" />', wc_placeholder_img_src(), __( 'Placeholder', 'woocommerce' ) ), $product_id, $component_id, $product->id );
							}

					?></div>
						<div class="thumbnail_description">
							<h5 class="thumbnail_title title">
								<?php echo $composited_product->get_product()->get_title() . $quantity; ?>
							</h5>
							<span class="thumbnail_price price"><?php
								$composited_product->add_filters();
								echo $composited_product->get_product()->get_price_html();
								$composited_product->remove_filters(); ?>
							</span>
						</div>
					</div>

				</li><?php
			}
		?></ul><?php
	} else {

		?><p><?php
			echo __( 'No options found.', 'woocommerce-composite-products' );
		?></p><?php
	}

	?><div class="cp_clearfix"></div>
</div>
