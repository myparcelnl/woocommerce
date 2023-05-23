<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\admin;

defined('ABSPATH') or die();

use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter;
use MyParcelNL\Sdk\src\Factory\ConsignmentFactory;
use MyParcelNL\Sdk\src\Model\Carrier\AbstractCarrier;
use MyParcelNL\Sdk\src\Model\Carrier\CarrierDHLEuroplus;
use MyParcelNL\Sdk\src\Model\Carrier\CarrierDHLForYou;
use MyParcelNL\Sdk\src\Model\Carrier\CarrierDHLParcelConnect;
use MyParcelNL\Sdk\src\Model\Carrier\CarrierPostNL;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\WooCommerce\includes\Settings\Api\AccountSettings;
use WC_Order;
use WCMP_Country_Codes;
use WCMP_Data;
use WCMP_Export;
use WCMP_Settings_Data;

class OrderSettingsRows
{
    private const HOME_COUNTRY_ONLY_ROWS                    = [
        self::OPTION_SHIPMENT_OPTIONS_AGE_CHECK,
        self::OPTION_SHIPMENT_OPTIONS_ONLY_RECIPIENT,
        self::OPTION_SHIPMENT_OPTIONS_SIGNATURE,
    ];
    private const DHL_ONLY_ROWS                             = [
        self::OPTION_SHIPMENT_OPTIONS_AGE_CHECK,
        self::OPTION_SHIPMENT_OPTIONS_ONLY_RECIPIENT,
    ];
    private const OPTION_CARRIER                            = '[carrier]';
    private const OPTION_DELIVERY_TYPE                      = '[delivery_type]';
    private const OPTION_EXTRA_OPTIONS_COLLO_AMOUNT         = '[extra_options][collo_amount]';
    private const OPTION_EXTRA_OPTIONS_DIGITAL_STAMP_WEIGHT = '[extra_options][digital_stamp_weight]';
    private const OPTION_PACKAGE_TYPE                       = '[package_type]';
    private const OPTION_SHIPMENT_OPTIONS_INSURED           = '[shipment_options][insured]';
    private const OPTION_SHIPMENT_OPTIONS_INSURED_AMOUNT    = '[shipment_options][insured_amount]';
    private const OPTION_SHIPMENT_OPTIONS_LABEL_DESCRIPTION = '[shipment_options][label_description]';
    private const OPTION_SHIPMENT_OPTIONS_LARGE_FORMAT      = '[shipment_options][large_format]';
    private const OPTION_SHIPMENT_OPTIONS_ONLY_RECIPIENT    = '[shipment_options][only_recipient]';
    private const OPTION_SHIPMENT_OPTIONS_RETURN_SHIPMENT   = '[shipment_options][return]';
    private const OPTION_SHIPMENT_OPTIONS_SAME_DAY_DELIVERY = '[shipment_options][same_day_delivery]';
    private const OPTION_SHIPMENT_OPTIONS_SIGNATURE         = '[shipment_options][signature]';
    private const OPTION_SHIPMENT_OPTIONS_AGE_CHECK         = '[shipment_options][age_check]';
    private const OPTION_SHIPMENT_OPTIONS_HIDE_SENDER       = '[shipment_options][hide_sender]';
    private const OPTION_SHIPMENT_OPTIONS_EXTRA_ASSURANCE   = '[shipment_options][extra_assurance]';
    /**
     * Maps shipment options in this form to their respective name in the SDK.
     */
    private const SHIPMENT_OPTIONS_ROW_MAP             = [
        self::OPTION_SHIPMENT_OPTIONS_AGE_CHECK         => AbstractConsignment::SHIPMENT_OPTION_AGE_CHECK,
        self::OPTION_SHIPMENT_OPTIONS_INSURED           => AbstractConsignment::SHIPMENT_OPTION_INSURANCE,
        self::OPTION_SHIPMENT_OPTIONS_LARGE_FORMAT      => AbstractConsignment::SHIPMENT_OPTION_LARGE_FORMAT,
        self::OPTION_SHIPMENT_OPTIONS_ONLY_RECIPIENT    => AbstractConsignment::SHIPMENT_OPTION_ONLY_RECIPIENT,
        self::OPTION_SHIPMENT_OPTIONS_RETURN_SHIPMENT   => AbstractConsignment::SHIPMENT_OPTION_RETURN,
        self::OPTION_SHIPMENT_OPTIONS_SAME_DAY_DELIVERY => AbstractConsignment::SHIPMENT_OPTION_SAME_DAY_DELIVERY,
        self::OPTION_SHIPMENT_OPTIONS_SIGNATURE         => AbstractConsignment::SHIPMENT_OPTION_SIGNATURE,
        self::OPTION_SHIPMENT_OPTIONS_HIDE_SENDER       => AbstractConsignment::SHIPMENT_OPTION_HIDE_SENDER,
        self::OPTION_SHIPMENT_OPTIONS_EXTRA_ASSURANCE   => AbstractConsignment::SHIPMENT_OPTION_EXTRA_ASSURANCE,
    ];
    private const CONDITION_DELIVERY_TYPE_DELIVERY     = [
        'parent_name'  => self::OPTION_DELIVERY_TYPE,
        'type'         => 'show',
        'parent_value' => [
            AbstractConsignment::DELIVERY_TYPE_MORNING_NAME,
            AbstractConsignment::DELIVERY_TYPE_STANDARD_NAME,
            AbstractConsignment::DELIVERY_TYPE_EVENING_NAME,
        ],
        'set_value'    => WCMP_Settings_Data::DISABLED,
    ];
    private const CONDITION_PACKAGE_TYPE_PACKAGE       = [
        'parent_name'  => self::OPTION_PACKAGE_TYPE,
        'type'         => 'show',
        'parent_value' => AbstractConsignment::PACKAGE_TYPE_PACKAGE_NAME,
    ];
    private const CONDITION_FORCE_ENABLED_ON_AGE_CHECK = [
        'parent_name'  => self::OPTION_SHIPMENT_OPTIONS_AGE_CHECK,
        'type'         => 'disable',
        'set_value'    => WCMP_Settings_Data::ENABLED,
        'parent_value' => WCMP_Settings_Data::DISABLED,
    ];
    private const CONDITION_FORCE_ENABLED              = [
        'parent_name'  => self::OPTION_CARRIER,
        'type'         => 'disable',
        'parent_value' => WCMP_Settings_Data::ENABLED,
    ];

