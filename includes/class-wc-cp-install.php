<?php
/**
 * Installation related functions and actions.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_CP_Install Class.
 */
class WC_CP_Install {
	
	/**
	 * Hook in tabs.
	 */
	public static function init() {
		
		add_action( 'init', array( __CLASS__, 'install' ), 20 );
		
	}

	/**
	 * Install WC.
	 */
	public static function install() {
		
		global $wpdb;

		if ( ! defined( 'WC_INSTALLING' ) ) {
			define( 'WC_INSTALLING', true );
		}

		self::create_tables();
		
	}

	private static function create_tables() {
		
		global $wpdb;

		$wpdb->hide_errors();
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		dbDelta( self::get_schema() );
		
	}

	/**
	 * Get Table schema.
	 * https://github.com/woothemes/woocommerce/wiki/Database-Description/
	 * @return string
	 */
	private static function get_schema() {
		
		global $wpdb;

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			
			$collate = $wpdb->get_charset_collate();
			
		}

		$tables = "
			CREATE TABLE {$wpdb->prefix}woocommerce_components (
				id bigint(20) NOT NULL auto_increment,
				component_id bigint(20) NOT NULL,
				product_id bigint(20) NOT NULL,
				style varchar(200) NOT NULL,
				title varchar(200) NOT NULL,
				description varchar(200) NULL,
				optional tinyint(1) NOT NULL DEFAULT 0,
				sovereign tinyint(1) NOT NULL DEFAULT 0,
				affect_sku tinyint(1) NOT NULL DEFAULT 0,
				sku_order tinyint(1) NOT NULL DEFAULT 0,
				sku_default tinyint(1) NULL,
				position NOT NULL DEFAULT 0,
				PRIMARY KEY  (id)
			) $collate;
			CREATE TABLE {$wpdb->prefix}woocommerce_component_options (
				id bigint(20) NOT NULL auto_increment,
				component_id bigint(20) NOT NULL,
				source varchar(255) NOT NULL DEFAULT 'product',
				entity_id bigint(20) NOT NULL,
				attribute_id bigint(20) NOT NULL,
				selected tinyint(1) NOT NULL DEFAULT 0,
				recommended tinyint(1) NOT NULL DEFAULT 0,
				sku varchar(200) NULL,
				price varchar(200) NULL,
				formula varchar(200) NULL,
				position NOT NULL DEFAULT 0,
				PRIMARY KEY  (id)
			) $collate;
		";

		return $tables;
		
	}

}

WC_CP_Install::init();
