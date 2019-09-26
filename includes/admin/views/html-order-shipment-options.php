<?php

/**
 * @var int      $order_id
 * @var WC_Order $order
 */

use WPO\WC\MyParcelBE\Compatibility\Order as WCX_Order;
use WPO\WC\MyParcelBE\Entity\SettingsFieldArguments;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/** @noinspection PhpUnhandledExceptionInspection */
$deliveryOptions = WCMP_Admin::getDeliveryOptionsFromOrder($order);

// todo fix shipment extra options?
$extraOptions = WCX_Order::get_meta($order, WCMP_Admin::META_SHIPMENT_OPTIONS_EXTRA);

?>
<div class="wcmp wcmp__change-order">
    <?php
    if ($deliveryOptions->isPickup()) {
        $pickup = $deliveryOptions->getPickupLocation();

        printf(
            "<div class=\"pickup-location\"><strong>%s:</strong><br /> %s<br />%s %s<br />%s %s</div>",
            _wcmp("Pickup location"),
            $pickup->getLocationName(),
            $pickup->getStreet(),
            $pickup->getNumber(),
            $pickup->getPostalCode(),
            $pickup->getCity()
        );

        echo "<hr>";
    }

    $isCarrierDisabled     = $deliveryOptions->isPickup();
    $isPackageTypeDisabled = count(WCMP_Data::getPackageTypes()) === 1 || $deliveryOptions->isPickup();
    $shipment_options      = $deliveryOptions->getShipmentOptions();

    if (! empty($shipment_options->insured)) {
        $insured = $shipment_options->insured;
    } else {
        $insured = WCMP()->setting_collection->getByName("insured");
    }

    if ($shipment_options->hasSignature()) {
        $signature = $shipment_options->hasSignature();
    } else {
        $signature = WCMP_Export::isSignatureByDeliveryOptions($deliveryOptions);
    }

    $option_rows = [
        [
            "name"              => "[carrier]",
            "label"             => _wcmp("Carrier"),
            "type"              => "select",
            "options"           => WCMP_Data::CARRIERS_HUMAN,
            "custom_attributes" => $isCarrierDisabled ? ["disabled" => "disabled"] : [],
            "value"             => $deliveryOptions->getCarrier(),
        ],
        [
            "name"              => "[$order_id][package_type]",
            "label"             => _wcmp("Shipment type"),
            "description"       => sprintf(
                _wcmp("Calculated weight: %s"),
                wc_format_weight($order->get_meta(WCMP_Admin::META_ORDER_WEIGHT))
            ),
            "type"              => "select",
            "options"           => WCMP_Data::getPackageTypes(),
            "value"             => $deliveryOptions->getDeliveryType(),
            "custom_attributes" => [
                "disabled" => $isPackageTypeDisabled ? "disabled" : null,
            ],
        ],
        [
            "name"              => "[extra_options][collo_amount]",
            "label"             => _wcmp("Number of labels"),
            "type"              => "number",
            "value"             => isset($extraOptions['collo_amount']) ? $extraOptions['collo_amount'] : 1,
            "custom_attributes" => [
                "step" => "1",
                "min"  => "1",
                "max"  => "10",
            ],
        ],
        [
            "name"              => "[shipment_options][signature]",
            "type"              => "toggle",
            "label"             => _wcmp("Signature on delivery"),
            "value"             => (int) $signature,
            "custom_attributes" => [
                "disabled" => $signature ? "disabled" : null,
            ],
        ],
        [
            "name"              => "[shipment_options][insured]",
            "type"              => "toggle",
            "condition"         => [
                "name"         => "[carrier]",
                "type"         => "disable",
                "parent_value" => "dpd",
                "set_value"    => WCMP_Settings_Data::DISABLED,
            ],
            "label"             => _wcmp("Insured to &euro; 500"),
            "value"             => $insured ? 1 : 0,
            "custom_attributes" => [
                "disabled" => isset($option_row['disabled']) ? "disabled" : null,
            ],
        ],
    ];

    if (isset($recipient) && isset($recipient['cc']) && $recipient['cc'] !== 'BE') {
        unset($option_rows['[signature]']);
    }

    foreach ($option_rows as $option_row) {
        $name = WCMP_Admin::SHIPMENT_OPTIONS_FORM_NAME . "[$order_id]" . $option_row["name"];
        if (isset($option_row["condition"])) {
            $option_row["condition"]["name"] = $name;
        }

        $class = new SettingsFieldArguments($option_row);

        woocommerce_form_field(
            $name,
            $class->getArguments(false),
            $option_row["value"] ?? null
        );
    }
    ?>
    <div class="wcmp_save_shipment_settings">
        <?php printf(
            '<div class="button wcmp__js-save-shipment-settings" data-order="%s">%s</div>',
            $order_id,
            _wcmp('Save')
        ) ?><?php $this->renderSpinner() ?>
    </div>
</div>
