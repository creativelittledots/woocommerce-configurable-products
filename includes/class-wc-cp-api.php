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

		$scenarios			= array();
		$scenario_ids       = empty( $bto_scenario_meta ) ? array() : array_map( 'strval', array_keys( $bto_scenario_meta ) );

		// Store the 'actions' associated with every scenario
		foreach ( $scenario_ids as $scenario_id ) {
			
			$scenarios[ $scenario_id ] = array();
			
			$scenarios[ $scenario_id ]['id'] = $scenario_id;

			$scenarios[ $scenario_id ][ 'actions' ] = array();

			if ( isset( $bto_scenario_meta[ $scenario_id ][ 'scenario_actions' ] ) ) {

				$actions = array();

				foreach ( $bto_scenario_meta[ $scenario_id ][ 'scenario_actions' ] as $action_name => $action_data ) {
					if ( isset( $action_data[ 'is_active' ] ) && $action_data[ 'is_active' ] === 'yes' ) {
						$actions[] = $action_name;
					}
				}

				$scenarios[ $scenario_id ][ 'actions' ] = $actions;

			} else {
				$scenarios[ $scenario_id ][ 'actions' ] = array( 'compat_group' );
			}
		}

		// Find which components in every scenario are 'non shaping components' (marked as unrelated)
		foreach ( $bto_scenario_meta as $scenario_id => $scenario_single_meta ) {

			$scenarios[ $scenario_id ][ 'masked_components' ] = array();

			foreach ( $bto_data as $group_id => $group_data ) {

				if ( isset( $scenario_single_meta[ 'modifier' ][ $group_id ] ) && $scenario_single_meta[ 'modifier' ][ $group_id ] === 'masked' ) {
					$scenarios[ $scenario_id ][ 'masked_components' ][] = ( string ) $group_id;
				}
			}
		}
		
		return $scenarios;
		
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
			return array();
		}

		$scenarios = array();

		foreach ( $scenario_meta as $scenario_id => $scenario_data ) {

			if ( $this->product_active_in_scenario( $scenario_data, $group_id, $product_id, $variation_id, $product_type ) ) {
				$scenarios[] = ( string ) $scenario_id;
			}
		}

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

		$id = $product_id;

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

				$product_active_in_scenario = false;

			} else {

				$product_active_in_scenario = true;
				
			}
		}

		return $product_active_in_scenario;
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
		
}
