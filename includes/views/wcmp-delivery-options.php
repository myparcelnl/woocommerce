<?php
/**
 * LOAD WORDPRESS
 */
define('WP_USE_THEMES', false);
require( '../../../../../wp-load.php');
?>
<!DOCTYPE html>
<html>
<head>
	<?php
	// define script & style formats
	$script_format = '<script type="text/javascript" data-cfasync="false" src="%s"></script>';
	$style_format = '<link rel="stylesheet" id="%s" href="%s" type="text/css" media="all">';

	// load jquery
	printf( $script_format, includes_url( 'js/jquery/jquery.js' ) );

	// PostNL scripts
	printf( $script_format, add_query_arg( 'ver', WC_POSTNL_VERSION, WooCommerce_PostNL()->plugin_url() . '/assets/delivery-options/js/moment.min.js' ) );
	printf( $script_format, add_query_arg( 'ver', WC_POSTNL_VERSION, WooCommerce_PostNL()->plugin_url() . '/assets/delivery-options/js/webcomponents.min.js' ) );
	printf( $script_format, add_query_arg( 'ver', WC_POSTNL_VERSION, WooCommerce_PostNL()->plugin_url() . '/assets/delivery-options/js/postnl.js' ) );
	printf( $script_format, add_query_arg( 'ver', WC_POSTNL_VERSION, WooCommerce_PostNL()->plugin_url() . '/assets/js/wcmp-frontend-iframe.js' ) );

	$autoload_google_fonts = isset(WooCommerce_PostNL()->checkout_settings['autoload_google_fonts']) ? 'true' : 'false';
	printf( '<script type="text/javascript">var autoload_google_fonts = %s</script>',$autoload_google_fonts);
	?>
</head>
<body>
<?php
// Include delivery options template
include('wcmp-delivery-options-template.php');
?>
<postnl id="postnl">Bezig met laden...</postnl>
</body>
</html>