    /**
     * @var \MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter
     */
    private $deliveryOptions;

    /**
     * @var \WC_Order
     */
    private $order;

    public function __construct(AbstractDeliveryOptionsAdapter $deliveryOptions, WC_Order $order)
    {
        $this->deliveryOptions = $deliveryOptions;
        $this->order           = $order;
    }

    /**
     * Filters out rows that should not be shown if the shipment is sent to the home country.
     *
     * @param  string $cc
     * @param  array  $rows
     *
     * @return array
     */
    public function filterRowsByCountry(string $cc, array $rows, string $carrier): array
    {
        if (WCMP_Data::DEFAULT_COUNTRY_CODE === $cc) {
            return $rows;
        }

        $shipmentOptions = self::HOME_COUNTRY_ONLY_ROWS;

        if (in_array($carrier, [CarrierDHLEuroplus::NAME, CarrierDHLParcelConnect::NAME], true)) {
            $shipmentOptions = self::DHL_ONLY_ROWS;
        }

        return array_filter($rows, static function ($row) use ($shipmentOptions) {
            return ! in_array($row['name'], $shipmentOptions, true);
        });
    }

    /**
     * @return array[]
     * @throws \JsonException
     * @throws \Exception
     */
    public function getOptionsRows(): array
    {
        $orderSettings      = new OrderSettings($this->order, $this->deliveryOptions);
        $shippingCountry    = $orderSettings->getShippingCountry();
        $isEuCountry        = WCMP_Country_Codes::isEuCountry($shippingCountry);
        $isHomeCountry      = WCMP_Data::isHomeCountry($shippingCountry);
        $packageTypeOptions = array_combine(WCMP_Data::getPackageTypes(), WCMP_Data::getPackageTypesHuman());

        $isDhlEuroPlusOrParcelConnect = in_array(
            $this->deliveryOptions->getCarrier(),
            [CarrierDHLEuroplus::NAME, CarrierDHLParcelConnect::NAME],
            true
        );

        // Remove mailbox and digital stamp, because this is not possible for international shipments
        if (! $isHomeCountry) {
            unset($packageTypeOptions['mailbox'], $packageTypeOptions['digital_stamp']);
        }

        $rows = [
            [
                'name'    => self::OPTION_CARRIER,
                'label'   => __('Carrier', 'woocommerce-myparcel'),
                'type'    => 'select',
                'options' => $this->getCarrierOptions($shippingCountry),
                'value'   => $this->deliveryOptions->getCarrier() ?? CarrierPostNL::NAME,
            ],
            [
                'name'              => self::OPTION_DELIVERY_TYPE,
                'label'             => __('Delivery type', 'woocommerce-myparcel'),
                'type'              => 'select',
                'options'           => WCMP_Data::getDeliveryTypesHuman(),
                'custom_attributes' => ['disabled' => 'disabled'],
                'value'             => $this->deliveryOptions->getDeliveryType(),
            ],
            [
                'name'      => self::OPTION_PACKAGE_TYPE,
                'label'     => __('Shipment type', 'woocommerce-myparcel'),
                'type'      => 'select',
                'options'   => $packageTypeOptions,
                'value'     => WCMYPA()->export->getPackageTypeFromOrder($this->order, $this->deliveryOptions),
                'condition' => [
                    $this->getCarrierPackageTypesCondition(),
                ],
            ],
            [
                'name'              => self::OPTION_EXTRA_OPTIONS_COLLO_AMOUNT,
                'label'             => __('Number of labels', 'woocommerce-myparcel'),
                'type'              => 'number',
                'value'             => $orderSettings->getColloAmount(),
                'custom_attributes' => [
                    'min' => '1',
                    'max' => '10',
                ],
            ],
        ];

        // Only add extra options and shipment options to home country shipments.
        if ($isHomeCountry) {
            $rows = array_merge($rows, $this->getAdditionalOptionsRows($orderSettings));
        }

        $rows[] = [
            'name'      => self::OPTION_SHIPMENT_OPTIONS_INSURED,
            'type'      => 'toggle',
            'label'     => __('insured', 'woocommerce-myparcel'),
            'value'     => $orderSettings->isInsured(),
            'condition' => [
                self::CONDITION_PACKAGE_TYPE_PACKAGE,
                $this->getCarriersWithFeatureCondition(self::OPTION_SHIPMENT_OPTIONS_INSURED),
            ],
        ];
        $rows[] = [
            'name'      => self::OPTION_SHIPMENT_OPTIONS_INSURED_AMOUNT,
            'type'      => 'select',
            'label'     => __('insured_amount', 'woocommerce-myparcel'),
            'options'   => WCMP_Data::getInsuranceAmounts(
                $shippingCountry,
                $orderSettings->getDeliveryOptions()
                    ->getCarrier()
            ),
            'value'     => $orderSettings->getInsuranceAmount(),
            'condition' => [
                self::CONDITION_PACKAGE_TYPE_PACKAGE,
                $this->getCarriersWithFeatureCondition(self::OPTION_SHIPMENT_OPTIONS_INSURED),
                self::OPTION_SHIPMENT_OPTIONS_INSURED,
            ],
        ];

        if ($isEuCountry) {
            $rows[] = [
                'name'      => self::OPTION_SHIPMENT_OPTIONS_LARGE_FORMAT,
                'type'      => 'toggle',
                'label'     => __('shipment_options_large_format', 'woocommerce-myparcel'),
                'help_text' => __('shipment_options_large_format_help_text', 'woocommerce-myparcel'),
                'value'     => $orderSettings->hasLargeFormat(),
                'condition' => [
                    self::CONDITION_PACKAGE_TYPE_PACKAGE,
                    $this->getCarriersWithFeatureCondition(self::OPTION_SHIPMENT_OPTIONS_LARGE_FORMAT),
                ],
            ];
        }

        if ($isDhlEuroPlusOrParcelConnect) {
            $rows[] = [
                'name'      => self::OPTION_SHIPMENT_OPTIONS_SIGNATURE,
                'type'      => 'toggle',
                'label'     => __('shipment_options_signature', 'woocommerce-myparcel'),
                'help_text' => __('shipment_options_signature_help_text', 'woocommerce-myparcel'),
                'value'     => true,
                'condition' => [
                    self::CONDITION_PACKAGE_TYPE_PACKAGE,
                    self::CONDITION_FORCE_ENABLED,
                    $this->getCarriersWithFeatureCondition(self::OPTION_SHIPMENT_OPTIONS_SIGNATURE),
                ],
            ];
        }

        $rows[] = [
            'name'  => self::OPTION_SHIPMENT_OPTIONS_LABEL_DESCRIPTION,
            'type'  => 'text',
            'label' => __('Custom ID (top left on label)', 'woocommerce-myparcel'),
            'value' => $orderSettings->getLabelDescription(),
        ];

        return $rows;
    }

