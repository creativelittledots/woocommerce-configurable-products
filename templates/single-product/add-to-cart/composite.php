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

if ( ! $product->is_purchasable() ) {
	return;
}

?>

<div class="row">

	<div class="medium-4 columns">
			
		<?php if ( ! $product->is_sold_individually() ) : ?>
		
		    <div class="quantity">
    		    
            	<input type="number" step="1" min="1" name="quantity" rv-value="product:quantity" title="<?php echo esc_attr_x( 'Qty', 'Product quantity input tooltip', 'woocommerce' ) ?>" class="input-text qty text" size="4" pattern="[0-9]*" inputmode="numeric" />
            	
            </div>

		<?php else : ?>
				
            <input class="qty" type="hidden" name="quantity" value="1" rv-value="product:quantity" />
				
        <?php endif; ?>
		
	</div>
	
	<div class="medium-8 columns">
		
		<button type="submit" class="button buy radius small-12 disabled composite_add_to_cart_button" rv-disabled="product:errors" rv-class-disabled="product:errors" rv-on-click="product.add_to_cart_click"><?php echo $product->single_add_to_cart_text(); ?></button>
		
	</div>
	
</div>