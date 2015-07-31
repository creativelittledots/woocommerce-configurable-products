<?php
/**
 * Show options for ordering.
 *
 * @version  3.0.0
 * @since    2.6.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$component_ordering_options = $product->get_component_ordering_options( $component_id );

if ( $component_ordering_options ) {

	$orderby = $product->get_component_default_ordering_option( $component_id );

	?><div class="component_ordering">

		<p class="component_section_title">
			<label class="component_orderby_title"><?php
			echo __( 'Sort options by', 'woocommerce-composite-products' );
			?></label>
		</p>

		<select name="component_orderby_<?php echo $component_id; ?>" class="component_orderby component_orderby_<?php echo $component_id; ?> orderby">
			<?php foreach ( $component_ordering_options as $id => $name ) : ?>
				<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $orderby, $id ); ?>><?php echo esc_html( $name ); ?></option>
			<?php endforeach; ?>
		</select>
	</div><?php
}
