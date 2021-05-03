<?php

/**
 * The shipment summary that shows when you click (i) in an order.
 */

use MyParcelNL\Sdk\src\Support\Arr;
use WPO\WC\MyParcelBE\Compatibility\WC_Core as WCX;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

$order_id    = $_POST["order_id"];
$shipment_id = $_POST["shipment_id"];

$order = WCX::get_order($order_id);

$shipments       = WCMYPABE()->export->getShipmentData([$shipment_id], $order);
$deliveryOptions = WCMYPABE_Admin::getDeliveryOptionsFromOrder($order);

$option_strings = [
    "signature"      => __("shipment_options_signature", "woocommerce-myparcelbe"),
    "only_recipient" => __("shipment_options_only_recipient", "woocommerce-myparcelbe"),
];

$firstShipment = $shipments[$shipment_id];

/**
 * Show options only for the first shipment as they are all the same.
 */
$insurance        = Arr::get($firstShipment, "shipment.options.insurance");
$labelDescription = Arr::get($firstShipment, "shipment.options.label_description");

echo '<ul class="wcmpbe__shipment-summary wcmpbe__ws--nowrap">';

/**
 *  Package type
 */
printf(
    '%s: %s',
    __("Shipment type", "woocommerce-myparcelbe"),
    WCMPBE_Data::getPackageTypeHuman(Arr::get($firstShipment, "shipment.options.package_type"))
);

foreach ($option_strings as $key => $label) {
    if (Arr::get($firstShipment, "shipment.options.$key")
        && (int) Arr::get($firstShipment, "shipment.options.$key") === 1) {
        printf('<li class="%s">%s</li>', $key, $label);
    }
}

if ($insurance) {
    $price = number_format(Arr::get($insurance, "amount") / 100, 2);
    printf('<li>%s: € %s</li>', __("insured_for", "woocommerce-myparcelbe"), $price);
}

if ($labelDescription) {
    printf(
        '<li>%s: %s</li>',
        __("Label description", "woocommerce-myparcelbe"),
        $labelDescription
    );
}
echo '</ul>';

echo "<hr>";

/**
 * Do show the Track & Trace status for all shipments.
 */
foreach ($shipments as $shipment_id => $shipment) {
    $trackTrace = Arr::get($shipment, "track_trace");

    /**
     * Show Track & Trace status.
     */
    if (! $trackTrace) {
        continue;
    }

    printf(
        '<a href="%2$s" target="_blank" title="%3$s">%3$s</a><br/> %1$s: %4$s<br/>',
        __("Status", "woocommerce-myparcelbe"),
        WCMYPABE_Admin::getTrackTraceUrl($order_id, $trackTrace),
        $trackTrace,
        Arr::get($shipment, "status")
    );
}
