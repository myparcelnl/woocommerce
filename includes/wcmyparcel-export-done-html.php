<?php
// create consigment list
$consignment_list = array();
foreach ($api->consignments as $order_id => $order_consignments) {
	foreach ($order_consignments as $order_consignment) {
		$consignments[$order_consignment['consignment_id']] = $order_id;
	}
	$consignment_list = array_keys($consignments);
}
$pdf_url = wp_nonce_url( admin_url( 'edit.php?&action=wcmyparcel-label&consignment=' . implode('x', $consignment_list) . '&order_ids=' . implode('x', array_keys($consignment_list)) ), 'wcmyparcel-label' );
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<?php
		require_once(ABSPATH . 'wp-admin/admin.php');
		wp_register_style( 'wcmyparcel-admin-styles', dirname(plugin_dir_url(__FILE__)) .  '/css/wcmyparcel-admin-styles.css', array(), '', 'all' );
		wp_enqueue_style( 'wcmyparcel-admin-styles' );		
		wp_enqueue_style( 'colors' );
		wp_enqueue_style( 'media' );
		do_action('admin_print_styles');
	?>
</head>
<body style="padding:10px 20px;">
	<?php if (!empty($api->consignments)): ?>
	<h1>Export voltooid</h1>
	<?php else: ?>
	<h1>Export mislukt</h1>	
	<?php endif ?>

	<?php
	if (!empty($api->errors)) {
		echo '<p>Er hebben zich fouten voorgedaan bij de volgende orders, deze zijn niet verwerkt:<ul style="margin-left:20px;">';
		foreach($api->errors as $order_id => $error_message) {
			$order = new WC_Order($order_id);
			$order_number = $order->get_order_number();
			echo '<li><strong>'.$order_number.'</strong> <i>'.$error_message.'</i></li>';
		}
		echo '</ul></p>';
	}

	if (!empty($api->consignments)) {
		if (!empty($api->errors)) {
			echo '<p>De overige orders zijn succesvol verzonden naar MyParcel.<br />';
		} else {
			echo '<p>De geselecteerde orders zijn succesvol verzonden naar MyParcel.<br />';		
		}
		$target = ( isset($this->settings['download_display']) && $this->settings['download_display'] == 'display') ? 'target="_blank"' : '';

		if ( isset( $this->settings['process'] ) ) {
			?>
			<p>Hieronder kunt u de labels in PDF formaat downloaden.</p>
			<?php printf('<a href="%1$s" %2$s><img src="%3$s"></a>', $pdf_url, $target, dirname(plugin_dir_url(__FILE__)) . '/img/download-pdf.png'); ?>
			<p>
			<strong>Let op!</strong><br />
			Uw pakket met daarop het verzendetiket dient binnen 9 werkdagen na het aanmaken bij PostNL binnen te zijn. Daarna verliest het zijn geldigheid.
			</p>
			<?php
		}
	}
	?>
</body></html>