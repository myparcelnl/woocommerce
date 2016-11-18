<?php 
/**
 * LOAD WORDPRESS
 */
// define('WP_USE_THEMES', false);
require( '../../../../../wp-blog-header.php');
?>
<html>
<head>
	<?php 
	// Delivery options template
	include('wcmp-delivery-options-template.php');

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
		WooCommerce_MyParcel()->plugin_url() . '/assets/js/wcmp-frontend-iframe.js',
		array( 'jquery' ),
		WC_MYPARCEL_VERSION
	);

	wp_enqueue_script( 'jquery' );

	// wp_enqueue_style( 'colors' );
	// wp_enqueue_style( 'media' );

	// load WordPress header (enqueued scripts etc.)
	wp_head();
	?>
	<style>
		body{
			background-color: white !important;
			word-wrap: break-word;
		}
	</style>
</head>
<body>
<myparcel id="myparcel">Bezig met laden...</myparcel>
</body>
</html>