<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Includes\Admin;

use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter;
use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractShipmentOptionsAdapter;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\PostNLConsignment;
use WC_Order;
use WCMP_Data;
use WCMP_Export;
use WCMP_Settings_Data;
use WCMYPA_Admin;
use WCMYPA_Settings;
use WPO\WC\MyParcel\Compatibility\Order as WCX_Order;

class ShipmentOptions
{
    private const HOME_COUNTRY_ONLY_ROWS = [
        self::OPTION_SHIPMENT_OPTIONS_AGE_CHECK,
        self::OPTION_SHIPMENT_OPTIONS_ONLY_RECIPIENT,
        self::OPTION_SHIPMENT_OPTIONS_SIGNATURE,
    ];

    private const OPTION_CARRIER                            = "[carrier]";
    private const OPTION_DELIVERY_TYPE                      = "[delivery_type]";
    private const OPTION_EXTRA_OPTIONS_COLLO_AMOUNT         = "[extra_options][collo_amount]";
    private const OPTION_EXTRA_OPTIONS_WEIGHT               = "[extra_options][weight]";
    private const OPTION_PACKAGE_TYPE                       = "[package_type]";
    private const OPTION_SHIPMENT_OPTIONS_INSURED           = "[shipment_options][insured]";
    private const OPTION_SHIPMENT_OPTIONS_INSURED_AMOUNT    = "[shipment_options][insured_amount]";
    private const OPTION_SHIPMENT_OPTIONS_LABEL_DESCRIPTION = "[shipment_options][label_description]";
    private const OPTION_SHIPMENT_OPTIONS_LARGE_FORMAT      = "[shipment_options][large_format]";
    private const OPTION_SHIPMENT_OPTIONS_ONLY_RECIPIENT    = "[shipment_options][only_recipient]";
    private const OPTION_SHIPMENT_OPTIONS_RETURN_SHIPMENT   = "[shipment_options][return_shipment]";
    private const OPTION_SHIPMENT_OPTIONS_SIGNATURE         = "[shipment_options][signature]";
    private const OPTION_SHIPMENT_OPTIONS_AGE_CHECK         = "[shipment_options][age_check]";

