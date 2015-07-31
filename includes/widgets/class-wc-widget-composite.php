<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Composite Product Config Summary Widget.
 *
 * Displays configuration summary of the currently displayed composite product.
 * By default applicable to Multi-page Composites only.
 *
 * @version  3.0.0
 * @since    3.0.0
 * @extends  WC_Widget
 */
class WC_Widget_Composite extends WC_Widget {

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->widget_cssclass    = 'woocommerce widget_composite_summary composite_summary';
		$this->widget_description = __( "Display a Composite Product configuration summary in the sidebar. The widget will be displayed only with Composite products that use the Stepped layout.", 'woocommerce-composite-products' );
		$this->widget_id          = 'woocommerce_widget_composite_summary';
		$this->widget_name        = __( 'WooCommerce Composite Product Summary', 'woocommerce-composite-products' );
		$this->settings           = array(
			'title'  => array(
				'type'  => 'text',
				'std'   => __( 'Your Configuration', 'woocommerce-composite-products' ),
				'label' => __( 'Title', 'woocommerce' )
			)
		);

		parent::__construct();
	}

	/**
	 * widget function.
	 *
	 * @see WP_Widget
	 *
	 * @param array $args
	 * @param array $instance
	 *
	 * @return void
	 */
	public function widget( $args, $instance ) {

		global $product;

		if ( ! is_product() ) {
			return;
		}

		if ( $product->product_type === 'composite' ) {

			$layout_style           = $product->get_composite_layout_style();
			$layout_style_variation = $product->get_composite_layout_style_variation();
			$show_widget            = apply_filters( 'woocommerce_composite_summary_widget_display', $layout_style === 'paged' && $layout_style_variation !== 'componentized', $layout_style, $layout_style_variation, $product );

			if ( ! $show_widget ) {
				return;
			}

		} else {
			return;
		}

		echo $args[ 'before_widget' ];

		$default = isset( $this->settings[ 'title' ][ 'std' ] ) ? $this->settings[ 'title' ][ 'std' ] : '';
		if ( $title = apply_filters( 'widget_title', empty( $instance[ 'title' ] ) ? $default : $instance[ 'title' ], $instance, $this->id_base ) ) {
			echo $args[ 'before_title' ] . $title . $args[ 'after_title' ];
		}

		// Insert cart widget placeholder - code in woocommerce.js will update this on page load
		echo '<div class="widget_composite_summary_content" data-container_id="' . $product->id . '"></div>';

		echo $args[ 'after_widget' ];
	}
}
