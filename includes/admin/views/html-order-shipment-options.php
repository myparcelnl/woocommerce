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
    <table>
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
    $shipmentOptions       = $deliveryOptions->getShipmentOptions();

    $packageTypeFromDeliveryOptions = $deliveryOptions->getPackageType();
    $selectedPackageType            = WCMYPA()->export->getPackageTypeFromOrder($order, $deliveryOptions);
    $carrier                        = $deliveryOptions->getCarrier();

    $data = array_map(
        function ($item) {
            return WCMP_Export::getChosenOrDefaultShipmentOption($item[0], $item[1]);
        },
        [
            'hasSignature'        => [
                $shipmentOptions->hasSignature(),
                "{$carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_SIGNATURE,
            ],
            'hasOnlyRecipient' => [
                $shipmentOptions->hasOnlyRecipient(),
                "{$carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_ONLY_RECIPIENT,
            ],
            'hasAgeCheck'      => [
                $shipmentOptions->hasAgeCheck(),
                "{$carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_AGE_CHECK,
            ],
            'hasLargeFormat'   => [
                $shipmentOptions->hasLargeFormat(),
                "{$carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_LARGE_FORMAT,
            ],
            'isReturn'         => [
                $shipmentOptions->isReturn(),
                "{$carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_RETURN,
            ],
            'insurance'        => [
                $shipmentOptions->getInsurance(),
                "{$carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED_AMOUNT,
            ],
        ]
    );

    $orderWeight        = (float) $order->get_meta(WCMYPA_Admin::META_ORDER_WEIGHT);
    $digitalStampWeight = $extraOptions["weight"] ?? WCMP_Export::getDigitalStampRangeFromWeight($orderWeight);

    $conditionCarrierPostNl = [
        "parent_name"  => "[carrier]",
        "type"         => "show",
        "parent_value" => WCMP_Data::getPostnlName(),
        "set_value"    => WCMP_Settings_Data::DISABLED,
    ];

    $conditionDeliveryTypeDelivery = [
        "parent_name"  => "[delivery_type]",
        "type"         => "show",
        "parent_value" => [
            AbstractConsignment::DELIVERY_TYPE_MORNING_NAME,
            AbstractConsignment::DELIVERY_TYPE_STANDARD_NAME,
            AbstractConsignment::DELIVERY_TYPE_EVENING_NAME,
        ],
        "set_value"    => WCMP_Settings_Data::DISABLED,
    ];

    $conditionPackageTypePackage = [
        "parent_name"  => "[package_type]",
        "type"         => "show",
        "parent_value" => AbstractConsignment::PACKAGE_TYPE_PACKAGE_NAME,
    ];

    $conditionForceEnabledOnAgeCheck = [
        "parent_name"  => "[shipment_options][age_check]",
        "type"         => "disable",
        "set_value"    => WCMP_Settings_Data::ENABLED,
        "parent_value" => WCMP_Settings_Data::DISABLED,
    ];

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
            "name"              => "[delivery_type]",
            "label"             => __("Delivery type", "woocommerce-myparcel"),
            "type"              => "select",
            "options"           => WCMP_Data::getDeliveryTypesHuman(),
            "custom_attributes" => ["disabled" => "disabled"],
            "value"             => $deliveryOptions->getDeliveryType(),
        ],
        [
            "name"              => "[package_type]",
            "label"             => __("Shipment type", "woocommerce-myparcel"),
            "type"              => "select",
            "options"           => array_combine(WCMP_Data::getPackageTypes(), WCMP_Data::getPackageTypesHuman()),
            "value"             => $selectedPackageType,
            "custom_attributes" => $isPackageTypeDisabled
                ? ["disabled" => "disabled"]
                : [],
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
            "type"        => "select",
            "label"       => __("Weight", "woocommerce-myparcel"),
            "description" => $orderWeight
                ? sprintf(
                    __("Calculated weight: %s", "woocommerce-myparcel"),
                    wc_format_weight($orderWeight)
                )
                : null,
            "options"     => WCMP_Export::getDigitalStampRangeOptions(),
            "value"       => $digitalStampWeight,
            "conditions"  => [
                $conditionCarrierPostNl,
                [
                    "parent_name"  => "[package_type]",
                    "type"         => "show",
                    "parent_value" => AbstractConsignment::PACKAGE_TYPE_DIGITAL_STAMP_NAME,
                ],
            ],
        ],
        [
            "name"       => "[shipment_options][only_recipient]",
            "type"       => "toggle",
            "label"      => __("Home address only", "woocommerce-myparcel"),
            "value"      => $data['hasOnlyRecipient'],
            "conditions" => [
                $conditionDeliveryTypeDelivery,
                $conditionPackageTypePackage,
                $conditionCarrierPostNl,
                $conditionForceEnabledOnAgeCheck,
            ],
        ],
        [
            "name"       => "[shipment_options][signature]",
            "type"       => "toggle",
            "label"      => __("Signature on delivery", "woocommerce-myparcel"),
            "value"      => $data['hasSignature'],
            "conditions" => [
                $conditionDeliveryTypeDelivery,
                $conditionPackageTypePackage,
                $conditionCarrierPostNl,
                $conditionForceEnabledOnAgeCheck,
            ],
        ],
        [
            "name"       => "[shipment_options][age_check]",
            "type"       => "toggle",
            "label"      => __("Age check", "woocommerce-myparcel"),
            "value"      => $data['hasAgeCheck'],
            "conditions" => [
                $conditionDeliveryTypeDelivery,
                $conditionPackageTypePackage,
                $conditionCarrierPostNl,
            ],
        ],
        [
            "name"       => "[shipment_options][large_format]",
            "type"       => "toggle",
            "label"      => __("Large format", "woocommerce-myparcel"),
            "value"      => $data['hasLargeFormat'],
            "conditions" => [
                $conditionDeliveryTypeDelivery,
                $conditionPackageTypePackage,
                $conditionCarrierPostNl,
            ],
        ],
        [
            "name"       => "[shipment_options][return_shipment]",
            "type"       => "toggle",
            "label"      => __("Return shipment", "woocommerce-myparcel"),
            "value"      => $data['isReturn'],
            "conditions" => [
                $conditionDeliveryTypeDelivery,
                $conditionPackageTypePackage,
                $conditionCarrierPostNl,
            ],
        ],
        [
            "name"       => "[shipment_options][insured]",
            "type"       => "toggle",
            "label"      => __("Insured", "woocommerce-myparcel"),
            "value"      => (bool) $data['insurance'],
            "conditions" => [
                $conditionDeliveryTypeDelivery,
                $conditionPackageTypePackage,
                [
                    "parent_name"  => "[carrier]",
                    "type"         => "disable",
                    "parent_value" => WCMP_Data::getPostnlName(),
                    "set_value"    => WCMP_Settings_Data::ENABLED,
                ],
            ],
        ],
        [
            "name"       => "[shipment_options][insured_amount]",
            "type"       => "select",
            "label"      => __("Insurance amount", "woocommerce-myparcel"),
            "options"    => WCMP_Data::getInsuranceAmount(),
            "value"      => (int) $data['insurance'],
            "conditions" => [
                $conditionDeliveryTypeDelivery,
                $conditionPackageTypePackage,
                "[shipment_options][insured]",
            ],
        ],
        [
            "name"  => "[shipment_options][label_description]",
            "type"  => "text",
            "label" => __("Custom ID (top left on label)", "woocommerce-myparcel"),
            "value" => $shipmentOptions->getLabelDescription() ?? null,
        ],
    ];

    if (isset($recipient) && isset($recipient["cc"]) && $recipient["cc"] !== "NL") {
        unset($option_rows["[signature]"]);
        unset($option_rows["[only_recipient]"]);
    }

    $namePrefix = WCMYPA_Admin::SHIPMENT_OPTIONS_FORM_NAME . "[{$order->get_id()}]";

    foreach ($option_rows as $option_row) :
        $class = new SettingsFieldArguments($option_row, $namePrefix);

        // Cast boolean values to the correct enabled/disabled values.
        if (is_bool($option_row["value"])) {
            $option_row["value"] = $option_row["value"] ? WCMP_Settings_Data::ENABLED : WCMP_Settings_Data::DISABLED;
        }

        $class->setValue($option_row["value"]);

        ?>

        <tr>
            <td>
                <label for="<?php echo $class->getName() ?>">
                    <?php echo $class->getArgument('label') ?>
                </label>
            </td>
            <td>
                <?php WCMP_Settings_Callbacks::renderField($class); ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <tr>
        <td colspan="2">
            <div class="button wcmp__shipment-options__save">
                <?php
                _e("Save", "woocommerce-myparcel");
                WCMYPA_Admin::renderSpinner();
                ?>
            </div>
        </td>
    </tr>
    </table>
</div>
