<?php
/**
 * Composite add-to-cart button template.
 *
 * @version 2.5.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

if ( ! $product->is_sold_individually() )
	woocommerce_quantity_input( array ( 'min_value' => 1 ) );
else {
	?><input class="qty" type="hidden" name="quantity" value="1" /><?php
}

?>
<button type="submit" class="single_add_to_cart_button composite_add_to_cart_button button alt"><?php echo $product->single_add_to_cart_text(); ?></button>
		
<div class="composite_sku"></div>

<?php if($product->bto_build_sku == 'yes') : ?>

	<input type="hidden" id="built_sku" name="built_sku" data-sku="<?php echo esc_attr( $product->bto_sku_start ); ?>" value="<?php echo esc_attr( $product->bto_sku_start ); ?>" />

<?php endif; ?>
