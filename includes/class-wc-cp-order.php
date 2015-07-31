<?php
/**
 * Composite order filters and functions.
 *
 * @class 	WC_CP_Order
 * @version 3.0.6
 * @since   2.2.2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_CP_Order {

	public function __construct() {

		// Filter price output shown in cart, review-order & order-details templates
		add_filter( 'woocommerce_order_formatted_line_subtotal', array( $this, 'wc_cp_order_item_subtotal' ), 10, 3 );

		// Composite containers should not affect order status
		add_filter( 'woocommerce_order_item_needs_processing', array( $this, 'wc_cp_container_items_need_no_processing' ), 10, 3 );

		// Modify order items to include composite meta
		add_action( 'woocommerce_add_order_item_meta', array( $this, 'wc_cp_add_order_item_meta' ), 10, 3 );

		// Hide composite configuration metadata in order line items
		add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'wc_cp_hide_order_item_meta' ) );

		// Filter admin dashboard item count
		add_filter( 'woocommerce_get_item_count',  array( $this, 'wc_cp_dashboard_recent_orders_item_count' ), 10, 3 );
		add_filter( 'woocommerce_admin_order_item_count',  array( $this, 'wc_cp_order_item_count_string' ), 10, 2 );
		add_filter( 'woocommerce_admin_html_order_item_class',  array( $this, 'wc_cp_html_order_item_class' ), 10, 2 );
		add_filter( 'woocommerce_admin_order_item_class',  array( $this, 'wc_cp_html_order_item_class' ), 10, 2 );
	}

	/**
	 * Find the parent of a composited item in an order.
	 *
	 * @param  array    $item
	 * @param  WC_Order $order
	 * @return array
	 */
	function get_composited_order_item_container( $item, $order ) {

		$composite_data = $item[ 'composite_data' ];

		// find container item
		foreach ( $order->get_items( 'line_item' ) as $order_item ) {

			/*------------------------------------------------------------------------------------------------------------------------------------*/
			/*	Find the parent.
			/*  WC 2.0 may result in a false match because there is no easy way to store the cart item key in the order meta.
			/*  Issues in 2.0 are caused by extensions that modify the cart id of the parent without adding anything in the composite_data array.
			/*------------------------------------------------------------------------------------------------------------------------------------*/

			if ( isset( $order_item[ 'composite_cart_key' ] ) ) {
				$is_parent = $item[ 'composite_parent' ] == $order_item[ 'composite_cart_key' ] ? true : false;
			} else {
				$is_parent = isset( $order_item[ 'composite_data' ] ) && $order_item[ 'composite_data' ] == $composite_data && isset( $order_item[ 'composite_children' ] ) ? true : false;
			}

			if ( $is_parent ) {
				return $order_item;
			}
		}

		return false;
	}

	/**
	 * Modifies the subtotal of order-items (order-details.php) depending on the composite pricing strategy.
	 *
	 * @param  string 	$subtotal
	 * @param  array 	$item
	 * @param  WC_Order $order
	 * @return string
	 */
	function wc_cp_order_item_subtotal( $subtotal, $item, $order ) {

		global $woocommerce_composite_products;

		// If it's a composited item
		if ( isset( $item[ 'composite_parent' ] ) ) {

			$composite_data = $item[ 'composite_data' ];

			// find composite parent
			$parent_item = '';

			foreach ( $order->get_items( 'line_item' ) as $order_item ) {

				/*------------------------------------------------------------------------------------------------------------------------------------*/
				/*	Find the parent.
				/*  WC 2.0 may result in a false match because there is no easy way to store the cart item key in the order meta.
				/*  Issues in 2.0 are caused by extensions that modify the cart id of the parent without adding anything in the composite_data array.
				/*------------------------------------------------------------------------------------------------------------------------------------*/

				if ( isset( $order_item[ 'composite_cart_key' ] ) ) {
					$is_parent = $item[ 'composite_parent' ] == $order_item[ 'composite_cart_key' ] ? true : false;
				} else {
					$is_parent = isset( $order_item[ 'composite_data' ] ) && $order_item[ 'composite_data' ] == $composite_data && isset( $order_item[ 'composite_children' ] ) ? true : false;
				}

				if ( $is_parent ) {

					$parent_item = $order_item;
					break;
				}

			}

			if ( function_exists( 'is_account_page' ) && is_account_page() || function_exists( 'is_checkout' ) && is_checkout() ) {
				$wrap_start = '';
				$wrap_end   = '';
			} else {
				$wrap_start = '<small>';
				$wrap_end   = '</small>';
			}

			if ( $parent_item[ 'per_product_pricing' ] === 'no' ) {
				return '';
			} else {
				return  $wrap_start . __( 'Option subtotal', 'woocommerce-composite-products' ) . ': ' . $subtotal . $wrap_end;
			}
		}

		// If it's a parent item
		if ( isset( $item[ 'composite_children' ] ) ) {

			if ( isset( $item[ 'subtotal_updated' ] ) )
				return $subtotal;

			foreach ( $order->get_items( 'line_item' ) as $order_item ) {

				/*------------------------------------------------------------------------------------------------------------------------------------*/
				/*	Find the children.
				/*  WC 2.0 may result in a false match because there is no easy way to store the cart item key in the order meta.
				/*  Issues in 2.0 are caused by extensions that modify the cart id of the parent without adding anything in the composite_data array.
				/*------------------------------------------------------------------------------------------------------------------------------------*/

				if ( isset( $order_item[ 'composite_cart_key' ] ) ) {
					$is_child = in_array( $order_item[ 'composite_cart_key' ], unserialize( $item[ 'composite_children' ] ) ) ? true : false;
				} else {
					$is_child = isset( $order_item[ 'composite_data' ] ) && $order_item[ 'composite_data' ] == $item[ 'composite_data' ] && isset( $order_item[ 'composite_parent' ] ) ? true : false;
				}

				$is_child = apply_filters( 'woocommerce_order_item_is_child_of_composite', $is_child, $order_item, $item, $order );

				if ( $is_child ) {

					$item[ 'line_subtotal' ]     += $order_item[ 'line_subtotal' ];
					$item[ 'line_subtotal_tax' ] += $order_item[ 'line_subtotal_tax' ];
				}
			}

			$item[ 'subtotal_updated' ] = 'yes';

			return $order->get_formatted_line_subtotal( $item );
		}

		return $subtotal;
	}

	/**
	 * Composite Containers should not affect order status - let it be decided by composited items only.
	 *
	 * @param  bool 		$is_needed
	 * @param  WC_Product 	$product
	 * @param  int 			$order_id
	 * @return bool
	 */
	function wc_cp_container_items_need_no_processing( $is_needed, $product, $order_id ) {

		if ( $product->is_type( 'composite' ) )
			return false;

		return $is_needed;
	}

	/**
	 * Adds composite info to order items.
	 *
	 * @param  int 		$order_item_id
	 * @param  array 	$cart_item_values
	 * @param  string 	$cart_item_key
	 * @return void
	 */
	function wc_cp_add_order_item_meta( $order_item_id, $cart_item_values, $cart_item_key ) {

		if ( ! empty( $cart_item_values[ 'composite_children' ] ) ) {

			wc_add_order_item_meta( $order_item_id, '_composite_children', $cart_item_values[ 'composite_children' ] );

			if ( $cart_item_values[ 'data' ]->is_priced_per_product() ) {
				wc_add_order_item_meta( $order_item_id, '_per_product_pricing', 'yes' );
			} else {
				wc_add_order_item_meta( $order_item_id, '_per_product_pricing', 'no' );
			}

			if ( $cart_item_values[ 'data' ]->is_shipped_per_product() ) {
				wc_add_order_item_meta( $order_item_id, '_per_product_shipping', 'yes' );
			} else {
				wc_add_order_item_meta( $order_item_id, '_per_product_shipping', 'no' );
			}
		}

		if ( ! empty( $cart_item_values[ 'composite_parent' ] ) ) {
			wc_add_order_item_meta( $order_item_id, '_composite_parent', $cart_item_values[ 'composite_parent' ] );
		}

		if ( ! empty( $cart_item_values[ 'composite_item' ] ) ) {
			wc_add_order_item_meta( $order_item_id, '_composite_item', $cart_item_values[ 'composite_item' ] );
		}

		if ( ! empty( $cart_item_values[ 'composite_data' ] ) ) {

			wc_add_order_item_meta( $order_item_id, '_composite_cart_key', $cart_item_key );

			wc_add_order_item_meta( $order_item_id, '_composite_data', $cart_item_values[ 'composite_data' ] );

			// Store shipping data - useful when exporting order content
			foreach ( WC()->cart->get_shipping_packages() as $package ) {

				foreach ( $package[ 'contents' ] as $pkg_item_id => $pkg_item_values ) {

					if ( $pkg_item_id === $cart_item_key ) {

						$bundled_shipping = $pkg_item_values[ 'data' ]->needs_shipping() ? 'yes' : 'no';
						$bundled_weight   = $pkg_item_values[ 'data' ]->get_weight();

						wc_add_order_item_meta( $order_item_id, '_bundled_shipping', $bundled_shipping );

						if ( $bundled_shipping === 'yes' ) {
							wc_add_order_item_meta( $order_item_id, '_bundled_weight', $bundled_weight );
						}
					}
				}
			}
		}
	}

	/**
	 * Hides composite metadata.
	 *
	 * @param  array $hidden
	 * @return array
	 */
	function wc_cp_hide_order_item_meta( $hidden ) {
		return array_merge( $hidden, array( '_composite_parent', '_composite_item', '_composite_total', '_composite_cart_key', '_per_product_pricing', '_per_product_shipping', '_bundled_shipping', '_bundled_weight' ) );
	}

	/**
	 * Filters the reported number of admin dashboard recent order items - counts only composite containers.
	 *
	 * @param  int 			$count
	 * @param  string 		$type
	 * @param  WC_Order 	$order
	 * @return int
	 */
	function wc_cp_dashboard_recent_orders_item_count( $count, $type, $order ) {

		$subtract = 0;

		foreach ( $order->get_items() as $order_item ) {

			if ( isset( $order_item[ 'composite_item' ] ) ) {

				$subtract += $order_item[ 'qty' ];

			}
		}

		return $count - $subtract;
	}

	/**
	 * Filters the string of order item count.
	 * Include bundled items as a suffix.
	 *
	 * @param  int          $count      initial reported count
	 * @param  WC_Order     $order      the order
	 * @return int                      modified count
	 */
	function wc_cp_order_item_count_string( $count, $order ) {

		$add = 0;

		foreach ( $order->get_items() as $item ) {

			// If it's a bundled item
			if ( isset( $item[ 'composite_item' ] ) ) {
				$add += $item[ 'qty' ];
			}
		}

		if ( $add > 0 ) {
			return sprintf( __( '%1$s, %2$s composited', 'woocommerce-composite-products' ), $count, $add );
		}

		return $count;
	}

	/**
	 * Filters the order item admin class.
	 *
	 * @param  string       $class     class
	 * @param  array        $item      the order item
	 * @return string                  modified class
	 */
	function wc_cp_html_order_item_class( $class, $item ) {

		// If it's a bundled item
		if ( isset( $item[ 'composite_item' ] ) ) {
			return $class . ' composited_item';
		}

		return $class;
	}


}
