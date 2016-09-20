<?php
/**
 * Composite front-end filters and functions.
 *
 * @class 	WC_CP_Display
 * @version 3.1.0
 * @since   2.2.2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_CP_Display {

	private $enqueued_composited_table_item_js = false;

	public function __construct() {

		/* ------------------------------------------------- */
		/* Composite products single product template hooks
		/* ------------------------------------------------- */

		// Single product template
		add_action( 'woocommerce_single_product_summary', array( $this, 'wc_cp_form' ), 30 );

		// Single product add-to-cart button template for composite products
		add_action( 'woocommerce_composite_add_to_cart', array( $this, 'wc_cp_add_to_cart' ) );


		/* ------------------------------- */
		/* Other display-related hooks
		/* ------------------------------- */

		// Filter add_to_cart_url and add_to_cart_text when product type is 'composite'
		add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'wc_cp_loop_add_to_cart_link' ), 10, 2 );

		// Front end scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'wc_cp_frontend_scripts' ) );

		// QV support
		add_action( 'wc_quick_view_enqueue_scripts', array( $this, 'wc_cp_qv' ) );
		
	}


	/* ------------------------------------------------------------------------------- */
	/* Other
	/* ------------------------------------------------------------------------------- */

	/**
	 * Add-to-cart button and quantity template for composite products.
	 * @return void
	 */
	public function wc_cp_add_to_cart() {

		global $woocommerce_composite_products;

		wc_get_template( 'single-product/add-to-cart/composite.php', array(), false, $woocommerce_composite_products->plugin_path() . '/templates/' );
		
	}

	/**
	 * Add-to-cart template for composite products.
	 * @return void
	 */
	public function wc_cp_form() {

		global $product, $woocommerce_composite_products;
		
		if( ! $product->is_type( 'composite' ) || ! $product->get_composite_data() ) {
			return;
		}

		// Enqueue scripts
		wp_enqueue_script( 'backbone' );
		wp_enqueue_script( 'rivets' );
		wp_enqueue_script( 'rivets-formatters' );
		wp_enqueue_script( 'rivets-backbone' );
		
		$product_data = apply_filters( 'wc_cp_product_data', array(
    		'id' => $product->id,
    		'min_price' => (float) $product->get_min_price(),
    		'min_price_incl_tax' => (float) $product->get_min_price_including_tax(),
			'base_price' => (float) $product->get_base_price(),
			'base_price_incl_tax' => (float) $product->get_base_price_including_tax(),
			'base_sku'  => $product->get_build_sku() ? $product->get_base_sku() : $product->get_sku(),
			'base_weight'  => (float) $product->get_base_weight(),
			'build_sku' => $product->get_build_sku(),
			'components' => $product->get_composite_data(),
			'scenarios' => array_values( $product->get_composite_scenario_data() )
		), $product );
		
		wp_localize_script( 'wc-add-to-cart-composite', 'wc_cp_product_data', $product_data );
		
		wp_enqueue_script( 'wc-add-to-cart-composite' );

		wp_enqueue_style( 'wc-composite-single-css' );

		wc_get_template( 'single-product/composite.php', array(
			'components'       => $components,
			'product'          => $product
		), '', $woocommerce_composite_products->plugin_path() . '/templates/' );

	}

	/**
	 * Adds QuickView support.
	 *
	 * @param  string      $link
	 * @param  WC_Product  $product
	 * @return string
	 */
	public function wc_cp_loop_add_to_cart_link( $link, $product ) {

		if ( $product->is_type( 'composite' ) ) {
			
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

		global $woocommerce_composite_products;

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		$dependencies = array( 'jquery', 'jquery-blockui', 'backbone', 'rivets', 'rivets-backbone', 'rivets-formatters' );

		// Add any custom script dependencies here
		// Examples: custom product type scripts and component layered filter scripts
		$dependencies = apply_filters( 'woocommerce_composite_script_dependencies', $dependencies );
		
		wp_register_script( 'backbone', $woocommerce_composite_products->plugin_url() . '/assets/js/vendor/backbone.min.js', array(), $woocommerce_composite_products->version );
		
		wp_register_script( 'rivets', $woocommerce_composite_products->plugin_url() . '/assets/js/vendor/rivets.bundled.min.js', array(), $woocommerce_composite_products->version );
		
		wp_register_script( 'rivets-formatters', $woocommerce_composite_products->plugin_url() . '/assets/js/vendor/rivets.formatters.min.js', array(), $woocommerce_composite_products->version );
		
		wp_register_script( 'rivets-backbone', $woocommerce_composite_products->plugin_url() . '/assets/js/vendor/rivets.backbone.min.js', array(), $woocommerce_composite_products->version );

		wp_register_script( 'wc-add-to-cart-composite', $woocommerce_composite_products->plugin_url() . '/assets/js/frontend/add-to-cart-composite' . $suffix . '.js', $dependencies, $woocommerce_composite_products->version );

		wp_register_style( 'wc-composite-single-css', $woocommerce_composite_products->plugin_url() . '/assets/css/frontend/wc-composite-single.css', false, $woocommerce_composite_products->version, 'all' );

		$params = apply_filters( 'wc_cp_params', array(
			'currency'                        		 => get_woocommerce_currency_symbol(),
			'script_debug'                           => defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? 'yes' : 'no'
		) );
		
		global $post;
			
		if( $post instanceOf WP_Post ) {
			
			$product = new WC_Product($post);
			
			$params = array_merge($params, array(
				'sku' => $product->get_sku(),
				'weight' => $product->get_weight(),
			));
		
		}
		
		wp_localize_script( 'wc-add-to-cart-composite', 'wc_cp_params', $params );
		
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
			wp_enqueue_script( 'wc-add-to-cart-composite' );

			// Enqueue styles
			wp_enqueue_style( 'wc-composite-single-css' );
		}
	}
	
}
