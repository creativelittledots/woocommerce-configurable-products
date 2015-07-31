<?php
/**
 * Show options for filtering.
 *
 * @version  3.0.3
 * @since    2.6.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$component_filtering_options = $product->get_component_filtering_options( $component_id );

if ( $component_filtering_options ) {

	?><div class="component_filters">

		<p class="component_section_title">
			<label class="component_filters_title">
				<?php echo __( 'Filter options', 'woocommerce-composite-products' ); ?>
			</label>
			<span class="reset_component_filters_wrapper">
				<a class="reset_component_filters" href="#">
					<span class="text"><?php
						echo __( 'Reset all', 'woocommerce-composite-products' );
					?></span>
				</a>
			</span>
		</p><?php

		foreach ( $component_filtering_options as $filter ) {

			?><div class="component_filter cp_clearfix closed" data-filter_type="<?php echo esc_attr( $filter[ 'filter_type' ] ); ?>" data-filter_id="<?php echo esc_attr( $filter[ 'filter_id' ] ); ?>">
				<div class="component_filter_title">
					<label><?php
						echo $filter[ 'filter_name' ];
						?>
						<span class="toggle_component_filter_wrapper">
							<a class="toggle_component_filter" href="#">
								<span class="toggle_component_filter_text"><?php
									echo __( 'Toggle', 'woocommerce-composite-products' );
								?></span>
							</a>
						</span>
					</label>
				</div>
				<div class="component_filter_reset">
					<span class="reset_component_filter_wrapper">
						<a class="reset_component_filter" href="#">
							<span class="text"><?php
								echo __( 'Reset', 'woocommerce-composite-products' );
							?></span>
						</a>
					</span>
				</div>
				<div class="component_filter_content" style="display:none;"><?php

					?><ul class="component_filter_options"><?php

						foreach ( $filter[ 'filter_options' ] as $option_id => $option_name ) {

							?><li class="component_filter_option" data-option_id="<?php echo $option_id; ?>">
								<a class="toggle_filter_option" href="#"><?php
									echo $option_name;
								?></a>
							</li><?php
						}

					?></ul><?php

				?></div>
			</div>
			<?php
		}

	?></div><?php
}