    /**
     * @param \MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter $deliveryOptions
     * @param \WC_Order                                                                  $order
     *
     * @return array[]
     * @throws \Exception
     */
    public static function getOptionsRows(
        AbstractDeliveryOptionsAdapter $deliveryOptions,
        WC_Order $order
    ): array {
        $extraOptionsMeta = WCX_Order::get_meta($order, WCMYPA_Admin::META_SHIPMENT_OPTIONS_EXTRA);

        $isPackageTypeDisabled = count(WCMP_Data::getPackageTypes()) === 1 || $deliveryOptions->isPickup();
        $selectedPackageType   = WCMYPA()->export->getPackageTypeFromOrder($order, $deliveryOptions);
        $orderWeight           = (float) $order->get_meta(WCMYPA_Admin::META_ORDER_WEIGHT);
        $digitalStampWeight    = $extraOptionsMeta["weight"] ?? WCMP_Export::getDigitalStampRangeFromWeight($orderWeight);

        $shipmentOptions = self::getShipmentOptions(
            $deliveryOptions->getShipmentOptions(),
            $deliveryOptions->getCarrier()
        );

        $conditionCarrierDefault = [
            "parent_name"  => self::OPTION_CARRIER,
            "type"         => "show",
            "parent_value" => WCMP_Data::DEFAULT_CARRIER,
            "set_value"    => WCMP_Settings_Data::DISABLED,
        ];

        $conditionDeliveryTypeDelivery = [
            "parent_name"  => self::OPTION_DELIVERY_TYPE,
            "type"         => "show",
            "parent_value" => [
                AbstractConsignment::DELIVERY_TYPE_MORNING_NAME,
                AbstractConsignment::DELIVERY_TYPE_STANDARD_NAME,
                AbstractConsignment::DELIVERY_TYPE_EVENING_NAME,
            ],
            "set_value"    => WCMP_Settings_Data::DISABLED,
        ];

        $conditionPackageTypePackage = [
            "parent_name"   => self::OPTION_PACKAGE_TYPE,
            "type"          => "show",
            "parent_value"  => AbstractConsignment::PACKAGE_TYPE_PACKAGE_NAME,
        ];

        $conditionForceEnabledOnAgeCheck = [
            "parent_name"  => self::OPTION_SHIPMENT_OPTIONS_AGE_CHECK,
            "type"         => "disable",
            "set_value"    => WCMP_Settings_Data::ENABLED,
            "parent_value" => WCMP_Settings_Data::DISABLED,
        ];

        return [
            [
                "name"              => self::OPTION_CARRIER,
                "label"             => __("Carrier", "woocommerce-myparcel"),
                "type"              => "select",
                "options"           => WCMP_Data::CARRIERS_HUMAN,
                "custom_attributes" => ["disabled" => "disabled"],
                "value"             => $deliveryOptions->getCarrier() ?? PostNLConsignment::CARRIER_NAME,
            ],
            [
                "name"              => self::OPTION_DELIVERY_TYPE,
                "label"             => __("Delivery type", "woocommerce-myparcel"),
                "type"              => "select",
                "options"           => WCMP_Data::getDeliveryTypesHuman(),
                "custom_attributes" => ["disabled" => "disabled"],
                "value"             => $deliveryOptions->getDeliveryType(),
            ],
            [
                "name"              => self::OPTION_PACKAGE_TYPE,
                "label"             => __("Shipment type", "woocommerce-myparcel"),
                "type"              => "select",
                "options"           => array_combine(WCMP_Data::getPackageTypes(), WCMP_Data::getPackageTypesHuman()),
                "value"             => $selectedPackageType,
                "custom_attributes" => $isPackageTypeDisabled
                    ? ["disabled" => "disabled"]
                    : [],
            ],
            [
                "name"              => self::OPTION_EXTRA_OPTIONS_COLLO_AMOUNT,
                "label"             => __("Number of labels", "woocommerce-myparcel"),
                "type"              => "number",
                "value"             => $extraOptionsMeta["collo_amount"] ?? 1,
                "custom_attributes" => [
                    "step" => "1",
                    "min"  => "1",
                    "max"  => "10",
                ],
                "condition"         => [
                    $conditionPackageTypePackage,
                ],
            ],
            [
                "name"        => self::OPTION_EXTRA_OPTIONS_WEIGHT,
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
                "condition"   => [
                    $conditionCarrierDefault,
                    [
                        "parent_name"  => self::OPTION_PACKAGE_TYPE,
                        "type"         => "show",
                        "parent_value" => AbstractConsignment::PACKAGE_TYPE_DIGITAL_STAMP_NAME,
                    ],
                ],
            ],
            [
                "name"      => self::OPTION_SHIPMENT_OPTIONS_ONLY_RECIPIENT,
                "type"      => "toggle",
                "label"     => __("Home address only", "woocommerce-myparcel"),
                "help_text" => __(
                    "If you don't want the parcel to be delivered at the neighbours, choose this option.",
                    "woocommerce-myparcel"
                ),
                "value"     => $shipmentOptions['hasOnlyRecipient'],
                "condition" => [
                    $conditionPackageTypePackage,
                    $conditionDeliveryTypeDelivery,
                    $conditionCarrierDefault,
                    $conditionForceEnabledOnAgeCheck,
                ],
            ],
            [
                "name"      => self::OPTION_SHIPMENT_OPTIONS_SIGNATURE,
                "type"      => "toggle",
                "label"     => __("Signature on delivery", "woocommerce-myparcel"),
                "value"     => $shipmentOptions['hasSignature'],
                "help_text" => __(
                    "The parcel will be offered at the delivery address. If the recipient is not at home, the parcel will be delivered to the neighbours. In both cases, a signature will be required.",
                    "woocommerce-myparcel"
                ),
                "condition" => [
                    $conditionPackageTypePackage,
                    $conditionDeliveryTypeDelivery,
                    $conditionCarrierDefault,
                    $conditionForceEnabledOnAgeCheck,
                ],
            ],
            [
                "name"      => self::OPTION_SHIPMENT_OPTIONS_AGE_CHECK,
                "type"      => "toggle",
                "label"     => __("Age check 18+", "woocommerce-myparcel"),
                "help_text" => __(
                    "The age check is intended for parcel shipments for which the recipient must show 18+ by means of a proof of identity. With this shipping option Signature for receipt and Delivery only at recipient are included. The age 18+ is further excluded from the delivery options morning and evening delivery.",
                    "woocommerce-myparcel"
                ),
                "value"     => $shipmentOptions['hasAgeCheck'],
                "condition" => [
                    $conditionPackageTypePackage,
                    $conditionDeliveryTypeDelivery,
                    $conditionCarrierDefault,
                ],
            ],
            [
                "name"      => self::OPTION_SHIPMENT_OPTIONS_LARGE_FORMAT,
                "type"      => "toggle",
                "label"     => __("Extra large size", "woocommerce-myparcel"),
                "help_text" => __(
                    "Enable this option when your shipment is bigger than 100 x 70 x 50 cm, but smaller than 175 x 78 x 58 cm. An extra fee will be charged. Note! If the parcel is bigger than 175 x 78 x 58 of or heavier than 30 kg, the pallet rate will be charged.",
                    "woocommerce-myparcel"
                ),
                "value"     => $shipmentOptions['hasLargeFormat'],
                "condition" => [
                    $conditionPackageTypePackage,
                    $conditionDeliveryTypeDelivery,
                    $conditionCarrierDefault,
                ],
            ],
            [
                "name"      => self::OPTION_SHIPMENT_OPTIONS_RETURN_SHIPMENT,
                "type"      => "toggle",
                "label"     => __("Return if no answer", "woocommerce-myparcel"),
                "value"     => $shipmentOptions['isReturn'],
                "help_text" => __(
                    "By default, a parcel will be offered twice. After two unsuccessful delivery attempts, the parcel will be available at the nearest pickup point for two weeks. There it can be picked up by the recipient with the note that was left by the courier. If you want to receive the parcel back directly and NOT forward it to the pickup point, enable this option.",
                    "woocommerce-myparcel"
                ),
                "condition" => [
                    $conditionPackageTypePackage,
                    $conditionDeliveryTypeDelivery,
                    $conditionCarrierDefault,
                ],
            ],
            [
                "name"      => self::OPTION_SHIPMENT_OPTIONS_INSURED,
                "type"      => "toggle",
                "label"     => __("Insured", "woocommerce-myparcel"),
                "value"     => (bool) $shipmentOptions['insurance'],
                "condition" => [
                    $conditionPackageTypePackage,
                    $conditionDeliveryTypeDelivery,
                    [
                        "parent_name"  => self::OPTION_CARRIER,
                        "type"         => "disable",
                        "parent_value" => WCMP_Data::DEFAULT_CARRIER,
                        "set_value"    => WCMP_Settings_Data::DISABLED,
                    ],
                ],
            ],
            [
                "name"      => self::OPTION_SHIPMENT_OPTIONS_INSURED_AMOUNT,
                "type"      => "select",
                "label"     => __("Insurance amount", "woocommerce-myparcel"),
                "options"   => WCMP_Data::getInsuranceAmount(),
                "value"     => (int) $shipmentOptions['insurance'],
                "condition" => [
                    $conditionPackageTypePackage,
                    $conditionDeliveryTypeDelivery,
                    self::OPTION_SHIPMENT_OPTIONS_INSURED,
                ],
            ],
            [
                "name"  => self::OPTION_SHIPMENT_OPTIONS_LABEL_DESCRIPTION,
                "type"  => "text",
                "label" => __("Custom ID (top left on label)", "woocommerce-myparcel"),
                "value" => $shipmentOptions['label_description'] ?? null,
            ],
        ];
    }

