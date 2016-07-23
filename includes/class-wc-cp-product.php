<?php
/**
 * Composited product wrapper class.
 *
 * @class    WC_CP_Product
 * @since    3.0.0
 * @version  2.6.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_CP_Product {

	private $product;

	private $min_price;
	private $max_price;
	private $min_regular_price;
	private $max_regular_price;

	private $min_price_incl_tax;
	private $min_price_excl_tax;

	private $is_nyp;
	private $is_sold_individually;
	private $purchasable;

	private $component_data;
	private $component_id;

	private $per_product_pricing;
	private $composite_id;

	function __construct( $product_id, $component_id, $parent ) {

		$this->product = wc_get_product( $product_id );

		if ( $this->product ) {

			$this->component_data      = $parent->get_component_data( $component_id );
			$this->component_id        = $component_id;
			$this->per_product_pricing = $parent->is_priced_per_product();
			$this->composite_id        = $parent->id;

			$this->init();

		}
	}

	/**
	 * Initialize composited product price data, if needed.
	 *
	 * @return void
	 */
	public function init() {

		global $woocommerce_composite_products;

		// Init prices
		$this->min_price          = 0;
		$this->max_price          = 0;
		$this->min_regular_price  = 0;
		$this->max_regular_price  = 0;

		$this->min_price_incl_tax = 0;
		$this->min_price_excl_tax = 0;

		$id = $this->get_product()->id;

		// Sold individually status
		$this->is_sold_individually = get_post_meta( $id, '_sold_individually', true );

		// Calculate product prices if the parent is priced per product
		if ( $this->per_product_pricing ) {

			$composited_product = $this->product;

			// Get product type
			$product_type = $composited_product->product_type;

			$this->is_nyp      = false;
			$this->purchasable = false;

			if ( $composited_product->is_purchasable() ) {

				$this->purchasable = true;

				/*-----------------------------------------------------------------------------------*/
				/*	Simple Products and Static Bundles
				/*-----------------------------------------------------------------------------------*/

				if ( $product_type === 'simple' || ( $product_type === 'bundle' && $composited_product->is_priced_per_product() == false ) ) {

					$product_price         = $composited_product->get_price();
					$product_regular_price = $composited_product->get_regular_price();

					// Name your price support
					if ( $woocommerce_composite_products->compatibility->is_nyp( $composited_product ) ) {

						$product_price = $product_regular_price = WC_Name_Your_Price_Helpers::get_minimum_price( $id ) ? WC_Name_Your_Price_Helpers::get_minimum_price( $id ) : 0;

						$this->is_nyp = true;
					}

					// Modify prices to respect woocommerce_tax_display_shop setting
					$product_prices           = $woocommerce_composite_products->api->get_composited_item_prices( $composited_product, $product_price );
					$product_regular_prices   = $woocommerce_composite_products->api->get_composited_item_prices( $composited_product, $product_regular_price );

					$this->min_price          = $this->max_price         = $product_prices[ 'shop' ];
					$this->min_regular_price  = $this->max_regular_price = $product_regular_prices[ 'shop' ];

					// Incl
					$this->min_price_incl_tax = $product_prices[ 'incl' ];
					// Excl
					$this->min_price_excl_tax = $product_prices[ 'excl' ];


				/*-----------------------------------------------------------------------------------*/
				/*	Per-Item Priced Bundles
				/*-----------------------------------------------------------------------------------*/

				} elseif ( $product_type === 'bundle' ) {

					$this->min_price         = $composited_product->get_min_bundle_price();
					$this->max_price         = $composited_product->get_max_bundle_price();

					$this->min_regular_price = $composited_product->min_bundle_regular_price;
					$this->max_regular_price = $composited_product->max_bundle_regular_price;


					if ( $composited_product->is_nyp() || $composited_product->contains_nyp() )
						$this->is_nyp = true;

					// Incl
					$this->min_price_incl_tax = $composited_product->get_min_bundle_price_incl_tax();
					// Excl
					$this->min_price_excl_tax = $composited_product->get_min_bundle_price_excl_tax();


				/*-----------------------------------------------------------------------------------*/
				/*	Variable Products
				/*-----------------------------------------------------------------------------------*/

				} elseif ( $product_type === 'variable' ) {

					if ( ! empty( $this->component_data[ 'discount' ] ) ) {

						// Product may need to be synced
						if ( $composited_product->get_variation_regular_price( 'min', false ) === false )
							$composited_product->variable_product_sync();

						// Grab the min/max regular price variation since discounts are calculated on top of the regular price only.
						$min_variation_id            = get_post_meta( $composited_product->id, '_min_regular_price_variation_id', true );
						$min_variation               = $composited_product->get_child( $min_variation_id );

						$min_variation_regular_price = $min_variation->get_regular_price();
						$min_variation_price         = $min_variation->get_price();


						$max_variation_id            = get_post_meta( $composited_product->id, '_max_regular_price_variation_id', true );
						$max_variation               = $composited_product->get_child( $max_variation_id );

						$max_variation_regular_price = $max_variation->get_regular_price();
						$max_variation_price         = $max_variation->get_price();

					} else {

						// Product may need to be synced
						if ( $composited_product->get_variation_price( 'min', false ) === false )
							$composited_product->variable_product_sync();

						// Grab the min/max price variation since there is no discount.
						$min_variation_id            = get_post_meta( $composited_product->id, '_min_price_variation_id', true );
						$min_variation               = $composited_product->get_child( $min_variation_id );

						$min_variation_regular_price = $min_variation->get_regular_price();
						$min_variation_price         = $min_variation->get_price();


						$max_variation_id            = get_post_meta( $composited_product->id, '_max_price_variation_id', true );
						$max_variation               = $composited_product->get_child( $max_variation_id );

						$max_variation_regular_price = $max_variation->get_regular_price();
						$max_variation_price         = $max_variation->get_price();
					}

					// Modify prices to respect woocommerce_tax_display_shop setting
					$min_variation_regular_prices = $woocommerce_composite_products->api->get_composited_item_prices( $min_variation, $min_variation_regular_price );
					$min_variation_prices         = $woocommerce_composite_products->api->get_composited_item_prices( $min_variation, $min_variation_price );

					$max_variation_regular_prices = $woocommerce_composite_products->api->get_composited_item_prices( $max_variation, $max_variation_regular_price );
					$max_variation_prices         = $woocommerce_composite_products->api->get_composited_item_prices( $max_variation, $max_variation_price );

					$this->min_regular_price      = $min_variation_regular_prices[ 'shop' ];
					$this->min_price              = $min_variation_prices[ 'shop' ];

					$this->max_regular_price      = $max_variation_regular_prices[ 'shop' ];
					$this->max_price              = $max_variation_prices[ 'shop' ];

					// Incl
					$this->min_price_incl_tax     = $min_variation_prices[ 'incl' ];
					// Excl
					$this->min_price_incl_tax     =  $min_variation_prices[ 'excl' ];


				/*-----------------------------------------------------------------------------------*/
				/*	Other Product Types
				/*-----------------------------------------------------------------------------------*/

				} else {

					// Make sure these are incl / excl tax depending on shop settings
					// If necessary, use 'woocommerce_composite_products->api->get_composited_item_prices'
					$this->min_price = apply_filters( 'woocommerce_composited_product_min_price', $this->min_price, $this );
					$this->max_price = apply_filters( 'woocommerce_composited_product_max_price', $this->max_price, $this );

					$this->min_regular_price = apply_filters( 'woocommerce_composited_product_min_regular_price', $this->min_regular_price, $this );
					$this->max_regular_price = apply_filters( 'woocommerce_composited_product_max_regular_price', $this->max_regular_price, $this );

					// Prices incl or excl tax, regardless of shop settings
					$this->min_price_incl_tax = apply_filters( 'woocommerce_composited_product_min_price_incl_tax', $this->min_price_incl_tax, $this );
					$this->min_price_excl_tax = apply_filters( 'woocommerce_composited_product_min_price_excl_tax', $this->min_price_excl_tax, $this );

				}
			}
		}
	}

	/**
	 * Generated dropdown price string for composited products in per product pricing mode.
	 *
	 * @return string
	 */
	public function get_price_string() {

		global $woocommerce_composite_products;

		if ( ! $this->exists() )
			return false;

		$price_string = '';
		$component_id = $this->component_id;
		$product_id   = $this->get_product()->id;

		if ( $this->per_product_pricing && $this->purchasable ) {

			$discount = $sale = '';

			$has_multiple = ! $this->is_sold_individually() && $this->component_data[ 'quantity_min' ] > 1;

			$ref_price = $this->min_regular_price;
			$price     = $this->min_price;
			$is_nyp    = $this->is_nyp;
			$is_range  = $this->min_price < $this->max_price;

			if ( ! empty( $this->component_data[ 'discount' ] ) && $ref_price > 0 && ! $is_nyp && $this->get_product() && $this->get_product()->product_type !== 'bundle' )
				$discount = sprintf( __( '(%s%% off)', 'woocommerce-composite-products' ), round( $this->component_data[ 'discount' ], 1 ) );

			if ( ! $discount && $ref_price > $price && $ref_price > 0 && ! $is_nyp )
				$sale = sprintf( __( '(%s%% off)', 'woocommerce-composite-products' ), round( 100 * ( $ref_price - $price ) / $ref_price, 1 ) );

			$pct_off = $discount . $sale;

			$suffix = apply_filters( 'woocommerce_composited_product_price_suffix', $pct_off, $component_id, $product_id, $price, $ref_price, $is_nyp, $is_range, $this ) ;

			$price_string = ' - ' . ( ( $is_range || $is_nyp ) ? _x( 'From:', 'min_price', 'woocommerce' ) : '' ) . ' ' . ( $price == 0 && ! $is_range ? __( 'Free!', 'woocommerce' ) : $woocommerce_composite_products->api->get_composited_item_price_string_price( $price ) . ( $has_multiple ? ' ' . __( '/ pc.', 'woocommerce-composite-products' ) : '' ) ) . ' ' . $suffix;
		}

		return apply_filters( 'woocommerce_composited_product_price_string', $price_string, $product_id, $component_id, $this );
	}

	/**
	 * Get composited product.
	 *
	 * @return WC_Product|false
	 */
	public function get_product() {

		if ( ! $this->exists() ) {
			return false;
		}

		return $this->product;
	}

	/**
	 * Return min price.
	 *
	 * @return double
	 */
	public function get_min_price() {

		return $this->min_price;
	}

	/**
	 * Return min regular price.
	 *
	 * @return double
	 */
	public function get_min_regular_price() {

		return $this->min_regular_price;
	}

	/**
	 * Return max price.
	 *
	 * @return double
	 */
	public function get_max_price() {

		return $this->max_price;
	}

	/**
	 * Return max regular price.
	 *
	 * @return double
	 */
	public function get_max_regular_price() {

		return $this->max_regular_price;
	}

	/**
	 * Return min price including tax.
	 *
	 * @return double
	 */
	public function get_min_price_incl_tax() {

		return $this->min_price_incl_tax;
	}

	/**
	 * Return min price excluding tax.
	 *
	 * @return double
	 */
	public function get_min_price_excl_tax() {

		return $this->min_price_excl_tax;
	}

	/**
	 * True if the composited product is marked as individually-sold item.
	 *
	 * @return boolean
	 */
	public function is_sold_individually() {

		$is_sold_individually = false;

		if ( $this->is_sold_individually === 'yes' ) {
			$is_sold_individually = true;
		}

		return $is_sold_individually;
	}

	/**
	 * True if the composited product is a NYP product.
	 *
	 * @return boolean
	 */
	public function is_nyp() {

		return $this->is_nyp;
	}

	/**
	 * True if the composited product is a valid product.
	 *
	 * @return boolean
	 */
	public function exists() {

		$exists = false;

		if ( ! empty( $this->product ) ) {
			$exists = true;
		}

		return $exists;
	}

}
