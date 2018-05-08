<?php
/**
 * Configurable front-end filters and functions.
 *
 * @class 	WC_CP_Display
 * @version 3.1.0
 * @since   2.2.2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_CP_Display {

	private $enqueued_configurabled_table_item_js = false;

	public function __construct() {

		/* ------------------------------------------------- */
		/* Composite products single product template hooks
		/* ------------------------------------------------- */

		// Single product template
		add_action( 'woocommerce_single_product_summary', array( $this, 'wc_cp_form' ), 30 );

		// Single product add-to-cart button template for configurable products
		add_action( 'woocommerce_configurable_add_to_cart', array( $this, 'wc_cp_add_to_cart' ) );


		/* ------------------------------- */
		/* Other display-related hooks
		/* ------------------------------- */

		// Filter add_to_cart_url and add_to_cart_text when product type is 'configurable'
		add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'wc_cp_loop_add_to_cart_link' ), 10, 2 );

		// Front end scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'wc_cp_frontend_scripts' ) );

		// QV support
		add_action( 'wc_quick_view_enqueue_scripts', array( $this, 'wc_cp_qv' ) );
		
		// GPF Support
		add_filter( 'woocommerce_gpf_product_feed_args', array($this, 'gpf_product_feed_args'));
			
		add_filter( 'woocommerce_gpf_other_supported_product_types', array($this, 'gpf_other_supported_product_types'));
		
	}


	/* ------------------------------------------------------------------------------- */
	/* Other
	/* ------------------------------------------------------------------------------- */

	/**
	 * Add-to-cart button and quantity template for configurable products.
	 * @return void
	 */
	public function wc_cp_add_to_cart() {

		global $wc_configurable_products;

		wc_get_template( 'single-product/add-to-cart/configurable.php', array(), false, $wc_configurable_products->plugin_path() . '/templates/' );
		
	}

	/**
	 * Add-to-cart template for configurable products.
	 * @return void
	 */
	public function wc_cp_form() {

		global $product, $wc_configurable_products;
		
		if( ! $product->is_type( 'configurable' ) || ! $product->get_configuration() ) {
			return;
		}

		// Enqueue scripts
		wp_enqueue_script( 'backbone' );
		wp_enqueue_script( 'rivets' );
		wp_enqueue_script( 'rivets-formatters' );
		wp_enqueue_script( 'rivets-backbone' );

		wp_localize_script( 'wc-add-to-cart-configurable', 'wc_cp_product_data', (array) $product->get_configuration() );
		
		wp_enqueue_script( 'wc-add-to-cart-configurable' );
		
		wp_enqueue_style( 'wc-configurable-single-css' );

		wc_get_template( 'single-product/configurable.php', array(
			'product'          => $product
		), '', $wc_configurable_products->plugin_path() . '/templates/' );

	}

	/**
	 * Adds QuickView support.
	 *
	 * @param  string      $link
	 * @param  WC_Product  $product
	 * @return string
	 */
	public function wc_cp_loop_add_to_cart_link( $link, $product ) {

		if ( $product->is_type( 'configurable' ) ) {
			
			return str_replace( 'add_to_cart_button', '', $link );
			
		}

		return $link;
	}

	/**
	 * Front end styles and scripts.
	 *
	 * @return void
	 */
	public function wc_cp_frontend_scripts() {

		global $wc_configurable_products;

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		$dependencies = array( 'jquery', 'jquery-blockui', 'backbone', 'rivets', 'rivets-backbone', 'rivets-formatters' );

		// Add any custom script dependencies here
		// Examples: custom product type scripts and component layered filter scripts
		$dependencies = apply_filters( 'woocommerce_configurable_script_dependencies', $dependencies );
		
		wp_register_script( 'backbone', $wc_configurable_products->plugin_url() . '/assets/js/vendor/backbone.min.js', array(), $wc_configurable_products->version );
		
		wp_register_script( 'rivets', $wc_configurable_products->plugin_url() . '/assets/js/vendor/rivets.bundled.min.js', array(), $wc_configurable_products->version );
		
		wp_register_script( 'rivets-formatters', $wc_configurable_products->plugin_url() . '/assets/js/vendor/rivets.formatters.min.js', array(), $wc_configurable_products->version );
		
		wp_register_script( 'rivets-backbone', $wc_configurable_products->plugin_url() . '/assets/js/vendor/rivets.backbone.min.js', array(), $wc_configurable_products->version );

		wp_register_script( 'wc-add-to-cart-configurable', $wc_configurable_products->plugin_url() . '/assets/js/frontend/add-to-cart-configurable' . $suffix . '.js', $dependencies, $wc_configurable_products->version );

		wp_register_style( 'wc-configurable-single-css', $wc_configurable_products->plugin_url() . '/assets/css/frontend/wc-configurable-single.css', false, $wc_configurable_products->version, 'all' );

		$params = apply_filters( 'wc_cp_params', array(
			'currency'	=> get_woocommerce_currency_symbol(),
			'script_debug'	=> defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? 'yes' : 'no'
		) );
		
		global $post;
			
		if( $post instanceOf WP_Post && $post->post_type == 'product' ) {
			
			$product = new WC_Product( $post );
			
			$params = array_merge($params, array(
				'sku' => $product->get_sku(),
				'weight' => $product->get_weight(),
			));
		
		}
		
		wp_localize_script( 'wc-add-to-cart-configurable', 'wc_cp_params', $params );
		
	}

	/**
	 * QuickView scripts init.
	 *
	 * @return void
	 */
	public function wc_cp_qv() {

		if ( ! is_product() ) {

			$this->wc_cp_frontend_scripts();

			// Enqueue script
			wp_enqueue_script( 'wc-add-to-cart-configurable' );

			// Enqueue styles
			wp_enqueue_style( 'wc-configurable-single-css' );
		}
	}
	
	
	public function gpf_other_supported_product_types($types) {
			
		$types[] = 'configurable';
		
		return $types;
		
	} 
	
	public function gpf_product_feed_args( $array ) {
		
		$configurables = wc_get_products(array(
			'type' => array('configurable'), 
			'visibility' => 'catalog', 
			'return' => 'ids',
			'limit' => 9999,
			'meta_key' => '_needs_configuration',
			'meta_value' => 1
		));
		
		$array['type'][] = 'configurable';
		
		if( $configurables ) {
			
			$array['post__not_in'] = ! empty( $array['post__not_in'] ) ? $array['post__not_in'] : [];
			
			$array['post__not_in'] = array_merge($array['post__not_in'], $configurables);
			
		}
		
		return $array;
		
	}
	
}
