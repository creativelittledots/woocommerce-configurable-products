<?php
/**
 * Composite Product Class
 *
 * @class 	WC_Product_Composite
 * @version 3.1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_Product_Composite extends WC_Product {

	private $composite_data = array();

	public $per_product_pricing;
	public $per_product_shipping;

	private $composite_layout;
	private $base_layout;
	private $base_layout_variation;
	private $selections_style;

	private $composited_products = array();

	private $component_options               = array();
	private $current_component_options_query = array();

	public $min_price;
	public $max_price;

	public $base_price;
	public $base_regular_price;
	private $hide_price_html;

	private $price_meta;

	public $min_composite_price;
	public $max_composite_price;
	public $min_composite_regular_price;
	public $max_composite_regular_price;

	public $min_composite_price_incl_tax;
	public $min_composite_price_excl_tax;

	private $composite_price_data = array();

	private $contains_nyp;
	private $has_discounts;

	private $has_multiple_quantities = false;

	private $is_synced = false;

	function __construct( $bundle_id ) {

		$this->product_type = 'composite';

		parent::__construct( $bundle_id );

		$this->composite_data       = get_post_meta( $this->id, '_bto_data', true );
		$this->per_product_pricing  = get_post_meta( $this->id, '_per_product_pricing_bto', true );
		$this->per_product_shipping = get_post_meta( $this->id, '_per_product_shipping_bto', true );

		$this->composite_layout     = get_post_meta( $this->id, '_bto_style', true );
		$this->selections_style     = get_post_meta( $this->id, '_bto_selection_mode', true );

		$this->contains_nyp         = false;
		$this->has_discounts        = false;

		$this->min_price            = get_post_meta( $this->id, '_min_composite_price', true );
		$this->max_price            = get_post_meta( $this->id, '_max_composite_price', true );

		$this->price_meta           = (double) get_post_meta( $this->id, '_price', true );

		$base_price                 = get_post_meta( $this->id, '_base_price', true );
		$base_regular_price         = get_post_meta( $this->id, '_base_regular_price', true );
		$base_sale_price            = get_post_meta( $this->id, '_base_sale_price', true );

		$this->base_price           = empty( $base_price ) ? 0.0 : (double) $base_price;
		$this->base_regular_price   = empty( $base_regular_price ) ? 0.0 : (double) $base_regular_price;
		$this->base_sale_price      = empty( $base_sale_price ) ? 0.0 : (double) $base_sale_price;

		$this->hide_price_html      = get_post_meta( $this->id, '_bto_hide_shop_price', true ) == 'yes' ? true : false;

		if ( $this->is_priced_per_product() ) {
			$this->price = $this->get_base_price();
		}
	}

	/**
	 * True if the composite is in sync with its contents.
	 *
	 * @return boolean
	 */
	public function is_synced() {

		return $this->is_synced;
	}

	/**
	 * Builds scenarios array for a single component.
	 *
	 * @param  int    $component_id
	 * @param  array  $current_component_options
	 * @return array
	 */
	public function get_current_component_scenarios( $component_id, $current_component_options ) {

		global $woocommerce_composite_products;

		$composite_scenario_meta = get_post_meta( $this->id, '_bto_scenario_data', true );
		$composite_scenario_meta = apply_filters( 'woocommerce_composite_scenario_meta', $composite_scenario_meta, $this );

		$component_data                                = $this->get_component_data( $component_id );
		$component_data[ 'current_component_options' ] = $current_component_options;

		$current_component_scenarios = $woocommerce_composite_products->api->build_scenarios( $composite_scenario_meta, array( $component_id => $component_data ) );

		return apply_filters( 'woocommerce_composite_component_current_scenario_data', $current_component_scenarios, $component_id, $current_component_options, $composite_scenario_meta, $this );
	}

	/**
	 * Calculates min and max prices based on the composited product data.
	 *
	 * @return void
	 */
	public function sync_composite() {

		global $woocommerce_composite_products;

		if ( empty( $this->composite_data ) )
			return false;

		$this->load_price_data();

		// Initialize min/max price information
		$this->min_composite_price = $this->max_composite_price = $this->min_composite_regular_price = $this->max_composite_regular_price = '';

		// Initialize component options
		foreach ( $this->get_composite_data() as $component_id => $component_data ) {

			$this->composited_products[ $component_id ] = array();

			// Do not pass any ordering args to speed up the query - ordering and filtering is done when calling get_current_component_options()
			$this->component_options[ $component_id ]   = $woocommerce_composite_products->api->get_component_options( $component_data );
		}

		if ( $this->is_priced_per_product() ) {

			if ( ! $this->hide_price_html() ) {

				foreach ( $this->get_composite_data() as $component_id => $component_data ) {

					$item_min_price = '';
					$item_max_price = '';

					$item_min_regular_price = '';
					$item_max_regular_price = '';

					$item_min_price_incl = '';
					$item_min_price_excl = '';

					if ( ! empty( $component_data[ 'discount' ] ) )
						$this->has_discounts = true;

					// No options available
					if ( empty( $this->component_options[ $component_id ] ) )
						continue;

					foreach ( $this->component_options[ $component_id ] as $id ) {

						$composited_product = $this->get_composited_product( $component_id, $id );

						if ( ! $composited_product )
							continue;

						if ( $composited_product->is_nyp() )
							$this->contains_nyp = true;

						// Update component prices
						$item_min_price = $item_min_price !== '' ? min( $item_min_price, $composited_product->get_min_price() ) : $composited_product->get_min_price();
						$item_max_price = $item_max_price !== '' ? max( $item_max_price, $composited_product->get_max_price() ) : $composited_product->get_max_price();

						$item_min_regular_price = $item_min_regular_price !== '' ? min( $item_min_regular_price, $composited_product->get_min_regular_price() ) : $composited_product->get_min_regular_price();
						$item_max_regular_price = $item_max_regular_price !== '' ? max( $item_max_regular_price, $composited_product->get_max_regular_price() ) : $composited_product->get_max_regular_price();

						// Price incl tax
						$item_min_price_incl = $item_min_price_incl !== '' ? min( $item_min_price_incl, $composited_product->get_min_price_incl_tax() ) : $composited_product->get_min_price_incl_tax();
						// Price excl tax
						$item_min_price_excl = $item_min_price_excl !== '' ? min( $item_min_price_excl, $composited_product->get_min_price_excl_tax() ) : $composited_product->get_min_price_excl_tax();
					}

					// Sync composite
					if ( $component_data[ 'optional' ] == 'yes' ) {

						$this->has_multiple_quantities 	= true;
						$component_data[ 'quantity_min' ] 	= 0;
					}

					if ( $component_data[ 'quantity_min' ] && $component_data[ 'quantity_max' ] )
						$this->has_multiple_quantities = true;

					$this->min_composite_price          = $this->min_composite_price + $component_data[ 'quantity_min' ] * $item_min_price;
					$this->min_composite_regular_price  = $this->min_composite_regular_price + $component_data[ 'quantity_min' ] * $item_min_regular_price;
					$this->max_composite_price          = $this->max_composite_price + $component_data[ 'quantity_max' ] * $item_max_price;
					$this->max_composite_regular_price  = $this->max_composite_regular_price + $component_data[ 'quantity_max' ] * $item_max_regular_price;

					$this->min_composite_price_incl_tax = $this->min_composite_price_incl_tax + $component_data[ 'quantity_min' ] * $item_min_price_incl;
					$this->min_composite_price_excl_tax = $this->min_composite_price_excl_tax + $component_data[ 'quantity_min' ] * $item_min_price_excl;
				}

				$composite_base_prices     = $woocommerce_composite_products->api->get_composited_item_prices( $this, $this->get_base_price() );
				$composite_base_reg_prices = $woocommerce_composite_products->api->get_composited_item_prices( $this, $this->get_base_regular_price() );

				$this->min_composite_price          = $composite_base_prices[ 'shop' ] + $this->min_composite_price;
				$this->min_composite_regular_price  = $composite_base_reg_prices[ 'shop' ] + $this->min_composite_regular_price;
				$this->max_composite_price          = $composite_base_prices[ 'shop' ] + $this->max_composite_price;
				$this->max_composite_regular_price  = $composite_base_reg_prices[ 'shop' ] + $this->max_composite_regular_price;

				$this->min_composite_price_incl_tax = $composite_base_prices[ 'incl' ] + $this->min_composite_price_incl_tax;
				$this->min_composite_price_excl_tax = $composite_base_prices[ 'excl' ] + $this->min_composite_price_excl_tax;

				if ( $this->contains_nyp )
					$this->max_composite_price = '';

			}

			if ( $this->price_meta != $this->min_composite_price && ! is_admin() ) {
				update_post_meta( $this->id, '_price', $this->min_composite_price );
			}

		} else {

			if ( $woocommerce_composite_products->compatibility->is_nyp( $this ) ) {

				$this->min_composite_price = get_post_meta( $this->id, '_min_price', true );
				$this->max_composite_price = '';

			} else {

				$composite_prices = $woocommerce_composite_products->api->get_composited_item_prices( $this, $this->get_price() );

				$this->min_composite_price = $composite_prices[ 'shop' ];
				$this->max_composite_price = $composite_prices[ 'shop' ];

				$this->min_composite_price_incl_tax = $composite_prices[ 'incl' ];
				$this->min_composite_price_excl_tax = $composite_prices[ 'excl' ];
			}

		}

		// Use these filters if get_hide_price_html() returns false but you still want to include the composite in price filter widget min/max results
		$this->min_composite_price = apply_filters( 'woocommerce_min_composite_price_meta', $this->min_composite_price, $this );
		$this->max_composite_price = apply_filters( 'woocommerce_max_composite_price_meta', $this->max_composite_price, $this );

		// Save modified min/max price meta to include product in price filter widget results
		if ( $this->min_price != $this->min_composite_price ) {
			update_post_meta( $this->id, '_min_composite_price', $this->min_composite_price );
		}

		if ( $this->max_price != $this->max_composite_price ) {
			update_post_meta( $this->id, '_max_composite_price', $this->max_composite_price );
		}

		$this->is_synced = true;
	}

	/**
	 * Stores bundle pricing strategy data that is passed to JS.
	 *
	 * @return void
	 */
	public function load_price_data() {

		global $woocommerce_composite_products;

		$this->composite_price_data[ 'per_product_pricing' ] = $this->is_priced_per_product();
		$this->composite_price_data[ 'show_free_string' ]    = $this->is_priced_per_product() ? apply_filters( 'woocommerce_composite_show_free_string', false, $this ) : true;

		$this->composite_price_data[ 'prices' ]         = array();
		$this->composite_price_data[ 'regular_prices' ] = array();
		$this->composite_price_data[ 'addons_prices' ]  = array();

		$this->composite_price_data[ 'price_undefined' ] = $this->get_price() === '' ? true : false;

		if ( $this->is_priced_per_product() ) {

			$this->composite_price_data[ 'base_price' ]         = $woocommerce_composite_products->api->get_composited_product_price( $this, $this->get_base_price() );
			$this->composite_price_data[ 'base_regular_price' ] = $woocommerce_composite_products->api->get_composited_product_price( $this, $this->get_base_regular_price() );

		} else {

			$this->composite_price_data[ 'base_price' ]         = $this->get_price() === '' ? 0.0 : $woocommerce_composite_products->api->get_composited_product_price( $this, $this->get_price() );
			$this->composite_price_data[ 'base_regular_price' ] = $this->get_regular_price() === '' ? 0.0 :$woocommerce_composite_products->api->get_composited_product_price( $this, $this->get_regular_price() );
		}

		$this->composite_price_data[ 'total' ]         = (float) 0;
		$this->composite_price_data[ 'regular_total' ] = (float) 0;
		
		// added by CLD
		$this->composite_price_data = apply_filters('woocommerce_composite_price_data', $this->composite_price_data, $this);
		
	}

	/**
	 * Overrides get_price to return base price in static price mode.
	 * In per-product pricing mode, get_price() returns the base composite price.
	 *
	 * @return double
	 */
	public function get_price() {

		if ( $this->is_priced_per_product() ) {
			return apply_filters( 'woocommerce_composite_get_price', $this->get_base_price(), $this );
		} else {
			return parent::get_price();
		}
	}

	/**
	 * Overrides get_regular_price to return base price in static price mode.
	 *
	 * @return double
	 */
	public function get_regular_price() {

		if ( $this->is_priced_per_product() ) {
			return apply_filters( 'woocommerce_composite_get_regular_price', $this->get_base_regular_price(), $this );
		} else {
			return parent::get_regular_price();
		}
	}

	/**
	 * Get composite base price.
	 *
	 * @return double
	 */
	public function get_base_price() {

		if ( $this->is_priced_per_product() ) {
			return apply_filters( 'woocommerce_composite_get_base_price', get_query_var( 'wc_query' ) == 'product_query' ? max($this->min_price, $this->base_price) : $this->base_price, $this );
		} else {
			return false;
		}
	}

	/**
	 * Get composite base regular price.
	 *
	 * @return double
	 */
	public function get_base_regular_price() {

		if ( $this->is_priced_per_product() ) {
			return apply_filters( 'woocommerce_composite_get_base_regular_price', get_query_var( 'wc_query' ) == 'product_query' ? max($this->min_price, $this->base_regular_price) : $this->base_regular_price, $this );
		} else {
			return false;
		}
	}

	/**
	 * Overrides adjust_price to use base price in per-item pricing mode.
	 *
	 * @return double
	 */
	public function adjust_price( $price ) {

		if ( $this->is_priced_per_product() ) {
			$this->price      = $this->price + $price;
			$this->base_price = $this->base_price + $price;
		} else {
			return parent::adjust_price( $price );
		}
	}

	public function hide_price_html() {

		return apply_filters( 'woocommerce_composite_hide_price_html', $this->hide_price_html, $this );
	}

	/**
	 * Returns range style html price string without min and max.
	 *
	 * @param  mixed    $price    default price
	 * @return string             overridden html price string (old style)
	 */
	public function get_price_html( $price = '' ) {

		global $woocommerce_composite_products;

		if ( ! $this->is_synced() )
			$this->sync_composite();

		if ( apply_filters( 'woocommerce_composite_force_old_style_price_html', false, $this ) || apply_filters( 'woocommerce_composite_show_free_string', false, $this ) ) {
			return $this->get_old_style_price_html();
		}

		if ( $this->is_priced_per_product() ) {

			if ( $this->hide_price_html() ) {
				return '';
			}

			// Get the price
			if ( $this->min_composite_price === '' ) {

				$price = apply_filters( 'woocommerce_composite_empty_price_html', '', $this );

			} else {

				// Main price
				$prices = array( $this->min_composite_price, $this->max_composite_price );

				if ( $this->contains_nyp || $this->has_multiple_quantities )
					$price = wc_price( $prices[0] );
				else
					$price = $prices[0] !== $prices[1] ? sprintf( _x( '%1$s&ndash;%2$s', 'Price range: from-to', 'woocommerce' ), wc_price( $prices[0] ), wc_price( $prices[1] ) ) : wc_price( $prices[0] );

				// Sale
				$prices = array( $this->min_composite_regular_price, $this->max_composite_regular_price );

				if ( $this->contains_nyp || $this->has_multiple_quantities ) {
					$saleprice = wc_price( $prices[0] );
				} else {
					sort( $prices );
					$saleprice = $prices[0] !== $prices[1] ? sprintf( _x( '%1$s&ndash;%2$s', 'Price range: from-to', 'woocommerce' ), wc_price( $prices[0] ), wc_price( $prices[1] ) ) : wc_price( $prices[0] );
				}

				if ( $price !== $saleprice ) {
					$price = apply_filters( 'woocommerce_composite_sale_price_html', $this->contains_nyp || $this->has_multiple_quantities ? sprintf( _x( '%1$s%2$s', 'Price range: from', 'woocommerce-composite-products' ), $this->get_price_html_from_text(), $this->get_price_html_from_to( $saleprice, $price ) . $this->get_price_suffix() ) : $this->get_price_html_from_to( $saleprice, $price ) . $this->get_price_suffix(), $this );
				} else {
					$price = apply_filters( 'woocommerce_composite_price_html', $this->contains_nyp || $this->has_multiple_quantities ? sprintf( _x( '%1$s%2$s', 'Price range: from', 'woocommerce-composite-products' ), $this->get_price_html_from_text(), $price . $this->get_price_suffix() ) : $price . $this->get_price_suffix(), $this );
				}

			}

			return apply_filters( 'woocommerce_get_price_html', $price, $this );

		} else {

			return parent::get_price_html();
		}

	}

	/**
	 * 'From:' style price html string.
	 *
	 * @param  double|string $price
	 * @return string
	 */
	private function get_old_style_price_html( $price = '' ) {

		if ( $this->is_priced_per_product() ) {

			if ( $this->hide_price_html() )
				return '';

			// Get the price
			if ( $this->min_composite_price > 0 ) :
				if ( $this->min_composite_regular_price !== $this->min_composite_price ) :

					if ( ! $this->min_composite_price || $this->min_composite_price !== $this->max_composite_price || $this->contains_nyp )
						$price .= $this->get_price_html_from_text();

					$price .= $this->get_price_html_from_to( $this->min_composite_regular_price, $this->min_composite_price ) . $this->get_price_suffix();

					$price = apply_filters( 'woocommerce_composite_sale_price_html', $price, $this );

				else :

					if ( ! $this->min_composite_price || $this->min_composite_price !== $this->max_composite_price || $this->contains_nyp )
						$price .= $this->get_price_html_from_text();

					$price .= wc_price( $this->min_composite_price ) . $this->get_price_suffix();

					$price = apply_filters( 'woocommerce_composite_price_html', $price, $this );

				endif;
			elseif ( $this->min_composite_price === '' ) :

				$price = apply_filters( 'woocommerce_composite_empty_price_html', '', $this );

			elseif ( $this->min_composite_price == 0 ) :

				if ( $this->is_on_sale() && isset( $this->min_composite_regular_price ) && $this->min_composite_regular_price !== $this->min_composite_price ) :

					if ( ! $this->min_composite_price || $this->min_composite_price !== $this->max_composite_price || $this->contains_nyp )
						$price .= $this->get_price_html_from_text();

					$price .= $this->get_price_html_from_to( $this->min_composite_regular_price, __( 'Free!', 'woocommerce' ) );

					$price = apply_filters( 'woocommerce_composite_free_sale_price_html', $price, $this );

				else :

					if ( !$this->min_composite_price || $this->min_composite_price !== $this->max_composite_price || $this->contains_nyp )
						$price .= $this->get_price_html_from_text();

					$price .= __( 'Free!', 'woocommerce' );

					$price = apply_filters( 'woocommerce_composite_free_price_html', $price, $this );

				endif;

			endif;

			return apply_filters( 'woocommerce_get_price_html', $price, $this );

		} else {

			return parent::get_price_html();
		}

	}

	/**
	 * Prices incl. or excl. tax are calculated based on the bundled products prices, so get_price_suffix() must be overridden to return the correct field in per-product pricing mode.
	 *
	 * @return 	string    modified price html suffix
	 */
	public function get_price_suffix($price = '', $qty = 1) {

		global $woocommerce_composite_products;

		if ( $this->is_priced_per_product() ) {

			$price_display_suffix  = get_option( 'woocommerce_price_display_suffix' );

			if ( $price_display_suffix ) {
				$price_display_suffix = ' <small class="woocommerce-price-suffix">' . $price_display_suffix . '</small>';

				$find = array(
					'{price_including_tax}',
					'{price_excluding_tax}'
				);

				$replace = array(
					wc_price( $this->min_composite_price_incl_tax ),
					wc_price( $this->min_composite_price_excl_tax ),
				);

				$price_display_suffix = str_replace( $find, $replace, $price_display_suffix );
			}

			return apply_filters( 'woocommerce_get_price_suffix', $price_display_suffix, $this );

		} else {

			return parent::get_price_suffix();
		}
	}

	/**
	 * Component configuration array passed through 'woocommerce_composite_component_data' filter.
	 *
	 * @return array
	 */
	public function get_composite_data() {

		if ( empty( $this->composite_data ) ) {
			return false;
		}

		$composite_data = array();

		foreach ( $this->composite_data as $component_id => $component_data ) {
			$composite_data[ $component_id ] = apply_filters( 'woocommerce_composite_component_data', $component_data, $component_id, $this );
		}

		return $composite_data;
	}

	/**
	 * Composite base layout.
	 *
	 * @return string
	 */
	public function get_composite_layout_style() {

		global $woocommerce_composite_products;

		if ( ! empty( $this->base_layout ) ) {
			return $this->base_layout;
		}

		$composite_layout = $woocommerce_composite_products->api->get_selected_layout_option( $this->composite_layout );

		$layout = explode( '-', $composite_layout, 2 );

		$this->base_layout = $layout[0];

		return $this->base_layout;
	}

	/**
	 * Composite base layout variation.
	 *
	 * @return string
	 */
	public function get_composite_layout_style_variation() {

		global $woocommerce_composite_products;

		if ( ! empty( $this->base_layout_variation ) ) {
			return $this->base_layout_variation;
		}

		$composite_layout = $woocommerce_composite_products->api->get_selected_layout_option( $this->composite_layout );

		$layout = explode( '-', $composite_layout, 2 );

		if ( ! empty( $layout[1] ) ) {
			$this->base_layout_variation = $layout[1];
		} else {
			$this->base_layout_variation = 'standard';
		}

		return $this->base_layout_variation;
	}

	/**
	 * Component options selections -- thumbnails or dropdowns.
	 *
	 * @return string
	 */
	public function get_composite_selections_style() {

		$selections_style = $this->selections_style;

		if ( empty( $selections_style ) ) {
			$selections_style = 'dropdowns';
		}

		return $selections_style;
	}

	/**
	 * Scenario data arrays used by JS scripts.
	 *
	 * @return array
	 */
	public function get_composite_scenario_data() {

		global $woocommerce_composite_products;

		$composite_scenario_meta = get_post_meta( $this->id, '_bto_scenario_data', true );
		$composite_scenario_meta = apply_filters( 'woocommerce_composite_scenario_meta', $composite_scenario_meta, $this );

		$composite_data = $this->get_composite_data();

		foreach ( $composite_data as $component_id => &$component_data ) {

			$current_component_options = $this->get_current_component_options( $component_id );

			$component_data[ 'current_component_options' ] = $current_component_options;
		}

		$composite_scenario_data = $woocommerce_composite_products->api->build_scenarios( $composite_scenario_meta, $composite_data );

		return apply_filters( 'woocommerce_composite_initial_scenario_data', $composite_scenario_data, $composite_data, $composite_scenario_meta, $this );
	}

	/**
	 * Grab component discount by component id.
	 *
	 * @param  string $component_id
	 * @return string
	 */
	public function get_component_discount( $component_id ) {

		if ( ! isset( $this->composite_data[ $component_id ][ 'discount' ] ) ) {
			return false;
		}

		$component_data = $this->get_component_data( $component_id );

		return $component_data[ 'discount' ];
	}

	/**
	 * Gets price data array. Contains localized strings and price data passed to JS.
	 *
	 * @return array
	 */
	public function get_composite_price_data() {

		if ( ! $this->is_synced() )
			$this->sync_composite();

		return $this->composite_price_data;
	}

	/**
	 * True if a component contains a NYP item.
	 *
	 * @return boolean
	 */
	public function contains_nyp() {

		if ( ! $this->is_synced() )
			$this->sync_composite();

		if ( $this->contains_nyp ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * True if a one of the composited products is on sale or has a component discount.
	 *
	 * @return boolean
	 */
	public function is_on_sale() {

		if ( $this->is_priced_per_product() ) {

			if ( ! $this->is_synced() )
				$this->sync_composite();

			$composite_on_sale = ( ( $this->base_sale_price != $this->base_regular_price && $this->base_sale_price == $this->base_price ) || $this->has_discounts );

		} else {

			$composite_on_sale = parent::is_on_sale();
		}

		return apply_filters( 'woocommerce_composite_on_sale', $composite_on_sale, $this );
	}

	/**
	 * Get composited product.
	 *
	 * @param  string            $component_id
	 * @param  int               $product_id
	 * @return WC_Product|false
	 */
	public function get_composited_product( $component_id, $product_id ) {

		if ( isset( $this->composited_products[ $component_id ][ $product_id ] ) ) {

			$composited_product = $this->composited_products[ $component_id ][ $product_id ];

		} else {

			$composited_product = new WC_CP_Product( $product_id, $component_id, $this );

			if ( ! $composited_product->exists() )
				return false;

			$this->composited_products[ $component_id ][ $product_id ] = $composited_product;
		}

		return $composited_product;
	}

	/**
	 * Get component options to display. Fetched using a WP Query wrapper to allow advanced component options filtering / ordering / pagination.
	 *
	 * @param  string $component_id
	 * @param  array  $args
	 * @return array
	 */
	public function get_current_component_options( $component_id, $args = array() ) {

		global $woocommerce_composite_products;

		$current_options = array();

		if ( isset( $this->current_component_options_query[ $component_id ] ) ) {

			$current_options = $this->current_component_options_query[ $component_id ]->get_component_options();

		} else {

			if ( ! $this->is_synced() )
				$this->sync_composite();

			// Only do paged component options in 'thumbnails' mode
			if ( $this->get_composite_selections_style() == 'dropdowns' ) {
				$per_page = false;
			} else {
				$thumbnail_columns = apply_filters( 'woocommerce_composite_component_loop_columns', max( apply_filters( 'loop_shop_columns', 4 ) - 1, 1 ), $component_id, $this );
				$per_page          = apply_filters( 'woocommerce_component_options_per_page', $thumbnail_columns * 2, $component_id, $this );
			}

			$defaults = array(
				'load_page'       => 'selected',
				'per_page'        => $per_page,
				'selected_option' => $this->get_component_default_option( $component_id ),
				'orderby'         => $this->get_component_default_ordering_option( $component_id ),
				'query_type'      => 'product_ids',
			);

			// Component option ids have already been queried without any pages / filters / sorting when initializing the product in 'sync_composite'.
			// This time, we can speed up our paged / filtered / sorted query by using the stored ids of the first "raw" query.

			$component_data                   = $this->get_component_data( $component_id );
			$component_data[ 'assigned_ids' ] = $this->get_component_options( $component_id );

			$current_args = apply_filters( 'woocommerce_composite_component_options_query_args_current', wp_parse_args( $args, $defaults ), $args, $component_id, $this );

			// Pass through query to apply filters / ordering
			$query = new WC_CP_Query( $component_data, $current_args );

			$this->current_component_options_query[ $component_id ] = $query;

			$current_options = $query->get_component_options();
		}

		// Append a value to the results
		if ( ! empty( $args[ 'append_option' ] ) && ! in_array( $args[ 'append_option' ], $current_options ) ) {

			$current_options[] = $args[ 'append_option' ];
		}

		return $current_options;
	}

	/**
	 * Get the default method to order the options of a component.
	 *
	 * @param  int    $component_id
	 * @return string
	 */
	public function get_component_default_ordering_option( $component_id ) {

		$default_orderby = apply_filters( 'woocommerce_composite_component_default_orderby', 'default', $component_id, $this );

		return $default_orderby;
	}

	/**
	 * Get component sorting options, if enabled.
	 *
	 * @param  int    $component_id
	 * @return array
	 */
	public function get_component_ordering_options( $component_id ) {

		$component_data = $this->get_component_data( $component_id );

		if ( isset( $component_data[ 'show_orderby' ] ) && $component_data[ 'show_orderby' ] == 'yes' ) {

			$show_default_orderby = 'default' === apply_filters( 'woocommerce_composite_component_default_orderby', 'default', $component_id, $this );

			$component_orderby_options = apply_filters( 'woocommerce_composite_component_orderby', array(
				'default'    => __( 'Default sorting', 'woocommerce' ),
				'popularity' => __( 'Sort by popularity', 'woocommerce' ),
				'rating'     => __( 'Sort by average rating', 'woocommerce' ),
				'date'       => __( 'Sort by newness', 'woocommerce' ),
				'price'      => __( 'Sort by price: low to high', 'woocommerce' ),
				'price-desc' => __( 'Sort by price: high to low', 'woocommerce' )
			), $component_id, $this );

			if ( ! $show_default_orderby ) {
				unset( $component_orderby_options[ 'default' ] );
			}

			if ( get_option( 'woocommerce_enable_review_rating' ) === 'no' ) {
				unset( $component_orderby_options[ 'rating' ] );
			}

			if ( ! $this->is_priced_per_product() ) {
				unset( $component_orderby_options[ 'price' ] );
				unset( $component_orderby_options[ 'price-desc' ] );
			}

			return $component_orderby_options;
		}

		return false;
	}

	/**
	 * Get component filtering options, if enabled.
	 *
	 * @param  int    $component_id
	 * @return array
	 */
	public function get_component_filtering_options( $component_id ) {

		global $wc_product_attributes;

		$component_data = $this->get_component_data( $component_id );

		if ( isset( $component_data[ 'show_filters' ] ) && $component_data[ 'show_filters' ] == 'yes' ) {

			$active_filters = array();

			if ( ! empty( $component_data[ 'attribute_filters' ] ) ) {

				foreach ( $wc_product_attributes as $attribute_taxonomy_name => $attribute_data ) {

					if ( in_array( $attribute_data->attribute_id, $component_data[ 'attribute_filters' ] ) && taxonomy_exists( $attribute_taxonomy_name ) ) {

						$orderby = $attribute_data->attribute_orderby;

						switch ( $orderby ) {
							case 'name' :
								$args = array( 'orderby' => 'name', 'hide_empty' => false, 'menu_order' => false );
							break;
							case 'id' :
								$args = array( 'orderby' => 'id', 'order' => 'ASC', 'menu_order' => false, 'hide_empty' => false );
							break;
							case 'menu_order' :
								$args = array( 'menu_order' => 'ASC', 'hide_empty' => false );
							break;
						}

						$taxonomy_terms = get_terms( $attribute_taxonomy_name, $args );

						if ( $taxonomy_terms ) {

							switch ( $orderby ) {
								case 'name_num' :
									usort( $taxonomy_terms, '_wc_get_product_terms_name_num_usort_callback' );
								break;
								case 'parent' :
									usort( $taxonomy_terms, '_wc_get_product_terms_parent_usort_callback' );
								break;
							}

							// Add to array
							$filter_options = array();

							foreach ( $taxonomy_terms as $term ) {
								$filter_options[ $term->term_id ] = $term->name;
							}

							// Default filter format
							$filter_data = array(
								'filter_type'    => 'attribute_filter',
								'filter_id'      => $attribute_taxonomy_name,
								'filter_name'    => $attribute_data->attribute_label,
								'filter_options' => $filter_options,
							);

							$active_filters[] = $filter_data;
						}
					}
				}
			}

			$component_filtering_options = apply_filters( 'woocommerce_composite_component_filters', $active_filters, $component_id, $this );

			if ( ! empty( $component_filtering_options ) ) {

				return $component_filtering_options;
			}
		}

		return false;
	}

	/**
	 * Get the query object used to retrieve the component options of a component.
	 * Should be called after @see get_current_component_options() has been used to retrieve / sort / filter a set of component options.
	 *
	 * @param  int          $component_id
	 * @return WC_CP_Query
	 */
	public function get_current_component_options_query( $component_id ) {

		if ( ! isset( $this->current_component_options_query[ $component_id ] ) ) {
			return false;
		}

		return $this->current_component_options_query[ $component_id ];
	}

	/**
	 * Get all component options (product ids) available in a component.
	 *
	 * @param  string $component_id
	 * @return array
	 */
	public function get_component_options( $component_id ) {

		global $woocommerce_composite_products;

		if ( ! $this->is_synced() )
			$this->sync_composite();

		return $this->component_options[ $component_id ];
	}

	/**
	 * True if a component has only one option and is not optional.
	 *
	 * @param  string  $component_id
	 * @return boolean
	 */
	public function is_component_static( $component_id ) {

		$component_data = $this->get_component_data( $component_id );

		$is_optional = $component_data[ 'optional' ] === 'yes';
		$is_static   = count( $this->get_component_options( $component_id ) ) == 1 && ! $is_optional;

		return $is_static;
	}

	/**
	 * True if a component is optional.
	 *
	 * @param  string  $component_id
	 * @return boolean
	 */
	public function is_component_optional( $component_id ) {

		$component_data = $this->get_component_data( $component_id );

		$is_optional = $component_data[ 'optional' ] === 'yes';

		return $is_optional;
	}

	/**
	 * Get the default option (product id) of a component.
	 *
	 * @param  string $component_id
	 * @return int
	 */
	public function get_component_default_option( $component_id ) {

		$component_data = $this->get_component_data( $component_id );

		if ( ! $component_data ) {
			return '';
		}

		$component_options = $this->get_component_options( $component_id );

		if ( isset( $_POST[ 'wccp_component_selection' ][ $component_id ] ) ) {
			$selected_value = $_POST[ 'wccp_component_selection' ][ $component_id ];
		} elseif ( $component_data[ 'optional' ] != 'yes' && count( $component_options ) == 1 ) {
			$selected_value = $component_options[0];
		} else {
			$selected_value = isset( $component_data[ 'default_id' ] ) && in_array( $component_data[ 'default_id' ], $this->get_component_options( $component_id ) ) ? $component_data[ 'default_id' ] : '';
		}

		return apply_filters( 'woocommerce_composite_component_default_option', $selected_value, $component_id, $this );
	}

	/**
	 * Get component data array by component id.
	 *
	 * @param  string $component_id
	 * @return array
	 */
	public function get_component_data( $component_id ) {

		if ( ! isset( $this->composite_data[ $component_id ] ) ) {
			return false;
		}

		return apply_filters( 'woocommerce_composite_component_data', $this->composite_data[ $component_id ], $component_id, $this );
	}

	/**
	 * Get component thumbnail.
	 *
	 * @param  string $component_id
	 * @return array
	 */
	public function get_component_image( $component_id ) {

		global $woocommerce_composite_products;

		$component_data = $this->get_component_data( $component_id );

		if ( ! $component_data ) {
			return '';
		}

		$image_src = '';

		if ( ! empty( $component_data[ 'thumbnail_id' ] ) ) {

			$image_src_data = wp_get_attachment_image_src( $component_data[ 'thumbnail_id' ], apply_filters( 'woocommerce_composite_component_image_size', 'shop_catalog' )  );
			$image_src      = $image_src_data ? current( $image_src_data ) : false;
		}

		if ( ! $image_src ) {
			$image_src = $woocommerce_composite_products->plugin_url() . '/assets/images/placeholder.png';
		}

		$image = sprintf( '<img class="summary_element_content" alt="%s" src="%s"/>', __( 'Component image', 'woocommerce-composite-products' ), $image_src );

		// Add class="norefresh" to prevent summary image updates and keep the original image static.
		// Return '' to hide all images from the summary section.
		return apply_filters( 'woocommerce_composite_component_image', $image, $image_src, $component_id, $this );
	}

	/**
	 * Create an array of classes to use in the component layout templates.
	 *
	 * @param  string $component_id
	 * @return array
	 */
	public function get_component_classes( $component_id ) {

		$classes    = array();
		$layout     = $this->get_composite_layout_style();
		$components = $this->get_composite_data();
		$toggled    = $layout === 'paged' ? false : apply_filters( 'woocommerce_composite_component_toggled', $layout === 'progressive' ? true : false, $component_id, $this );

		$classes[]  = 'component';
		$classes[]  = 'options-style-' . $this->get_composite_selections_style();

		if ( $layout === 'paged' ) {
			$classes[] = 'multistep';
		} elseif ( $layout === 'progressive' ) {
			$classes[] = 'multistep';
			$classes[] = 'progressive';
			$classes[] = 'autoscrolled';
		}

		if ( $toggled ) {
			$classes[] = 'toggled';
		}

		if ( array_search( $component_id, array_keys( $components ) ) === 0 ) {
			$classes[] = 'active';
			$classes[] = 'first';

			if ( $toggled ) {
				$classes[] = 'open';
			}
		} else {

			if ( $layout === 'progressive' ) {
				$classes[] = 'blocked';
			}

			if ( $toggled ) {
				$classes[] = 'closed';
			}
		}

		if ( array_search( $component_id, array_keys( $components ) ) === count( $components ) - 1 ) {
			$classes[] = 'last';
		}

		if ( $this->is_component_static( $component_id ) ) {
			$classes[] = 'static';
		}

		$hide_product_thumbnail = isset( $components[ $component_id ][ 'hide_product_thumbnail' ] ) ? $components[ $component_id ][ 'hide_product_thumbnail' ] : 'no';

		if ( $hide_product_thumbnail === 'yes' ) {
			$classes[] = 'selection_thumbnail_hidden';
		}

		return apply_filters( 'woocommerce_composite_component_classes', $classes, $component_id, $this );
	}

	/**
	 * True if the composite is priced per product.
	 *
	 * @return boolean
	 */
	public function is_priced_per_product() {

		$is_priced_per_product = $this->per_product_pricing === 'yes' ? true : false;

		return $is_priced_per_product;
	}

	/**
	 * True if the composite is priced per product.
	 *
	 * @return boolean
	 */
	public function is_shipped_per_product() {

		$is_shipped_per_product = $this->per_product_shipping === 'yes' ? true : false;

		return $is_shipped_per_product;
	}

	/**
	 * Get the add to url used mainly in loops.
	 *
	 * @return  string
	 */
	public function add_to_cart_url() {

		return apply_filters( 'woocommerce_composite_add_to_cart_url', get_permalink( $this->id ), $this );
	}

	/**
	 * Get the add to cart button text
	 *
	 * @return  string
	 */
	public function add_to_cart_text() {

		$text = __( 'Read More', 'woocommerce' );

		if ( $this->is_purchasable() && $this->is_in_stock() ) {
			$text =  __( 'Select options', 'woocommerce' );
		}

		return apply_filters( 'woocommerce_composite_add_to_cart_text', $text, $this );
	}


	// Deprecated functions

	/**
	 * @deprecated
	 */
	public function get_bto_scenario_data() {
		_deprecated_function( 'get_bto_scenario_data', '2.5.0', 'get_composite_scenario_data' );
		return $this->get_composite_scenario_data();
	}

	/**
	 * @deprecated
	 */
	public function get_bto_data() {
		_deprecated_function( 'get_bto_data', '2.5.0', 'get_composite_data' );
		return $this->get_composite_data();
	}

	/**
	 * @deprecated
	 */
	public function get_bto_price_data() {
		_deprecated_function( 'get_bto_price_data', '2.5.0', 'get_composite_price_data' );
		return $this->get_composite_price_data();
	}

}

