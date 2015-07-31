<?php
/**
 * Composite navigation template.
 *
 * @version  3.0.0
 * @since  2.5.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( in_array( $navigation_style, array( 'paged', 'progressive' ) ) ) {

	?><div id="composite_navigation_<?php echo $product->id; ?>" class="composite_navigation <?php echo $navigation_style_variation; ?> <?php echo $navigation_style === 'paged' ? 'paged' : 'progressive'; ?>" <?php echo $navigation_style === 'progressive' ? 'style="display:none"' : ''; ?>>
		<a class="page_button prev" href="#">
		</a>
		<span class="prompt invisible">
		</span>
		<a class="page_button next" href="#">
		</a>
	</div><?php
}