    /**
     * @param  OrderSettings $orderSettings
     *
     * @return array[]
     * @throws \Exception
     */
    private function getAdditionalOptionsRows(OrderSettings $orderSettings): array
    {
        $carrier = $orderSettings->getDeliveryOptions()
            ->getCarrier();
        $rows    = [
            [
                'name'        => self::OPTION_EXTRA_OPTIONS_DIGITAL_STAMP_WEIGHT,
                'type'        => 'select',
                'label'       => __('weight', 'woocommerce-myparcel'),
                'description' => sprintf(
                    __('calculated_order_weight', 'woocommerce-myparcel'),
                    wc_format_weight($orderSettings->getWeight())
                ),
                'options'     => WCMP_Export::getDigitalStampRangeOptions(),
                'value'       => $orderSettings->getDigitalStampRangeWeight(),
                'condition'   => [
                    [
                        'parent_name'  => self::OPTION_PACKAGE_TYPE,
                        'type'         => 'show',
                        'parent_value' => AbstractConsignment::PACKAGE_TYPE_DIGITAL_STAMP_NAME,
                    ],
                ],
            ],
            [
                'name'      => self::OPTION_SHIPMENT_OPTIONS_ONLY_RECIPIENT,
                'type'      => 'toggle',
                'label'     => __('shipment_options_only_recipient', 'woocommerce-myparcel'),
                'help_text' => __('shipment_options_only_recipient_help_text', 'woocommerce-myparcel'),
                'value'     => $orderSettings->hasOnlyRecipient(),
                'condition' => CarrierPostNL::NAME === $carrier
                    ? [
                        self::CONDITION_PACKAGE_TYPE_PACKAGE,
                        self::CONDITION_DELIVERY_TYPE_DELIVERY,
                        $this->getCarriersWithFeatureCondition(self::OPTION_SHIPMENT_OPTIONS_ONLY_RECIPIENT),
                        self::CONDITION_FORCE_ENABLED_ON_AGE_CHECK,
                    ]
                    : [
                        self::CONDITION_PACKAGE_TYPE_PACKAGE,
                        self::CONDITION_DELIVERY_TYPE_DELIVERY,
                        $this->getCarriersWithFeatureCondition(self::OPTION_SHIPMENT_OPTIONS_ONLY_RECIPIENT),
                    ],
            ],
            [
                'name'      => self::OPTION_SHIPMENT_OPTIONS_AGE_CHECK,
                'type'      => 'toggle',
                'label'     => __('shipment_options_age_check', 'woocommerce-myparcel'),
                'help_text' => __('shipment_options_age_check_help_text', 'woocommerce-myparcel'),
                'value'     => $orderSettings->hasAgeCheck(),
                'condition' => [
                    self::CONDITION_PACKAGE_TYPE_PACKAGE,
                    $this->getCarriersWithFeatureCondition(self::OPTION_SHIPMENT_OPTIONS_AGE_CHECK),
                ],
            ],
            [
                'name'      => self::OPTION_SHIPMENT_OPTIONS_EXTRA_ASSURANCE,
                'type'      => 'toggle',
                'label'     => __('shipment_options_extra_assurance', 'woocommerce-myparcel'),
                'help_text' => __('shipment_options_extra_assurance_help_text', 'woocommerce-myparcel'),
                'value'     => $orderSettings->hasExtraAssurance(),
                'condition' => [
                    self::CONDITION_PACKAGE_TYPE_PACKAGE,
                    self::CONDITION_DELIVERY_TYPE_DELIVERY,
                    $this->getCarriersWithFeatureCondition(self::OPTION_SHIPMENT_OPTIONS_EXTRA_ASSURANCE),
                ],
            ],
            [
                'name'      => self::OPTION_SHIPMENT_OPTIONS_RETURN_SHIPMENT,
                'type'      => 'toggle',
                'label'     => __('shipment_options_return', 'woocommerce-myparcel'),
                'help_text' => __('shipment_options_return_help_text', 'woocommerce-myparcel'),
                'value'     => $orderSettings->hasReturnShipment(),
                'condition' => [
                    self::CONDITION_PACKAGE_TYPE_PACKAGE,
                    self::CONDITION_DELIVERY_TYPE_DELIVERY,
                    $this->getCarriersWithFeatureCondition(self::OPTION_SHIPMENT_OPTIONS_RETURN_SHIPMENT),
                ],
            ],
            [
                'name'      => self::OPTION_SHIPMENT_OPTIONS_HIDE_SENDER,
                'type'      => 'toggle',
                'label'     => __('shipment_options_hide_sender', 'woocommerce-myparcel'),
                'help_text' => __('shipment_options_hide_sender_help_text', 'woocommerce-myparcel'),
                'value'     => $orderSettings->hasHideSender(),
                'condition' => [
                    self::CONDITION_DELIVERY_TYPE_DELIVERY,
                    $this->getCarriersWithFeatureCondition(self::OPTION_SHIPMENT_OPTIONS_HIDE_SENDER),
                    [
                        'parent_name'  => self::OPTION_SHIPMENT_OPTIONS_EXTRA_ASSURANCE,
                        'type'         => 'disable',
                        'set_value'    => WCMP_Settings_Data::DISABLED,
                        'parent_value' => WCMP_Settings_Data::DISABLED,
                    ],
                ],
            ],
            [
                'name'      => self::OPTION_SHIPMENT_OPTIONS_SAME_DAY_DELIVERY,
                'type'      => 'toggle',
                'label'     => __('shipment_options_same_day_delivery', 'woocommerce-myparcel'),
                'help_text' => __('shipment_options_same_day_delivery_help_text', 'woocommerce-myparcel'),
                'value'     => $orderSettings->hasSameDayDelivery(),
                'condition' => [
                    $this->getCarriersWithFeatureCondition(self::OPTION_SHIPMENT_OPTIONS_SAME_DAY_DELIVERY),
                ],
            ],
        ];

        if (in_array($carrier, [CarrierPostNL::NAME, CarrierDHLForYou::NAME], true)) {
            $rows[] = [
                'name'      => self::OPTION_SHIPMENT_OPTIONS_SIGNATURE,
                'type'      => 'toggle',
                'label'     => __('shipment_options_signature', 'woocommerce-myparcel'),
                'help_text' => __('shipment_options_signature_help_text', 'woocommerce-myparcel'),
                'value'     => $orderSettings->hasSignature(),
            ];
        }

        return $rows;
    }

