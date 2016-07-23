<?php
/*
* Plugin Name: WooCommerce Composite Products Extended
* Plugin URI: http://www.woothemes.com/products/composite-products/
* Description: Create complex, configurable product kits and let your customers build their own, personalized versions. Extends the Woocommerce Composite Products Plugin to Allow Radio Box as an Option Style.
* Version: 1.0.0
* Author: Creative Little Dots
* Author URI: http://creativelittledots.co.uk
*
* Text Domain: woocommerce-composite-products
* Domain Path: /languages/
*
*
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Required functions
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}

// Check if WooCommerce is active
if ( ! is_woocommerce_active() ) {
	return;
}

class WC_Composite_Products {

	public $version 	= '3.1.0';
	public $required 	= '2.1.0';

	public $admin;
	public $api;
	public $cart;
	public $order;
	public $display;
	public $compatibility;

	public function __construct() {

		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

	}

	public function plugin_url() {
		return plugins_url( basename( plugin_dir_path(__FILE__) ), basename( __FILE__ ) );
	}

	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	public function plugins_loaded() {

		global $woocommerce;

		// WC 2 check
		if ( version_compare( $woocommerce->version, $this->required ) < 0 ) {
			add_action( 'admin_notices', array( $this, 'admin_notice' ) );
			return false;
		}

		// Functions for 2.X back-compat
		include_once( 'includes/wc-cp-functions.php' );

		// Class containing extensions compatibility functions and filters
		require_once( 'includes/class-wc-cp-compatibility.php' );
		$this->compatibility = new WC_CP_Compatibility();
		
		// WP_Query wrapper for component option queries
		require_once( 'includes/class-wc-cp-query.php' );

		// Composited product wrapper
		require_once( 'includes/class-wc-cp-product.php' );

		// Composite product API
		require_once( 'includes/class-wc-cp-api.php' );
		$this->api = new WC_CP_API();

		// Composite product class
		require_once( 'includes/class-wc-product-composite.php' );


		// Admin functions and meta-boxes
		if ( is_admin() ) {
			$this->admin_includes();
		}

		// Cart-related functions and filters
		require_once( 'includes/class-wc-cp-cart.php' );
		$this->cart = new WC_CP_Cart();

		// Order-related functions and filters
		require_once( 'includes/class-wc-cp-order.php' );
		$this->order = new WC_CP_Order();

		// Front-end filters
		require_once( 'includes/class-wc-cp-display.php' );
		$this->display = new WC_CP_Display();

	}

	/**
	 * Loads the Admin filters / hooks.
	 *
	 * @return void
	 */
	private function admin_includes() {

		require_once( 'includes/admin/class-wc-cp-admin.php' );
		$this->admin = new WC_CP_Admin();
	}

	/**
	 * Load textdomain.
	 *
	 * @return void
	 */
	public function init() {

		load_plugin_textdomain( 'woocommerce-composite-products', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Displays a warning message if version check fails.
	 *
	 * @return string
	 */
	public function admin_notice() {

	    echo '<div class="error"><p>' . sprintf( __( 'WooCommerce Composite Products requires at least WooCommerce %s in order to function. Please upgrade WooCommerce.', 'woocommerce-composite-products'), $this->required ) . '</p></div>';
	}

	/**
	 * Update or create 'Composite' product type on activation as required.
	 *
	 * @return void
	 */
	public function activate() {

		global $wpdb;

		$version = get_option( 'woocommerce_composite_products_version', false );

		if ( $version == false ) {

			$composite_type_exists = false;

			$product_type_terms = get_terms( 'product_type', array( 'hide_empty' => false ) );

			foreach ( $product_type_terms as $product_type_term ) {

				if ( $product_type_term->name === 'bto' ) {

					$composite_type_exists = true;

					// Check for existing 'composite' slug and if it exists, modify it
					if ( $existing_term_id = term_exists( 'composite' ) )
						$wpdb->update( $wpdb->terms, array( 'slug' => 'composite-b' ), array( 'term_id' => $existing_term_id ) );

					// Update composite type term
					wp_update_term( $product_type_term->term_id, 'product_type', array( 'slug' => 'composite', 'name' => 'composite' ) );

					break;

				} elseif ( $product_type_term->name === 'composite' ) {

					$composite_type_exists = true;
					break;
				}

			}

			if ( ! $composite_type_exists ) {

				// Check for existing 'composite' slug and if it exists, modify it
				if ( $existing_term_id = term_exists( 'composite' ) )
					$wpdb->update( $wpdb->terms, array( 'slug' => 'composite-b' ), array( 'term_id' => $existing_term_id ) );

				wp_insert_term( 'composite', 'product_type' );
			}

			add_option( 'woocommerce_composite_products_version', $this->version );

			// Update from previous versions

			// delete old option
			delete_option( 'woocommerce_composite_products_active' );

		} elseif ( version_compare( $version, $this->version, '<' ) ) {

			update_option( 'woocommerce_composite_products_version', $this->version );
		}

	}

	/**
	 * Deactivate extension.
	 *
	 * @return void
	 */
	public function deactivate() {

		delete_option( 'woocommerce_composite_products_version' );
	}
	
}

$GLOBALS[ 'woocommerce_composite_products' ] = new WC_Composite_Products();
