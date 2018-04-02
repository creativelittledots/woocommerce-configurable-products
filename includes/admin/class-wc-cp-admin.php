<?php
/**
 * Admin filters and functions.
 *
 * @class 	WC_CP_Admin
 * @version 3.1.0
 * @since   2.2.2
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_CP_Admin {

	private $save_errors = array();
	private $importer = null;

	public function __construct() {
		
		include( 'class-wc-cp-importer.php' );
		
		$this->importer = new WC_CP_Importer();

		// Admin jquery
		add_action( 'admin_enqueue_scripts', array( $this, 'configuration_admin_scripts' ) );

		// Creates the admin Components and Scenarios panel tabs
		add_action( 'woocommerce_product_write_panel_tabs', array( $this, 'configuration_write_panel_tabs' ) );

		// Adds the base price options
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'configuration_pricing_options' ) );

		// Creates the admin Components and Scenarios panels
		add_action( 'woocommerce_product_write_panels', array( $this, 'configuration_write_panel' ) );

		// Allows the selection of the 'configuration product' type
		add_filter( 'product_type_options', array( $this, 'add_configuration_type_options' ) );

		// Processes and saves the necessary post metas from the selections made above
		add_action( 'woocommerce_process_product_meta_configurable', array( $this, 'process_configuration_meta' ) );

		// Allows the selection of the 'configuration product' type
		add_filter( 'product_type_selector', array( $this, 'add_configuration_type' ) );

		// Ajax save configuration config
		add_action( 'wp_ajax_wc_cp_save_configuration', array( $this, 'wc_cp_save_configuration' ) );
		
		add_action( 'admin_post_export_components', array($this, 'export_components') ); // For Export Components
		add_action( 'woocommerce_process_product_meta_configurable', array($this, 'import_components'), 15); // For Import Components
		
		add_action( 'admin_post_export_scenarios', array($this, 'export_scenarios') ); // For Export Scenarios
		add_action( 'woocommerce_process_product_meta_configurable', array($this, 'import_scenarios'), 15); // For Import Scenarios
		
		add_action( 'post_edit_form_tag', array($this, 'add_enctype_to_edit_form') ); // For Import
		
		
        add_filter( 'woocommerce_product_data_tabs', array($this, 'configuration_product_tabs') );
        
        add_action( 'wp_ajax_woocommerce_cp_json_search_products', array( $this, 'json_search_products' ) );
        
        add_action( 'export_filters', array($this, 'display_export_filters') );
        add_filter( 'export_args', array($this, 'export_bulk_args') );
        add_action( 'export_wp', array($this, 'export_config_products'));
        
        add_action( 'admin_init', array( $this, 'register_importers' ) );
        
        add_action( 'woocommerce_product_duplicate', array($this, 'duplicate_configurable_data'), 10, 2);
			
	}
	
	/**
	 * Register WordPress based importers.
	 */
	public function register_importers() {
		
		if ( defined( 'WP_LOAD_IMPORTERS' ) ) {
			
			register_importer( 'woocommerce_config_product_csv',  __( 'WooCommerce configurable products (CSV)', 'woocommerce' ),  __( 'Import <strong> configurable products, components, options and fields</strong> to your store via a csv file.', 'woocommerce' ),  array( $this->importer, 'dispatch' ) );
			
		}
	}
	
	public function display_export_filters() {
		
		include( 'html/html-components-export.php' );
		
	}
	
	public function export_bulk_args( $args = array() ) {
		
		switch( $args['content'] ) {
			
			case 'configurable-products':
		
				if( ! empty( $_REQUEST['product_brand'] ) ) {
							
					$args['product_brand'] = $_REQUEST['product_brand'];
					
				}
				
				if( ! empty( $_REQUEST['product_cat'] ) ) {
					
					$args['product_cat'] = $_REQUEST['product_cat'];
					
				}
				
				if( ! empty( $_REQUEST['product_tag'] ) ) {
					
					$args['product_tag'] = $_REQUEST['product_tag'];
					
				}
				
			break;
			
		}

		return $args;
		
	}
	
	public function export_config_products( $args = array() ) {
		
		switch( $args['content'] ) {
			
			case 'configurable-products':
			
				$query = array(
					'post_type' => 'product',
					'product_type' => 'configurable',
					'showposts' => -1
				);
				
				if( ! empty( $args['product_brand'] ) ) {
					
					$query['product_brand'] = $args['product_brand'];
					
				}
				
				if( ! empty( $args['product_cat'] ) ) {
					
					$query['product_cat'] = $args['product_cat'];
					
				}
				
				if( ! empty( $args['product_tag'] ) ) {
					
					$query['product_tag'] = $args['product_tag'];
					
				}
			
				$this->importer->export_config_products( $query );
			
			break;
			
		}
		
	}
	
	public function configuration_product_tabs( $tabs ) {

    	$tabs['inventory']['class'] = [];

    	return $tabs;
    	
    }

	/**
	 * Admin writepanel scripts.
	 *
	 * @return void
	 */
	public function configuration_admin_scripts( $hook ) {

		global $post, $wc_configurable_products;

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		
		wp_register_script( 'backbone', $wc_configurable_products->plugin_url() . '/assets/js/vendor/backbone.min.js', array( 'underscore' ), $wc_configurable_products->version );
		
		wp_register_script( 'rivets', $wc_configurable_products->plugin_url() . '/assets/js/vendor/rivets.bundled.min.js', array(), $wc_configurable_products->version );
		
		wp_register_script( 'rivets-formatters', $wc_configurable_products->plugin_url() . '/assets/js/vendor/rivets.formatters.min.js', array( 'rivets' ), $wc_configurable_products->version );
		
		wp_register_script( 'rivets-backbone', $wc_configurable_products->plugin_url() . '/assets/js/vendor/rivets.backbone.min.js', array( 'rivets', 'backbone', 'rivets-formatters' ), $wc_configurable_products->version );
		
		wp_register_script( 'wc-cp-sortable', $wc_configurable_products->plugin_url() . '/assets/js/admin/jquery-sortable' . $suffix . '.js', array( 'jquery' ), $wc_configurable_products->version );

		wp_register_script( 'wc-cp-edit-product', $wc_configurable_products->plugin_url() . '/assets/js/admin/wc-cp-edit-product' . $suffix . '.js', array( 'jquery', 'jquery-ui-datepicker', 'wc-admin-meta-boxes', 'rivets-backbone', 'wc-cp-sortable' ), $wc_configurable_products->version );
		
		wp_register_style( 'wc-cp-edit-product', $wc_configurable_products->plugin_url() . '/assets/css/admin/wc-cp-edit-product.css', array( 'woocommerce_admin_styles' ), $wc_configurable_products->version );
		// Get admin screen id
		$screen = get_current_screen();

		// WooCommerce admin pages
		if ( in_array( $screen->id, array( 'product' ) ) ) {
			
			wp_enqueue_script( 'wc-cp-edit-product' );
			
			global $post;
			
			$product = wc_get_product( $post );

			$params = array(
				'save_configuration_nonce'      => wp_create_nonce( 'wc_cp_save_configuration' ),
				'i18n_no_default'           => __( 'No default option&hellip;', 'woocommerce-configurable-products' ),
				'i18n_all'                  => __( 'All Products and Variations', 'woocommerce-configurable-products' ),
				'i18n_none'                 => __( 'None', 'woocommerce-configurable-products' ),
				'is_wc_version_gte_2_3'     => 'yes',
				'i18n_matches_1'            => _x( 'One result is available, press enter to select it.', 'enhanced select', 'woocommerce' ),
				'i18n_matches_n'            => _x( '%qty% results are available, use up and down arrow keys to navigate.', 'enhanced select', 'woocommerce' ),
				'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'woocommerce' ),
				'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'woocommerce' ),
				'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'woocommerce' ),
				'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'woocommerce' ),
				'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'woocommerce' ),
				'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'woocommerce' ),
				'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'woocommerce' ),
				'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'woocommerce' ),
				'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'woocommerce' ),
				'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'woocommerce' ),
				'product'					=> $product->is_type( 'configurable' ) ? $product->get_configuration() : null
			);

			wp_localize_script( 'wc-cp-edit-product', 'wc_cp_admin_params', $params );
			
		}

		if ( in_array( $screen->id, array( 'edit-product', 'product' ) ) ) {
			
			wp_enqueue_style( 'wc-cp-edit-product' );
			
		}
		
	}
	
	/**
	 * Search for products and echo json.
	 *
	 * @param string $term (default: '')
	 * @param string $product_type (default: '*')
	 */
	public function json_search_products( $term = '', $product_type = '*' ) {
		global $wpdb;

		ob_start();

		check_ajax_referer( 'search-products', 'security' );

		if ( empty( $term ) ) {
			$term = wc_clean( stripslashes( $_GET['term'] ) );
		} else {
			$term = wc_clean( $term );
		}
		
		if ( ! empty( $_GET['product_type'] ) ) {
			$product_type = wc_clean( stripslashes( $_GET['product_type'] ) );
		} else {
			$product_type = wc_clean( $product_type );
		}

		if ( empty( $term ) ) {
			die();
		}

		$like_term = '%' . $wpdb->esc_like( $term ) . '%';

		if ( is_numeric( $term ) ) {
			$query = $wpdb->prepare( "
				SELECT ID FROM {$wpdb->posts} posts LEFT JOIN {$wpdb->postmeta} postmeta ON posts.ID = postmeta.post_id
				WHERE (
					posts.post_parent = %s
					OR posts.ID = %s
					OR posts.post_title LIKE %s
					OR (
						postmeta.meta_key = '_sku' AND postmeta.meta_value LIKE %s
					)
				)
			", $term, $term, $term, $like_term );
		} else {
			$query = $wpdb->prepare( "
				SELECT ID FROM {$wpdb->posts} posts LEFT JOIN {$wpdb->postmeta} postmeta ON posts.ID = postmeta.post_id
				WHERE (
					posts.post_title LIKE %s
					or posts.post_content LIKE %s
					OR (
						postmeta.meta_key = '_sku' AND postmeta.meta_value LIKE %s
					)
				)
			", $like_term, $like_term, $like_term );
		}

		$query .= " AND posts.post_type IN ('" . implode( "','", array_map( 'esc_sql', array( 'product' ) ) ) . "')";

		if ( ! empty( $_GET['exclude'] ) ) {
			$query .= " AND posts.ID NOT IN (" . implode( ',', array_map( 'intval', explode( ',', $_GET['exclude'] ) ) ) . ")";
		}

		if ( ! empty( $_GET['include'] ) ) {
			$query .= " AND posts.ID IN (" . implode( ',', array_map( 'intval', explode( ',', $_GET['include'] ) ) ) . ")";
		}
		
		if( $product_type !== '*' ) {
			$query .= $wpdb->prepare( " AND posts.ID IN ( SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN ( SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'product_type' AND term_id IN ( SELECT term_id FROM {$wpdb->terms} WHERE slug = %s ) ) )", $product_type );
		}

		if ( ! empty( $_GET['limit'] ) ) {
			$query .= " LIMIT " . intval( $_GET['limit'] );
		}

		$posts          = array_unique( $wpdb->get_col( $query ) );
		$found_products = array();

		if ( ! empty( $posts ) ) {
			foreach ( $posts as $post ) {
				$product = wc_get_product( $post );

				if ( ! current_user_can( 'read_product', $post ) ) {
					continue;
				}

				if ( ! $product || ( $product->is_type( 'variation' ) && empty( $product->parent ) ) ) {
					continue;
				}

				$found_products[ $post ] = array( 'text' => rawurldecode( $product->get_formatted_name() ), 'title' => rawurldecode( $product->get_title() ) );
			}
		}

		$found_products = apply_filters( 'woocommerce_json_search_found_products', $found_products );

		wp_send_json( $found_products );
	}

	/**
	 * Adds the configuration Product write panel tabs.
	 *
	 * @return string
	 */
	public function configuration_write_panel_tabs() {

		echo '<li class="show_if_configurable configuration_components"><a href="#configuration_components"><span>'.__( 'Components', 'woocommerce-configurable-products' ).'</span></a></li>';
		echo '<li class="show_if_configurable configuration_scenarios"><a href="#configuration_scenarios"><span>'.__( 'Scenarios', 'woocommerce-configurable-products' ).'</span></a></li>';
	}

	/**
	 * Adds the base and sale price option writepanel options.
	 *
	 * @return void
	 */
	public function configuration_pricing_options() {

		echo '<div class="options_group show_if_configurable">';

		// Price
		woocommerce_wp_text_input( array( 'id' => '_base_regular_price', 'class' => 'short', 'label' => __( 'Base Regular Price', 'woocommerce-configurable-products' ) . ' (' . get_woocommerce_currency_symbol().')', 'data_type' => 'price' ) );

		// Sale Price
		woocommerce_wp_text_input( array( 'id' => '_base_sale_price', 'class' => 'short', 'label' => __( 'Base Sale Price', 'woocommerce-configurable-products' ) . ' (' . get_woocommerce_currency_symbol() . ')', 'data_type' => 'price' ) );

		echo '</div>';
	}

	/**
	 * Components and Scenarios write panels.
	 *
	 * @return void
	 */
	public function configuration_write_panel() {

		global $wc_configurable_products, $post, $wpdb;
		
		include( 'html/html-components-admin.php' );

		include( 'html/html-scenarios-admin.php' );
		
	}

	/**
	 * Product options for post-1.6.2 product data section.
	 *
	 * @param  array $options
	 * @return array
	 */
	public function add_configuration_type_options( $options ) {
		
		global $post;

		$options[ 'wc_cp_per_product_pricing' ] = array(
			'id'            => '_wc_cp_per_product_pricing',
			'wrapper_class' => 'show_if_configurable',
			'label'         => __( 'Per-Item Pricing', 'woocommerce-configurable-products' ),
			'description'   => __( 'When <strong>Per-Item Pricing</strong> is checked, the configurable product will be priced according to the cost of its contents. To add a fixed amount to the configuration price when thr <strong>Per-Item Pricing</strong> option is checked, use the Base Price fields below.', 'woocommerce-configurable-products' ),
			'default'       => 'no'
		);

		return $options;
	}

	/**
	 * Adds the 'configuration product' type to the menu.
	 *
	 * @param  array 	$options
	 * @return array
	 */
	public function add_configuration_type( $options ) {

		$options[ 'configurable' ] = __( 'Configurable product', 'woocommerce-configurable-products' );

		return $options;
	}

	/**
	 * Process, verify and save configuration product data.
	 *
	 * @param  int 	$post_id
	 * @return void
	 */
	public function process_configuration_meta( $post_id ) {

		// Per-Item Pricing

		if ( isset( $_POST[ '_wc_cp_per_product_pricing' ] ) ) {

			update_post_meta( $post_id, '_wc_cp_per_product_pricing', 'yes' );

			update_post_meta( $post_id, '_base_sale_price', $_POST[ '_base_sale_price' ] === '' ? '' : stripslashes( wc_format_decimal( $_POST[ '_base_sale_price' ] ) ) );
			
			update_post_meta( $post_id, '_base_regular_price', $_POST[ '_base_regular_price' ] === '' ? '' : stripslashes( wc_format_decimal( $_POST[ '_base_regular_price' ] ) ) );

			if ( $_POST[ '_base_sale_price' ] !== '' ) {
				
				update_post_meta( $post_id, '_base_price', stripslashes( wc_format_decimal( $_POST[ '_base_sale_price' ] ) ) );
				
			} else {
				
				update_post_meta( $post_id, '_base_price', stripslashes( wc_format_decimal( $_POST[ '_base_regular_price' ] ) ) );
				
			}

		} else {

			update_post_meta( $post_id, '_wc_cp_per_product_pricing', 'no' );
			
		}
		
		$product = wc_get_product($post_id);
		
		$product->sync_configurable(true);

	}

	/**
	 * Save components and scenarios.
	 *
	 * @param  int   $post_id
	 * @param  array $configuration
	 * @return boolean
	 */
	public function save_configuration( $post_id, $configuration ) {
		
		try {

			// configuration selection mode
	
			update_post_meta( $post_id, '_wc_cp_build_sku', ! empty( $configuration[ 'build_sku' ] ) ? true : false );
			update_post_meta( $post_id, '_wc_cp_sku_start', isset( $configuration[ 'base_sku' ] ) ? $configuration[ 'base_sku' ] : '' );
			
			$product = wc_get_product( $post_id );
			
			$product->save_configuration( $configuration );
			
			foreach( $product->get_errors() as $error ) {
				
				$this->add_error( $error );
				
			}
	
			if ( ! isset( $configuration[ 'components' ] ) || count( $configuration[ 'components' ] ) == 0 ) {
	
				throw new Exception( __( 'Please create at least one Component before publishing. To add a Component, go to the Components tab and click on the Add Component button.', 'woocommerce-configurable-products' ) );
	
				if ( isset( $configuration[ 'post_status' ] ) && $configuration[ 'post_status' ] == 'publish' ) {
					
					global $wpdb;
					
					$wpdb->update( $wpdb->posts, array( 'post_status' => 'draft' ), array( 'ID' => $post_id ) );
					
				}
	
				return false;
			}
			
		} catch(Exception $e) {
			
			$this->add_error( $e->getMessage() );
			
		}

		return wc_get_product( $post_id );
		
	}

	/**
	 * Handles saving configuration config via ajax.
	 *
	 * @return void
	 */
	public function wc_cp_save_configuration() {

		check_ajax_referer( 'wc_cp_save_configuration', 'security' );
		
		$data = json_decode(file_get_contents('php://input'), true);

		$product = $this->save_configuration( absint( $data[ 'id' ] ), $data );
		
		if( $this->save_errors ) {
			
			status_header( 400 );
			
			wp_send_json_error( array( 'message' => reset( $this->save_errors ) ) );
			
		} else {
			
			wp_send_json( array_replace_recursive( $data, json_decode( json_encode( $product->get_configuration() ), true ) ) );
			
		}
		
	}

	/**
	 * Add and return admin errors.
	 *
	 * @param  string $error
	 * @return string
	 */
	private function add_admin_error( $error ) {

		WC_Admin_Meta_Boxes::add_error( $error );

		return $error;
	}

	/**
	 * Add custom save errors via filters.
	 *
	 * @param string $error
	 */
	public function add_error( $error ) {

		$this->save_errors[] = $this->add_admin_error( $error );
	}
	
	/**
	 * Retreieve option styles for option styles
	 *
	 * @return array()
	 */
	public function get_option_styles() {
		
		$styles = array(
			'dropdown' => array(
				'title' => 'Dropdown',
				'description' => 'Component Options are listed in dropdown menus without any pagination. Use this setting if your Components include just a few product options. Also recommended if you want to keep the layout as compact as possible.',
			),
			'radios' => array(
				'title' => 'Radio Buttons',
				'description' => 'Component Options are presented as radio buttons, all in list. Radio buttons are disabled when outside of scenarios.',
			),
			'checkboxes' => array(
				'title' => 'Checkboxes',
				'description' => 'Component Options are presented as checkboxes, where many products can be selected, all in a vertical list. Checkboxes are disabled when outside of scenarios.',
			),
/*
			'thumbnails' => array(
				'title' => 'Thumbnails',
				'description' => 'Component Options are presented as thumbnails, all in a list. Thumbnails are disabled when outside of scenarios.',
			),
*/
			'number' => array(
				'title' => 'Number Field',
				'description' => 'Component Option is presented as number field, where the user can enter a number according to the step interval you set.',
			),
			'text' => array(
				'title' => 'Text Field',
				'description' => 'Component Option is presented as text field, where the user can enter a string.',
			)
		);
		
		return apply_filters('woocommerce_configuration_products_selection_modes', $styles);
		
	}
	
	/**
	 * Retreieve option style description for option styles
	 *
	 * @return array()
	 */
	public function get_option_styles_descriptions() {
		
		$description = '';
		
		$i = 1;
		
		$option_styles = $this->get_option_styles();
		
		foreach($option_styles as $style) {
			
			$description .= '<strong>' . $style['title'] . '</strong>:</br>' . $style['description'];
			
			if(count($option_styles) > $i) {
				
				$description .= '</br></br>';
				
			}
			
			$i++;
			
		}
		
		return apply_filters('woocommerce_configuration_products_option_style_description', $description);
		
	}
	
	public function export_components() {
		
		if( ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'export_components' ) ) {
		
			if( ! empty( $_REQUEST['product_id'] ) ) {
				
				$this->importer->export_components( $_REQUEST['product_id'] );
				
			} else {
			
				wp_die( __( 'You did not provide a product id in your request, must have been a mistake? <a href="' . admin_url('edit.php?post_type=product') . '">Go back to the products screen</a>' ) );	
				
			}	
				
		} else {
		
			wp_die( __( 'There was a security error, please try again. <a href="' . ( isset( $_REQUEST['product_id'] ) ? get_edit_post_link( $_REQUEST['product_id'] ) : admin_url('edit.php?post_type=product')) . '">Go back</a>' ) );	
			
		}
		
	}
	
	public function export_scenarios() {
		
		if( ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'export_scenarios' ) ) {
		
			if( ! empty( $_REQUEST['product_id'] ) ) {
				
				$this->importer->export_scenarios( $_REQUEST['product_id'] );
				
			} else {
			
				wp_die( __( 'You did not provide a product id in your request, must have been a mistake? <a href="' . admin_url('edit.php?post_type=product') . '">Go back to the products screen</a>' ) );	
				
			}	
				
		} else {
		
			wp_die( __( 'There was a security error, please try again. <a href="' . ( isset( $_REQUEST['product_id'] ) ? get_edit_post_link( $_REQUEST['product_id'] ) : admin_url('edit.php?post_type=product')) . '">Go back</a>' ) );	
			
		}
		
	}

	public function import_components( $product_id ) {
		
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
			return;
		
		if( isset( $_FILES['import_components'] ) && $_FILES['import_components']['size'] ) {
			
			$this->importer->import_components( $product_id, $_FILES['import_components']['tmp_name'] );
			
		}
		
	}
	
	public function import_scenarios( $product_id ) {
		
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
			return;
		
		if( isset( $_FILES['import_scenarios'] ) && $_FILES['import_scenarios']['size'] ) {
			
			$this->importer->import_scenarios( $product_id, $_FILES['import_scenarios']['tmp_name'] );
			
		}
		
	}
	
	public function add_enctype_to_edit_form($post) {
		
		if($post->post_type == 'product') {
			
			echo ' enctype="multipart/form-data"';
			
		}
		
	}
	
	public function duplicate_configurable_data($duplicate, $product) {
		
		if( $duplicate->is_type( 'configurable' ) ) {

			$duplicate->save_configuration( $product->get_raw_configuration() );
			
		}
		
	}
	
}
