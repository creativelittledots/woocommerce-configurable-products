<?php
/**
 * Admin Add Scenario markup.
 * @version 3.1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;
?>
<div class="bto_scenario bto_scenario_<?php echo $id; ?> wc-metabox closed">
	<h3>
		<button type="button" class="remove_row button"><?php echo __( 'Remove', 'woocommerce' ); ?></button>
		<div class="handlediv" title="<?php echo __( 'Click to toggle', 'woocommerce' ); ?>"></div>
		<strong class="scenario_name"></strong>
	</h3>
	<div class="bto_scenario_data wc-metabox-content">
		<div class="options_group">
			<h4><?php echo __( 'Scenario Name &amp; Description', 'woocommerce-composite-products' ); ?></h4><?php

			do_action( 'woocommerce_composite_scenario_admin_info_html', $id, $scenario_data, $composite_data, $post_id );

			?><h4><?php echo __( 'Scenario Configuration', 'woocommerce-composite-products' ); ?></h4><?php

			do_action( 'woocommerce_composite_scenario_admin_config_html', $id, $scenario_data, $composite_data, $post_id );

			?><h4><?php echo __( 'Scenario Actions', 'woocommerce-composite-products' ); ?></h4><?php

			do_action( 'woocommerce_composite_scenario_admin_actions_html', $id, $scenario_data, $composite_data, $post_id );

		?></div>
	</div>
</div>
