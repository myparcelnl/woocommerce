<?php 
/**
 * LOAD WORDPRESS
 */
define('WP_USE_THEMES', false);
require( '../../../../../wp-load.php');
?>
<html>
<head>
	<?php 
	// Delivery options template
	include('wcmp-delivery-options-template.php');

	// define script & style formats
	$script_format = '<script type="text/javascript" data-cfasync="false" src="%s"></script>';
	$style_format = '<link rel="stylesheet" id="%s" href="%s" type="text/css" media="all">';

	// load jquery
	printf( $script_format, includes_url( 'js/jquery/jquery.js' ) );

	// MyParcel scripts
	printf( $script_format, add_query_arg( 'ver', WC_MYPARCEL_VERSION, WooCommerce_MyParcel()->plugin_url() . '/assets/delivery-options/js/moment.min.js' ) );
	printf( $script_format, add_query_arg( 'ver', WC_MYPARCEL_VERSION, WooCommerce_MyParcel()->plugin_url() . '/assets/delivery-options/js/webcomponents.min.js' ) );
	printf( $script_format, add_query_arg( 'ver', WC_MYPARCEL_VERSION, WooCommerce_MyParcel()->plugin_url() . '/assets/delivery-options/js/myparcel.js' ) );
	printf( $script_format, add_query_arg( 'ver', WC_MYPARCEL_VERSION, WooCommerce_MyParcel()->plugin_url() . '/assets/js/wcmp-frontend-iframe.js' ) );
	?>
</head>
<body>
<myparcel id="myparcel">Bezig met laden...</myparcel>
</body>
</html>