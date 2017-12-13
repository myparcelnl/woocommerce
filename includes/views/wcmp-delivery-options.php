<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * LOAD WORDPRESS
 */
define('WP_USE_THEMES', false);
?>
<!DOCTYPE html>
<html>
<head>
	<?php
	// define script & style formats
	$script_format = '<script type="text/javascript" data-cfasync="false" src="%s"></script>';
	$style_format = '<link rel="stylesheet" id="%s" href="%s" type="text/css" media="all">';

    printf( $script_format, '../../../../../wp-includes/js/jquery/jquery.js' );
    printf( $script_format, '../../assets/delivery-options/js/moment.min.js' );
    printf( $script_format, '../../assets/delivery-options/js/webcomponents.min.js' );
    printf( $script_format, '../../assets/delivery-options/js/postnl.js' );
    printf( $script_format, '../../assets/js/wcmp-frontend-iframe.js' );
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