    /**
     * Filters out rows that should not be shown if the shipment is sent to the home country.
     *
     * @param string $cc
     * @param array  $rows
     *
     * @return array
     */
    public static function filterRowsByCountry(string $cc, array $rows): array
    {
        if (WCMP_Data::DEFAULT_COUNTRY_CODE === $cc) {
            return $rows;
        }

        return array_filter(
            $rows,
            function ($row) {
                return ! in_array($row['name'], self::HOME_COUNTRY_ONLY_ROWS);
            }
        );
    }

    /**
     * @param \MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractShipmentOptionsAdapter $shipmentOptions
     * @param string|null                                                                $carrier
     *
     * @return array
     */
    private static function getShipmentOptions(
        AbstractShipmentOptionsAdapter $shipmentOptions,
        ?string $carrier = null
    ): array {
        $carrier = $carrier ?? WCMP_Data::DEFAULT_CARRIER;

        return array_map(
            static function ($item) {
                return WCMP_Export::getChosenOrDefaultShipmentOption($item[0], $item[1]);
            },
            [
                'hasSignature'      => [
                    $shipmentOptions->hasSignature(),
                    "{$carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_SIGNATURE,
                ],
                'hasOnlyRecipient'  => [
                    $shipmentOptions->hasOnlyRecipient(),
                    "{$carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_ONLY_RECIPIENT,
                ],
                'hasAgeCheck'       => [
                    $shipmentOptions->hasAgeCheck(),
                    "{$carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_AGE_CHECK,
                ],
                'hasLargeFormat'    => [
                    $shipmentOptions->hasLargeFormat(),
                    "{$carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_LARGE_FORMAT,
                ],
                'isReturn'          => [
                    $shipmentOptions->isReturn(),
                    "{$carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_RETURN,
                ],
                'insurance'         => [
                    $shipmentOptions->getInsurance(),
                    "{$carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED_AMOUNT,
                ],
                'label_description' => [
                    $shipmentOptions->getInsurance(),
                    WCMYPA_Settings::SETTING_LABEL_DESCRIPTION,
                ],
            ]
        );
    }
}