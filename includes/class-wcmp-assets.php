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
		if ( is_checkout() /* && isset(WooCommerce_MyParcel()->checkout_settings['delivery_options']) */ ) {
			// MyParcel bundled scripts
			wp_enqueue_script(
				'wc-myparcel-delivery-options-moment',
				WooCommerce_MyParcel()->plugin_url() . '/assets/delivery-options/js/moment.min.js',
				array( 'jquery' ),
				WC_MYPARCEL_VERSION
			);
			wp_enqueue_script(
				'wc-myparcel-delivery-options-webcomponents',
				WooCommerce_MyParcel()->plugin_url() . '/assets/delivery-options/js/webcomponents.min.js',
				array(),
				WC_MYPARCEL_VERSION
			);
			wp_enqueue_script(
				'wc-myparcel-delivery-options',
				WooCommerce_MyParcel()->plugin_url() . '/assets/delivery-options/js/myparcel.js',
				array( 'jquery','wc-myparcel-delivery-options-webcomponents','wc-myparcel-delivery-options-moment' ),
				WC_MYPARCEL_VERSION
			);

			wp_enqueue_script(
				'wc-myparcel-frontend',
				WooCommerce_MyParcel()->plugin_url() . '/assets/js/wcmp-frontend.js',
				array( 'jquery','wc-myparcel-delivery-options' ),
				WC_MYPARCEL_VERSION
			);

		}
		/* for reference
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
		$screen = get_current_screen();

		if( $post_type == 'shop_order' || ( is_object( $screen ) && $screen->id == 'woocommerce_page_woocommerce_myparcel_settings' ) ) {
			// WC2.3+ load all WC scripts for shipping_method search!
			if ( version_compare( WOOCOMMERCE_VERSION, '2.3', '>=' ) ) {
				wp_enqueue_script( 'woocommerce_admin' );
				wp_enqueue_script( 'iris' );
				if (!wp_script_is( 'wc-enhanced-select', 'registered' )) {
					$suffix       = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
					wp_register_script( 'wc-enhanced-select', WC()->plugin_url() . '/assets/js/admin/wc-enhanced-select' . $suffix . '.js', array( 'jquery', 'select2' ), WC_VERSION );
				}
				wp_enqueue_script( 'wc-enhanced-select' );
				wp_enqueue_script( 'jquery-ui-sortable' );
				wp_enqueue_script( 'jquery-ui-autocomplete' );
				wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );
			}

			wp_enqueue_script( 'thickbox' );
			wp_enqueue_style( 'thickbox' );
			wp_enqueue_script(
				'wcmyparcel-export',
				WooCommerce_MyParcel()->plugin_url() . '/assets/js/wcmyparcel-script.js',
				array( 'jquery', 'thickbox' ),
				WC_MYPARCEL_VERSION
			);
			wp_localize_script(
				'wcmyparcel-export',
				'wc_myparcel',
				array(  
					'ajax_url'			=> admin_url( 'admin-ajax.php' ),
					'nonce'				=> wp_create_nonce('wc_myparcel'),
					'download_display'	=> isset(WooCommerce_MyParcel()->general_settings['download_display'])?WooCommerce_MyParcel()->general_settings['download_display']:'',
				)
			);

			wp_enqueue_style(
				'wcmp-admin-styles',
				WooCommerce_MyParcel()->plugin_url() . '/assets/css/wcmp-admin-styles.css',
				array(),
				WC_MYPARCEL_VERSION,
				'all'
			);

			// Legacy styles (WC 2.1+ introduced MP6 style with larger buttons)
			if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '<=' ) ) {
				wp_enqueue_style(
					'wcmp-admin-styles-legacy',
					WooCommerce_MyParcel()->plugin_url() . '/assets/css/wcmp-admin-styles-legacy.css',
					array(),
					WC_MYPARCEL_VERSION,
					'all'
				);
			}




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