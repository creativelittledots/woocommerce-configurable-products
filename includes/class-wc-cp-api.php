<?php
/**
 * Composited Product API
 *
 * @class 	WC_CP_API
 * @version 3.1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_CP_API {

	// Composited product filter parameters set by 'add_composited_product_filters'.
	public $filter_params = array();

	// Include these once for slightly better performance.
	public $wc_option_calculate_taxes;
	public $wc_option_tax_display_shop;
	public $wc_option_prices_include_tax;

	public $wc_option_price_decimal_sep;
	public $wc_option_price_thousand_sep;
	public $wc_option_price_num_decimals;

	public function __construct() {

		global $woocommerce;

		$this->wc_option_calculate_taxes    = get_option( 'woocommerce_calc_taxes' );
		$this->wc_option_tax_display_shop   = get_option( 'woocommerce_tax_display_shop' );
		$this->wc_option_prices_include_tax = get_option( 'woocommerce_prices_include_tax' );

		$this->wc_option_price_decimal_sep  = wp_specialchars_decode( stripslashes( get_option( 'woocommerce_price_decimal_sep' ) ), ENT_QUOTES );
		$this->wc_option_price_thousand_sep = wp_specialchars_decode( stripslashes( get_option( 'woocommerce_price_thousand_sep' ) ), ENT_QUOTES );
		$this->wc_option_price_num_decimals = absint( get_option( 'woocommerce_price_num_decimals' ) );

		// Ajax product front-end handler
		add_action( 'wp_ajax_woocommerce_show_composited_product', array( $this, 'show_composited_product_ajax' ) );
		add_action( 'wp_ajax_nopriv_woocommerce_show_composited_product', array( $this, 'show_composited_product_ajax' ) );

		// Ajax component options front-end handler
		add_action( 'wp_ajax_woocommerce_show_component_options', array( $this, 'show_component_options_ajax' ) );
		add_action( 'wp_ajax_nopriv_woocommerce_show_component_options', array( $this, 'show_component_options_ajax' ) );
		
		add_filter( 'woocommerce_available_variation', array($this, 'add_data_to_available_variation'), 10, 3 ); // For showing prices before and after Tax
		add_filter( 'woocommerce_composite_price_data', array($this, 'add_data_to_price_data'), 10, 2); // For showing prices before and after Tax
		
	}
	
	/**
	 * Show composited product data in the front-end.
	 * Used on first product page load to display content for component defaults.
	 *
	 * @param  mixed                   $product_id
	 * @param  mixed                   $component_id
	 * @param  WC_Product_Composite    $container_id
	 * @return string
	 */
	public function show_composited_product( $product_ids, $component_id, $composite ) {
	
		global $woocommerce_composite_products;
		
		if ( $product_ids === '0' || $product_ids === '' || empty( $product_ids ) || !$product_ids ) {

			echo '<div class="component_data" data-component_set="true" data-price="0" data-regular_price="0" data-product_type="none" style="display:none;"></div>';
			
			return;

		}
		
		$component_data      = $composite->get_component_data( $component_id );
		
		$per_product_pricing = $composite->is_priced_per_product();
	
		$quantity_min        = $component_data[ 'quantity_min' ];
		$quantity_max        = $component_data[ 'quantity_max' ];
		
		$data = $products = $price_data[ 'price' ] = $price_data[ 'regular_price' ] = $price_data[ 'custom_data' ] = array();
		
		$output = '';
		
		$show_selection_ui = apply_filters('woocommerce_composite_extension_show_selection_ui', true, $component_data, $composite);
		
		$data = $products = $price_data[ 'price' ] = $price_data[ 'regular_price' ] = $price_data[ 'custom_data' ] = array();
		
		$output = '';
		
		ob_start();
		
		foreach($product_ids as $product_id) {
			
			if ( $product_id === '0' || $product_id === '' ) {

				$output .= '<div class="component_data" data-component_set="true" data-price="0" data-regular_price="0" data-product_type="none" style="display:none;"></div>';
				
				continue;
	
			}
			
			$product = $composite->get_composited_product( $component_id, $product_id )->get_product();		
			
			$products[] = $product;
				
			if ( ! $product || ! $product->is_purchasable() ) {
				
				$output .= sprintf( '<div class="component_data" data-component_set="false" data-price="0" data-regular_price="0" data-product_type="invalid-product">%s</div>', __( 'Sorry, this item cannot be purchased at the moment.', 'woocommerce-composite-products' ) );
				
				continue;
				
			}
	
			if ( $product->sold_individually == 'yes' ) {
	 			$quantity_max = 1;
	 			$quantity_min = min( $quantity_min, 1 );
	 		}
	
	 		$data[$product_id][ 'purchasable' ] = 'yes';
	 		$data[$product_id][ 'custom_data' ] = apply_filters( 'woocommerce_composited_product_custom_data', array(), $product, $component_id, $component_data, $composite );
	
			$discount = isset( $component_data[ 'discount' ] ) ? $component_data[ 'discount' ] : 0;
	
			$hide_product_title       = isset( $component_data[ 'hide_product_title' ] ) ? $component_data[ 'hide_product_title' ] : 'no';
			$hide_product_description = isset( $component_data[ 'hide_product_description' ] ) ? $component_data[ 'hide_product_description' ] : 'no';
			$hide_product_thumbnail   = isset( $component_data[ 'hide_product_thumbnail' ] ) ? $component_data[ 'hide_product_thumbnail' ] : 'no';
	
			$args = array(
				'per_product_pricing' => $per_product_pricing,
				'discount'            => $discount,
				'quantity_min'        => $quantity_min,
				'quantity_max'        => $quantity_max,
				'composite_id'        => $composite->id,
				'component_id'        => $component_id
			);
	
			$this->add_composited_product_filters( $args, $product );
	
			if ( $product->is_type( 'simple' ) ) {
	
				$data[$product_id][ 'product_type' ] = 'simple';
	
				$product_regular_price = $product->get_regular_price();
				$product_price         = $product->get_price();
	
				$price_data[ 'price' ][ $product_id ] = $data[ $product_id ][ 'price_data' ][ 'price' ] = $this->get_composited_product_price( $product, $product_price );
				$price_data[ 'regular_price' ][ $product_id ] = $data[ $product_id ][ 'price_data' ][ 'regular_price' ] = $this->get_composited_product_price( $product, $product_regular_price );
				
				$data[$product_id][ 'custom_data' ][ 'all_prices' ] = array();
				
				$data[$product_id][ 'custom_data' ][ 'all_prices' ][ 'price' ] = $this->get_composited_item_prices( $product, $product_price );
				$data[$product_id][ 'custom_data' ][ 'all_prices' ][ 'regular_price' ] = $this->get_composited_item_prices( $product, $product_regular_price );
	
				wc_get_template( 'composited-product/simple-product.php', array(
					'product'                  => $product,
					'data'                     => $data[$product_id],
					'composite_id'             => $composite->id,
					'component_id'             => $component_id,
					'quantity_min'             => $quantity_min,
					'quantity_max'             => $quantity_max,
					'per_product_pricing'      => $per_product_pricing,
					'hide_product_title'       => $hide_product_title,
					'hide_product_description' => $hide_product_description,
					'hide_product_thumbnail'   => $hide_product_thumbnail,
					'show_selection_ui'        => $show_selection_ui && $composite->is_component_static( $component_id ) === false,
					'composite_product'        => $composite
				), '', $woocommerce_composite_products->plugin_path() . '/templates/' );
	
			} elseif ( $product->is_type( 'variable' ) ) {
	
				$data[$product_id][ 'product_variations' ] = apply_filters('woocommerce_composite_products_extension_available_product_variations', $product->get_available_variations(), $product, $composite);
	
				$data[$product_id][ 'product_type' ] = 'variable';
	
				foreach ( $data[ $product_id ][ 'product_variations' ] as &$variation_data ) {
	
					$variation_data[ 'min_qty' ] = $quantity_min;
					$variation_data[ 'max_qty' ] = $quantity_max;
				}
				
				if(isset($_REQUEST[ 'wccp_variation_id' ][ $component_id ][ $product_id ])) {
					
					$variation_id = $_REQUEST[ 'wccp_variation_id' ][ $component_id ][ $product_id ];
					
					$variation = new WC_Product_Variation($variation_id);
					
					$variation_regular_price = $variation->get_regular_price();
					$variation_price         = $variation->get_price();
		
					$price_data[ 'price' ][ $product_id ] = $data[ $product_id ][ 'price_data' ][ 'price' ] = $this->get_composited_product_price( $variation, $variation_price );
					$price_data[ 'regular_price' ][ $product_id ] = $data[ $product_id ][ 'price_data' ][ 'regular_price' ] = $this->get_composited_product_price( $variation, $variation_regular_price );
					
					$data[$product_id][ 'custom_data' ][ 'all_prices' ] = array();
					
					$data[$product_id][ 'custom_data' ][ 'all_prices' ][ 'price' ] = $this->get_composited_item_prices( $variation, $variation_price );
					$data[$product_id][ 'custom_data' ][ 'all_prices' ][ 'regular_price' ] = $this->get_composited_item_prices( $variation, $variation_regular_price );
					
				}
	
				wc_get_template( 'composited-product/variable-product.php', array(
					'product'                  => $product,
					'data'                     => $data[$product_id],
					'composite_id'             => $composite->id,
					'component_id'             => $component_id,
					'quantity_min'             => $quantity_min,
					'quantity_max'             => $quantity_max,
					'hide_product_title'       => $hide_product_title,
					'hide_product_description' => $hide_product_description,
					'hide_product_thumbnail'   => $hide_product_thumbnail,
					'show_selection_ui'        => $show_selection_ui && $composite->is_component_static( $component_id ) === false,
					'composite_product'        => $composite
				), '', $woocommerce_composite_products->plugin_path() . '/templates/' );
	
			} else {
	
				// Support for custom product types
				do_action( 'woocommerce_composite_show_custom_product_type', $product, $component_id, $composite );
			}	
			
			$show_selection_ui = false;
			
		}
		
		if(count($products) > 1) {
			
			wc_get_template( 'composited-product/total-price.php', array(
				'total_price' => array_sum($price_data[ 'price' ]),
				'total_regular_price' => array_sum($price_data[ 'regular_price' ]),
				'product' => $product,
			), '', $woocommerce_composite_products->plugin_path() . '/templates/' );
			
		} elseif(!$products) {
			
			echo '<div class="component_data" data-component_set="true" data-price="0" data-regular_price="0" data-product_type="none" style="display:none;"></div>';
			
		}

		$this->remove_composited_product_filters();

		$output .= ob_get_clean();
		
		return $output;
		
	}

	/**
	 * Sets up a WP_Query wrapper object to fetch component options. The query is configured based on the data stored in the 'component_data' array.
	 * Note that the query parameters are filterable - @see WC_CP_Query for details.
	 *
	 * @param  array  $component_data
	 * @param  array  $query_args
	 * @return array
	 */
	public function get_component_options( $component_data, $query_args = array() ) {

		$query = new WC_CP_Query( $component_data, $query_args );

		return $query->get_component_options();
	}

	/**
	 * Display paged component options via ajax. Effective in 'thumbnails' mode only.
	 *
	 * @return void
	 */
	public function show_component_options_ajax() {
		
		global $woocommerce_composite_products;

		$data = array();

		header( 'Content-Type: application/json; charset=utf-8' );

		if ( ! check_ajax_referer( 'wc_bto_show_product', 'security', false ) ) {

			echo json_encode( array(
				'result'                  => 'failure',
				'component_scenario_data' => array(),
				'options_markup'          => __( 'Sorry, there are options to show at the moment. Please refresh the page and try again.', 'woocommerce-composite-products' )
			) );

			die();
		}

		if ( isset( $_POST[ 'selected_option' ] ) &&
			isset( $_POST[ 'load_page' ] ) && intval( $_POST[ 'load_page' ] ) > 0 &&
			isset( $_POST[ 'composite_id' ] ) && intval( $_POST[ 'composite_id' ] ) > 0 &&
			! empty( $_POST[ 'component_id' ] )
		) {

			$component_id    = intval( $_POST[ 'component_id' ] );
			$composite_id    = intval( $_POST[ 'composite_id' ] );
			$selected_option = ! empty( $_POST[ 'selected_option' ] ) ? intval( $_POST[ 'selected_option' ] ) : '';
			$load_page       = intval( $_POST[ 'load_page' ] );

		} else {

			echo json_encode( array(
				'result'                  => 'failure',
				'component_scenario_data' => array(),
				'options_markup'          => __( 'Looks like something went wrong. Please refresh the page and try again.', 'woocommerce-composite-products' )
			) );

			die();
		}

		$product = WC_CP_Core_Compatibility::wc_get_product( $composite_id );

		$query_args = array(
			'selected_option' => $selected_option,
			'load_page'       => $load_page,
		);

		// Include orderby argument if posted -- if not, the default ordering method will be used
		if ( ! empty( $_POST[ 'orderby' ] ) ) {
			$query_args[ 'orderby' ] = $_POST[ 'orderby' ];
		}

		// Include filters argument if posted -- if not, no filters will be applied to the query
		if ( ! empty( $_POST[ 'filters' ] ) ) {
			$query_args[ 'filters' ] = $_POST[ 'filters' ];
		}

		// Load Component Options
		$current_options = $product->get_current_component_options( $component_id, $query_args );

		ob_start();

		wc_get_template( 'single-product/component-options.php', array(
			'product'             => $product,
			'component_id'        => $component_id,
			'component_options'   => $current_options,
			'component_data'      => $product->get_component_data( $component_id ),
			'selected_option'     => $selected_option,
			'selection_mode'      => $product->get_composite_selections_style()
		), '', $woocommerce_composite_products->plugin_path() . '/templates/' );

		$component_options_markup = ob_get_clean();

		ob_start();

		wc_get_template( 'single-product/component-options-pagination.php', array(
			'product'             => $product,
			'component_id'        => $component_id,
		), '', $woocommerce_composite_products->plugin_path() . '/templates/' );

		$component_pagination_markup = ob_get_clean();

		// Calculate scenario data for the displayed component options, including the current selection
		if ( $selected_option && ! in_array( $selected_option, $current_options ) ) {
			$current_options[] = $selected_option;
		}

		$scenario_data = $product->get_current_component_scenarios( $component_id, $current_options );

		echo json_encode( array(
			'result'                  => 'success',
			'component_scenario_data' => $scenario_data[ 'scenario_data' ][ $component_id ],
			'options_markup'          => $component_options_markup,
			'pagination_markup'       => $component_pagination_markup,
		) );

		die();

	}

	/**
	 * Ajax listener that fetches product data when a new selection is made.
	 *
	 * @param  mixed    $product_id
	 * @param  mixed    $item_id
	 * @param  mixed    $container_id
	 * @return string
	 */
	public function show_composited_product_ajax( $product_id = '', $component_id = '', $composite_id = '' ) {
	
		global $woocommerce_composite_products;

		$data = array();

		header( 'Content-Type: application/json; charset=utf-8' );

		if ( ! check_ajax_referer( 'wc_bto_show_product', 'security', false ) ) {

			$data[ 'purchasable' ] = 'no';

			echo json_encode( array(
				'product_data' => $data,
				'markup'       => __( 'Sorry, this item cannot be purchased at the moment. Please refresh the page and try again.', 'woocommerce-composite-products' ),
			) );

			die();
		}

		if ( isset( $_POST[ 'product_id' ] ) && count( $_POST[ 'product_id' ] ) > 0 && isset( $_POST[ 'component_id' ] ) && ! empty( $_POST[ 'component_id' ] ) && isset( $_POST[ 'composite_id' ] ) && ! empty( $_POST[ 'composite_id' ] ) ) {
			
			$product_ids = array();
		
			foreach(is_array($_POST[ 'product_id' ]) ? $_POST[ 'product_id' ] : array($_POST[ 'product_id' ]) as $product_id) {
				$product_ids[]   = intval( $product_id );
			}
			
			$component_id = intval( $_POST[ 'component_id' ] );
			$composite_id = intval( $_POST[ 'composite_id' ] );

		} else {

			$data[ 'purchasable' ] = 'no';

			echo json_encode( array(
				'product_data' => $data,
				'markup'       => sprintf( '<div class="component_data" data-component_set="false" data-price="0" data-regular_price="0" data-product_type="invalid-data">%s</div>', __( 'Sorry, this item cannot be purchased at the moment.', 'woocommerce-composite-products' ) ),
			) );

			die();
		}
		
		$composite           = WC_CP_Core_Compatibility::wc_get_product( $composite_id );
		$component_data      = $composite->get_component_data( $component_id );
		
		$per_product_pricing = $composite->is_priced_per_product();
	
		$quantity_min        = $component_data[ 'quantity_min' ];
		$quantity_max        = $component_data[ 'quantity_max' ];
		
		$data = $products = $price_data[ 'price' ] = $price_data[ 'regular_price' ] = $price_data[ 'custom_data' ] = array();
		
		$output = '';
		
		$show_selection_ui = apply_filters('woocommerce_composite_extension_show_selection_ui', true, $component_data, $composite);
		
		ob_start();
		
		foreach($product_ids as $product_id) {
			
			if(!$product_id) continue;
			
			$product = WC_CP_Core_Compatibility::wc_get_product( $product_id );
			
			$products[] = $product;

			if ( ! $product || ! $product->is_purchasable() ) {
	
				$data[$product_id][ 'purchasable' ] = 'no';
	
				continue;
	
			} else {
	
				$data[$product_id][ 'purchasable' ] = 'yes';
			}
	
			if ( $product->sold_individually == 'yes' ) {
	 			$quantity_max = 1;
	 			$quantity_min = min( $quantity_min, 1 );
	 		}
	
	 		$data[$product_id][ 'custom_data' ] = apply_filters( 'woocommerce_composited_product_custom_data', array(), $product, $component_id, $component_data, $composite );
	
			$discount = isset( $component_data[ 'discount' ] ) ? $component_data[ 'discount' ] : 0;
	
			$hide_product_title       = isset( $component_data[ 'hide_product_title' ] ) ? $component_data[ 'hide_product_title' ] : 'no';
			$hide_product_description = isset( $component_data[ 'hide_product_description' ] ) ? $component_data[ 'hide_product_description' ] : 'no';
			$hide_product_thumbnail   = isset( $component_data[ 'hide_product_thumbnail' ] ) ? $component_data[ 'hide_product_thumbnail' ] : 'no';
	
			$args = array(
				'per_product_pricing' => $per_product_pricing,
				'discount'            => $discount,
				'quantity_min'        => $quantity_min,
				'quantity_max'        => $quantity_max,
				'composite_id'        => $composite_id,
				'component_id'        => $component_id
			);
	
			$this->add_composited_product_filters( $args, $product );
	
			if ( $product->is_type( 'simple' ) ) {
	
				$data[$product_id][ 'product_type' ] = 'simple';
	
				$product_regular_price = $product->get_regular_price();
				$product_price         = $product->get_price();
	
				$price_data[ 'price' ][ $product_id ] = $data[ $product_id ][ 'price_data' ][ 'price' ] = $this->get_composited_product_price( $product, $product_price );
				$price_data[ 'regular_price' ][ $product_id ] = $data[ $product_id ][ 'price_data' ][ 'regular_price' ] = $this->get_composited_product_price( $product, $product_regular_price );
				
				$data[$product_id][ 'custom_data' ][ 'all_prices' ] = array();
				
				$data[$product_id][ 'custom_data' ][ 'all_prices' ][ 'price' ] = $this->get_composited_item_prices( $product, $product_price );
				$data[$product_id][ 'custom_data' ][ 'all_prices' ][ 'regular_price' ] = $this->get_composited_item_prices( $product, $product_regular_price );
	
				wc_get_template( 'composited-product/simple-product.php', array(
					'product'                  => $product,
					'data'                     => $data[$product_id],
					'composite_id'             => $composite_id,
					'component_id'             => $component_id,
					'quantity_min'             => $quantity_min,
					'quantity_max'             => $quantity_max,
					'per_product_pricing'      => $per_product_pricing,
					'hide_product_title'       => $hide_product_title,
					'hide_product_description' => $hide_product_description,
					'hide_product_thumbnail'   => $hide_product_thumbnail,
					'show_selection_ui'        => $show_selection_ui,
					'composite_product'        => $composite
				), '', $woocommerce_composite_products->plugin_path() . '/templates/' );
	
			} elseif ( $product->is_type( 'variable' ) ) {
	
				$data[$product_id][ 'product_variations' ] = apply_filters('woocommerce_composite_products_extension_available_product_variations', $product->get_available_variations(), $product, $composite);
				$data[$product_id][ 'product_type' ] = 'variable';
	
				foreach ( $data[$product_id][ 'product_variations' ] as &$variation_data ) {
	
					$variation_data[ 'min_qty' ] = $quantity_min;
					$variation_data[ 'max_qty' ] = $quantity_max;
	
				}
				
				if(isset($_REQUEST[ 'wccp_variation_id' ][ $component_id ][ $product_id ])) {
					
					$variation_id = $_REQUEST[ 'wccp_variation_id' ][ $component_id ][ $product_id ];
					
					$variation = new WC_Product_Variation($variation_id);
					
					$variation_regular_price = $variation->get_regular_price();
					$variation_price         = $variation->get_price();
		
					$price_data[ 'price' ][ $product_id ] = $data[ $product_id ][ 'price_data' ][ 'price' ] = $this->get_composited_product_price( $variation, $variation_price );
					$price_data[ 'regular_price' ][ $product_id ] = $data[ $product_id ][ 'price_data' ][ 'regular_price' ] = $this->get_composited_product_price( $variation, $variation_regular_price );
					
					$data[$product_id][ 'custom_data' ][ 'all_prices' ] = array();
					
					$data[$product_id][ 'custom_data' ][ 'all_prices' ][ 'price' ] = $this->get_composited_item_prices( $variation, $variation_price );
					$data[$product_id][ 'custom_data' ][ 'all_prices' ][ 'regular_price' ] = $this->get_composited_item_prices( $variation, $variation_regular_price );
					
				}
	
				wc_get_template( 'composited-product/variable-product.php', array(
					'product'                  => $product,
					'data'                     => $data[$product_id],
					'composite_id'             => $composite_id,
					'component_id'             => $component_id,
					'quantity_min'             => $quantity_min,
					'quantity_max'             => $quantity_max,
					'hide_product_title'       => $hide_product_title,
					'hide_product_description' => $hide_product_description,
					'hide_product_thumbnail'   => $hide_product_thumbnail,
					'show_selection_ui'        => $show_selection_ui,
					'composite_product'        => $composite
				), '', $woocommerce_composite_products->plugin_path() . '/templates/' );
	
			} else {
	
				// Support for custom product types
				do_action( 'woocommerce_composite_show_custom_product_type', $product, $component_id, $composite );
			}
			
			$show_selection_ui = false;
			
		}
		
		$this->remove_composited_product_filters();
		
		if(count($products) > 1) {
			
			wc_get_template( 'composited-product/total-price.php', array(
				'total_price' => array_sum($price_data[ 'price' ]),
				'total_regular_price' => array_sum($price_data[ 'regular_price' ]),
				'product' => $product,
			), '', $woocommerce_composite_products->plugin_path() . '/templates/' );
			
		} elseif(!$products) {
			
			echo '<div class="component_data" data-component_set="true" data-price="0" data-regular_price="0" data-product_type="none" style="display:none;"></div>';
			
		}
		
		$output .= ob_get_clean();
		
		$data[ 'purchasable' ] = 'yes';
		
		echo json_encode( array(
			'markup'       => $output,
			'product_data' => $data
		) );

		die();
	}

	/**
	 * Filters variation data in the show_product function.
	 *
	 * @param  mixed                    $variation_data
	 * @param  WC_Product               $bundled_product
	 * @param  WC_Product_Variation     $bundled_variation
	 * @return mixed
	 */
	public function filter_available_variation( $variation_data, $product, $variation ) {

		if ( ! empty ( $this->filter_params ) ) {

			$variation_data[ 'regular_price' ]        = $this->get_composited_product_price( $variation, $variation->get_regular_price() );
			$variation_data[ 'price' ]                = $this->get_composited_product_price( $variation, $variation->get_price() );

			$variation_data[ 'price_html' ]           = $this->filter_params[ 'per_product_pricing' ] ? ( $variation_data[ 'price_html' ] === '' ? '<span class="price">' . $variation->get_price_html() . '</span>' : $variation_data[ 'price_html' ] ) : '';

			$availability = $this->get_composited_item_availability( $variation, $this->filter_params[ 'quantity_min' ] );

			$variation_data[ 'availability_html' ]    = empty( $availability['availability'] ) ? '' : apply_filters( 'woocommerce_stock_html', '<p class="stock ' . esc_attr( $availability['class'] ) . '">'. wp_kses_post( $availability['availability'] ).'</p>', wp_kses_post( $availability['availability'] ) );
			$variation_data[ 'is_sold_individually' ] = $variation_data[ 'is_sold_individually' ] == 'yes' && $this->filter_params[ 'quantity_min' ] == 1 ? 'yes' : 'no';
		}

		return $variation_data;
	}

	/**
	 * Price-related filters. Modify composited product prices to take into account component discounts.
	 *
	 * @param  array      $args
	 * @param  WC_Product $product
	 * @return void
	 */
	public function add_composited_product_filters( $args, $product = false ) {

		$defaults = array(
			'composite_id'        => '',
			'component_id'        => '',
			'discount'            => '',
			'per_product_pricing' => '',
			'quantity_min'        => '',
			'quantity_max'        => '',
		);

		$args = wp_parse_args( $args, $defaults );

		extract( $args );

		$this->filter_params[ 'composite_id' ]        = $composite_id;
		$this->filter_params[ 'component_id' ]        = $component_id;
		$this->filter_params[ 'discount' ]            = $discount;
		$this->filter_params[ 'per_product_pricing' ] = $per_product_pricing;
		$this->filter_params[ 'quantity_min' ]        = $quantity_min;
		$this->filter_params[ 'quantity_max' ]        = $quantity_max;
		$this->filter_params[ 'product' ]             = $product;

		add_filter( 'woocommerce_available_variation', array( $this, 'filter_available_variation' ), 10, 3 );
		add_filter( 'woocommerce_get_price', array( $this, 'filter_show_product_get_price' ), 16, 2 );
		add_filter( 'woocommerce_get_regular_price', array( $this, 'filter_show_product_get_regular_price' ), 16, 2 );
		add_filter( 'woocommerce_get_sale_price', array( $this, 'filter_show_product_get_sale_price' ), 16, 2 );
		add_filter( 'woocommerce_get_price_html', array( $this, 'filter_show_product_get_price_html' ), 5, 2 );
		add_filter( 'woocommerce_get_variation_price_html', array( $this, 'filter_show_product_get_price_html' ), 5, 2 );

		add_filter( 'woocommerce_bundles_update_price_meta', array( $this, 'filter_show_product_bundles_update_price_meta' ), 10, 2 );
		add_filter( 'woocommerce_bundle_is_composited', array( $this, 'filter_bundle_is_composited' ), 10, 2 );
		add_filter( 'woocommerce_bundle_is_priced_per_product', array( $this, 'filter_bundle_is_priced_per_product' ), 10, 2 );

		add_filter( 'woocommerce_nyp_html', array( $this, 'filter_show_product_get_nyp_price_html' ), 15, 2 );

		do_action( 'woocommerce_composite_products_add_product_filters', $args, $product );
	}

	/**
	 * Filter 'woocommerce_bundle_is_composited'.
	 *
	 * @param  boolean            $is
	 * @param  WC_Product_Bundle  $bundle
	 * @return boolean
	 */
	public function filter_bundle_is_composited( $is, $bundle ) {
		return true;
	}

	/**
	 * Components discounts should not trigger bundle price updates.
	 *
	 * @param  boolean            $is
	 * @param  WC_Product_Bundle  $bundle
	 * @return boolean
	 */
	public function filter_show_product_bundles_update_price_meta( $update, $bundle ) {
		return false;
	}

	/**
	 * Filter 'woocommerce_bundle_is_priced_per_product'. If a composite is not priced per product, this should force composited bundles to revert to static pricing, too, to force bundled items to return a zero price.
	 *
	 * @param  boolean            $is
	 * @param  WC_Product_Bundle  $bundle
	 * @return boolean
	 */
	public function filter_bundle_is_priced_per_product( $is_ppp, $bundle ) {

		if ( ! empty ( $this->filter_params ) ) {

			if ( ! $this->filter_params[ 'per_product_pricing' ] ) {
				return false;
			}
		}

		return $is_ppp;
	}

	/**
	 * Filters get_price_html to include component discounts.
	 *
	 * @param  string     $price_html
	 * @param  WC_Product $product
	 * @return string
	 */
	public function filter_show_product_get_price_html( $price_html, $product ) {

		if ( ! empty ( $this->filter_params ) ) {

			// Tells NYP to back off
			$product->is_filtered_price_html = 'yes';

			if ( ! $this->filter_params[ 'per_product_pricing' ] ) {

				$price_html = '';

			} else {

				$add_suffix = true;

				// Don't add /pc suffix to products in composited bundles (possibly duplicate)
				if ( isset( $this->filter_params[ 'product' ] ) ) {
					$filtered_product = $this->filter_params[ 'product' ];
					if ( $filtered_product->id != $product->id ) {
						$add_suffix = false;
					}
				}

				if ( $add_suffix ) {
					$suffix     = $this->filter_params[ 'quantity_min' ] > 1 && $product->sold_individually !== 'yes' ? ' ' . __( '/ pc.', 'woocommerce-composite-products' ) : '';
					$price_html = $price_html . $suffix;
				}
			}

			$price_html = apply_filters( 'woocommerce_composited_item_price_html', $price_html, $product, $this->filter_params[ 'component_id' ], $this->filter_params[ 'composite_id' ] );
		}

		return $price_html;
	}

	/**
	 * Filters get_price_html to hide nyp prices in static pricing mode.
	 *
	 * @param  string     $price_html
	 * @param  WC_Product $product
	 * @return string
	 */
	public function filter_show_product_get_nyp_price_html( $price_html, $product ) {

		if ( ! empty ( $this->filter_params ) ) {

			if ( ! $this->filter_params[ 'per_product_pricing' ] ) {

				$price_html = '';

			}
		}

		return $price_html;
	}

	/**
	 * Filters get_price to include component discounts.
	 *
	 * @param  double     $price
	 * @param  WC_Product $product
	 * @return string
	 */
	public function filter_show_product_get_price( $price, $product ) {

		if ( ! empty ( $this->filter_params ) ) {

			if ( $price === '' ) {
				return $price;
			}

			if ( ! $this->filter_params[ 'per_product_pricing' ] ) {
				return ( double ) 0;
			}

			if ( isset( $product->bundled_item_price ) ) {
				$regular_price = $product->bundled_item_price;
			} else {

				if ( apply_filters( 'woocommerce_composited_product_discount_from_regular', true, $this->filter_params[ 'component_id' ], $this->filter_params[ 'composite_id' ] ) ) {
					$regular_price = $product->get_regular_price();
				} else {
					$regular_price = $price;
				}
			}

			$discount = $this->filter_params[ 'discount' ];

			return empty( $discount ) ? $price : round( ( double ) $regular_price * ( 100 - $discount ) / 100, $this->wc_option_price_num_decimals );
		}

		return $price;
	}

	/**
	 * Filters get_regular_price to include component discounts.
	 *
	 * @param  double     $price
	 * @param  WC_Product $product
	 * @return string
	 */
	public function filter_show_product_get_regular_price( $price, $product ) {

		if ( ! empty ( $this->filter_params ) ) {

			if ( ! $this->filter_params[ 'per_product_pricing' ] ) {
				return ( double ) 0;
			}

			return empty( $product->regular_price ) ? $product->price : $price;
		}

		return $price;
	}

	/**
	 * Filters get_sale_price to include component discounts.
	 *
	 * @param  double     $price
	 * @param  WC_Product $product
	 * @return string
	 */
	public function filter_show_product_get_sale_price( $price, $product ) {

		if ( ! empty ( $this->filter_params ) ) {

			if ( ! $this->filter_params[ 'per_product_pricing' ] ) {
				return ( double ) 0;
			}

			$discount = $this->filter_params[ 'discount' ];

			return empty( $discount ) ? $price : $this->filter_show_product_get_price( $product->price, $product );
		}

		return $price;
	}

	/**
	 * Remove price filters. @see add_composited_product_filters.
	 *
	 * @return void
	 */
	public function remove_composited_product_filters() {

		do_action( 'woocommerce_composite_products_remove_product_filters', $this->filter_params );

		$this->filter_params = array();

		remove_filter( 'woocommerce_available_variation', array( $this, 'filter_available_variation' ), 10, 3 );
		remove_filter( 'woocommerce_get_price', array( $this, 'filter_show_product_get_price' ), 16, 2 );
		remove_filter( 'woocommerce_get_regular_price', array( $this, 'filter_show_product_get_regular_price' ), 16, 2 );
		remove_filter( 'woocommerce_get_sale_price', array( $this, 'filter_show_product_get_sale_price' ), 16, 2 );
		remove_filter( 'woocommerce_get_price_html', array( $this, 'filter_show_product_get_price_html' ), 5, 2 );
		remove_filter( 'woocommerce_get_variation_price_html', array( $this, 'filter_show_product_get_price_html' ), 5, 2 );

		remove_filter( 'woocommerce_nyp_html', array( $this, 'filter_show_product_get_nyp_price_html' ), 15, 2 );

		remove_filter( 'woocommerce_bundle_is_priced_per_product', array( $this, 'filter_bundle_is_priced_per_product' ), 10, 2 );
		remove_filter( 'woocommerce_bundle_is_composited', array( $this, 'filter_bundle_is_composited' ), 10, 2 );
		remove_filter( 'woocommerce_bundles_update_price_meta', array( $this, 'filter_show_product_bundles_update_price_meta' ), 10, 2 );
	}

	/**
	 * Returns an array that contains:
	 *
	 * 1. The shop price of a product depending on the 'woocommerce_tax_display_shop' setting.
	 * 2. The price incl tax.
	 * 3. The price excl tax.
	 *
	 * @param  WC_product $product
	 * @param  int        $price
	 * @return array
	 */
	public function get_composited_item_prices( $product, $price = '' ) {

		if ( $price === '' ) {
			$price = $product->price;
		}

		if ( $this->wc_option_calculate_taxes == 'yes' ) {

			if ( $this->wc_option_tax_display_shop == 'excl' ) {
				$product_price = $this->get_item_price_excluding_tax( $product, $price );
			} else {
				$product_price = $this->get_item_price_including_tax( $product, $price );
			}

			$product_price_excl_tax = $this->get_item_price_excluding_tax( $product, $price );
			$product_price_incl_tax = $this->get_item_price_including_tax( $product, $price );

		} else {

			$product_price = $product_price_excl_tax = $product_price_incl_tax = $price;
		}

		return array(
			'shop' => $product_price,
			'excl' => $product_price_excl_tax,
			'incl' => $product_price_incl_tax
		);
	}

	/**
	 * Get the shop price of a product incl or excl tax, depending on the 'woocommerce_tax_display_shop' setting.
	 *
	 * @param  WC_Product $product
	 * @param  double $price
	 * @return double
	 */
	public function get_composited_product_price( $product, $price = '' ) {

		if ( $price === '' ) {
			$price = $product->get_price();
		}

		if ( $this->wc_option_tax_display_shop === 'excl' ) {
			$product_price = $product->get_price_excluding_tax( 1, $price );
		} else {
			$product_price = $product->get_price_including_tax( 1, $price );
		}

		return $product_price;
	}

	/**
	 * Get the price of a product incl tax.
	 * Used instead of 'product->get_price_including_tax' for performance reasons.
	 *
	 * @param  int $product_id
	 * @param  double $price
	 * @return double
	 */
	public function get_item_price_including_tax( $product, $price ) {

		global $woocommerce;

		if ( $this->wc_option_calculate_taxes === 'yes' && $this->wc_option_prices_include_tax === 'no' ) {
			$price = $product->get_price_including_tax( 1, $price );
		}

		return $price;
	}

	/**
	 * Get the price of a product excl tax.
	 * Used instead of 'product->get_price_excluding_tax' for performance reasons.
	 *
	 * @param  int $product_id
	 * @param  double $price
	 * @return double
	 */
	public function get_item_price_excluding_tax( $product, $price ) {

		if ( $this->wc_option_calculate_taxes === 'yes' && $this->wc_option_prices_include_tax === 'yes' ) {
			$price = $product->get_price_excluding_tax( 1, $price );
		}

		return $price;
	}

	/**
	 * Used throughout the extension instead of 'wc_price'.
	 *
	 * @param  double $price
	 * @return string
	 */
	public function get_composited_item_price_string_price( $price, $args = array() ) {

		$return          = '';
		$num_decimals    = $this->wc_option_price_num_decimals;
		$currency        = isset( $args['currency'] ) ? $args['currency'] : '';
		$currency_symbol = get_woocommerce_currency_symbol( $currency );
		$decimal_sep     = $this->wc_option_price_decimal_sep;
		$thousands_sep   = $this->wc_option_price_thousand_sep;

		$price = apply_filters( 'raw_woocommerce_price', floatval( $price ) );
		$price = apply_filters( 'formatted_woocommerce_price', number_format( $price, $num_decimals, $decimal_sep, $thousands_sep ), $price, $num_decimals, $decimal_sep, $thousands_sep );

		if ( apply_filters( 'woocommerce_price_trim_zeros', false ) && $num_decimals > 0 ) {
			$price = wc_trim_zeros( $price );
		}

		$return = sprintf( get_woocommerce_price_format(), $currency_symbol, $price );

		return $return;
	}

	/**
	 * Composited product availability function that takes into account min quantity.
	 *
	 * @param  WC_Product $product
	 * @param  int $quantity
	 * @return array
	 */
	public function get_composited_item_availability( $product, $quantity ) {

		$availability = $class = '';

		if ( $product->managing_stock() ) {

			if ( $product->is_in_stock() && $product->get_total_stock() > get_option( 'woocommerce_notify_no_stock_amount' ) && $product->get_total_stock() >= $quantity ) {

				switch ( get_option( 'woocommerce_stock_format' ) ) {

					case 'no_amount' :
						$availability = __( 'In stock', 'woocommerce' );
					break;

					case 'low_amount' :
						if ( $product->get_total_stock() <= get_option( 'woocommerce_notify_low_stock_amount' ) ) {
							$availability = sprintf( __( 'Only %s left in stock', 'woocommerce' ), $product->get_total_stock() );

							if ( $product->backorders_allowed() && $product->backorders_require_notification() ) {
								$availability .= ' ' . __( '(can be backordered)', 'woocommerce' );
							}
						} else {
							$availability = __( 'In stock', 'woocommerce' );
						}
					break;

					default :
						$availability = sprintf( __( '%s in stock', 'woocommerce' ), $product->get_total_stock() );

						if ( $product->backorders_allowed() && $product->backorders_require_notification() ) {
							$availability .= ' ' . __( '(can be backordered)', 'woocommerce' );
						}
					break;
				}

				$class        = 'in-stock';

			} elseif ( $product->backorders_allowed() && $product->backorders_require_notification() ) {

				if ( $product->get_total_stock() >= $quantity || get_option( 'woocommerce_stock_format' ) == 'no_amount' || $product->get_total_stock() <= 0 ) {
					$availability = __( 'Available on backorder', 'woocommerce' );
				} else {
					$availability = __( 'Available on backorder', 'woocommerce' ) . ' ' . sprintf( __( '(only %s left in stock)', 'woocommerce-composite-products' ), $product->get_total_stock() );
				}

				$class        = 'available-on-backorder';

			} elseif ( $product->backorders_allowed() ) {

				$availability = __( 'In stock', 'woocommerce' );
				$class        = 'in-stock';

			} else {

				if ( $product->is_in_stock() && $product->get_total_stock() > get_option( 'woocommerce_notify_no_stock_amount' ) ) {

					if ( get_option( 'woocommerce_stock_format' ) == 'no_amount' ) {
						$availability = __( 'Insufficient stock', 'woocommerce-composite-products' );
					} else {
						$availability = __( 'Insufficient stock', 'woocommerce-composite-products' ) . ' ' . sprintf( __( '(only %s left in stock)', 'woocommerce-composite-products' ), $product->get_total_stock() );
					}

					$class        = 'out-of-stock';

				} else {

					$availability = __( 'Out of stock', 'woocommerce' );
					$class        = 'out-of-stock';
				}
			}

		} elseif ( ! $product->is_in_stock() ) {

			$availability = __( 'Out of stock', 'woocommerce' );
			$class        = 'out-of-stock';
		}

		return apply_filters( 'woocommerce_composited_product_availability', array( 'availability' => $availability, 'class' => $class ), $product );
	}

	/**
	 * Filter scenarios by action type.
	 *
	 * @param  array  $scenarios
	 * @param  string $type
	 * @param  array  $scenario_data
	 * @return array
	 */
	public function filter_scenarios_by_type( $scenarios, $type, $scenario_data ) {

		$filtered = array();

		if ( ! empty( $scenarios ) ) {
			foreach ( $scenarios as $scenario_id ) {

				if ( ! empty( $scenario_data [ 'scenario_settings' ][ 'scenario_actions' ][ $scenario_id ] ) ) {
					$actions = $scenario_data [ 'scenario_settings' ][ 'scenario_actions' ][ $scenario_id ];

					if ( is_array( $actions ) && in_array( $type, $actions ) ) {
						$filtered[] = $scenario_id;
					}
				}
			}
		}

		return $filtered;
	}

	/**
	 * Returns the following arrays:
	 *
	 * 1. $scenarios             - contains all scenario ids.
	 * 1. $scenario_settings     - includes scenario actions and masked components in scenarios.
	 * 2. $scenario_data         - maps every product/variation in a group to the scenarios where it is active.
	 * 3. $defaults_in_scenarios - the scenarios where all default component selections coexist.
	 *
	 * @param  array $bto_scenario_meta     scenarios meta
	 * @param  array $bto_data              component data - values may contain a 'current_component_options' key to generate scenarios for a subset of all component options
	 * @return array
	 */
	public function build_scenarios( $bto_scenario_meta, $bto_data ) {

		$scenarios          = empty( $bto_scenario_meta ) ? array() : array_map( 'strval', array_keys( $bto_scenario_meta ) );
		$common_scenarios   = $scenarios;
		$scenario_data      = array();
		$scenario_settings  = array();

		$compat_group_count = 0;

		// Store the 'actions' associated with every scenario
		foreach ( $scenarios as $scenario_id ) {

			$scenario_settings[ 'scenario_actions' ][ $scenario_id ] = array();

			if ( isset( $bto_scenario_meta[ $scenario_id ][ 'scenario_actions' ] ) ) {

				$actions = array();

				foreach ( $bto_scenario_meta[ $scenario_id ][ 'scenario_actions' ] as $action_name => $action_data ) {
					if ( isset( $action_data[ 'is_active' ] ) && $action_data[ 'is_active' ] === 'yes' ) {
						$actions[] = $action_name;

						if ( $action_name === 'compat_group' ) {
							$compat_group_count++;
						}
					}
				}

				$scenario_settings[ 'scenario_actions' ][ $scenario_id ] = $actions;

			} else {
				$scenario_settings[ 'scenario_actions' ][ $scenario_id ] = array( 'compat_group' );
				$compat_group_count++;
			}
		}

		$scenario_settings[ 'scenario_actions' ][ '0' ] = array( 'compat_group' );

		// Find which components in every scenario are 'non shaping components' (marked as unrelated)
		foreach ( $bto_scenario_meta as $scenario_id => $scenario_single_meta ) {

			$scenario_settings[ 'masked_components' ][ $scenario_id ] = array();

			foreach ( $bto_data as $group_id => $group_data ) {

				if ( isset( $scenario_single_meta[ 'modifier' ][ $group_id ] ) && $scenario_single_meta[ 'modifier' ][ $group_id ] === 'masked' ) {
					$scenario_settings[ 'masked_components' ][ $scenario_id ][] = ( string ) $group_id;
				}
			}
		}

		$scenario_settings[ 'masked_components' ][ '0' ] = array();

		// Include the '0' scenario for use when no 'compat_group' scenarios exist
		if ( $compat_group_count === 0 ) {
			$scenarios[] = '0';
		}

		// Map each product and variation to the scenarios that contain it
		foreach ( $bto_data as $group_id => $group_data ) {

			$scenario_data[ $group_id ] = array();

			// 'None' option
			if ( $group_data[ 'optional' ] === 'yes' ) {

				$scenarios_for_product = $this->get_scenarios_for_product( $bto_scenario_meta, $group_id, -1, '', 'none' );

				$scenario_data[ $group_id ][ 0 ] = $scenarios_for_product;
			}

			// Component options

			// When indicated, build scenarios only based on a limited set of component options
			if ( isset( $bto_data[ $group_id ][ 'current_component_options' ] ) ) {

				$component_options = $bto_data[ $group_id ][ 'current_component_options' ];

			// Otherwise run a query to get all component options
			} else {

				$component_options = $this->get_component_options( $group_data );
			}

			foreach ( $component_options as $product_id ) {

				if ( ! is_numeric( $product_id ) ) {
					continue;
				}

				// Get product type
				$terms        = get_the_terms( $product_id, 'product_type' );
				$product_type = ! empty( $terms ) && isset( current( $terms )->name ) ? sanitize_title( current( $terms )->name ) : 'simple';

				if ( $product_type === 'variable' ) {

					$variations = $this->get_product_variations( $product_id );

					if ( ! empty( $variations ) ) {

						$scenarios_for_product = array();

						foreach ( $variations as $variation_id ) {

							$scenarios_for_variation = $this->get_scenarios_for_product( $bto_scenario_meta, $group_id, $product_id, $variation_id, 'variation' );

							$scenarios_for_product   = array_merge( $scenarios_for_product, $scenarios_for_variation );

							$scenario_data[ $group_id ][ $variation_id ] = $scenarios_for_variation;
						}

						$scenario_data[ $group_id ][ $product_id ] = array_values( array_unique( $scenarios_for_product ) );
					}

				} else {

					$scenarios_for_product = $this->get_scenarios_for_product( $bto_scenario_meta, $group_id, $product_id, '', $product_type );

					$scenario_data[ $group_id ][ $product_id ] = $scenarios_for_product;
				}
			}

			if ( isset( $group_data[ 'default_id' ] ) && $group_data[ 'default_id' ] !== '' ) {

				if ( ! empty ( $scenario_data[ $group_id ][ $group_data[ 'default_id' ] ] ) ) {
					$common_scenarios = array_intersect( $common_scenarios, $scenario_data[ $group_id ][ $group_data[ 'default_id' ] ] );
				} else {
					$common_scenarios = array();
				}
			}
		}

		return array( 'scenarios' => $scenarios, 'scenario_settings' => $scenario_settings, 'scenario_data' => $scenario_data, 'defaults_in_scenarios' => $common_scenarios );
	}

	/**
	 * Returns an array of all scenarios where a particular component option (product/variation) is active.
	 *
	 * @param  array   $scenario_meta
	 * @param  string  $group_id
	 * @param  int     $product_id
	 * @param  int     $variation_id
	 * @param  string  $product_type
	 * @return array
	 */
	public function get_scenarios_for_product( $scenario_meta, $group_id, $product_id, $variation_id, $product_type ) {

		if ( empty( $scenario_meta ) ) {
			return array( '0' );
		}

		$scenarios = array();

		foreach ( $scenario_meta as $scenario_id => $scenario_data ) {

			if ( $this->product_active_in_scenario( $scenario_data, $group_id, $product_id, $variation_id, $product_type ) ) {
				$scenarios[] = ( string ) $scenario_id;
			}
		}

		// All products belong in the '0' scenario
		$scenarios[] = '0';

		return $scenarios;
	}

	/**
	 * Returns true if a product/variation id of a particular component is present in the scenario meta array. Also @see product_active_in_scenario function.
	 *
	 * @param  array   $scenario_data
	 * @param  string  $group_id
	 * @param  int     $product_id
	 * @return boolean
	 */
	public function scenario_contains_product( $scenario_data, $group_id, $product_id ) {

		if ( isset( $scenario_data[ 'component_data' ] ) && ! empty( $scenario_data[ 'component_data' ][ $group_id ] ) && is_array( $scenario_data[ 'component_data' ][ $group_id ] ) && in_array( $product_id, $scenario_data[ 'component_data' ][ $group_id ] ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Returns true if a product/variation id of a particular component is present in the scenario meta array. Uses 'scenario_contains_product' but also takes exclusion rules into account.
	 * When checking a variation, also makes sure that the parent product is also tested against the scenario meta array.
	 *
	 * @param  array   $scenario_data
	 * @param  string  $group_id
	 * @param  int     $product_id
	 * @param  int     $variation_id
	 * @param  string  $product_type
	 * @return boolean
	 */
	public function product_active_in_scenario( $scenario_data, $group_id, $product_id, $variation_id, $product_type ) {

		if ( empty( $scenario_data[ 'component_data' ] ) || empty( $scenario_data[ 'component_data' ][ $group_id ] ) ) {
			return true;
		}

		$id = ( $product_type === 'variation' ) ? $variation_id : $product_id;

		if ( $this->scenario_contains_product( $scenario_data, $group_id, 0 ) ) {
			return true;
		}

		$exclude = false;

		if ( isset( $scenario_data[ 'modifier' ][ $group_id ] ) && $scenario_data[ 'modifier' ][ $group_id ] === 'not-in' ) {
			$exclude = true;
		} elseif ( isset( $scenario_data[ 'exclude' ][ $group_id ] ) && $scenario_data[ 'exclude' ][ $group_id ] === 'yes' ) {
			$exclude = true;
		}

		$product_active_in_scenario = false;

		if ( $this->scenario_contains_product( $scenario_data, $group_id, $id ) ) {
			if ( ! $exclude ) {
				$product_active_in_scenario = true;
			} else {
				$product_active_in_scenario = false;
			}
		} else {
			if ( ! $exclude ) {

				if ( $product_type === 'variation' ) {

					if ( $this->scenario_contains_product( $scenario_data, $group_id, $product_id ) ) {
						$product_active_in_scenario = true;
					} else {
						$product_active_in_scenario = false;
					}

				} else {
					$product_active_in_scenario = false;
				}

			} else {

				if ( $product_type === 'variation' ) {

					if ( $this->scenario_contains_product( $scenario_data, $group_id, $product_id ) ) {
						$product_active_in_scenario = false;
					} else {
						$product_active_in_scenario = true;
					}

				} else {
					$product_active_in_scenario = true;
				}
			}
		}

		return $product_active_in_scenario;
	}

	/**
	 * Loads variation ids for a given variable product.
	 *
	 * @param  int    $item_id
	 * @return array
	 */
	public function get_product_variations( $item_id ) {

		$transient_name = 'wc_product_children_ids_' . $item_id;

        if ( false === ( $variations = get_transient( $transient_name ) ) ) {

			$args = array(
				'post_type'   => 'product_variation',
				'post_status' => array( 'publish' ),
				'numberposts' => -1,
				'orderby'     => 'menu_order',
				'order'       => 'asc',
				'post_parent' => $item_id,
				'fields'      => 'ids'
			);

			$variations = get_posts( $args );
		}

		return $variations;

	}

	/**
	 * Loads variation descriptions and ids for a given variable product.
	 *
	 * @param  int $item_id    product id
	 * @return array           array that contains variation ids => descriptions
	 */
	public function get_product_variation_descriptions( $item_id ) {

		$variation_descriptions = array();

		$variations = $this->get_product_variations( $item_id );

		if ( empty( $variations ) ) {
			return $variation_descriptions;
		}

		foreach ( $variations as $variation_id ) {

			$variation_description = $this->get_product_variation_title( $variation_id );

			if ( ! $variation_description ) {
				continue;
			}

			$variation_descriptions[ $variation_id ] = $variation_description;
		}

		return $variation_descriptions;
	}

	/**
	 * Return a formatted product title based on variation id.
	 *
	 * @param  int    $item_id
	 * @return string
	 */
	public function get_product_variation_title( $variation_id ) {

		if ( is_object( $variation_id ) ) {
			$variation = $variation_id;
		} else {
			$variation = WC_CP_Core_Compatibility::wc_get_product( $variation_id );
		}

		if ( ! $variation )
			return false;

		$description = wc_get_formatted_variation( $variation->get_variation_attributes(), true );

		$title = $variation->get_title();
		$sku   = $variation->get_sku();

		if ( $sku ) {
			$identifier = $sku;
		} else {
			$identifier = '#' . $variation->variation_id;
		}

		return $this->format_product_title( $title, $identifier, $description );
	}

	/**
	 * Return a formatted product title based on id.
	 *
	 * @param  int    $product_id
	 * @return string
	 */
	public function get_product_title( $product_id ) {

		if ( is_object( $product_id ) ) {
			$title = $product_id->get_title();
			$sku   = $product_id->get_sku();
			$id    = $product_id->id;
		} else {
			$title = get_the_title( $product_id );
			$sku   = get_post_meta( $product_id, '_sku', true );
			$id    = $product_id;
		}

		if ( ! $title ) {
			return false;
		}

		if ( $sku ) {
			$identifier = $sku;
		} else {
			$identifier = '#' . $id;
		}

		return $this->format_product_title( $title, $identifier );
	}

	/**
	 * Format a product title.
	 *
	 * @param  string $title
	 * @param  string $identifier
	 * @param  string $meta
	 * @return string
	 */
	public function format_product_title( $title, $identifier = '', $meta = '' ) {

		if ( $identifier && $meta ) {
			$title = sprintf( _x( '%1$s &ndash; %2$s &mdash; %3$s', 'product title followed by sku and meta', 'woocommerce-composite-products' ), $identifier, $title, $meta );
		} elseif ( $identifier ) {
			$title = sprintf( _x( '%1$s &ndash; %2$s', 'product title followed by sku', 'woocommerce-composite-products' ), $identifier, $title );
		} elseif ( $meta ) {
			$title = sprintf( _x( '%1$s &mdash; %2$s', 'product title followed by meta', 'woocommerce-composite-products' ), $title, $meta );
		}

		return $title;
	}

	/**
	 * Get composite layout tooltips.
	 * @param  string
	 * @return string
	 */
	public function get_layout_tooltip( $layout_id ) {

		$tooltips = array(
			'single'              => __( 'Components are presented in a stacked, <strong>single-page</strong> layout, with the add-to-cart button located at the bottom. Component Options can be selected in any sequence.', 'woocommerce-composite-products' ),
			'progressive'         => __( 'Similar to the Stacked layout, however, Components must be configured in sequence and can be toggled open/closed.', 'woocommerce-composite-products' ),
			'paged'               => __( 'In this <strong>multi-page</strong> layout, Components are presented as individual steps in the configuration process. Selections are summarized in a final Review step, at which point the Composite can be added to the cart. The Stepped layout allows you to use the <strong>Composite Products Summary Widget</strong> to constantly show a mini version of the Summary on your sidebar.', 'woocommerce-composite-products' ),
			'paged-componentized' => __( 'A <strong>multi-page</strong> layout that begins with a configuration Summary of all Components. The Summary is temporarily hidden from view while inspecting or configuring a Component. The Composite can be added to the cart by returning to the Summary.', 'woocommerce-composite-products' ),
		);

		if ( ! isset( $tooltips[ $layout_id ] ) ) {
			return '';
		}

		$tooltip = '<a href="#" class="help_tip tips" data-tip="' . $tooltips[ $layout_id ] . '" >[?]</a>';

		return $tooltip;
	}

	/**
	 * Get selected layout option.
	 * @param  string $layout
	 * @return string
	 */
	public function get_selected_layout_option( $layout ) {

		if ( ! $layout ) {
			return 'single';
		}

		$layouts         = $this->get_layout_options();
		$layout_id_parts = explode( '-', $layout, 2 );

		if ( array_key_exists( $layout, $layouts ) ) {
			return $layout;
		} elseif ( array_key_exists( $layout_id_parts[0], $layouts ) ) {
			return $layout_id_parts[0];
		}

		return 'single';
	}
	
	public function add_data_to_price_data($price_data, $product) {
			
		$base_price_data = $this->get_composited_item_prices($product, $product->get_base_price());
		$base_regular_price_data = $this->get_composited_item_prices($product, $product->get_base_regular_price());
		
		$price_data[ 'base_price_incl_tax' ] = $base_price_data['incl'] ? $base_price_data['incl'] : 0;
		$price_data[ 'base_price_excl_tax' ] = $base_price_data['excl'] ? $base_price_data['excl'] : 0;
		
		$price_data[ 'base_regular_price_incl_tax' ] = $base_regular_price_data['incl'] ? $base_regular_price_data['incl'] : 0;
		$price_data[ 'base_regular_price_excl_tax' ] = $base_regular_price_data['excl'] ? $base_regular_price_data['excl'] : 0;
		
		return $price_data;
		
	}
	
	public function add_data_to_available_variation($available_variation, $product, $variation) {
		
		$available_variation['all_prices'] = array();
		$available_variation['all_prices']['price'] = $this->get_composited_item_prices( $variation, $variation->get_price() );
		$available_variation['all_prices']['regular_price'] = $this->get_composited_item_prices( $variation, $variation->get_regular_price() );
		
		$available_variation['product_id'] = $product->id;
		
		return $available_variation;
		
	}
		
}
