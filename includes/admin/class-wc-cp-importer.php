<?php
/**
 * Config Product Importer
 *
 * @class 	WC_CP_Importer
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_CP_Importer {

	public function __construct() {
		
	}
	
	public function dispatch() {
		
		$this->header();

		$step = empty( $_GET['step'] ) ? 0 : (int) $_GET['step'];
		switch ( $step ) {
			case 0:
				$this->greet();
			break;
			case 1:
				check_admin_referer( 'import-upload' );
				if ( $file = $this->handle_upload() )
					check_admin_referer( 'import-upload' );
					set_time_limit(0);
					$this->import_config_products( $file );
			break;
		}

		$this->footer();
		
	}
	
	/**
	 * The main controller for the actual import stage.
	 *
	 * @param string $file Path to the CSV file for importing
	 */
	public function import_config_products( $file ) {
		add_filter( 'http_request_timeout', array( &$this, 'bump_request_timeout' ) );

		$this->import_start( $file );

		wp_suspend_cache_invalidation( true );
		$this->process_config_products();
		wp_suspend_cache_invalidation( false );

		$this->import_end( $file );
	}
	
	/**
	 * Create / update config products based on import information
	 *
	 */
	public function process_config_products() {
		
		$this->rows = apply_filters( 'wp_cp_import_rows', $this->rows );
		
		$rows = [];

		foreach ( $this->rows as $row ) {
			
			if( ! empty( $row['product_id'] ) ) {
				
				$product = wc_get_product( $row['product_id'] );
				
			} else {
				
				$product = new WC_Product_Configurable();
				$product->name  = $row['product_title'];
				$product->save();
				
			}
			
			$rows[$product->get_id()][] = $row;
			
		}
		
		foreach($rows as $product_id => $components) {
			
			$product = wc_get_product( $product_id );
			
			$product->save_components( $product->prepare_components_for_import( $components ) );
			
		}
		
	}
	
	/**
	 * Parses the CSV file and prepares us for the task of processing parsed data
	 *
	 * @param string $file Path to the WXR file for importing
	 */
	public function import_start( $file ) {
		
		if ( ! is_file( $file['file'] ) ) {
			echo '<p><strong>' . __( 'Sorry, there has been an error.', 'woocommerce' ) . '</strong><br />';
			echo __( 'The file does not exist, please try again.', 'woocommerce' ) . '</p>';
			$this->footer();
			die();
		}

		$rows = $this->parse( $file['file'] );

		if ( is_wp_error( $rows ) ) {
			echo '<p><strong>' . __( 'Sorry, there has been an error.', 'woocommerce' ) . '</strong><br />';
			echo esc_html( $rows->get_error_message() ) . '</p>';
			$this->footer();
			die();
		}

		$this->rows = $rows;
	}
	
	/**
	 * Performs post-import cleanup of files and the cache
	 */
	public function import_end( $file ) {
		
		wp_import_cleanup( $file['id'] );

		wp_cache_flush();

		echo '<p>' . __( 'All done.', 'woocommerce' ) . ' <a href="' . admin_url() . '">' . __( 'Have fun!', 'woocommerce' ) . '</a>' . '</p>';
	}
	
	/**
	 * Added to http_request_timeout filter to force timeout at 60 seconds during import
	 * @return int 60
	 */
	public function bump_request_timeout( $val ) {
		return 60;
	}
	
	/**
	 * Handles the CSV upload and initial parsing of the file to prepare for
	 * displaying author import options
	 *
	 * @return bool False if error uploading or invalid file, true otherwise
	 */
	public function handle_upload() {
		$file = wp_import_handle_upload();

		if ( isset( $file['error'] ) ) {
			echo '<p><strong>' . __( 'Sorry, there has been an error.', 'woocommerce' ) . '</strong><br />';
			echo esc_html( $file['error'] ) . '</p>';
			return false;
		} else if ( ! file_exists( $file['file'] ) ) {
			echo '<p><strong>' . __( 'Sorry, there has been an error.', 'woocommerce' ) . '</strong><br />';
			printf( __( 'The export file could not be found at <code>%s</code>. It is likely that this was caused by a permissions problem.', 'woocommerce' ), esc_html( $file['file'] ) );
			echo '</p>';
			return false;
		}

		$rows = $this->parse( $file['file'] );
		if ( is_wp_error( $rows ) ) {
			echo '<p><strong>' . __( 'Sorry, there has been an error.', 'woocommerce' ) . '</strong><br />';
			echo esc_html( $rows->get_error_message() ) . '</p>';
			return false;
		}

		return $file;
	}
	
	/**
	 * Parse a CSV file
	 *
	 * @param string $file Path to CSV file for parsing
	 * @return array Information gathered from the CSV file
	 */
	public function parse( $file ) {
		
		$data = file_get_contents( $file );
			
		$rows = array_map('str_getcsv', explode("\n", $data));
		
		$headers = $rows[0];
		unset($rows[0]);
		
		$headers = array_map(function($header) {
			return str_replace('-', '_', sanitize_title($header));
		}, $headers);
		
		$rows = array_values($rows);
		
		$items = [];
		
		foreach($rows as $i => $row) {
			
			if( count( $row ) == count( $headers ) ) {
				
				$items[$i] = array_combine($headers, $row);
				
			}
			
		}

		return $items;
		
	}
	
	// Display import page title
	public function header() {
		echo '<div class="wrap">';
		screen_icon();
		echo '<h2>' . __( 'Import Configurable Products', 'woocommerce' ) . '</h2>';
	}

	// Close div.wrap
	public function footer() {
		echo '</div>';
	}
	
	/**
	 * Display introductory text and file upload form
	 */
	public function greet() {
		echo '<div class="narrow">';
		echo '<p>'.__( 'Howdy! Upload your CSV file and we&#8217;ll import the configurable products, components and options into this site.', 'woocommerce' ).'</p>';
		echo '<p>'.__( 'Choose a CSV file to upload, then click Upload file and import.', 'woocommerce' ).'</p>';
		wp_import_upload_form( 'admin.php?import=woocommerce_config_product_csv&amp;step=1' );
		echo '</div>';
	}
	
	public function import_components( $product_id, $file ) {
			
		$product = wc_get_product( $product_id );
		
		$product->save_components( $product->prepare_components_for_import( $this->parse( $file ) ) );
		
	}
	
	public function import_scenarios( $product_id, $file ) {

		$product = wc_get_product( $product_id );
		
		global $wpdb;
		
		$data = file_get_contents( $file );
		
		$rows = array_map('str_getcsv', explode("\n", $data));
		
		$headers = $rows[0];
		unset($rows[0]);
		
		unset($headers[0]);
		unset($headers[1]);
		unset($headers[2]);
		unset($headers[3]);
		
		$descriptions = $rows[1];
		unset($rows[1]);
		
		$rows = array_values($rows);
		
		$product->save_scenarios( $product->prepare_scenarios_for_import( $rows ) );
		
	}
	
	public function export_components( $product_id ) {
		
		$product = wc_get_product( $product_id );
				
		$filename = "Components exported for " . $product->get_title() . ".csv";
		
		$data = $product->get_component_data_for_export();
    
		$this->outputCsv($filename, $data);
		
		exit();
		
	}
	
	public function export_scenarios( $product_id ) {
		
		$product = wc_get_product( $product_id );
				
		$data = $product->get_scenario_data_for_export();
		
		$filename = "Scenarios exported for " . $product->get_title() . ".csv";
    
		$this->outputCsv($filename, $data);
		
		exit();
		
	}
	
	public function export_config_products( $args = array() ) {
		
		$posts = get_posts( $args );
				
		$data = [];
		
		foreach($posts as $i => $post) {
			
			$product = wc_get_product( $post );
			
			$rows = $product->get_component_data_for_export( true );
			
			$headers = $rows[0];
			
			unset($rows[0]);
			
			if( ! $i ) {
				
				array_unshift($headers, 'Product ID', 'Product Title');
				
				$data[] = $headers;
				
			}
			
			$rows = array_map(function($row) use($product) {
				array_unshift($row, $product->get_id(), $product->get_title());
				return $row;
			}, $rows);
			
			$data = array_merge($data, $rows);
			
		}
		
		$filename = "Components " . date('Y-m-d') . ".csv";

		$this->outputCsv($filename, $data);
		
		exit();
		
	}
	
	private function outputCsv($filename, $data) {
		ob_clean();
		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Cache-Control: private', false);
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="' . $filename . '"');    
		$output = fopen("php://output", "w");
		foreach ($data as $row) {
	    	fputcsv($output, $row); // here you can change delimiter/enclosure
	   	}	
	   	fclose($output);
		ob_flush();
	}
	
}