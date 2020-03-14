<?php

use MyParcelNL\Sdk\src\Model\Consignment\BpostConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\DPDConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\PostNLConsignment;
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
    $shippingCountry       = WCX_Order::get_prop($order, "shipping_country");

    $carriersHuman = WCMP_Data::CARRIERS_HUMAN;
    $insurance     = false;
    $signature     = false;
    $onlyRecipient = $shipment_options->hasOnlyRecipient();
    $largeFormat   = $shipment_options->hasLargeFormat();

    if (DPDConsignment::CARRIER_NAME !== $deliveryOptions->getCarrier()) {
        $insurance = WCMP_Export::getChosenOrDefaultShipmentOption(
            $shipment_options->getInsurance(),
            "{$deliveryOptions->getCarrier()}_" . WCMP_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED

        );

        $signature = WCMP_Export::getChosenOrDefaultShipmentOption(
            $shipment_options->hasSignature(),
            "{$deliveryOptions->getCarrier()}_" . WCMP_Settings::SETTING_CARRIER_DEFAULT_EXPORT_SIGNATURE
        );
    }

    // If there are extra costs associated with DPD shipment, then DPD should not be used.
    if (in_array($shippingCountry, DPDConsignment::ADDITIONAL_COUNTRY_COSTS)) {
        unset($carriersHuman[DPDConsignment::CARRIER_NAME]);
    }

    $option_rows = [
        [
            "name"              => "[carrier]",
            "label"             => __("Carrier", "woocommerce-myparcelbe"),
            "type"              => "select",
            "options"           => $carriersHuman,
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
            "options"           => array_combine(WCMP_Data::getPackageTypes(), WCMP_Data::getPackageTypesHuman()),
            // TODO for NL: set "value" correctly.
            "value"             => null,
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
        [
            "name"      => "[shipment_options][only_recipient]",
            "type"      => "toggle",
            "condition" => [
                "name"         => "[carrier]",
                "type"         => "disable",
                "parent_value" => WCMP_Data::getCarriersWithOnlyRecipient(),
                "set_value"    => WCMP_Settings_Data::ENABLED,
            ],
            "label"     => __("Only recipient", "woocommerce-myparcelbe"),
            "value"     => (bool) $onlyRecipient,
        ],
        [
            "name"      => "[shipment_options][large_format]",
            "type"      => "toggle",
            "condition" => [
                "name"         => "[carrier]",
                "type"         => "disable",
                "parent_value" => WCMP_Data::getCarriersWithLargeFormat(),
                "set_value"    => WCMP_Settings_Data::ENABLED,
            ],
            "label"     => __("Large format", "woocommerce-myparcelbe"),
            "value"     => (bool) $largeFormat,
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
    <div>
        <div class="button wcmp__shipment-settings__save">
            <?php
            _e("Save", "woocommerce-myparcelbe");
            WCMP_Admin::renderSpinner();
            ?>
        </div>
    </div>
</div>
