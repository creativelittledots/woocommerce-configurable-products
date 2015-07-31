<?php
/**
 * Composited Product Price.
 *
 * @version  3.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

?>
<p class="price"><?php echo $product->get_price_html(); ?></p>
