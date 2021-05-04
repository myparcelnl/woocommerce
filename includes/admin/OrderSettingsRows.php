<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Includes\Admin;

use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\BpostConsignment;
use OrderSettings;
use WC_Order;
use WCMPBE_Country_Codes;
use WCMPBE_Data;
use WCMPBE_Export;
use WCMPBE_Settings_Data;

class OrderSettingsRows
{
    private const HOME_COUNTRY_ONLY_ROWS = [
        self::OPTION_SHIPMENT_OPTIONS_AGE_CHECK,
        self::OPTION_SHIPMENT_OPTIONS_ONLY_RECIPIENT,
        self::OPTION_SHIPMENT_OPTIONS_SIGNATURE,
    ];

    private const OPTION_CARRIER                            = "[carrier]";
    private const OPTION_DELIVERY_TYPE                      = "[delivery_type]";
    private const OPTION_EXTRA_OPTIONS_COLLO_AMOUNT         = "[extra_options][collo_amount]";
    private const OPTION_EXTRA_OPTIONS_DIGITAL_STAMP_WEIGHT = "[extra_options][digital_stamp_weight]";
    private const OPTION_PACKAGE_TYPE                       = "[package_type]";
    private const OPTION_SHIPMENT_OPTIONS_INSURED           = "[shipment_options][insured]";
    private const OPTION_SHIPMENT_OPTIONS_INSURED_AMOUNT    = "[shipment_options][insured_amount]";
    private const OPTION_SHIPMENT_OPTIONS_LABEL_DESCRIPTION = "[shipment_options][label_description]";
    private const OPTION_SHIPMENT_OPTIONS_LARGE_FORMAT      = "[shipment_options][large_format]";
    private const OPTION_SHIPMENT_OPTIONS_ONLY_RECIPIENT    = "[shipment_options][only_recipient]";
    private const OPTION_SHIPMENT_OPTIONS_RETURN_SHIPMENT   = "[shipment_options][return_shipment]";
    private const OPTION_SHIPMENT_OPTIONS_SIGNATURE         = "[shipment_options][signature]";
    private const OPTION_SHIPMENT_OPTIONS_AGE_CHECK         = "[shipment_options][age_check]";

    private const CONDITION_CARRIER_DEFAULT = [
        "parent_name"  => self::OPTION_CARRIER,
        "type"         => "show",
        "parent_value" => WCMPBE_Data::DEFAULT_CARRIER,
        "set_value"    => WCMPBE_Settings_Data::DISABLED,
    ];

    private const CONDITION_DELIVERY_TYPE_DELIVERY = [
        "parent_name"  => self::OPTION_DELIVERY_TYPE,
        "type"         => "show",
        "parent_value" => [
            AbstractConsignment::DELIVERY_TYPE_MORNING_NAME,
            AbstractConsignment::DELIVERY_TYPE_STANDARD_NAME,
            AbstractConsignment::DELIVERY_TYPE_EVENING_NAME,
        ],
        "set_value"    => WCMPBE_Settings_Data::DISABLED,
    ];

    private const CONDITION_PACKAGE_TYPE_PACKAGE = [
        "parent_name"  => self::OPTION_PACKAGE_TYPE,
        "type"         => "show",
        "parent_value" => AbstractConsignment::PACKAGE_TYPE_PACKAGE_NAME,
    ];


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
        $orderSettings      = new OrderSettings($order, $deliveryOptions);
        $shippingCountry    = $orderSettings->getShippingCountry();
        $isEuCountry        = WCMPBE_Country_Codes::isEuCountry($shippingCountry);
        $isHomeCountry      = WCMPBE_Data::isHomeCountry($shippingCountry);
        $packageTypeOptions = array_combine(WCMPBE_Data::getPackageTypes(), WCMPBE_Data::getPackageTypesHuman());

        // Remove mailbox and digital stamp, because this is not possible for international shipments
        if (! $isHomeCountry) {
            unset($packageTypeOptions['mailbox']);
            unset($packageTypeOptions['digital_stamp']);
        }

        $rows = [
            [
                "name"              => self::OPTION_CARRIER,
                "label"             => __("Carrier", "woocommerce-myparcelbe"),
                "type"              => "select",
                "options"           => WCMPBE_Data::CARRIERS_HUMAN,
                "value"             => $deliveryOptions->getCarrier() ?? BpostConsignment::CARRIER_NAME,
            ],
            [
                "name"              => self::OPTION_DELIVERY_TYPE,
                "label"             => __("Delivery type", "woocommerce-myparcelbe"),
                "type"              => "select",
                "options"           => WCMPBE_Data::getDeliveryTypesHuman(),
                "custom_attributes" => ["disabled" => "disabled"],
                "value"             => $deliveryOptions->getDeliveryType(),
            ],
            [
                "name"              => self::OPTION_PACKAGE_TYPE,
                "label"             => __("Shipment type", "woocommerce-myparcelbe"),
                "type"              => "select",
                "options"           => $packageTypeOptions,
                "custom_attributes" => ["disabled" => "disabled"],
                "value"             => WCMYPABE()->export->getPackageTypeFromOrder($order, $deliveryOptions),
            ],
            [
                "name"              => self::OPTION_EXTRA_OPTIONS_COLLO_AMOUNT,
                "label"             => __("Number of labels", "woocommerce-myparcelbe"),
                "type"              => "number",
                "value"             => $orderSettings->getColloAmount(),
                "custom_attributes" => [
                    "min" => "1",
                    "max" => "10",
                ],
            ],
        ];

