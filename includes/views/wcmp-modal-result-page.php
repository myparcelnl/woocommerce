<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<?php
		wp_enqueue_script(
			'wcmyparcel-export',
			WooCommerce_MyParcel()->plugin_url() . '/assets/js/wcmp-admin.js',
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

		wp_enqueue_style( 'wcmyparcel-admin-styles' );	
		wp_enqueue_style( 'colors' );
		wp_enqueue_style( 'media' );
		wp_enqueue_script( 'jquery' );
		do_action('admin_print_styles');
		do_action('admin_print_scripts');
	?>
</head>
<body style="padding:10px 20px;">
	<?php 
	switch ($request) {
		case 'add_return':
			printf('<h3>%s</h3>', __('Return email successfully sent to customer') );
			break;
		default:
			# code...
			break;
	}
	?>
</body></html>