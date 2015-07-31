<?php
/*
* Plugin Name: WooCommerce Composite Products
* Plugin URI: http://www.woothemes.com/products/composite-products/
* Description: Create complex, configurable product kits and let your customers build their own, personalized versions.
* Version: 3.1.0
* Author: WooThemes
* Author URI: http://woothemes.com/
* Developer: SomewhereWarm
* Developer URI: http://somewherewarm.net/
*
* Text Domain: woocommerce-composite-products
* Domain Path: /languages/
*
* Requires at least: 3.8
* Tested up to: 4.2
*
* Copyright: © 2009-2015 WooThemes.
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Required functions
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}

// Plugin updates
woothemes_queue_update( plugin_basename( __FILE__ ), '0343e0115bbcb97ccd98442b8326a0af', '216836' );

// Check if WooCommerce is active
if ( ! is_woocommerce_active() ) {
	return;
}

/**
 * # Composite Products
 *
 * This extension implements dynamic bundling functionalities by utilizing a container product (the "composite" type) that triggers the addition of other products to the cart.
 * Composite Products consist of Components. Components are created by defining a set of Component Options. Any existing catalog product (simple, variable or bundle) can be selected as a Component Option.
 * A Composite Product can be added to the cart when all of its Components are configured.
 * The extension does its own validation to ensure that the selected "Composited Products" can be added to the cart.
 * Composited products are added on the woocommerce_add_to_cart hook after adding the main container item.
 * Using a main container item makes it possible to define pricing properties and/or physical properties that replace the pricing and/or physical properties of the bundled products. This is useful when the composite has a new static price and/or new shipping properties.
 * Depending on the chosen pricing / shipping mode, the container item OR the contained products are marked as virtual, or are assigned a zero price in the cart.
 * To avoid confusion with zero prices in the front end, the extension filters the displayed price strings, cart item meta and markup classes in order to give the impression of a grouping relationship between the container and its "contents".
 *
 * @class WC_Composite_Products
 * @author  SomewhereWarm
 * @version 3.1.0
 */

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
		add_filter( 'plugin_row_meta', array( $this, 'plugin_meta_links' ), 10 ,2 );
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

		// Class containing core compatibility functions and filters
		require_once( 'includes/class-wc-cp-core-compatibility.php' );

		// Functions for 2.X back-compat
		include_once( 'includes/wc-cp-functions.php' );

		// Composite widget
		include_once( 'includes/wc-cp-widget-functions.php' );

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

		// Stock manager
		require_once( 'includes/class-wc-cp-stock-manager.php' );

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

	/**
	 * Show row meta on the plugin screen.
	 *
	 * @param	mixed $links Plugin Row Meta
	 * @param	mixed $file  Plugin Base file
	 * @return	array
	 */
	public function plugin_meta_links( $links, $file ) {

		if ( $file == plugin_basename( __FILE__ ) ) {
			$links[] ='<a href="http://docs.woothemes.com/document/composite-products/">' . __( 'Docs', 'woocommerce-composite-products' ) . '</a>';
			$links[] = '<a href="http://support.woothemes.com/">' . __( 'Support', 'woocommerce-composite-products' ) . '</a>';
		}

		return $links;
	}
}

$GLOBALS[ 'woocommerce_composite_products' ] = new WC_Composite_Products();
