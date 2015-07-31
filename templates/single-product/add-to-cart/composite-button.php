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

?>
<button type="submit" class="single_add_to_cart_button composite_add_to_cart_button button alt"><?php echo $product->single_add_to_cart_text(); ?></button>
