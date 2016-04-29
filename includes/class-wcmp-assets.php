<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'WooCommerce_MyParcel_Assets' ) ) :

class WooCommerce_MyParcel_Assets {
	
	function __construct()	{
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'backend_scripts_styles' ) );
	}

	/**
	 * Load styles & scripts
	 */
	public function frontend_scripts_styles ( $hook ) {
		/* for reference
		wp_enqueue_style(
			'wcmyparcel',
			WooCommerce_MyParcel()->plugin_url() . '/assets/css/wcmyparcel-main.css',
			array(),
			WC_MYPARCEL_VERSION
		);

		wp_enqueue_script(
			'wcmyparcel',
			WooCommerce_MyParcel()->plugin_url() . '/assets/js/wcmyparcel.js',
			array( 'jquery' ),
			WC_MYPARCEL_VERSION
		);

		wp_localize_script(
			'wcmyparcel',
			'wcmyparcel_ajax',
			array(  
				'ajaxurl'        => $ajax_url,
				'nonce'          => wp_create_nonce('wcmyparcel'),
			)
		);
		*/
	}

	/**
	 * Load styles & scripts
	 */
	public function backend_scripts_styles ( $hook ) {
	 	global $post_type;
		if( $post_type == 'shop_order' ) {
			wp_enqueue_script( 'thickbox' );
			wp_enqueue_style( 'thickbox' );
			wp_enqueue_script(
				'wcmyparcel-export',
				WooCommerce_MyParcel()->plugin_url() . '/assets/js/wcmyparcel-script.js',
				array( 'jquery', 'thickbox' ),
				WC_MYPARCEL_VERSION
			);

			if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '<=' ) ) {
				// Old versions
				wp_register_style(
					'wcmyparcel-admin-styles',
					WooCommerce_MyParcel()->plugin_url() . '/assets/css/wcmyparcel-admin-styles.css',
					array(),
					WC_MYPARCEL_VERSION,
					'all'
				);
			} else {
				// WC 2.1+, MP6 style with larger buttons
				wp_register_style(
					'wcmyparcel-admin-styles',
					WooCommerce_MyParcel()->plugin_url() . '/assets/css/wcmyparcel-admin-styles-wc21.css',
					array(),
					WC_MYPARCEL_VERSION,
					'all'
				);
			}				

			wp_enqueue_style( 'wcmyparcel-admin-styles' );  
		}

		/* for reference
		// only load on our own settings page
		if ( $hook == 'woocommerce_page_woocommerce_myparcel_settings' ) {
			wp_enqueue_style(
				'wcmyparcel-icons',
				WooCommerce_MyParcel()->plugin_url() . '/assets/css/wcmyparcel-icons-pro.css',
				array(),
				WC_MYPARCEL_VERSION
			);

			wp_enqueue_style(
				'wcmyparcel-admin',
				WooCommerce_MyParcel()->plugin_url() . '/assets/css/wcmyparcel-admin.css',
				array(),
				WC_MYPARCEL_VERSION
			);
			
			wp_enqueue_script(
				'wcmyparcel-admin',
				WooCommerce_MyParcel()->plugin_url() . '/assets/js/wcmyparcel-admin.js',
				array( 'common', 'jquery', 'jquery-ui-tabs' ),
				WC_MYPARCEL_VERSION
			);
		}
		*/
	}
}

endif; // class_exists

return new WooCommerce_MyParcel_Assets();