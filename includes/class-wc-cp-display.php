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
		add_action( 'woocommerce_composite_add_to_cart', array( $this, 'wc_cp_add_to_cart' ) );

		// Single product add-to-cart button template for composite products
		add_action( 'woocommerce_composite_add_to_cart_button', array( $this, 'wc_cp_add_to_cart_button' ) );

		// Basic single/multi page layout elements
		add_action( 'woocommerce_composite_before_components', array( $this, 'wc_cp_add_paged_mode_show_component_scroll_target' ), 10, 2 );
		add_action( 'woocommerce_composite_before_components', array( $this, 'wc_cp_add_paged_mode_pagination' ), 15, 2 );

		// Note:
		// If 'wc_cp_add_paged_mode_cart' is moved to a later priority, the add-to-cart and summary section will no longer be part of the step-based process
		// In this case, use 'wc_cp_add_paged_mode_final_step_scroll_target' to define the auto-scroll target after clicking on the "Next" button of the final component

		// add_action( 'woocommerce_composite_after_components', array( $this, 'wc_cp_add_paged_mode_final_step_scroll_target' ), 9, 2 );
		add_action( 'woocommerce_composite_after_components', array( $this, 'wc_cp_add_single_mode_cart' ), 10, 2 );
		add_action( 'woocommerce_composite_after_components', array( $this, 'wc_cp_add_paged_mode_cart' ), 10, 2 );
		add_action( 'woocommerce_composite_after_components', array( $this, 'wc_cp_add_navigation' ), 15, 2 );
		add_action( 'woocommerce_composite_after_components', array( $this, 'wc_cp_add_paged_mode_select_component_option_scroll_target' ), 20, 2 );

		// Single page layout elements

		// Component options: Sorting and filtering
		add_action( 'woocommerce_composite_component_selections_single', array( $this, 'wc_cp_add_sorting' ), 10, 2 );
		add_action( 'woocommerce_composite_component_selections_single', array( $this, 'wc_cp_add_filtering' ), 20, 2 );
		// Component options: Dropdowns / Product thumbnails
		add_action( 'woocommerce_composite_component_selections_single', array( $this, 'wc_cp_add_component_options' ), 25, 2 );
		// Component options: Pagination
		add_action( 'woocommerce_composite_component_selections_single', array( $this, 'wc_cp_add_component_options_pagination' ), 26, 2 );
		// Component options: Current selection in single-page mode
		add_action( 'woocommerce_composite_component_selections_single', array( $this, 'wc_cp_add_current_selection_details' ), 30, 2 );

		// Progressive page layout elements

		// Component options: Current selections block wrapper in progressive mode -- start
		add_action( 'woocommerce_composite_component_selections_progressive', array( $this, 'wc_cp_add_progressive_mode_block_wrapper_start' ), 5, 2 );
		// Component options: Sorting and filtering
		add_action( 'woocommerce_composite_component_selections_progressive', array( $this, 'wc_cp_add_sorting' ), 10, 2 );
		add_action( 'woocommerce_composite_component_selections_progressive', array( $this, 'wc_cp_add_filtering' ), 20, 2 );
		// Component options: Dropdowns / Product thumbnails
		add_action( 'woocommerce_composite_component_selections_progressive', array( $this, 'wc_cp_add_component_options' ), 25, 2 );
		// Component options: Pagination
		add_action( 'woocommerce_composite_component_selections_progressive', array( $this, 'wc_cp_add_component_options_pagination' ), 26, 2 );
		// Component options: Current selections block wrapper in progressive mode -- end
		add_action( 'woocommerce_composite_component_selections_progressive', array( $this, 'wc_cp_add_progressive_mode_block_wrapper_end' ), 29, 2 );
		// Component options: Current selection in single-page mode
		add_action( 'woocommerce_composite_component_selections_progressive', array( $this, 'wc_cp_add_current_selection_details' ), 35, 2 );

		// Multi page layout elements

		// Component options: Sorting and filtering
		add_action( 'woocommerce_composite_component_selections_multi', array( $this, 'wc_cp_add_sorting' ), 15, 2 );
		add_action( 'woocommerce_composite_component_selections_multi', array( $this, 'wc_cp_add_filtering' ), 20, 2 );
		// Component options: Dropdowns / Product thumbnails
		add_action( 'woocommerce_composite_component_selections_multi', array( $this, 'wc_cp_add_component_options' ), 25, 2 );
		// Component options: Pagination
		add_action( 'woocommerce_composite_component_selections_multi', array( $this, 'wc_cp_add_component_options_pagination' ), 26, 2 );
		// Component options: Current selection in paged mode
		add_action( 'woocommerce_composite_component_selections_multi', array( $this, 'wc_cp_add_current_selection_details' ), 30, 2 );

		// Summary added inside the composite-add-to-cart.php template
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'wc_cp_before_add_to_cart_button' ), 5 );


		/* ------------------------------- */
		/* Other display-related hooks
		/* ------------------------------- */

		// Filter add_to_cart_url and add_to_cart_text when product type is 'composite'
		add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'wc_cp_loop_add_to_cart_link' ), 10, 2 );

		// Change the tr class attributes when displaying bundled items in templates
		add_filter( 'woocommerce_cart_item_class', array( $this, 'wc_cp_table_item_class' ), 10, 2 );
		add_filter( 'woocommerce_order_item_class', array( $this, 'wc_cp_table_item_class' ), 10, 2 );

		// Add preamble info to composited products
		add_filter( 'woocommerce_cart_item_name', array( $this, 'wc_cp_in_cart_component_title' ), 10, 3 );
		add_filter( 'woocommerce_checkout_cart_item_quantity', array( $this, 'wc_cp_cart_item_component_quantity' ), 10, 3 );

		add_filter( 'woocommerce_order_item_name', array( $this, 'wc_cp_order_table_component_title' ), 10, 2 );
		add_filter( 'woocommerce_order_item_quantity_html', array( $this, 'wc_cp_order_table_component_quantity' ), 10, 2 );

		// Filter cart item count
		add_filter( 'woocommerce_cart_contents_count',  array( $this, 'wc_cp_cart_contents_count' ) );

		// Filter cart widget items
		add_filter( 'woocommerce_before_mini_cart', array( $this, 'wc_cp_add_cart_widget_filters' ) );
		add_filter( 'woocommerce_after_mini_cart', array( $this, 'wc_cp_remove_cart_widget_filters' ) );

		// Wishlists
		add_filter( 'woocommerce_wishlist_list_item_price', array( $this, 'wishlist_list_item_price' ), 10, 3 );
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

		// Indent composited items in emails
		add_action( 'woocommerce_email_styles', array( $this, 'wc_cp_email_styles' ) );
	}

	/* ---------------------------------------------------------------------- */
	/* Composite products single product template hooks - Component options
	/* ---------------------------------------------------------------------- */

	/**
	 * In progressive mode, wrap component options & sorting/filtering controls in a blockable div.
	 *
	 * @param  string               $component_id
	 * @param  WC_Product_Composite $product
	 * @return void
	 */
	public function wc_cp_add_progressive_mode_block_wrapper_end( $component_id, $product ) {

		?></div><?php
	}

	/**
	 * In progressive mode, wrap component options & sorting/filtering controls in a blockable div.
	 *
	 * @param  string               $component_id
	 * @param  WC_Product_Composite $product
	 * @return void
	 */
	public function wc_cp_add_progressive_mode_block_wrapper_start( $component_id, $product ) {

		?><div class="component_selections_inner">
			<div class="block_component_selections_inner"></div><?php
	}

	/**
	 * Show current selection details.
	 *
	 * @param  string               $component_id
	 * @param  WC_Product_Composite $product
	 * @return void
	 */
	public function wc_cp_add_current_selection_details( $component_id, $product ) {

		global $woocommerce_composite_products;

		// Default Component Option
		$selected_option = $product->get_component_default_option( $component_id );

		?><div class="component_content" data-product_id="<?php echo $component_id; ?>">
			<div class="component_summary cp_clearfix">
				<div class="product content"><?php
					echo $woocommerce_composite_products->api->show_composited_product( $selected_option, $component_id, $product );
				?></div>
			</div>
		</div><?php
	}

	/**
	 * Show component options pagination.
	 *
	 * @param  string               $component_id
	 * @param  WC_Product_Composite $product
	 * @return void
	 */
	public function wc_cp_add_component_options_pagination( $component_id, $product ) {

		global $woocommerce_composite_products;

		// Component Options Pagination template
		wc_get_template( 'single-product/component-options-pagination.php', array(
			'product'      => $product,
			'component_id' => $component_id,
		), '', $woocommerce_composite_products->plugin_path() . '/templates/' );
	}

	/**
	 * Show component options.
	 *
	 * @param  string               $component_id
	 * @param  WC_Product_Composite $product
	 * @return void
	 */
	public function wc_cp_add_component_options( $component_id, $product ) {

		global $woocommerce_composite_products;

		// Default Component Option
		$selected_option = $product->get_component_default_option( $component_id );

		// Component Options template
		wc_get_template( 'single-product/component-options.php', array(
			'product'           => $product,
			'component_id'      => $component_id,
			'component_options' => $product->get_current_component_options( $component_id ),
			'component_data'    => $product->get_component_data( $component_id ),
			'selected_option'   => $selected_option,
		), '', $woocommerce_composite_products->plugin_path() . '/templates/' );
	}

	/**
	 * Add sorting input.
	 *
	 * @param  string               $component_id
	 * @param  WC_Product_Composite $product
	 * @return void
	 */
	public function wc_cp_add_sorting( $component_id, $product ) {

		global $woocommerce_composite_products;

		// Component Options sorting template
		wc_get_template( 'single-product/component-options-orderby.php', array(
			'product'      => $product,
			'component_id' => $component_id,
		), '', $woocommerce_composite_products->plugin_path() . '/templates/' );

	}

	/**
	 * Add attribute filters.
	 *
	 * @param  string               $component_id
	 * @param  WC_Product_Composite $product
	 * @return void
	 */
	public function wc_cp_add_filtering( $component_id, $product ) {

		global $woocommerce_composite_products;

		// Component Options filtering template
		wc_get_template( 'single-product/component-options-filters.php', array(
			'product'      => $product,
			'component_id' => $component_id,
		), '', $woocommerce_composite_products->plugin_path() . '/templates/' );

	}

	/**
	 * Adds component pagination in paged mode.
	 *
	 * @param  string               $component_id
	 * @param  WC_Product_Composite $product
	 * @return void
	 */
	public function wc_cp_add_paged_mode_pagination( $components, $product ) {

		global $woocommerce_composite_products;

		$navigation_style = $product->get_composite_layout_style();
		$layout_variation = $product->get_composite_layout_style_variation();

		if ( $navigation_style === 'paged' && $layout_variation !== 'componentized' ) {

			wc_get_template( 'single-product/composite-pagination.php', array(
				'product'          => $product,
				'components'       => $components,
			), '', $woocommerce_composite_products->plugin_path() . '/templates/' );

		}
	}

	/**
	 * When changing between components in paged mode, the viewport will scroll to this div if it's not visible.
	 * Adding the 'scroll_bottom' class to the element will scroll the bottom of the viewport to the target.
	 *
	 * @param  string               $component_id
	 * @param  WC_Product_Composite $product
	 * @return void
	 */
	public function wc_cp_add_paged_mode_show_component_scroll_target( $components, $product ) {

		$navigation_style = $product->get_composite_layout_style();

		if ( $navigation_style === 'paged' ) {

			?><div class="scroll_show_component"></div><?php
		}
	}

	/**
	 * When changing component selections in paged mode, the viewport will scroll to this div if it's not visible.
	 * Adding the 'scroll_bottom' class to the element will scroll the bottom of the viewport to the target.
	 *
	 * @param  string               $component_id
	 * @param  WC_Product_Composite $product
	 * @return void
	 */
	public function wc_cp_add_paged_mode_select_component_option_scroll_target( $components, $product ) {

		$navigation_style = $product->get_composite_layout_style();

		if ( $navigation_style === 'paged' ) {

			?><div class="scroll_select_component_option scroll_bottom"></div><?php
		}
	}

	/**
	 * When selecting the final step in paged mode, the viewport will scroll to this div.
	 * Adding the 'scroll_bottom' class to the element will scroll the bottom of the viewport to the target.
	 *
	 * @param  string               $component_id
	 * @param  WC_Product_Composite $product
	 * @return void
	 */
	public function wc_cp_add_paged_mode_final_step_scroll_target( $components, $product ) {

		$navigation_style = $product->get_composite_layout_style();

		if ( $navigation_style === 'paged' ) {

			?><div class="scroll_final_step"></div><?php
		}
	}

	/* ------------------------------------------------------------------------------- */
	/* Composite products single product template hooks - single/multi page layout
	/* ------------------------------------------------------------------------------- */

	/**
	 * Add Composite Summary on the 'woocommerce_before_add_to_cart_button' hook.
	 *
	 * @return void
	 */
	public function wc_cp_before_add_to_cart_button() {

		global $woocommerce_composite_products, $product;

		if ( $product->product_type === 'composite' ) {

			$this->wc_cp_add_summary( $product->get_composite_data(), $product );
		}
	}

	/**
	 * Add Review/Summary with current configuration details.
	 * The Summary template must be loaded if the summary widget is active.
	 *
	 * @param  string               $component_id
	 * @param  WC_Product_Composite $product
	 * @return void
	 */
	public function wc_cp_add_summary( $components, $product ) {

		global $woocommerce_composite_products;

		$navigation_style           = $product->get_composite_layout_style();
		$navigation_style_variation = $product->get_composite_layout_style_variation();
		$show_summary               = apply_filters( 'woocommerce_composite_summary_display', $navigation_style === 'paged', $navigation_style, $navigation_style_variation, $product );
		$show_summary_widget        = apply_filters( 'woocommerce_composite_summary_widget_display', $navigation_style === 'paged' && $navigation_style_variation !== 'componentized', $navigation_style, $navigation_style_variation, $product );

		if ( $show_summary || $show_summary_widget ) {

			// Summary
			wc_get_template( 'single-product/composite-summary.php', array(
				'product'    => $product,
				'components' => $components,
				'hidden'     => false === $show_summary,
			), '', $woocommerce_composite_products->plugin_path() . '/templates/' );
		}
	}

	/**
	 * Add-to-cart section in multi-page mode -- added at the bottom of the page under the summary.
	 *
	 * @param  string               $component_id
	 * @param  WC_Product_Composite $product
	 * @return void
	 */
	public function wc_cp_add_paged_mode_cart( $components, $product ) {

		global $woocommerce_composite_products;

		$navigation_style = $product->get_composite_layout_style();

		if ( $navigation_style === 'paged' ) {

			// Add to cart section
			wc_get_template( 'single-product/composite-add-to-cart.php', array(
				'product'                    => $product,
				'components'                 => $components,
				'navigation_style'           => $navigation_style,
				'navigation_style_variation' => $product->get_composite_layout_style_variation(),
				'selection_mode'             => $product->get_composite_selections_style(),
			), '', $woocommerce_composite_products->plugin_path() . '/templates/' );
		}

	}

	/**
	 * Add previous/next navigation buttons in multi-page mode -- added on bottom of page under the component options section.
	 *
	 * @param  string               $component_id
	 * @param  WC_Product_Composite $product
	 * @return void
	 */
	public function wc_cp_add_navigation( $components, $product ) {

		global $woocommerce_composite_products;

		// Navigation - Previous / Next
		wc_get_template( 'single-product/composite-navigation.php', array(
			'product'                    => $product,
			'navigation_style'           => $product->get_composite_layout_style(),
			'navigation_style_variation' => $product->get_composite_layout_style_variation(),
		), '', $woocommerce_composite_products->plugin_path() . '/templates/' );
	}

	/**
	 * Add-to-cart section in single-page mode -- added at the end of everything.
	 *
	 * @param  string               $component_id
	 * @param  WC_Product_Composite $product
	 * @return void
	 */
	public function wc_cp_add_single_mode_cart( $components, $product ) {

		global $woocommerce_composite_products;

		$navigation_style = $product->get_composite_layout_style();

		if ( $navigation_style === 'single' || $navigation_style === 'progressive' ) {

			// Add to cart section
			wc_get_template( 'single-product/composite-add-to-cart.php', array(
				'product'                    => $product,
				'components'                 => $components,
				'navigation_style'           => $navigation_style,
				'navigation_style_variation' => $product->get_composite_layout_style_variation(),
				'selection_mode'             => $product->get_composite_selections_style(),
			), '', $woocommerce_composite_products->plugin_path() . '/templates/' );
		}

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

		wc_get_template( 'single-product/add-to-cart/composite-quantity-input.php', array(), false, $woocommerce_composite_products->plugin_path() . '/templates/' );
		wc_get_template( 'single-product/add-to-cart/composite-button.php', array(), false, $woocommerce_composite_products->plugin_path() . '/templates/' );
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

		$navigation_style = $product->get_composite_layout_style();
		$components       = $product->get_composite_data();

		if ( ! empty( $components ) ) {
			wc_get_template( 'single-product/add-to-cart/composite.php', array(
				'navigation_style' => $navigation_style,
				'components'       => $components,
				'product'          => $product
			), '', $woocommerce_composite_products->plugin_path() . '/templates/' );
		}

	}

	/**
	 * Replaces add_to_cart button url with something more appropriate.
	 *
	 * @param  string $url
	 * @return string
	 */
	public function wc_cp_add_to_cart_url( $url ) {

		global $product;

		if ( $product->is_type( 'composite' ) ) {
			return $product->add_to_cart_url();
		}

		return $url;
	}

	/**
	 * Replaces the add to cart class for ajax add-to-cart and qv.
	 *
	 * @param  string $text
	 * @return string
	 */
	public function wc_cp_add_to_cart_class( $class ) {

		global $product;

		if ( $product->is_type( 'composite' ) ) {
			return '';
		}

		return $class;
	}

	/**
	 * Replaces add_to_cart text with something more appropriate.
	 *
	 * @param  string $text
	 * @return string
	 */
	public function wc_cp_add_to_cart_text( $text ) {

		global $product;

		if ( $product->is_type( 'composite' ) ) {
			return $product->add_to_cart_text();
		}

		return $text;
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
	 * Changes the tr class of composited items in all templates to allow their styling.
	 *
	 * @param  string   $classname
	 * @param  array    $values
	 * @return string
	 */
	public function wc_cp_table_item_class( $classname, $values ) {

		if ( isset( $values[ 'composite_data' ] ) && ! empty( $values[ 'composite_parent' ] ) ) {
			return $classname . ' component_table_item';
		} elseif ( isset( $values[ 'composite_data' ] ) && ! empty( $values[ 'composite_children' ] ) ) {
			return $classname . ' component_container_table_item';
		}

		return $classname;
	}

	/**
	 * Adds order item title preambles to cart items ( Composite Attribute Descriptions ).
	 *
	 * @param  string   $content
	 * @param  array    $cart_item_values
	 * @param  string   $cart_item_key
	 * @return string
	 */
	public function wc_cp_in_cart_component_title( $content, $cart_item_values, $cart_item_key, $append_qty = false ) {

		global $woocommerce_composite_products;

		if ( ! empty( $cart_item_values[ 'composite_item' ] ) && ! empty( $cart_item_values[ 'composite_parent' ] ) ) {

			$item_id      = $cart_item_values[ 'composite_item' ];
			$composite_id = ! empty( $cart_item_values[ 'composite_data' ][ $item_id ][ 'composite_id' ] ) ? $cart_item_values[ 'composite_data' ][ $item_id ][ 'composite_id' ] : '';
			$item_title   = apply_filters( 'woocommerce_composite_component_title', $cart_item_values[ 'composite_data' ][ $item_id ][ 'title' ], $item_id, $composite_id );

			if ( is_checkout() || ( isset( $_REQUEST[ 'action' ] ) && $_REQUEST[ 'action' ] === 'woocommerce_update_order_review' ) ) {
				$append_qty = true;
			}

			if ( $append_qty ) {
				$item_quantity = apply_filters( 'woocommerce_composited_cart_item_quantity_html', '<strong class="composited-product-quantity">' . sprintf( ' &times; %s', $cart_item_values[ 'quantity' ] ) . '</strong>', $cart_item_values, $cart_item_key );
			} else {
				$item_quantity = '';
			}

			$product_title = $content . $item_quantity;
			$item_data     = array( 'key' => $item_title, 'value' => $product_title );

			$this->wc_cp_enqueue_composited_table_item_js();

			ob_start();

			wc_get_template( 'component-item.php', array( 'component_data' => $item_data ), '', $woocommerce_composite_products->plugin_path() . '/templates/' );

			return ob_get_clean();
		}

		return $content;
	}

	/**
	 * Delete composited item quantity from the review-order.php template. Quantity is inserted into the product name by 'wc_cp_in_cart_component_title'.
	 *
	 * @param  string 	$quantity
	 * @param  array 	$cart_item
	 * @param  string 	$cart_key
	 * @return string
	 */
	public function wc_cp_cart_item_component_quantity( $quantity, $cart_item, $cart_key ) {

		if ( ! empty( $cart_item[ 'composite_item' ] ) ) {
			return '';
		}

		return $quantity;
	}

	/**
	 * Adds component title preambles to order-details template.
	 *
	 * @param  string 	$content
	 * @param  array 	$order_item
	 * @return string
	 */
	public function wc_cp_order_table_component_title( $content, $order_item ) {

		global $woocommerce_composite_products;

		if ( ! empty( $order_item[ 'composite_item' ] ) ) {

			$item_id        = $order_item[ 'composite_item' ];
			$composite_data = maybe_unserialize( $order_item[ 'composite_data' ] );
			$composite_id   = ! empty( $composite_data[ $item_id ][ 'composite_id' ] ) ? $composite_data[ $item_id ][ 'composite_id' ] : '';

			$item_title     = apply_filters( 'woocommerce_composite_component_title', $composite_data[ $item_id ][ 'title' ], $item_id, $composite_id );
			$item_quantity  = apply_filters( 'woocommerce_composited_order_item_quantity_html', '<strong class="composited-product-quantity">' . sprintf( ' &times; %s', $order_item[ 'qty' ] ) . '</strong>', $order_item );

			if ( function_exists( 'is_account_page' ) && is_account_page() || function_exists( 'is_checkout' ) && is_checkout() ) {

				$item_data  = array( 'key' => $item_title, 'value' => $content . $item_quantity );

				$this->wc_cp_enqueue_composited_table_item_js();

				ob_start();

				wc_get_template( 'component-item.php', array( 'component_data' => $item_data ), '', $woocommerce_composite_products->plugin_path() . '/templates/' );

				return ob_get_clean();

			} else {

				return '<small><span style="display:block">' . wp_kses_post( $item_title ) . ':</span> ' . wp_kses_post( $content ) . '</small>';
			}
		}

		return $content;
	}

	/**
	 * Delete composited item quantity from order-details template. Quantity is inserted into the product name by 'wc_cp_order_table_component_title'.
	 *
	 * @param  string 	$content
	 * @param  array 	$order_item
	 * @return string
	 */
	public function wc_cp_order_table_component_quantity( $content, $order_item ) {

		if ( isset( $order_item[ 'composite_item' ] ) && ! empty( $order_item[ 'composite_item' ] ) ) {
			return '';
		}

		return $content;
	}

	/**
	 * Enqeue js that wraps bundled table items in a div in order to apply indentation reliably.
	 *
	 * @return void
	 */
	private function wc_cp_enqueue_composited_table_item_js() {

		if ( ! $this->enqueued_composited_table_item_js ) {
			wc_enqueue_js( "
				var wc_cp_wrap_composited_table_item = function() {
					jQuery( '.component_table_item td.product-name' ).wrapInner( '<div class=\"component_table_item_indent\"></div>' );
				}

				jQuery( 'body' ).on( 'updated_checkout', function() {
					wc_cp_wrap_composited_table_item();
				} );

				wc_cp_wrap_composited_table_item();
			" );

			$this->enqueued_composited_table_item_js = true;
		}
	}

	/**
	 * Filters the reported number of cart items - counts only composite containers.
	 *
	 * @param  int 			$count
	 * @param  WC_Order 	$order
	 * @return int
	 */
	function wc_cp_cart_contents_count( $count ) {

		$cart     = WC()->cart->get_cart();
		$subtract = 0;

		foreach ( $cart as $key => $value ) {

			if ( isset( $value[ 'composite_item' ] ) ) {
				$subtract += $value[ 'quantity' ];
			}
		}

		return $count - $subtract;

	}

	/**
	 * Add cart widget filters.
	 *
	 * @return void
	 */
	function wc_cp_add_cart_widget_filters() {

		add_filter( 'woocommerce_widget_cart_item_visible', array( $this, 'wc_cp_cart_widget_item_visible' ), 10, 3 );
		add_filter( 'woocommerce_widget_cart_item_quantity', array( $this, 'wc_cp_cart_widget_item_qty' ), 10, 3 );
		add_filter( 'woocommerce_cart_item_name', array( $this, 'wc_cp_cart_widget_container_item_name' ), 10, 3 );
	}

	/**
	 * Remove cart widget filters.
	 *
	 * @return void
	 */
	function wc_cp_remove_cart_widget_filters() {

		remove_filter( 'woocommerce_widget_cart_item_visible', array( $this, 'wc_cp_cart_widget_item_visible' ), 10, 3 );
		remove_filter( 'woocommerce_widget_cart_item_quantity', array( $this, 'wc_cp_cart_widget_item_qty' ), 10, 3 );
		remove_filter( 'woocommerce_cart_item_name', array( $this, 'wc_cp_cart_widget_container_item_name' ), 10, 3 );
	}

	/**
	 * Tweak composite container qty.
	 *
	 * @param  bool 	$qty
	 * @param  array 	$cart_item
	 * @param  string 	$cart_item_key
	 * @return bool
	 */
	function wc_cp_cart_widget_item_qty( $qty, $cart_item, $cart_item_key ) {

		global $woocommerce_composite_products;

		if ( isset( $cart_item[ 'composite_children' ] ) ) {
			$qty = '<span class="quantity">' . apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $cart_item[ 'data' ], $cart_item[ 'quantity' ] ), $cart_item, $cart_item_key ) . '</span>';
		}

		return $qty;
	}

	/**
	 * Do not show composited items.
	 *
	 * @param  bool 	$show
	 * @param  array 	$cart_item
	 * @param  string 	$cart_item_key
	 * @return bool
	 */
	function wc_cp_cart_widget_item_visible( $show, $cart_item, $cart_item_key ) {

		if ( isset( $cart_item[ 'composite_item' ] ) ) {
			return false;
		}

		return $show;
	}

	/**
	 * Tweak composite container name.
	 *
	 * @param  bool 	$show
	 * @param  array 	$cart_item
	 * @param  string 	$cart_item_key
	 * @return bool
	 */
	function wc_cp_cart_widget_container_item_name( $name, $cart_item, $cart_item_key ) {

		if ( isset( $cart_item[ 'composite_children' ] ) ) {
			$name = sprintf( '%s &times; %s', $name , $cart_item[ 'quantity' ] );
		}

		return $name;
	}

	/**
	 * Front end styles and scripts.
	 *
	 * @return void
	 */
	public function wc_cp_frontend_scripts() {

		global $woocommerce_composite_products;

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		$dependencies = array( 'jquery', 'jquery-blockui', 'wc-add-to-cart-variation' );

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
			'show_quantity_buttons'                  => 'no',
			'transition_type'                        => 'fade',
		) );

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
	 * Modifies wishlist bundle item price - the precise sum cannot be displayed reliably unless the item is added to the cart.
	 *
	 * @param  double   $price      Item price
	 * @param  array    $item       Wishlist item
	 * @param  array    $wishlist   Wishlist
	 * @return string   $price
	 */
	public function wishlist_list_item_price( $price, $item, $wishlist ) {

		if ( ! empty( $item[ 'composite_data' ] ) )
			return __( '*', 'woocommerce-composite-products' );

		return $price;

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

	/**
	 * Indent composited items in emails
	 *
	 * @param  string 	$css
	 * @return string
	 */
	function wc_cp_email_styles( $css ) {
		$css = $css . ".component_table_item td:nth-child(1) { padding-left: 35px !important; } .component_table_item td { border-top: none; }";
		return $css;
	}
}
