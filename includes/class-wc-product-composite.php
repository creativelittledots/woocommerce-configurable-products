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
				
				$args = array(
					'price_format' => '%1$s<span rv-text="product:price">%2$s</span>'
				);

				if ( $this->contains_nyp || $this->has_multiple_quantities )
					$price = wc_price( $prices[0], $args );
				else
					$price = $prices[0] !== $prices[1] ? sprintf( _x( '%1$s&ndash;%2$s', 'Price range: from-to', 'woocommerce' ), wc_price( $prices[0], $args ), wc_price( $prices[1], $args ) ) : wc_price( $prices[0], $args );

				// Sale
				$prices = array( $this->min_composite_regular_price, $this->max_composite_regular_price );

				if ( $this->contains_nyp || $this->has_multiple_quantities ) {
					$saleprice = wc_price( $prices[0], $args );
				} else {
					sort( $prices );
					$saleprice = $prices[0] !== $prices[1] ? sprintf( _x( '%1$s&ndash;%2$s', 'Price range: from-to', 'woocommerce' ), wc_price( $prices[0], $args ), wc_price( $prices[1], $args ) ) : wc_price( $prices[0], $args );
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
     * Functions for getting parts of a price, in html, used by get_price_html.
     *
     * @return string
     */
    public function get_price_html_from_text() {
	    
        return '<span class="from" rv-show="product:errors">' . _x( 'From:', 'min_price', 'woocommerce' ) . ' </span>';
        
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
			
			$options = array();
			
			foreach($component_data['assigned_ids'] as $id) {
				
				if( $product = wc_get_product($id) ) {
				
					$options[] = array(
						'title' => $product->get_title(),
						'price' => (float) $product->get_price()
					);
					
				}
				
			}
			
			$composite_data[] = apply_filters( 'woocommerce_composite_component_data', [
				'id' => $component_data['component_id'],
				'style' => ! empty( $component_data['option_style'] ) ? $component_data['option_style'] : $this->selections_style,
				'title' => $component_data['title'],
				'description' => $component_data['description'],
				'optional' => $component_data['optional'] === 'yes' ? true : false,
				'options' => $options
			], $component_id, $this );
			
		}

		return array_values($composite_data);
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
			
			$per_page = false;

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
	 * True if the composite is priced per product.
	 *
	 * @return boolean
	 */
	public function is_priced_per_product() {

		$is_priced_per_product = $this->per_product_pricing === 'yes' ? true : false;

		return $is_priced_per_product;
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

