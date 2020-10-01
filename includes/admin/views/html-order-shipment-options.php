<?php

use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\PostNLConsignment;
use WPO\WC\MyParcel\Compatibility\Order as WCX_Order;
use WPO\WC\MyParcel\Entity\SettingsFieldArguments;

/**
 * @var WC_Order $order
 */

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

try {
    $deliveryOptions = WCMYPA_Admin::getDeliveryOptionsFromOrder($order);
} catch (Exception $e) {
    return;
}

$extraOptions = WCX_Order::get_meta($order, WCMYPA_Admin::META_SHIPMENT_OPTIONS_EXTRA);

?>
<div class="wcmp wcmp__shipment-options">
    <?php
    if ($deliveryOptions->isPickup()) {
        $pickup = $deliveryOptions->getPickupLocation();

        printf(
            "<div class=\"pickup-location\"><strong>%s:</strong><br /> %s<br />%s %s<br />%s %s</div>",
            __("Pickup location", "woocommerce-myparcel"),
            $pickup->getLocationName(),
            $pickup->getStreet(),
            $pickup->getNumber(),
            $pickup->getPostalCode(),
            $pickup->getCity()
        );

        echo "<hr>";
    }

    $isPackageTypeDisabled = count(WCMP_Data::getPackageTypes()) === 1 || $deliveryOptions->isPickup();
    $shipment_options      = $deliveryOptions->getShipmentOptions();

    $packageTypes                   = array_flip(AbstractConsignment::PACKAGE_TYPES_NAMES_IDS_MAP);
    $packageTypeFromDeliveryOptions = $deliveryOptions->getPackageType();
    $selectedPackageType            = WCMYPA()->export->getPackageTypeFromOrder($order, $deliveryOptions);

    $postnl          = PostNLConsignment::CARRIER_NAME;
    $insurance       = false;
    $insuranceAmount = 0;
    $signature       = false;
    $onlyRecipient   = false;
    $ageCheck        = false;
    $returnShipment  = false;

    $insurance = WCMP_Export::getChosenOrDefaultShipmentOption(
        $shipment_options->getInsurance(),
        "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED
    );

    $signature = WCMP_Export::getChosenOrDefaultShipmentOption(
        $shipment_options->hasSignature(),
        "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_SIGNATURE
    );

    $onlyRecipient = WCMP_Export::getChosenOrDefaultShipmentOption(
        $shipment_options->hasOnlyRecipient(),
        "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_ONLY_RECIPIENT
    );

    $ageCheck = WCMP_Export::getChosenOrDefaultShipmentOption(
        $shipment_options->hasAgeCheck(),
        "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_AGE_CHECK
    );

    $largeFormat = WCMP_Export::getChosenOrDefaultShipmentOption(
        $shipment_options->hasLargeFormat(),
        "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_LARGE_FORMAT
    );

    $returnShipment = WCMP_Export::getChosenOrDefaultShipmentOption(
        $shipment_options->isReturn(),
        "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_RETURN
    );

    $insuranceAmount = WCMP_Export::getChosenOrDefaultShipmentOption(
        $shipment_options->getInsurance(),
        "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED_AMOUNT
    );

    $orderWeight        = (float) $order->get_meta(WCMYPA_Admin::META_ORDER_WEIGHT);
    $digitalStampWeight = $extraOptions["weight"] ?? WCMP_Export::getDigitalStampRangeFromWeight($orderWeight);

    $option_rows = [
        [
            "name"              => "[carrier]",
            "label"             => __("Carrier", "woocommerce-myparcel"),
            "type"              => "select",
            "options"           => WCMP_Data::CARRIERS_HUMAN,
            "custom_attributes" => ["disabled" => "disabled"],
            "value"             => $deliveryOptions->getCarrier() ?? PostNLConsignment::CARRIER_NAME,
        ],
        [
            "name"              => "[package_type]",
            "label"             => __("Shipment type", "woocommerce-myparcel"),
            "type"              => "select",
            "options"           => array_combine(WCMP_Data::getPackageTypes(), WCMP_Data::getPackageTypesHuman()),
            "value"             => $packageTypes[$selectedPackageType],
            "custom_attributes" => $isPackageTypeDisabled ? ["disabled" => "disabled"] : [],
        ],
        [
            "name"              => "[extra_options][collo_amount]",
            "label"             => __("Number of labels", "woocommerce-myparcel"),
            "type"              => "number",
            "value"             => $extraOptions["collo_amount"] ?? 1,
            "custom_attributes" => [
                "step" => "1",
                "min"  => "1",
                "max"  => "10",
            ],
        ],
        [
            "name"        => "[extra_options][weight]",
            "label"       => __("Weight", "woocommerce-myparcel"),
            "description" => sprintf(
                __("Calculated weight: %s", "woocommerce-myparcel"),
                wc_format_weight($orderWeight)
            ),
            "type"        => "select",
            "options"     => WCMP_Export::getDigitalStampRangeOptions(),
            "condition"   => [
                "name"         => "[carrier]",
                "type"         => "disable",
                "parent_value" => WCMP_Data::getPostnlName(),
                "set_value"    => WCMP_Settings_Data::DISABLED,
            ],
            "value"       => $digitalStampWeight,
        ],
        [
            "name"      => "[shipment_options][only_recipient]",
            "type"      => "toggle",
            "condition" => [
                "name"         => "[carrier]",
                "type"         => "disable",
                "parent_value" => WCMP_Data::getPostnlName(),
                "set_value"    => WCMP_Settings_Data::DISABLED,
            ],
            "label"     => __("Home address only", "woocommerce-myparcel"),
            "value"     => $onlyRecipient,
        ],
        [
            "name"      => "[shipment_options][signature]",
            "type"      => "toggle",
            "condition" => [
                "name"         => "[carrier]",
                "type"         => "disable",
                "parent_value" => WCMP_Data::getPostnlName(),
                "set_value"    => WCMP_Settings_Data::DISABLED,
            ],
            "label"     => __("Signature on delivery", "woocommerce-myparcel"),
            "value"     => $signature,
        ],
        [
            "name"      => "[shipment_options][large_format]",
            "type"      => "toggle",
            "condition" => [
                "name"         => "[carrier]",
                "type"         => "disable",
                "parent_value" => WCMP_Data::getPostnlName(),
                "set_value"    => WCMP_Settings_Data::DISABLED,
            ],
            "label"     => __("Large format", "woocommerce-myparcel"),
            "value"     => $largeFormat,
        ],
        [
            "name"      => "[shipment_options][age_check]",
            "type"      => "toggle",
            "condition" => [
                "name"         => "[carrier]",
                "type"         => "disable",
                "parent_value" => WCMP_Data::getPostnlName(),
                "set_value"    => WCMP_Settings_Data::DISABLED,
            ],
            "label"     => __("Age check", "woocommerce-myparcel"),
            "value"     => $ageCheck,
        ],
        [
            "name"      => "[shipment_options][return_shipment]",
            "type"      => "toggle",
            "condition" => [
                "name"         => "[carrier]",
                "type"         => "disable",
                "parent_value" => WCMP_Data::getPostnlName(),
                "set_value"    => WCMP_Settings_Data::DISABLED,
            ],
            "label"     => __("Return shipment", "woocommerce-myparcel"),
            "value"     => $returnShipment,
        ],
        [
            "name"      => "[shipment_options][insured]",
            "type"      => "toggle",
            "condition" => [
                "name"         => "[carrier]",
                "type"         => "disable",
                "parent_value" => WCMP_Data::getPostnlName(),
                "set_value"    => WCMP_Settings_Data::ENABLED,
            ],
            "label"     => __("Insured", "woocommerce-myparcel"),
            "value"     => (bool) $insurance,
        ],
        [
            "name"    => "[shipment_options][insured_amount]",
            "label"   => __("Insurance amount", "woocommerce-myparcel"),
            "type"    => "select",
            "options" => WCMP_Data::getInsuranceAmount(),
            "value"   => (int) $insuranceAmount,
        ],
    ];

    if (isset($recipient) && isset($recipient["cc"]) && $recipient["cc"] !== "NL") {
        unset($option_rows["[signature]"]);
        unset($option_rows["[only_recipient]"]);
    }

    $namePrefix = WCMYPA_Admin::SHIPMENT_OPTIONS_FORM_NAME . "[{$order->get_id()}]";

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
        <div class="button wcmp__shipment-options__save">
            <?php
            _e("Save", "woocommerce-myparcel");
            WCMYPA_Admin::renderSpinner();
            ?>
        </div>
    </div>
</div>
