<?php
/**
 * Admin Add Component markup.
 * @version 3.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;
?>

<div class="bto_group wc-metabox <?php echo $toggle; ?>" rel="<?php echo $data[ 'position' ]; ?>">
	<h3>
		<button type="button" class="remove_row button"><?php _e( 'Remove', 'woocommerce' ); ?></button>
		<div class="handlediv" title="<?php _e( 'Click to toggle', 'woocommerce' ); ?>"></div>
		<strong class="group_name">
			<?php 
				if (isset($data[ 'component_id' ])) {
					echo apply_filters( 'woocommerce_composite_component_title', $data[ 'title' ], $data[ 'component_id' ], $post_id ); 
				}
			?>
		</strong>
		<?php if (isset($data[ 'component_id' ]) && $data[ 'component_id' ]) : ?>
			<input type="hidden" name="bto_data[<?php echo $data[ 'position' ]; ?>][group_id]" class="group_id" value="<?php echo $data[ 'component_id' ]; ?>" />
		<?php endif; ?>
	</h3>
	<div class="bto_group_data wc-metabox-content">
		<ul class="subsubsub">
			<?php $i = 0; foreach($tabs as $tab_key => $tab) : ?>
			<li<?php echo ! empty( $tab['condition'] ) ? ' class="' . $tab['condition'] . '"' : ''; ?>><a href="#" data-tab="<?php echo $tab_key; ?>" class="<?php echo $i == 0 ? 'current' : ''; ?> <?php implode(' ', $tab['classes']); ?>"><?php
				echo __( $tab['title'] , 'woocommerce-composite-products' );
			?></a><?php echo count($tabs) > ($i+1)  ? ' | ' : ''; ?></li>
			<?php $i++; endforeach; ?>
		</ul>
		<?php $i = 0; foreach($tabs as $tab_key => $tab) : ?>
		<div class="options_group options_group_<?php echo $tab_key; ?> <?php echo $i > 0 ? 'options_group_hidden' : ''; ?>">
			<?php do_action( $tab['action'], $data[ 'position' ], $data, $post_id ); ?>
		</div>
		<?php $i++; endforeach; ?>
		<?php if (isset($data[ 'component_id' ]) && $data[ 'component_id' ]) : ?>
			<span class="group_id">
				<?php echo sprintf( __( '#id: %s', 'woocommerce-composite-products' ), $data[ 'component_id' ] ); ?>
			</span>
		<?php endif; ?>
	</div>
</div>
