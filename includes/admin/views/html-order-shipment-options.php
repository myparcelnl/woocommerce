<?php

use MyParcelNL\Sdk\src\Model\Consignment\BpostConsignment;
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
            __("Pickup location", "woocommerce-myparcelbe"),
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

    $bpost     = BpostConsignment::CARRIER_NAME;
    $insurance = false;
    $signature = false;

    if (DPDConsignment::CARRIER_NAME !== $deliveryOptions->getCarrier()) {
        $insurance = WCMP_Export::getChosenOrDefaultShipmentOption(
            $shipment_options->getInsurance(),
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
            "label"             => __("Carrier", "woocommerce-myparcelbe"),
            "type"              => "select",
            "options"           => WCMP_Data::CARRIERS_HUMAN,
            "custom_attributes" => $isCarrierDisabled ? ["disabled" => "disabled"] : [],
            "value"             => $deliveryOptions->getCarrier(),
        ],
        [
            "name"              => "[package_type]",
            "label"             => __("Shipment type", "woocommerce-myparcelbe"),
            "description"       => sprintf(
                __("Calculated weight: %s", "woocommerce-myparcelbe"),
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
            "label"             => __("Number of labels", "woocommerce-myparcelbe"),
            "type"              => "number",
            "value"             => isset($extraOptions["collo_amount"]) ? $extraOptions["collo_amount"] : 1,
            "custom_attributes" => [
                "step" => "1",
                "min"  => "1",
                "max"  => "10",
            ],
        ],
        [
            "name"      => "[shipment_options][signature]",
            "type"      => "toggle",
            "condition" => [
                "name"         => "[carrier]",
                "type"         => "disable",
                "parent_value" => WCMP_Data::getCarriersWithSignature(),
                "set_value"    => WCMP_Settings_Data::DISABLED,
            ],
            "label"     => __("Signature on delivery", "woocommerce-myparcelbe"),
            "value"     => $signature,
        ],
        [
            "name"      => "[shipment_options][insurance]",
            "type"      => "toggle",
            "condition" => [
                "name"         => "[carrier]",
                "type"         => "disable",
                "parent_value" => WCMP_Data::getCarriersWithInsurance(),
                "set_value"    => WCMP_Settings_Data::ENABLED,
            ],
            "label"     => __("Insured to &euro; 500", "woocommerce-myparcelbe"),
            "value"     => (bool) $insurance,
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

        // Cast boolean values to the correct enabled/disabled values.
        if (is_bool($option_row["value"])) {
            $option_row["value"] = $option_row["value"] ? WCMP_Settings_Data::ENABLED : WCMP_Settings_Data::DISABLED;
        }

        woocommerce_form_field(
            $namePrefix . $option_row["name"],
            $class->getArguments(false),
            $option_row["value"] ?? null
        );
    }
    ?>
    <div class="wcmp__shipment-settings__save">
        <?php printf(
            '<div class="button wcmp__shipment-settings__save">%s</div>',
            __("Save", "woocommerce-myparcelbe")
        );

        $this->renderSpinner()

        ?>
    </div>
</div>
