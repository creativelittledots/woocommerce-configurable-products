<?php
/**
 * Functions related to 3rd party extensions compatibility.
 *
 * @class 	WC_CP_Compatibility
 * @since  3.0.6
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_CP_Compatibility {

	private $addons_prefix = '';
	private $nyp_prefix    = '';

	private $compat_composited_product = '';

	function __construct() {

		// Support for Product Addons
		add_action( 'woocommerce_composite_product_add_to_cart', array( $this, 'addons_display_support' ), 10, 3 );
		add_filter( 'product_addons_field_prefix', array( $this, 'addons_cart_prefix' ), 9, 2 );

		add_filter( 'woocommerce_addons_price_for_display_product', array( $this, 'addons_price_for_display_product' ) );

		// Support for NYP
		add_action( 'woocommerce_composite_product_add_to_cart', array( $this, 'nyp_display_support' ), 9, 3 );
		add_filter( 'nyp_field_prefix', array( $this, 'nyp_cart_prefix' ), 9, 2 );

		// Validate add to cart NYP and Addons
		add_filter( 'woocommerce_composite_component_add_to_cart_validation', array( $this, 'validate_component_nyp_and_addons' ), 10, 5 );

		// Add addons identifier to composited item stamp
		add_filter( 'woocommerce_composite_component_cart_item_identifier', array( $this, 'composited_item_addons_identifier' ), 10, 2 );

		// Add NYP identifier to composited item stamp
		add_filter( 'woocommerce_composite_component_cart_item_identifier', array( $this, 'composited_item_nyp_stamp' ), 10, 2 );

		// PnR support
		if ( class_exists( 'WC_Points_Rewards_Product' ) ) {

			// Points and Rewards support
			add_filter( 'woocommerce_points_earned_for_cart_item', array( $this, 'points_earned_for_composited_cart_item' ), 10, 3 );
			add_filter( 'woocommerce_points_earned_for_order_item', array( $this, 'points_earned_for_composited_order_item' ), 10, 5 );

			// Change earn points message for per-product-priced bundles
			add_filter( 'wc_points_rewards_single_product_message', array( $this, 'points_rewards_composite_message' ), 10, 2 );

			// Remove PnR message from variations
			add_action( 'woocommerce_composite_products_add_product_filters', array( $this, 'points_rewards_remove_price_html_messages' ) );
			add_action( 'woocommerce_composite_products_remove_product_filters', array( $this, 'points_rewards_restore_price_html_messages' ) );
		}

		// Pre-orders support
		add_filter( 'wc_pre_orders_cart_item_meta', array( $this, 'remove_composite_pre_orders_cart_item_meta' ), 10, 2 );
		add_filter( 'wc_pre_orders_order_item_meta', array( $this, 'remove_composite_pre_orders_order_item_meta' ), 10, 3 );

		// Bundles support
		add_action( 'woocommerce_add_cart_item', array( $this, 'bundled_cart_item_price_modification' ), 9, 2 );
		add_action( 'woocommerce_get_cart_item_from_session', array( $this, 'bundled_cart_item_session_price_modification' ), 9, 3 );

		add_action( 'woocommerce_add_cart_item', array( $this, 'bundled_cart_item_after_price_modification' ), 11 );
		add_action( 'woocommerce_get_cart_item_from_session', array( $this, 'bundled_cart_item_after_price_modification' ), 11 );

		// OPC support
		add_action( 'wcopc_composite_add_to_cart', array( $this, 'opc_single_add_to_cart_composite' ) );
		add_filter( 'wcopc_allow_cart_item_modification', array( $this, 'opc_disallow_composited_cart_item_modification' ), 10, 4 );

		// Cost of Goods support
		add_filter( 'wc_cost_of_goods_save_checkout_order_item_meta_item_cost', array( $this, 'cost_of_goods_checkout_order_composited_item_cost' ), 10, 3 );
		add_filter( 'wc_cost_of_goods_save_checkout_order_meta_item_cost', array( $this, 'cost_of_goods_checkout_order_composited_item_cost' ), 10, 3 );
		add_filter( 'wc_cost_of_goods_set_order_item_cost_meta_item_cost', array( $this, 'cost_of_goods_set_order_item_cost_composited_item_cost' ), 10, 3 );

		// Shipstation compatibility
		add_filter( 'woocommerce_get_product_from_item', array( $this, 'get_product_from_item' ), 10, 3 );
		add_filter( 'woocommerce_order_amount_item_total', array( $this, 'order_amount_composite_total' ), 10, 5 );
		add_filter( 'woocommerce_order_get_items', array( $this, 'order_add_composited_meta' ), 11, 2 );
	}

	/**
	 * Shipstation compatibility:
	 *
	 * When returning a single container item, add bundled items as metadata.
	 *
	 * @param  array    $items
	 * @param  WC_Order $order
	 * @return array
	 */
	function order_add_composited_meta( $items, $order ) {

		global $wp;

		if ( isset( $wp->query_vars[ 'wc-api' ] ) && $wp->query_vars[ 'wc-api' ] === 'wc_shipstation' ) {

			foreach ( $items as $item_id => $item ) {

				if ( isset( $item[ 'composite_children' ] ) && isset( $item[ 'composite_cart_key' ] ) && isset( $item[ 'per_product_shipping' ] ) && $item[ 'per_product_shipping' ] === 'no' ) {

					$bundle_key = $item[ 'composite_cart_key' ];

					$meta_key   = __( 'Contents', 'woocommerce-composite-products' );
					$meta_value = '';

					foreach ( $items as $child_item ) {

						if ( isset( $child_item[ 'composite_parent' ] ) && $child_item[ 'composite_parent' ] === $bundle_key ) {

							$child = $order->get_product_from_item( $child_item );

							if ( $child && $sku = $child->get_sku() ) {
								$sku .= ' &ndash; ';
							} else {
								$sku = '#' . ( isset( $child->variation_id ) ? $child->variation_id : $child->id ) . ' &ndash; ';
							}

							$meta_value .= $sku . $child_item[ 'name' ];

							if ( ! empty( $child_item[ 'item_meta' ][ __( 'Part of', 'woocommerce-composite-products' ) ] ) ) {
								unset( $child_item[ 'item_meta' ][ __( 'Part of', 'woocommerce-composite-products' ) ] );
							}

							$item_meta      = new WC_Order_Item_Meta( $child_item[ 'item_meta' ] );
							$formatted_meta = $item_meta->display( true, true, '_', ', ' );

							if ( $formatted_meta ) {
								$meta_value .= ' (' . $formatted_meta . ')';
							}

							$meta_value .= ' &times; ' . $child_item[ 'qty' ] . ', ';
						}
					}

					$items[ $item_id ][ 'item_meta' ][ $meta_key ] = rtrim( $meta_value, ', ' );
				}
			}
		}

		return $items;
	}

	/**
	 * Shipstation compatibility:
	 *
	 * Ensure that non-virtual containers/children, which are shipped, have a valid price that can be used for insurance calculations.
	 *
	 * Note: If you charge a static price for the bundle but ship bundled items individually, the only working solution is to spread the total value among the bundled items.
	 *
	 * @param  double   $price
	 * @param  WC_Order $order
	 * @param  array    $item
	 * @param  boolean  $inc_tax
	 * @param  boolean  $round
	 * @return double
	 */
	function order_amount_composite_total( $price, $order, $item, $inc_tax, $round ) {

		global $wp, $woocommerce_composite_products;

		if ( isset( $wp->query_vars[ 'wc-api' ] ) && $wp->query_vars[ 'wc-api' ] === 'wc_shipstation' ) {

			if ( isset( $item[ 'composite_children' ] ) && isset( $item[ 'composite_cart_key' ] ) && isset( $item[ 'bundled_shipping' ] ) && $item[ 'bundled_shipping' ] === 'yes' ) {

				$bundle_key   = $item[ 'composite_cart_key' ];
				$bundle_qty   = $item[ 'qty' ];
				$bundle_value = $price;

				foreach ( $order->get_items( 'line_item' ) as $order_item ) {

					if ( apply_filters( 'woocommerce_order_item_is_child_of_composite', isset( $order_item[ 'composite_parent' ] ) && $order_item[ 'composite_parent' ] === $bundle_key, $order_item, $item, $order ) ) {
						$bundle_value += $order->get_line_total( $order_item, $inc_tax, $round ) / $bundle_qty;
					}
				}

				$price = $round ? round( $bundle_value, 2 ) : $bundle_value;

			} elseif ( isset( $item[ 'composite_parent' ] ) && isset( $item[ 'composite_cart_key' ] ) && isset( $item[ 'bundled_shipping' ] ) && $item[ 'bundled_shipping' ] === 'yes' ) {

				$parent = $woocommerce_composite_products->order->get_composited_order_item_container( $item, $order );

				if ( $parent && isset( $parent[ 'per_product_shipping' ] ) && $parent[ 'per_product_shipping' ] === 'yes' && isset( $parent[ 'per_product_pricing' ] ) && $parent[ 'per_product_pricing' ] === 'no' && isset( $parent[ 'composite_cart_key' ] ) ) {

					$bundle_value = $order->get_line_total( $parent, $inc_tax, $round );
					$bundle_key   = $parent[ 'composite_cart_key' ];
					$child_count  = 0;

					foreach ( $order->get_items( 'line_item' ) as $child_item ) {

						if ( isset( $child_item[ 'composite_parent' ] ) && $child_item[ 'composite_parent' ] === $bundle_key ) {

							$bundle_value += $order->get_line_total( $child_item, $inc_tax, $round );
							$child_count  += $child_item[ 'qty' ];
						}
					}

					$price = $round ? round( $bundle_value / $child_count, 2 ) : $bundle_value / $child_count;
				}
			}
		}

		return $price;
	}

	/**
	 * Shipstation compatibility:
	 *
	 * Restore virtual statuses and weights.
	 *
	 * @param  WC_Product $product
	 * @param  array      $item
	 * @param  WC_Order   $order
	 * @return WC_Product
	 */
	function get_product_from_item( $product, $item, $order ) {

		global $wp;

		if ( isset( $wp->query_vars[ 'wc-api' ] ) && $wp->query_vars[ 'wc-api' ] === 'wc_shipstation' ) {

			if ( isset( $item[ 'composite_data' ] ) && isset( $item[ 'bundled_shipping' ] ) ) {

				if ( $item[ 'bundled_shipping' ] === 'yes' ) {

					if ( isset( $item[ 'bundled_weight' ] ) ) {
						$product->weight = $item[ 'bundled_weight' ];
					}

				} else {

					$product->virtual = 'yes';
				}
			}
		}

		return $product;
	}

	/**
	 * Cost of goods compatibility: Zero order item cost for composited products that belong to statically priced composites.
	 *
	 * @param  double $cost
	 * @param  array  $values
	 * @param  string $cart_item_key
	 * @return double
	 */
	function cost_of_goods_checkout_order_composited_item_cost( $cost, $values, $cart_item_key ) {

		if ( ! empty( $values[ 'composite_parent' ] ) ) {

			$cart_contents = WC()->cart->get_cart();
			$parent_key    = $values[ 'composite_parent' ];

			if ( isset( $cart_contents[ $parent_key ] ) ) {
				if ( ! $cart_contents[ $parent_key ][ 'data' ]->is_priced_per_product() ) {
					return 0;
				}
			}

		} elseif ( ! empty( $values[ 'composite_children' ] ) ) {
			if ( $values[ 'data' ]->is_priced_per_product() ) {
				return 0;
			}
		}

		return $cost;
	}

	/**
	 * Cost of goods compatibility: Zero order item cost for composited products that belong to statically priced composites.
	 *
	 * @param  double   $cost
	 * @param  array    $item
	 * @param  WC_Order $order
	 * @return double
	 */
	function cost_of_goods_set_order_item_cost_composited_item_cost( $cost, $item, $order ) {

		global $woocommerce_composite_products;

		if ( ! empty( $item[ 'composite_parent' ] ) ) {

			// find bundle parent
			$parent_item = $woocommerce_composite_products->order->get_composited_order_item_container( $item, $order );

			$per_product_pricing = ! empty( $parent_item ) && isset( $parent_item[ 'per_product_pricing' ] ) ? $parent_item[ 'per_product_pricing' ] : get_post_meta( $parent_item[ 'product_id' ], '_per_product_pricing_bto', true );

			if ( $per_product_pricing === 'no' ) {
				return 0;
			}

		} elseif ( isset( $item[ 'composite_children' ] ) ) {

			$per_product_pricing = isset( $item[ 'per_product_pricing' ] ) ? $item[ 'per_product_pricing' ] : get_post_meta( $item[ 'product_id' ], '_per_product_pricing_bto', true );

			if ( $per_product_pricing === 'yes' ) {
				return 0;
			}
		}

		return $cost;
	}

	/**
	 * OPC Single-product bundle-type add-to-cart template
	 *
	 * @param  int  $opc_post_id
	 * @return void
	 */
	function opc_single_add_to_cart_composite( $opc_post_id ) {

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

		ob_start();

		if ( ! empty( $components ) ) {
			wc_get_template( 'single-product/add-to-cart/composite.php', array(
				'navigation_style' => $navigation_style,
				'components'       => $components,
				'product'          => $product
			), '', $woocommerce_composite_products->plugin_path() . '/templates/' );
		}

		echo str_replace( array( '<form method="post" enctype="multipart/form-data"', '</form>' ), array( '<div', '</div>' ), ob_get_clean() );
	}

	/**
	 * Prevent OPC from managing composited cart items.
	 *
	 * @param  bool   $allow
	 * @param  array  $cart_item
	 * @param  string $cart_item_key
	 * @param  string $opc_id
	 * @return bool
	 */
	function opc_disallow_composited_cart_item_modification( $allow, $cart_item, $cart_item_key, $opc_id ) {

		if ( ! empty( $cart_item[ 'composite_parent' ] ) ) {
			return false;
		}

		return $allow;
	}

	/**
	 * Filter the product which add-ons prices are displayed for.
	 *
	 * @param  WC_Product  $product
	 * @return WC_Product
	 */
	function addons_price_for_display_product( $product ) {

		if ( ! empty( $this->compat_composited_product ) )
			return $this->compat_composited_product;

		return $product;
	}

	/**
	 * Add filters to modify bundled product prices when parent product is composited and has a discount.
	 * @param  array  $cart_item_data
	 * @param  string $cart_item_key
	 * @return void
	 */
	function bundled_cart_item_price_modification( $cart_item_data, $cart_item_key ) {

		global $woocommerce_composite_products;

		if ( isset( $cart_item_data[ 'bundled_by' ] ) ) {

			$bundle_key = $cart_item_data[ 'bundled_by' ];

			if ( isset( WC()->cart->cart_contents[ $bundle_key ] ) ) {

				$bundle_cart_data = WC()->cart->cart_contents[ $bundle_key ];

				if ( isset( $bundle_cart_data[ 'composite_parent' ] ) ) {

					$composite_key = $bundle_cart_data[ 'composite_parent' ];

					if ( isset( WC()->cart->cart_contents[ $composite_key ] ) ) {

						$composite    = WC()->cart->cart_contents[ $composite_key ][ 'data' ];
						$component_id = $bundle_cart_data[ 'composite_item' ];

						$args = array(
							'per_product_pricing' => $composite->is_priced_per_product(),
							'discount'            => $composite->get_component_discount( $component_id ),
							'composite_id'        => $composite->id,
							'component_id'        => $component_id
						);

						$woocommerce_composite_products->api->add_composited_product_filters( $args );
					}
				}
			}
		}

		return $cart_item_data;
	}

	/**
	 * Add filters to modify bundled product prices when parent product is composited and has a discount.
	 * @param  string $cart_item_data
	 * @param  array  $session_item_data
	 * @param  string $cart_item_key
	 * @return void
	 */
	function bundled_cart_item_session_price_modification( $cart_item_data, $session_item_data, $cart_item_key ) {
		return $this->bundled_cart_item_price_modification( $cart_item_data, $cart_item_key );
	}

	/**
	 * Remove filters that modify bundled product prices when parent product is composited and has a discount.
	 * @param  string $cart_item_data
	 * @return void
	 */
	function bundled_cart_item_after_price_modification( $cart_item_data ) {

		global $woocommerce_composite_products;

		if ( isset( $cart_item_data[ 'bundled_by' ] ) ) {

			$bundle_key = $cart_item_data[ 'bundled_by' ];

			if ( isset( WC()->cart->cart_contents[ $bundle_key ] ) ) {

				$bundle_cart_data = WC()->cart->cart_contents[ $bundle_key ];

				if ( isset( $bundle_cart_data[ 'composite_parent' ] ) ) {

					$composite_key = $bundle_cart_data[ 'composite_parent' ];

					if ( isset( WC()->cart->cart_contents[ $composite_key ] ) ) {

						$woocommerce_composite_products->api->remove_composited_product_filters();
					}
				}
			}
		}

		return $cart_item_data;
	}

	/**
	 * Remove composited cart item meta "Available On" text.
	 * @param  array  $pre_order_meta
	 * @param  array  $cart_item_data
	 * @return array
	 */
	function remove_composite_pre_orders_cart_item_meta( $pre_order_meta, $cart_item_data ) {

		if ( isset( $cart_item_data[ 'composite_parent' ] ) ) {
			$pre_order_meta = array();
		}

		return $pre_order_meta;
	}

	/**
	 * Remove composited order item meta "Available On" text.
	 * @param  array    $pre_order_meta
	 * @param  array    $order_item
	 * @param  WC_Order $order
	 * @return array
	 */
	function remove_composite_pre_orders_order_item_meta( $pre_order_meta, $order_item, $order ) {

		if ( isset( $order_item[ 'composite_parent' ] ) )
			$pre_order_meta = array();

		return $pre_order_meta;
	}

	/**
	 * Points and Rewards single product message for per-product priced Bundles.
	 * @param  string                    $message
	 * @param  WC_Points_Rewards_Product $points_n_rewards
	 * @return string
	 */
	function points_rewards_composite_message( $message, $points_n_rewards ) {

		global $product;

		if ( $product->product_type == 'composite' ) {

			if ( ! $product->is_priced_per_product() ) {
				return $message;
			}

			// Will calculate points based on min_composite_price, which is saved as _price meta
			$composite_points = WC_Points_Rewards_Product::get_points_earned_for_product_purchase( $product );

			$message = $points_n_rewards->create_at_least_message_to_product_summary( $composite_points );

		}

		return $message;
	}

	/**
	 * Return zero points for composited cart items if container item has product level points.
	 *
	 * @param  int        $points
	 * @param  string     $item_key
	 * @param  array      $item
	 * @param  WC_Order   $order
	 * @return int
	 */
	function points_earned_for_composited_order_item( $points, $product, $item_key, $item, $order ) {

		if ( isset( $item[ 'composite_parent' ] ) ) {

			// find container item
			foreach ( $order->get_items() as $order_item ) {

				$is_parent = ( isset( $order_item[ 'composite_cart_key' ] ) && $item[ 'composite_parent' ] == $order_item[ 'composite_cart_key' ] ) ? true : false;

				if ( $is_parent ) {

					$parent_item  = $order_item;
					$composite_id = $parent_item[ 'product_id' ];

					// check if earned points are set at product-level
					$composite_points = get_post_meta( $composite_id, '_wc_points_earned', true );

					$per_product_priced_composite = isset( $parent_item[ 'per_product_pricing' ] ) ? $parent_item[ 'per_product_pricing' ] : get_post_meta( $composite_id, '_per_product_pricing_bto', true );

					if ( ! empty( $composite_points ) || $per_product_priced_composite !== 'yes' ) {
						$points = 0;
					} else {
						$points = WC_Points_Rewards_Manager::calculate_points( $product->get_price() );
					}

					break;
				}
			}
		}

		return $points;
	}

	/**
	 * Return zero points for composited cart items if container item has product level points.
	 *
	 * @param  int     $points
	 * @param  string  $cart_item_key
	 * @param  array   $cart_item_values
	 * @return int
	 */
	function points_earned_for_composited_cart_item( $points, $cart_item_key, $cart_item_values ) {

		if ( isset( $cart_item_values[ 'composite_parent' ] ) ) {

			$cart_contents = WC()->cart->get_cart();

			$composite_cart_id = $cart_item_values[ 'composite_parent' ];
			$composite         = $cart_contents[ $composite_cart_id ][ 'data' ];

			// check if earned points are set at product-level
			$composite_points = WC_Points_Rewards_Product::get_product_points( $composite );

			$per_product_priced_composite = $composite->is_priced_per_product();

			$has_composite_points = is_numeric( $composite_points ) ? true : false;

			if ( $has_composite_points || $per_product_priced_composite == false  )
				$points = 0;
			else
				$points = WC_Points_Rewards_Manager::calculate_points( $cart_item_values[ 'data' ]->get_price() );

		}

		return $points;
	}

	/**
	 * Filter option_wc_points_rewards_single_product_message in order to force 'WC_Points_Rewards_Product::render_variation_message' to display nothing.
	 *
	 * @return void
	 */
	function points_rewards_remove_price_html_messages( $args ) {
		add_filter( 'option_wc_points_rewards_single_product_message', array( $this, 'return_empty_message' ) );
	}

	/**
	 * Restore option_wc_points_rewards_single_product_message. Forced in order to force 'WC_Points_Rewards_Product::render_variation_message' to display nothing.
	 *
	 * @return void
	 */
	function points_rewards_restore_price_html_messages( $args ) {
		remove_filter( 'option_wc_points_rewards_single_product_message', array( $this, 'return_empty_message' ) );
	}

	/**
	 * @see points_rewards_remove_price_html_messages
	 * @param  string  $message
	 * @return void
	 */
	function return_empty_message( $message ) {
		return false;
	}

	/**
	 * Runs before adding a composited item to the cart.
	 * @param  int                $product_id
	 * @param  int                $quantity
	 * @param  int                $variation_id
	 * @param  array              $variations
	 * @param  array              $composited_item_cart_data
	 * @return void
	 */
	function after_composited_add_to_cart( $product_id, $quantity, $variation_id, $variations, $composited_item_cart_data ) {

		global $Product_Addon_Cart;

		// Reset addons and nyp prefix
		$this->addons_prefix = $this->nyp_prefix = '';

		if ( ! empty ( $Product_Addon_Cart ) )
			add_filter( 'woocommerce_add_cart_item_data', array( $Product_Addon_Cart, 'add_cart_item_data' ), 10, 2 );

		// Similarly with NYP
		if ( function_exists( 'WC_Name_Your_Price' ) )
			add_filter( 'woocommerce_add_cart_item_data', array( WC_Name_Your_Price()->cart, 'add_cart_item_data' ), 5, 3 );
	}

	/**
	 * Runs before adding a composited item to the cart.
	 *
	 * @param  int                $product_id
	 * @param  int                $quantity
	 * @param  int                $variation_id
	 * @param  array              $variations
	 * @param  array              $composited_item_cart_data
	 * @return void
	 */
	function before_composited_add_to_cart( $product_id, $quantity, $variation_id, $variations, $composited_item_cart_data ) {

		global $Product_Addon_Cart;

		// Set addons and nyp prefixes
		$this->addons_prefix = $this->nyp_prefix = $composited_item_cart_data[ 'composite_item' ];

		// Add-ons cart item data is already stored in the composite_data array, so we can grab it from there instead of allowing Addons to re-add it
		// Not doing so results in issues with file upload validation

		if ( ! empty ( $Product_Addon_Cart ) )
			remove_filter( 'woocommerce_add_cart_item_data', array( $Product_Addon_Cart, 'add_cart_item_data' ), 10, 2 );

		// Similarly with NYP
		if ( function_exists( 'WC_Name_Your_Price' ) )
			remove_filter( 'woocommerce_add_cart_item_data', array( WC_Name_Your_Price()->cart, 'add_cart_item_data' ), 5, 3 );
	}

	/**
	 * Retrieve child cart item data from the parent cart item data array, if necessary.
	 *
	 * @param  array  $composited_item_cart_data
	 * @param  array  $cart_item_data
	 * @return array
	 */
	function get_composited_cart_item_data_from_parent( $composited_item_cart_data, $cart_item_data ) {

		// Add-ons cart item data is already stored in the composite_data array, so we can grab it from there instead of allowing Addons to re-add it

		if ( isset( $composited_item_cart_data[ 'composite_item' ] ) && isset( $cart_item_data[ 'composite_data' ][ $composited_item_cart_data[ 'composite_item' ] ][ 'addons' ] ) )
			$composited_item_cart_data[ 'addons' ] = $cart_item_data[ 'composite_data' ][ $composited_item_cart_data[ 'composite_item' ] ][ 'addons' ];

		// Similarly with NYP

		if ( isset( $composited_item_cart_data[ 'composite_item' ] ) && isset( $cart_item_data[ 'composite_data' ][ $composited_item_cart_data[ 'composite_item' ] ][ 'nyp' ] ) )
			$composited_item_cart_data[ 'nyp' ] = $cart_item_data[ 'composite_data' ][ $composited_item_cart_data[ 'composite_item' ] ][ 'nyp' ];

		return $composited_item_cart_data;
	}

	/**
	 * Add addons identifier to composited item stamp, in order to generate new cart ids for composites with different addons configurations.
	 *
	 * @param  array  $composited_item_identifier
	 * @param  string $composited_item_id
	 * @return array
	 */
	function composited_item_addons_identifier( $composited_item_identifier, $composited_item_id ) {

		global $Product_Addon_Cart;

		// Store composited item addons add-ons config in indentifier to avoid generating the same composite cart id
		if ( ! empty( $Product_Addon_Cart ) ) {

			$addon_data = array();

			// Set addons prefix
			$this->addons_prefix = $composited_item_id;

			$composited_product_id = $composited_item_identifier[ 'product_id' ];

			$addon_data = $Product_Addon_Cart->add_cart_item_data( $addon_data, $composited_product_id );

			// Reset addons prefix
			$this->addons_prefix = '';

			if ( ! empty( $addon_data[ 'addons' ] ) )
				$composited_item_identifier[ 'addons' ] = $addon_data[ 'addons' ];
		}

		return $composited_item_identifier;
	}

	/**
	 * Add nyp identifier to composited item stamp, in order to generate new cart ids for composites with different nyp configurations.
	 *
	 * @param  array  $composited_item_identifier
	 * @param  string $composited_item_id
	 * @return array
	 */
	function composited_item_nyp_stamp( $composited_item_identifier, $composited_item_id ) {

		if ( function_exists( 'WC_Name_Your_Price' ) ) {

			$nyp_data = array();

			// Set nyp prefix
			$this->nyp_prefix = $composited_item_id;

			$composited_product_id = $composited_item_identifier[ 'product_id' ];

			$nyp_data = WC_Name_Your_Price()->cart->add_cart_item_data( $nyp_data, $composited_product_id, '' );

			// Reset nyp prefix
			$this->nyp_prefix = '';

			if ( ! empty( $nyp_data[ 'nyp' ] ) )
				$composited_item_identifier[ 'nyp' ] = $nyp_data[ 'nyp' ];
		}

		return $composited_item_identifier;
	}

	/**
	 * Validate composited item NYP and Addons.
	 *
	 * @param  bool   $add
	 * @param  int    $composite_id
	 * @param  int    $component_id
	 * @param  int    $product_id
	 * @param  int    $quantity
	 * @return bool
	 */
	function validate_component_nyp_and_addons( $add, $composite_id, $component_id, $product_id, $quantity ) {

		// Ordering again? When ordering again, do not revalidate addons & nyp
		$order_again = isset( $_GET[ 'order_again' ] ) && isset( $_GET[ '_wpnonce' ] ) && wp_verify_nonce( $_GET[ '_wpnonce' ], 'woocommerce-order_again' );

		if ( $order_again  )
			return $add;

		// Validate add-ons
		global $Product_Addon_Cart;

		if ( ! empty( $Product_Addon_Cart ) ) {

			$this->addons_prefix = $component_id;

			if ( ! $Product_Addon_Cart->validate_add_cart_item( true, $product_id, $quantity ) )
				return false;

			$this->addons_prefix = '';
		}

		// Validate nyp
		if ( get_post_meta( $composite_id, '_per_product_pricing_bto', true ) == 'yes' && function_exists( 'WC_Name_Your_Price' ) ) {

			$this->nyp_prefix = $component_id;

			if ( ! WC_Name_Your_Price()->cart->validate_add_cart_item( true, $product_id, $quantity ) )
				return false;

			$this->nyp_prefix = '';
		}

		return $add;
	}

	/**
	 * Set the addons fields prefix value.
	 *
	 * @param string $prefix
	 */
	function set_addons_prefix( $prefix ) {

		$this->addons_prefix = $prefix;
	}

	/**
	 * Set the nyp fields prefix value.
	 *
	 * @param string $prefix
	 */
	function set_nyp_prefix( $prefix ) {

		$this->nyp_prefix = $prefix;
	}

	/**
	 * Outputs add-ons for composited products.
	 *
	 * @param  int $product_id
	 * @param  int $component_id
	 * @return void
	 */
	function addons_display_support( $product_id, $component_id, $product ) {

		global $Product_Addon_Display;

		if ( ! empty( $Product_Addon_Display ) ) {

			$this->compat_composited_product = $product;

			$Product_Addon_Display->display( $product_id, $component_id . '-' );

			$this->compat_composited_product = '';
		}

	}

	/**
	 * Outputs nyp markup.
	 *
	 * @param  int $product_id
	 * @param  int $component_id
	 * @return void
	 */
	function nyp_display_support( $product_id, $component_id, $product ) {

		global $woocommerce_composite_products;

		if ( ! empty( $woocommerce_composite_products->api->filter_params ) && ! $woocommerce_composite_products->api->filter_params[ 'per_product_pricing' ] )
			return;

		if ( function_exists( 'WC_Name_Your_Price' ) && ( $product->product_type == 'simple' || $product->product_type == 'bundle' ) ) {
			WC_Name_Your_Price()->display->display_price_input( $product_id, '-' . $component_id );
		}

	}

	/**
	 * Sets a prefix for unique add-ons.
	 *
	 * @param  string 	$prefix
	 * @param  int 		$product_id
	 * @return string
	 */
	function addons_cart_prefix( $prefix, $product_id ) {

		if ( ! empty( $this->addons_prefix ) )
			return $this->addons_prefix . '-';

		return $prefix;
	}

	/**
	 * Sets a prefix for unique nyp.
	 *
	 * @param  string 	$prefix
	 * @param  int 		$product_id
	 * @return string
	 */
	function nyp_cart_prefix( $prefix, $product_id ) {

		if ( ! empty( $this->nyp_prefix ) )
			return '-' . $this->nyp_prefix;

		return $prefix;
	}

	/**
	 * Tells if a product is a Name Your Price product, provided that the extension is installed.
	 *
	 * @param  mixed    $product      product or id to check
	 * @return boolean                true if NYP exists and product is a NYP
	 */
	function is_nyp( $product ) {

		if ( ! class_exists( 'WC_Name_Your_Price_Helpers' ) )
			return false;

		if ( WC_Name_Your_Price_Helpers::is_nyp( $product ) )
			return true;

		return false;
	}

}
