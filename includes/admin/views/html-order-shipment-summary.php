<?php

/**
 * The shipment summary that shows when you click (i) in an order.
 */

use MyParcelNL\Sdk\src\Support\Arr;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

$order_id    = $_POST["order_id"];
$shipment_id = $_POST["shipment_id"];

$order           = wc_get_order($order_id);
$shipment        = WCMP()->export->get_shipment_data([$shipment_id], $order)[$shipment_id];
$deliveryOptions = WCMP_Admin::getDeliveryOptionsFromOrder($order);

$trackTrace = Arr::get($shipment, "track_trace'");

if ($trackTrace) {
    $order_has_shipment = true;
    $track_trace_url    = WCMP_Admin::getTrackTraceUrl($order_id, $trackTrace);
}

$option_strings   = [
    "signature" => __("Signature on delivery", "woocommerce-myparcelbe"),
];
$insurance        = Arr::get($shipment, "shipment.options.insurance");
$labelDescription = Arr::get($shipment, "shipment.options.label_description");

/**
 * Status
 */
printf(
    '%1$s: <a href="%2$s" target="_blank" title="%3$s">%4$s</a><br/>',
    __("Status", "woocommerce-myparcelbe"),
    $track_trace_url,
    Arr::get($shipment, "track_trace"),
    Arr::get($shipment, "status")
);

/**
 *  Package type
 */
printf(
    '%s: %s',
    __("Shipment type", "woocommerce-myparcelbe"),
    WCMP_Data::getPackageTypeHuman(Arr::get($shipment, "shipment.options.package_type"))
);

echo '<ul class="wcmp__shipment-summary">';
foreach ($option_strings as $key => $label) {
    if (Arr::get($shipment, "shipment.options.$key")
        && (int) Arr::get($shipment, "shipment.options.$key") === 1) {
        printf('<li class="%s">%s</li>', $key, $label);
    }
}

if ($insurance) {
    $price = number_format(Arr::get($insurance, "amount") / 100, 2);
    printf('<li>%s: € %s</li>', __("Insured for", "woocommerce-myparcelbe"), $price);
}

if ($labelDescription) {
    printf(
        '<li>%s: %s</li>',
        __("Label description", "woocommerce-myparcelbe"),
        $labelDescription
    );
}

echo '</ul>';
