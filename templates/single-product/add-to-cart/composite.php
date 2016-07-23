<?php
/**
 * Composite quantity input template.
 *
 * @version 2.5.2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

?>

<div class="row">

	<div class="medium-4 columns">
			
		<?php
	
			if ( ! $product->is_sold_individually() )
				woocommerce_quantity_input( array ( 'min_value' => 1 ) );
			else {
				?><input class="qty" type="hidden" name="quantity" value="1" /><?php
			}
			
		?>
		
	</div>
	
	<div class="medium-8 columns">
		
		<button type="submit" class="button buy radius small-12 disabled" rv-disabled="product:errors" rv-class-disabled="product:errors" rv-on-click="product.add_to_cart"><?php echo $product->single_add_to_cart_text(); ?></button>
		
	</div>
	
</div>