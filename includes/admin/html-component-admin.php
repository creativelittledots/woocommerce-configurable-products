<?php
/**
 * Admin Add Component markup.
 * @version 3.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;
?>

<div class="bto_group wc-metabox open">
	<h3>
		<button type="button" class="remove_row button"><?php _e( 'Remove', 'woocommerce' ); ?></button>
		<div class="handlediv" title="<?php _e( 'Click to toggle', 'woocommerce' ); ?>"></div>
		<strong class="group_name"></strong>
	</h3>
	<div class="bto_group_data wc-metabox-content">
		<ul class="subsubsub">
			<li><a href="#" data-tab="basic" class="current"><?php
				echo __( 'Basic Configuration', 'woocommerce-composite-products' );
			?></a> | </li>
			<li><a href="#" data-tab="advanced"><?php
				echo __( 'Advanced Configuration', 'woocommerce-composite-products' );
				?></a>
			</li>
		</ul>
		<div class="options_group options_group_basic">
			<?php do_action( 'woocommerce_composite_component_admin_config_html', $id, array(), $post_id ); ?>
		</div>
		<div class="options_group options_group_advanced options_group_hidden">
			<?php do_action( 'woocommerce_composite_component_admin_advanced_html', $id, array(), $post_id ); ?>
		</div>
	</div>
</div>
