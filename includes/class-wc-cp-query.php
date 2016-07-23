<?php
/**
 * WP_Query wrapper for fetching component option ids.
 *
 * Supports two query types: 1) By product ID and 2) by product category ID.
 * Note that during composite product initialization, custom queries are used to fetch an unpaginated array of product ids -- @see WC_Composite_Product::sync_composite()
 * This is necessary to sync prices (when 'woocommerce_composite_hide_price_html' is true) and initialize template parameters.
 * When a component is rendered, sorting / filtering / pagination are handled via WC_Composite_Product::get_current_component_options() which uses the results of the initialization query.
 * Therefore, all rendering queries are done by fetching product IDs directly.
 *
 * You can add your own custom query types by hooking into 'woocommerce_composite_component_query_types' to add the query key/description.
 * Then, implement the query itself by hooking into 'woocommerce_composite_component_options_query_args'.
 *
 * You can add you own custom sorting function by hooking into 'woocommerce_composite_component_orderby' - or you can extend/modfify the behaviour of the 'default' orderby case.
 * To implement it, hook into 'woocommerce_composite_component_options_query_args'.
 *
 * @class    WC_CP_Query
 * @version  3.0.0
 * @since    2.6.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_CP_Query {

	private $query;
	private $storage;

	function __construct( $component_data, $query_args = array() ) {

		$this->query( $component_data, $query_args );
		
	}

	/**
	 * Get queried component option ids.
	 *
	 * @return array
	 */
	public function get_component_options() {

		if ( empty( $this->query->posts ) )
			return array();

		return $this->query->posts;
	}

	/**
	 * Query object getter.
	 *
	 * @return WP_Query
	 */
	public function get_query() {

		return $this->query;
	}

	/**
	 * True if the query was paged.
	 * @return boolean
	 */
	public function has_pages() {

		return $this->query->max_num_pages > 0;
	}

	/**
	 * Get the page number of the query.
	 * @return int
	 */
	public function get_current_page() {

		return $this->query->get( 'paged' );
	}

	/**
	 * Get the total number of pages.
	 * @return int
	 */
	public function get_pages_num() {

		return $this->query->max_num_pages;
	}

	/**
	 * Component options query constructor.
	 *
	 * @param  array $component_data
	 * @param  array $query_args
	 * @return void
	 */
	private function query( $component_data, $query_args ) {

		$defaults = array(
			/* set to false when running raw queries */
			'orderby'           => false,
			/* use false to get all results -- set to false when running raw queries or dropdown-template queries */
			'per_page'          => false,
			/* page number to load, in effect only when 'per_page' is set */
			/* when set to 'selected', 'load_page' will point to the page that contains the current option, passed in 'selected_option' */
			'load_page'         => 1,
			'post_ids'          => ! empty( $component_data[ 'assigned_ids' ] ) ? $component_data[ 'assigned_ids' ] : false,
			'query_type'        => ! empty( $component_data[ 'query_type' ] ) ? $component_data[ 'query_type' ] : 'product_ids',
			/* id of selected option, used when 'load_page' is set to 'selected' */
			'selected_option'   => '',
		);

		$query_args = wp_parse_args( $query_args, $defaults );

		$args = array(
			'post_type'            => 'product',
			'post_status'          => 'publish',
			'ignore_sticky_posts'  => 1,
			'nopaging'             => true,
			'order'                => 'desc',
			'fields'               => 'ids',
			'use_transients_cache' => false,
		);

		/*-----------------------------------------------------------------------------------*/
		/*	Prepare query for product ids
		/*-----------------------------------------------------------------------------------*/

		if ( $query_args[ 'query_type' ] === 'product_ids' ) {

			if ( $query_args[ 'post_ids' ] ) {

				$args[ 'post__in' ] = array_values( $query_args[ 'post_ids' ] );

			} else {

				$args[ 'post__in' ] = array( '0' );
			}
		}

		/*-----------------------------------------------------------------------------------*/
		/*	Pagination
		/*-----------------------------------------------------------------------------------*/

		$load_selected_page = false;

		// Check if we need to find the page that contains the current selection -- 'load_page' must be set to 'selected' and all relevant parameters must be provided

		if ( $query_args[ 'load_page' ] === 'selected' ) {

			if ( $query_args[ 'per_page' ] ) {
				$load_selected_page = true;
			} else {
				$query_args[ 'load_page' ] = 1;
			}
		}

		// Otherwise, just check if we need to do a paginated query -- note that when looking for the page that contains the current selection, we are running an unpaginated query first

		if ( $query_args[ 'per_page' ] && false === $load_selected_page ) {

			$args[ 'nopaging' ]       = false;
			$args[ 'posts_per_page' ] = $query_args[ 'per_page' ];
			$args[ 'paged' ]          = $query_args[ 'load_page' ];
		}

		/*-----------------------------------------------------------------------------------*/
		/*	Optimize 'raw' queries
		/*-----------------------------------------------------------------------------------*/

		if ( false === $query_args[ 'orderby' ] && false === $query_args[ 'per_page' ] ) {

			$args[ 'update_post_term_cache' ] = false;
			$args[ 'update_post_meta_cache' ] = false;
			$args[ 'cache_results' ]          = false;

			if ( class_exists( 'WC_Cache_Helper' ) && ! empty( $component_data[ 'component_id' ] ) ) {
				$args[ 'use_transients_cache' ] = true;
			}
		}

		/*-----------------------------------------------------------------------------------*/
		/*	Modify query and apply filters by hooking at this point
		/*-----------------------------------------------------------------------------------*/

		$args = apply_filters( 'woocommerce_composite_component_options_query_args', $args, $query_args, $component_data );

		/*-----------------------------------------------------------------------------------*/
		/*	Go for it
		/*-----------------------------------------------------------------------------------*/

		if ( $args[ 'use_transients_cache' ] ) {

			$cached_query_name = 'wccp_q_' . $component_data[ 'component_id' ] . '_' . substr( md5( json_encode( $args ) ), 16 ) . '_' . WC_Cache_Helper::get_transient_version( 'wccp_q' );
			$cached_query      = get_transient( $cached_query_name );

			if ( false === $cached_query ) {

				$this->query = new WP_Query( $args );

				set_transient( $cached_query_name, $this->query, ( 60 * 60 * 24 ) );

			} else {

				$this->query = $cached_query;
			}

		} else {

			$this->query = new WP_Query( $args );
		}

		/*-----------------------------------------------------------------------------------------------------------------------------------------------*/
		/*	When told to do so, use the results of the query to find the page that contains the current selection
		/*-----------------------------------------------------------------------------------------------------------------------------------------------*/

		if ( $load_selected_page && $query_args[ 'per_page' ] && $query_args[ 'per_page' ] < $this->query->found_posts ) {

			$results               = $this->get_component_options();
			$selected_option_index = array_search( $query_args[ 'selected_option' ], $results ) + 1;
			$selected_option_page  = ceil( $selected_option_index / $query_args[ 'per_page' ] );

			// Sorting and filtering has been done, so now just run a simple query to paginate the results

			if ( ! empty( $results ) ) {

				$selected_args = array(
					'post_type'           => 'product',
					'post_status'         => 'publish',
					'ignore_sticky_posts' => 1,
					'nopaging'            => false,
					'posts_per_page'      => $query_args[ 'per_page' ],
					'paged'               => $selected_option_page,
					'order'               => 'desc',
					'orderby'             => 'post__in',
					'post__in'            => $results,
					'fields'              => 'ids',
				);

				$this->query = new WP_Query( $selected_args );
			}
		}
	}

}
