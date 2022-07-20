<?php

/**
 * The shipment summary that shows when you click (i) in an order.
 */

use MyParcelNL\Sdk\src\Support\Arr;
use WPO\WC\MyParcel\Compatibility\WC_Core as WCX;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

$order_id    = $_POST['order_id'];
$shipment_id = $_POST['shipment_id'];

$order = WCX::get_order($order_id);

$shipments       = WCMYPA()->export->getShipmentData([$shipment_id], $order);
$deliveryOptions = WCMYPA_Admin::getDeliveryOptionsFromOrder($order);

$option_strings = [
    'signature'      => __('shipment_options_signature', 'woocommerce-myparcel'),
    'only_recipient' => __('shipment_options_only_recipient', 'woocommerce-myparcel'),
];

$firstShipment = $shipments[$shipment_id];

/**
 * Show options only for the first shipment as they are all the same.
 */
$insurance        = Arr::get($firstShipment, 'shipment.options.insurance');
$labelDescription = Arr::get($firstShipment, 'shipment.options.label_description');

echo '<ul class="wcmp__shipment-summary wcmp__ws--nowrap">';

/**
 *  Package type
 */
printf(
    '%s: %s',
    __('Shipment type', 'woocommerce-myparcel'),
    WCMP_Data::getPackageTypeHuman(Arr::get($firstShipment, 'shipment.options.package_type'))
);

foreach ($option_strings as $key => $label) {
    if (Arr::get($firstShipment, "shipment.options.$key")
        && (int) Arr::get($firstShipment, "shipment.options.$key") === 1) {
        printf('<li class="%s">%s</li>', $key, $label);
    }
}

if ($insurance) {
    $price = number_format(Arr::get($insurance, 'amount') / 100, 2);
    printf('<li>%s: € %s</li>', __('insured_for', 'woocommerce-myparcel'), $price);
}

if ($labelDescription) {
    printf(
        '<li>%s: %s</li>',
        __('Label description', 'woocommerce-myparcel'),
        $labelDescription
    );
}
echo '</ul>';

echo '<hr>';

/**
 * Do show the Track & Trace status for all shipments.
 */
foreach ($shipments as $shipment_id => $shipment) {
    $trackTrace       = Arr::get($shipment, 'track_trace');
    $shipmentShipment = Arr::get($shipment, 'shipment');
    $shipmentStatusId = Arr::get($shipmentShipment, 'status');
    $printedStatuses  = [WCMYPA_Admin::ORDER_STATUS_PRINTED_DIGITAL_STAMP, WCMYPA_Admin::ORDER_STATUS_PRINTED_LETTER];

    /**
     * Show Track & Trace status.
     */
    if (! $trackTrace) {
        if (in_array($shipmentStatusId, $printedStatuses)) {
            echo __('The label has been printed.', 'woocommerce-myparcel');
            echo '<br/>';
        }
        continue;
    }

    printf(
        '<a href="%1$s" target="_blank" title="%2$s">%2$s</a><br/> %3$s: %4$s<br/>',
        WCMYPA_Admin::getTrackTraceUrl($order_id, $trackTrace),
        $trackTrace,
        __('Status', 'woocommerce-myparcel'),
        Arr::get($shipment, 'status')
    );
}
