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
		add_action( 'woocommerce_single_product_summary', array( $this, 'wc_cp_add_to_cart' ) );

		// Single product add-to-cart button template for composite products
		add_action( 'woocommerce_composite_add_to_cart_button', array( $this, 'wc_cp_add_to_cart_button' ) );


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
	public function wc_cp_add_to_cart_button() {

		global $woocommerce_composite_products;

		wc_get_template( 'single-product/add-to-cart/composite.php', array(), false, $woocommerce_composite_products->plugin_path() . '/templates/' );
		
	}

	/**
	 * Add-to-cart template for composite products.
	 * @return void
	 */
	public function wc_cp_add_to_cart() {

		global $product, $woocommerce_composite_products;

		// Enqueue scripts
		wp_enqueue_script( 'wc-add-to-cart-composite' );

		// Enqueue styles
		wp_enqueue_style( 'wc-composite-single-css' );

		// Load NYP scripts
		if ( function_exists( 'WC_Name_Your_Price' ) ) {
			WC_Name_Your_Price()->display->nyp_scripts();
		}

		// Enqueue Bundle styles
		if ( class_exists( 'WC_Bundles' ) ) {
			wp_enqueue_style( 'wc-bundle-css' );
		}

		$components       = $product->get_composite_data();

		if ( ! empty( $components ) ) {
			wc_get_template( 'single-product/composite.php', array(
				'components'       => $components,
				'product'          => $product
			), '', $woocommerce_composite_products->plugin_path() . '/templates/' );
		}

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

		$dependencies = array( 'jquery', 'jquery-blockui' );

		if ( class_exists( 'WC_Bundles' ) )
			$dependencies[] = 'wc-add-to-cart-bundle';

		// Add any custom script dependencies here
		// Examples: custom product type scripts and component layered filter scripts
		$dependencies = apply_filters( 'woocommerce_composite_script_dependencies', $dependencies );

		wp_register_script( 'wc-add-to-cart-composite', $woocommerce_composite_products->plugin_url() . '/assets/js/add-to-cart-composite' . $suffix . '.js', $dependencies, $woocommerce_composite_products->version );

		wp_register_style( 'wc-composite-single-css', $woocommerce_composite_products->plugin_url() . '/assets/css/wc-composite-single.css', false, $woocommerce_composite_products->version, 'all' );

		wp_register_style( 'wc-composite-css', $woocommerce_composite_products->plugin_url() . '/assets/css/wc-composite-styles.css', false, $woocommerce_composite_products->version, 'all' );

		wp_enqueue_style( 'wc-composite-css' );

		$params = apply_filters( 'woocommerce_composite_front_end_params', array(
			'small_width_threshold'                  => 450,
			'full_width_threshold'                   => 450,
			'legacy_width_threshold'                 => 450,
			'i18n_free'                              => __( 'Free!', 'woocommerce' ),
			'i18n_total'                             => __( 'Total', 'woocommerce-composite-products' ) . ': ',
			'i18n_none'                              => __( 'None', 'woocommerce-composite-products' ),
			'i18n_select_an_option'                  => _x( 'Select an option&hellip;', 'select option dropdown text - optional component', 'woocommerce-composite-products' ),
			'i18n_previous_step'                     => __( 'Previous &ndash; %s', 'woocommerce-composite-products' ),
			'i18n_next_step'                         => __( 'Next &ndash; %s', 'woocommerce-composite-products' ),
			'i18n_final_step'                        => __( 'Review Configuration', 'woocommerce-composite-products' ),
			'i18n_reset_selection'                   => __( 'Reset selection', 'woocommerce-composite-products' ),
			'i18n_clear_selection'                   => __( 'Clear selection', 'woocommerce-composite-products' ),
			'i18n_select_options'                    => sprintf( __( '<p class="price"><span class="composite_error">%s</span></p>', 'woocommerce-composite-products' ), __( 'Please select %s options to update your total and continue&hellip;', 'woocommerce-composite-products' ) ),
			'i18n_select_options_and_sep'            => sprintf( __( '%1$s and &quot;%2$s&quot;', 'woocommerce-composite-products', 'name of last component pending selections' ), '%s', '%v' ),
			'i18n_select_options_comma_sep'          => sprintf( __( '%1$s, &quot;%2$s&quot;', 'woocommerce-composite-products', 'name of comma-appended component pending selections' ), '%s', '%v' ),
			'i18n_unavailable_text'                  => sprintf( __( '<p class="price"><span class="composite_error">%s</span></p>', 'woocommerce-composite-products' ), __( 'Sorry, this product cannot be purchased at the moment.', 'woocommerce-composite-products' ) ),
			'i18n_select_component_options'          => __( 'Select an option to continue&hellip;', 'woocommerce-composite-products' ),
			'i18n_summary_empty_component'           => __( 'Configure', 'woocommerce-composite-products' ),
			'i18n_summary_filled_component'          => __( 'Change', 'woocommerce-composite-products' ),
			'i18n_summary_static_component'          => __( 'View', 'woocommerce-composite-products' ),
			'i18n_insufficient_stock'                => __( 'Insufficient stock: %s', 'woocommerce-composite-products' ),
			'i18n_insufficient_item_stock_comma_sep' => sprintf( __( '%1$s, %2$s', 'woocommerce-composite-products', 'name of comma-appended out-of-stock product' ), '%s', '%v' ),
			'i18n_insufficient_item_stock'           => sprintf( __( '<span class="out-of-stock-component">%2$s</span> &ndash; <span class="out-of-stock-product">%1$s</span>', 'woocommerce-composite-products' ), '%s', '%v' ),
			'currency_symbol'                        => get_woocommerce_currency_symbol(),
			'currency_position'                      => esc_attr( stripslashes( get_option( 'woocommerce_currency_pos' ) ) ),
			'currency_format_num_decimals'           => absint( get_option( 'woocommerce_price_num_decimals' ) ),
			'currency_format_decimal_sep'            => esc_attr( stripslashes( get_option( 'woocommerce_price_decimal_sep' ) ) ),
			'currency_format_thousand_sep'           => esc_attr( stripslashes( get_option( 'woocommerce_price_thousand_sep' ) ) ),
			'currency_format_trim_zeros'             => false === apply_filters( 'woocommerce_price_trim_zeros', false ) ? 'no' : 'yes',
			'script_debug'                           => 'no',
			'show_product_nonce'                     => wp_create_nonce( 'wc_bto_show_product' ),
			'is_wc_version_gte_2_3'                  => WC_CP_Core_Compatibility::is_wc_version_gte_2_3() ? 'yes' : 'no',
			'show_quantity_buttons'                  => defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? 'yes' : 'no',
			'transition_type'                        => 'fade',
			'block_components'						 => true,
		) );
		
		global $post;
			
		if( $post instanceOf WP_Post ) {
			
			$product = new WC_Product($post);
			
			$params = array_merge($params, array(
				'sku' => $product->get_sku(),
				'weight' => $product->get_weight(),
			));
		
		}
		
		wp_localize_script( 'wc-add-to-cart-composite', 'wc_composite_params', $params );
		
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
