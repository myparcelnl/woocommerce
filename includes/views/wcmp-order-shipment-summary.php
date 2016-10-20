<?php
// Status
printf('%1$s: <a href="%2$s" class="myparcel_tracktrace_link" target="_blank" title="%3$s">%4$s</a><br/>', __('Status','woocommerce-myparcel'), $tracktrace_url, $shipment['tracktrace'], $shipment['status']);
// Shipment type
printf('%s: %s', __( 'Shipment type', 'woocommerce-myparcel' ), $package_types[$shipment['shipment']['options']['package_type']] );
?>
<ul class="wcmyparcel_shipment_summary">
	<?php
	// echo '<pre>';var_dump($shipment);echo '</pre>';die();
	// Options
	$option_strings = array(
		'large_format'		=> __( 'Extra large size', 'woocommerce-myparcel' ),
		'only_recipient'	=> __( 'Home address only', 'woocommerce-myparcel' ),
		'signature'			=> __( 'Signature on delivery', 'woocommerce-myparcel' ),
		'return'			=> __( 'Return if no answer', 'woocommerce-myparcel' ),
	);

	foreach ($option_strings as $key => $label) {
		if (isset($shipment['shipment']['options'][$key]) && (int) $shipment['shipment']['options'][$key] == 1) {
			printf('<li class="%s">%s</li>', $key, $label);
		}
	}

	// Insurance
	if (!empty($shipment['shipment']['options']['insurance'])) {
		$price = number_format ( $shipment['shipment']['options']['insurance']['amount'] / 100, 2 );
		printf('<li>%s: € %s</li>', __('Insured for', 'woocommerce-myparcel'), $price);
	}

	// Custom ID
	if (!empty($shipment['shipment']['options']['label_description'])) {
		printf('<li>%s: %s</li>', __( 'Custom ID (top left on label)', 'woocommerce-myparcel'), $shipment['shipment']['options']['label_description']);
	}
	?>
</ul>
