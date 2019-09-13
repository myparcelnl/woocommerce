<?php

if ( ! defined('ABSPATH')) exit; // Exit if accessed directly

// Status
printf('%1$s: <a href="%2$s" class="myparcelbe_tracktrace_link" target="_blank" title="%3$s">%4$s</a><br/>', _wcmp('Status'), $tracktrace_url, $shipment['tracktrace'], $shipment['status']);
// Shipment type
printf('%s: %s', _wcmp('Shipment type'), $package_types[$shipment['shipment']['options']['package_type']]);
?>
<ul class="wcmyparcelbe_shipment_summary">
    <?php
    // Options
    $option_strings = array(
        'signature'      => _wcmp('Signature on delivery')
    );

    foreach ($option_strings as $key => $label) {
        if (isset($shipment['shipment']['options'][$key]) && (int) $shipment['shipment']['options'][$key] == 1) {
            printf('<li class="%s">%s</li>', $key, $label);
        }
    }

    // Insurance
    if ( ! empty($shipment['shipment']['options']['insurance'])) {
        $price = number_format($shipment['shipment']['options']['insurance']['amount'] / 100, 2);
        printf('<li>%s: € %s</li>', _wcmp('Insured for'), $price);
    }

    // Custom ID
    if ( ! empty($shipment['shipment']['options']['label_description'])) {
        printf('<li>%s: %s</li>', _wcmp('Custom ID (top left on label)'), $shipment['shipment']['options']['label_description']);
    }
    ?>
</ul>