    /**
     * @param  string $shippingCountry
     *
     * @return array
     */
    private function getCarrierOptions(string $shippingCountry): array
    {
        return AccountSettings::getInstance()
            ->getCarriersForCountry($shippingCountry)
            ->reduce(function (array $carry, AbstractCarrier $carrier) {
                $carry[$carrier->getName()] = $carrier->getHuman();

                return $carry;
            }, []);
    }

    /**
     * @return array
     */
    private function getCarrierPackageTypesCondition(): array
    {
        return [
            'parent_name'  => self::OPTION_CARRIER,
            'type'         => 'options',
            'parent_value' =>
                AccountSettings::getInstance()
                    ->getEnabledCarriers()
                    ->map(function (AbstractCarrier $carrier) {
                        return [
                            $carrier->getName() => ConsignmentFactory::createFromCarrier($carrier)
                                ->getAllowedPackageTypes(),
                        ];
                    })
                    ->getIterator(),
            'set_value'    => AbstractConsignment::DEFAULT_PACKAGE_TYPE_NAME,
        ];
    }

    /**
     * @param  string $feature
     *
     * @return array
     */
    private function getCarriersWithFeatureCondition(string $feature): array
    {
        $carriers = AccountSettings::getInstance()
            ->getEnabledCarriers();

        $shipmentOption      = self::SHIPMENT_OPTIONS_ROW_MAP[$feature];
        $carriersWithFeature = [];

        /** @var \MyParcelNL\Sdk\src\Model\Account\CarrierOptions $carrier */
        foreach ($carriers as $carrier) {
            $carrierClass    = ConsignmentFactory::createFromCarrier($carrier);
            $shipmentOptions = $this->deliveryOptions->isPickup()
                ? $carrierClass->getAllowedShipmentOptionsForPickup()
                : $carrierClass->getAllowedShipmentOptions();

            if (in_array($shipmentOption, $shipmentOptions, true)) {
                $carriersWithFeature[] = $carrier->getName();
            }
        }

        return [
            'parent_name'  => self::OPTION_CARRIER,
            'type'         => 'show',
            'parent_value' => $carriersWithFeature,
            'set_value'    => WCMP_Settings_Data::DISABLED,
        ];
    }
}
