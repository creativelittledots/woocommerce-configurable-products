<?php
/**
 * Composite Widget Functions.
 *
 * Register a widget that displays a Composite configuration summary in Multi-page mode.
 *
 * @version  3.0.0
 * @since    3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Widgets
 *
 * @since 3.0.0
 */
function wc_cp_register_widgets() {

	// Include widget classes
	include_once( 'widgets/class-wc-widget-composite.php' );

	register_widget( 'WC_Widget_Composite' );
}

add_action( 'widgets_init', 'wc_cp_register_widgets', 11 );
