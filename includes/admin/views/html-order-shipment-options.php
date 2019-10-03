<?php

use MyParcelNL\Sdk\src\Model\Consignment\DPDConsignment;
use WPO\WC\MyParcelBE\Compatibility\Order as WCX_Order;
use WPO\WC\MyParcelBE\Entity\SettingsFieldArguments;

/**
 * @var int      $order_id
 * @var WC_Order $order
 */

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

try {
    $deliveryOptions = WCMP_Admin::getDeliveryOptionsFromOrder($order);
} catch (Exception $e) {
    return;
}

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

    $bpost     = DPDConsignment::CARRIER_NAME;
    $insured   = false;
    $signature = false;

    if (DPDConsignment::CARRIER_NAME !== $deliveryOptions->getCarrier()) {
        $insured = WCMP_Export::getChosenOrDefaultShipmentOption(
            $shipment_options->hasInsurance(),
            "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED
        );

        $signature = WCMP_Export::getChosenOrDefaultShipmentOption(
            $shipment_options->hasSignature(),
            "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_DEFAULT_EXPORT_SIGNATURE
        );
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
            "name"              => "[package_type]",
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
            "value"             => isset($extraOptions["collo_amount"]) ? $extraOptions["collo_amount"] : 1,
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
            "condition"         => [
                "name"         => "[carrier]",
                "type"         => "disable",
                "parent_value" => WCMP_Data::getCarriersWithSignature(),
                "set_value"    => WCMP_Settings_Data::DISABLED,
            ],
            "custom_attributes" => [
                "disabled" => $signature ? "disabled" : null,
            ],
        ],
        [
            "name"              => "[shipment_options][insurance]",
            "type"              => "toggle",
            "condition"         => [
                "name"         => "[carrier]",
                "type"         => "disable",
                "parent_value" => WCMP_Data::getCarriersWithInsurance(),
                "set_value"    => WCMP_Settings_Data::DISABLED,
            ],
            "label"             => _wcmp("Insured to &euro; 500"),
            "value"             => (int) $insured,
            "custom_attributes" => [
                "disabled" => $insured ? "disabled" : null,
            ],
        ],
    ];

    if (isset($recipient) && isset($recipient["cc"]) && $recipient["cc"] !== "BE") {
        unset($option_rows["[signature]"]);
    }

    $namePrefix = WCMP_Admin::SHIPMENT_OPTIONS_FORM_NAME . "[$order_id]";

    foreach ($option_rows as $option_row) {
        if (isset($option_row["condition"])) {
            $option_row["condition"]["name"] = $namePrefix . $option_row["condition"]["name"];
        }

        $class = new SettingsFieldArguments($option_row);

        woocommerce_form_field(
            $namePrefix . $option_row["name"],
            $class->getArguments(false),
            $option_row["value"] ?? null
        );
    }
    ?>
    <div class="wcmp__shipment-settings__save">
        <?php printf(
            '<div class="button wcmp__shipment-settings__save" data-order="%s">%s</div>',
            $order_id,
            _wcmp("Save")
        );

        $this->renderSpinner()

        ?>
    </div>
</div>