        // Only add extra options and shipment options to home country shipments.
        if ($isHomeCountry) {
            $rows = array_merge($rows, self::getAdditionalOptionsRows($orderSettings));
        }

        if ($isEuCountry) {
            $rows[] = [
                "name"      => self::OPTION_SHIPMENT_OPTIONS_LARGE_FORMAT,
                "type"      => "toggle",
                "label"     => __("shipment_options_large_format", "woocommerce-myparcelbe"),
                "help_text" => __("shipment_options_large_format_help_text", "woocommerce-myparcelbe"),
                "value"     => $orderSettings->hasLargeFormat(),
                "condition" => [
                    self::CONDITION_PACKAGE_TYPE_PACKAGE,
                    self::CONDITION_DELIVERY_TYPE_DELIVERY,
                    self::CONDITION_CARRIER_DEFAULT,
                ],
            ];
        }


        $rows[] = [
            "name"  => self::OPTION_SHIPMENT_OPTIONS_LABEL_DESCRIPTION,
            "type"  => "text",
            "label" => __("Custom ID (top left on label)", "woocommerce-myparcelbe"),
            "value" => $orderSettings->getLabelDescription(),
        ];

        return $rows;
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
        if (WCMPBE_Data::DEFAULT_COUNTRY_CODE === $cc) {
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
     * @param \OrderSettings $orderSettings
     *
     * @return array[]
     */
    private static function getAdditionalOptionsRows(OrderSettings $orderSettings): array
    {
        return [
            [
                "name"        => self::OPTION_EXTRA_OPTIONS_DIGITAL_STAMP_WEIGHT,
                "type"        => "select",
                "label"       => __("weight", "woocommerce-myparcelbe"),
                "description" => sprintf(
                    __("calculated_order_weight", "woocommerce-myparcelbe"),
                    wc_format_weight($orderSettings->getWeight())
                ),
                "options"     => WCMPBE_Export::getDigitalStampRangeOptions(),
                "value"       => $orderSettings->getDigitalStampRangeWeight(),
                "condition"   => [
                    [
                        "parent_name"  => self::OPTION_CARRIER,
                        "type"         => "show",
                        "parent_value" => WCMPBE_Data::DEFAULT_CARRIER,
                    ],
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
                "label"     => __("shipment_options_only_recipient", "woocommerce-myparcelbe"),
                "help_text" => __("shipment_options_only_recipient_help_text", "woocommerce-myparcelbe"),
                "value"     => $orderSettings->hasOnlyRecipient(),
                "condition" => [
                    self::CONDITION_PACKAGE_TYPE_PACKAGE,
                    self::CONDITION_DELIVERY_TYPE_DELIVERY,
                    self::CONDITION_CARRIER_DEFAULT,
                ],
            ],
            [
                "name"      => self::OPTION_SHIPMENT_OPTIONS_SIGNATURE,
                "type"      => "toggle",
                "label"     => __("shipment_options_signature", "woocommerce-myparcelbe"),
                "help_text" => __("shipment_options_signature_help_text", "woocommerce-myparcelbe"),
                "value"     => $orderSettings->hasSignature(),
                "condition" => [
                    self::CONDITION_PACKAGE_TYPE_PACKAGE,
                    self::CONDITION_DELIVERY_TYPE_DELIVERY,
                    self::CONDITION_CARRIER_DEFAULT,
                ],
            ],
            [
                "name"      => self::OPTION_SHIPMENT_OPTIONS_INSURED,
                "type"      => "toggle",
                "label"     => __("insured", "woocommerce-myparcelbe"),
                "value"     => $orderSettings->isInsured(),
                "condition" => [
                    self::CONDITION_PACKAGE_TYPE_PACKAGE,
                    self::CONDITION_DELIVERY_TYPE_DELIVERY,
                    [
                        "parent_name"  => self::OPTION_CARRIER,
                        "type"         => "disable",
                        "parent_value" => WCMPBE_Data::DEFAULT_CARRIER,
                        "set_value"    => WCMPBE_Settings_Data::DISABLED,
                    ],
                ],
            ],
            [
                "name"      => self::OPTION_SHIPMENT_OPTIONS_INSURED_AMOUNT,
                "type"      => "select",
                "label"     => __("insured_amount", "woocommerce-myparcelbe"),
                "options"   => WCMPBE_Data::getInsuranceAmounts(),
                "value"     => $orderSettings->getInsuranceAmount(),
                "condition" => [
                    self::CONDITION_PACKAGE_TYPE_PACKAGE,
                    self::CONDITION_DELIVERY_TYPE_DELIVERY,
                    self::OPTION_SHIPMENT_OPTIONS_INSURED,
                ],
            ],
        ];
    }
}
