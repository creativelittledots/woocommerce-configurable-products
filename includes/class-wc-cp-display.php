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

		// Wishlists
		add_action( 'woocommerce_wishlist_after_list_item_name', array( $this, 'wishlist_after_list_item_name' ), 10, 2 );

		// Fix microdata price in per product pricing mode
		add_action( 'woocommerce_single_product_summary', array( $this, 'showing_microdata' ), 9 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'showing_microdata_end' ), 11 );

		// Price filter results
		add_filter( 'woocommerce_price_filter_meta_keys', array( $this, 'price_filter_meta_keys' ) );
		add_filter( 'woocommerce_price_filter_results', array( $this, 'price_filter_results' ), 10, 3 );

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
			'base_price' => 52,
			'components' => $product->get_composite_data(),
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

	/**
	 * Inserts bundle contents after main wishlist bundle item is displayed.
	 *
	 * @param  array    $item       Wishlist item
	 * @param  array    $wishlist   Wishlist
	 * @return void
	 */
	public function wishlist_after_list_item_name( $item, $wishlist ) {

		global $woocommerce_composite_products;

		if ( ! empty( $item[ 'composite_data' ] ) ) {
			echo '<dl>';
			foreach ( $item[ 'composite_data' ] as $composited_item => $composited_item_data ) {

				echo '<dt class="component_title_meta wishlist_component_title_meta">' . $composited_item_data[ 'title' ] . ':</dt>';
				echo '<dd class="component_option_meta wishlist_component_option_meta">' . get_the_title( $composited_item_data[ 'product_id' ] ) . ' <strong class="component_quantity_meta wishlist_component_quantity_meta product-quantity">&times; ' . $composited_item_data[ 'quantity' ] . '</strong></dd>';

				if ( ! empty ( $composited_item_data[ 'attributes' ] ) ) {

					$attributes = '';

					foreach ( $composited_item_data[ 'attributes' ] as $attribute_name => $attribute_value ) {

						$taxonomy = wc_attribute_taxonomy_name( str_replace( 'attribute_pa_', '', urldecode( $attribute_name ) ) );

						// If this is a term slug, get the term's nice name
			            if ( taxonomy_exists( $taxonomy ) ) {

			            	$term = get_term_by( 'slug', $attribute_value, $taxonomy );

			            	if ( ! is_wp_error( $term ) && $term && $term->name ) {
			            		$attribute_value = $term->name;
			            	}

			            	$label = wc_attribute_label( $taxonomy );

			            // If this is a custom option slug, get the options name
			            } else {

							$attribute_value    = apply_filters( 'woocommerce_variation_option_name', $attribute_value );
							$composited_product = WC_CP_Core_Compatibility::wc_get_product( $composited_item_data[ 'product_id' ] );
							$product_attributes = $composited_product->get_attributes();

							if ( isset( $product_attributes[ str_replace( 'attribute_', '', $attribute_name ) ] ) ) {
								$label = wc_attribute_label( $product_attributes[ str_replace( 'attribute_', '', $attribute_name ) ][ 'name' ] );
							} else {
								$label = $attribute_name;
							}
						}

						$attributes = $attributes . $label . ': ' . $attribute_value . ', ';
					}
					echo '<dd class="component_attribute_meta wishlist_component_attribute_meta">' . rtrim( $attributes, ', ' ) . '</dd>';
				}
			}
			echo '</dl>';
			echo '<p class="component_notice wishlist_component_notice">' . __( '*', 'woocommerce-composite-products' ) . '&nbsp;&nbsp;<em>' . __( 'Accurate pricing info available in cart.', 'woocommerce-composite-products' ) . '</em></p>';
		}
	}

	/**
	 * Modify microdata get_price call.
	 *
	 * @return void
	 */
	public function showing_microdata() {

		global $product;

		if ( $product->is_type( 'composite' ) ) {

			if ( ! $product->is_synced() )
				$product->sync_composite();

			add_filter( 'woocommerce_composite_get_price', array( $this, 'get_microdata_composite_price' ), 10, 2 );
		}
	}

	/**
	 * Modify microdata get_price call.
	 *
	 * @return void
	 */
	public function showing_microdata_end() {

		remove_filter( 'woocommerce_composite_get_price', array( $this, 'get_microdata_composite_price' ), 10, 2 );
	}

	/**
	 * Modify microdata get_price call.
	 *
	 * @return void
	 */
	public function get_microdata_composite_price( $price, $composite ) {

		return $composite->min_price;
	}

	/**
	 * Filter price filter widget range.
	 *
	 * @param  array  $price_keys
	 * @return array
	 */
	public function price_filter_meta_keys( $price_keys ) {

		$composite_price_keys = array( '_min_composite_price', '_max_composite_price' );

		return array_merge( $price_keys, $composite_price_keys );
	}

	/**
	 * Modify price filter widget results to include Composite Products.
	 *
	 * @param  mixed $results
	 * @param  float $min
	 * @param  float $max
	 * @return mixed
	 */
	public function price_filter_results( $results, $min, $max ) {

		global $wpdb;

		// Clean out composites
		$args = array(
			'post_type' => 'product',
			'tax_query' => array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'name',
					'terms'    => 'composite'
					)
			),
			'fields'    => 'ids'
		);

		$composite_ids 	= get_posts( $args );

		$clean_results 	= array();

		if ( ! empty ( $composite_ids ) ) {

			foreach ( $results as $key => $result ) {

				if ( $result->post_type == 'product' && in_array( $result->ID, $composite_ids ) )
					continue;

				$clean_results[ $key ] = $result;
			}
		} else {

			$clean_results = $results;
		}

		$composite_results = array();

		$composite_results = $wpdb->get_results( $wpdb->prepare( "
        	SELECT DISTINCT ID, post_parent, post_type FROM $wpdb->posts
			INNER JOIN $wpdb->postmeta meta_1 ON ID = meta_1.post_id
			INNER JOIN $wpdb->postmeta meta_2 ON ID = meta_2.post_id
			WHERE post_type IN ( 'product' )
				AND post_status = 'publish'
				AND meta_1.meta_key = '_max_composite_price' AND ( meta_1.meta_value >= %d OR meta_1.meta_value = '' )
				AND meta_2.meta_key = '_min_composite_price' AND meta_2.meta_value <= %d AND meta_2.meta_value != ''
		", $min, $max ), OBJECT_K );

		$merged_results = $clean_results + $composite_results;

		return $merged_results;
	}
	
}
