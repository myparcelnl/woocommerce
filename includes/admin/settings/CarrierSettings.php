<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\admin\settings;

defined('ABSPATH') or die();

use Data;
use MyParcelNL\Sdk\src\Factory\ConsignmentFactory;
use MyParcelNL\Sdk\src\Model\Carrier\AbstractCarrier;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\WooCommerce\includes\Settings\Api\AccountSettings;
use WCMP_Settings_Callbacks;
use WCMP_Settings_Data;
use WPO\WC\MyParcel\Collections\SettingsCollection;

class CarrierSettings
{
    /**
     * Map consignment options to correct settings and translations.
     */
    private const OPTIONS_EXTRA_DELIVERY_DAY_MAP = [
        AbstractConsignment::EXTRA_OPTION_DELIVERY_MONDAY   => [
            'cut_off_time_day'     => 'Saturday',
            'cut_off_time_setting' => 'saturday_cutoff_time',
            'day'                  => 'Monday',
            'setting'              => 'monday_delivery_enabled',
        ],
        AbstractConsignment::EXTRA_OPTION_DELIVERY_SATURDAY => [
            'cut_off_time_day'     => 'Friday',
            'cut_off_time_default' => '',
            'cut_off_time_setting' => 'friday_cutoff_time',
            'day'                  => 'Saturday',
            'fee'                  => 'saturday_delivery_fee',
            'setting'              => 'saturday_delivery_enabled',
        ],
    ];

    /**
     * @param  \MyParcelNL\Sdk\src\Model\Carrier\AbstractCarrier $carrier
     *
     * @return array
     * @throws \Exception
     */
    public function getCarrierSection(AbstractCarrier $carrier): array
    {
        $consignment = ConsignmentFactory::createFromCarrier($carrier)->setPackageType(AbstractConsignment::PACKAGE_TYPE_PACKAGE);

        return array_filter(
            [
                $this->getCarrierExportSettingsSection($carrier, $consignment),
                $this->getCarrierDeliveryOptionsSection($carrier, $consignment),
                $this->getCarrierPickupOptionsSection($carrier, $consignment),
                $this->getCarrierDropOffPointSection($carrier),
            ]
        );
    }

    /**
     * @param  \MyParcelNL\Sdk\src\Model\Carrier\AbstractCarrier $carrier
     *
     * @return string
     */
    public function getDropOffPointDescription(AbstractCarrier $carrier): string
    {
        $configuration = AccountSettings::getInstance()
            ->getCarrierConfigurationByCarrierId($carrier->getId());

        $dropOffPoint = $configuration ? $configuration->getDefaultDropOffPoint() : null;

        if (! $dropOffPoint) {
            return WCMP_Settings_Callbacks::getLink(
                __('diagnostics_status_drop_off_point_missing', 'woocommerce-myparcel'),
                self::getRetailOverviewLink($carrier)
            );
        }

        return sprintf(
            '<div class="wcmp__d--flex"><div class="wcmp__box"><strong>%s</strong><br />%s %s<br />%s %s<br/><br/>%s</div></div>',
            $dropOffPoint->getLocationName(),
            $dropOffPoint->getStreet(),
            $dropOffPoint->getNumber(),
            $dropOffPoint->getPostalCode(),
            $dropOffPoint->getCity(),
            WCMP_Settings_Callbacks::getLink(
                __('diagnostics_status_drop_off_point_manage', 'woocommerce-myparcel'),
                self::getRetailOverviewLink($carrier)
            )
        );
    }

    /**
     * @param  \MyParcelNL\Sdk\src\Model\Carrier\AbstractCarrier $carrier
     *
     * @return string
     */
    public static function getRetailOverviewLink(AbstractCarrier $carrier): string
    {
        return Status::LINK_RETAIL_OVERVIEW . "?carrier={$carrier->getId()}";
    }

    /**
     * @param  \MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment $consignment
     * @param  array                                                     $settings
     *
     * @return array
     */
    private function addDeliveryDateSettings(AbstractConsignment $consignment, array $settings): array
    {
        if ($consignment->canHaveExtraOption(AbstractConsignment::EXTRA_OPTION_DELIVERY_DATE)) {
            $settings[] = [
                'name'      => 'allow_show_delivery_date',
                'condition' => 'delivery_enabled',
                'label'     => __('feature_allow_show_delivery_date_title', 'woocommerce-myparcel'),
                'type'      => 'toggle',
                'default'   => WCMP_Settings_Data::ENABLED,
                'help_text' => __('feature_allow_show_delivery_date_help_text', 'woocommerce-myparcel'),
            ];
            $settings[] = [
                'name'      => 'delivery_days_window',
                'condition' => [
                    'delivery_enabled',
                    'allow_show_delivery_date',
                ],
                'class'     => ['wcmp__child'],
                'label'     => __('setting_carrier_delivery_days_window_title', 'woocommerce-myparcel'),
                'type'      => 'number',
                'min'       => 1,
                'max'       => 14,
                'default'   => 1,
                'help_text' => __('setting_carrier_delivery_days_window_help_text', 'woocommerce-myparcel'),
            ];
        }

        return $settings;
    }

    /**
     * @param  \MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment $consignment
     * @param  callable                                                  $callback
     *
     * @return array
     */
    private function createCarrierOptions(
        AbstractConsignment $consignment,
        callable            $callback
    ): array {
        $options  = $consignment->getAllowedShipmentOptions();
        $settings = [];

        foreach ($options as $option) {
            $settings[] = $callback($option);
        }

        return array_merge(...$settings);
    }

    /**
     * @param  string $option
     *
     * @return array
     * @throws \Exception
     */
    private function createDefaultExportSettingsArray(string $option): array
    {
        $settings = [];

        switch ($option) {
            case AbstractConsignment::SHIPMENT_OPTION_AGE_CHECK:
                $settings[] = [
                    'name'      => 'export_age_check',
                    'label'     => __('shipment_options_age_check', 'woocommerce-myparcel'),
                    'type'      => 'toggle',
                    'help_text' => __('shipment_options_age_check_help_text', 'woocommerce-myparcel'),
                ];
                break;
            case AbstractConsignment::SHIPMENT_OPTION_INSURANCE:
                $settings[] = [
                    'name'      => 'export_insured',
                    'label'     => __('shipment_options_insured', 'woocommerce-myparcel'),
                    'help_text' => __('shipment_options_insured_help_text', 'woocommerce-myparcel'),
                    'type'      => 'toggle',
                ];
                $settings[] = [
                    'name'      => 'export_insured_from_price',
                    'condition' => 'export_insured',
                    'label'     => __('shipment_options_insured_from_price', 'woocommerce-myparcel'),
                    'help_text' => __('shipment_options_insured_from_price_help_text', 'woocommerce-myparcel'),
                    'type'      => 'number',
                ];
                $settings[] = [
                    'name'      => 'export_insured_amount',
                    'condition' => 'export_insured',
                    'label'     => __('shipment_options_insured_amount', 'woocommerce-myparcel'),
                    'help_text' => __('shipment_options_insured_amount_help_text', 'woocommerce-myparcel'),
                    'type'      => 'select',
                    'options'   => Data::getInsuranceAmounts(),
                ];
                $settings[] = [
                    'name'      => 'export_insured_for_be',
                    'condition' => 'export_insured',
                    'label'     => __('shipment_options_insured_for_be', 'woocommerce-myparcel'),
                    'default'   => WCMP_Settings_Data::ENABLED,
                    'help_text' => __('shipment_options_insured_for_be_help_text', 'woocommerce-myparcel'),
                    'type'      => 'toggle',
                ];
                break;
            case AbstractConsignment::SHIPMENT_OPTION_LARGE_FORMAT:
                $settings[] = [
                    'name'      => 'export_large_format',
                    'label'     => __('shipment_options_large_format', 'woocommerce-myparcel'),
                    'help_text' => __('shipment_options_large_format_help_text', 'woocommerce-myparcel'),
                    'type'      => 'toggle',
                ];
                break;
            case AbstractConsignment::SHIPMENT_OPTION_ONLY_RECIPIENT:
                $settings[] = [
                    'name'      => 'export_only_recipient',
                    'label'     => __('shipment_options_only_recipient', 'woocommerce-myparcel'),
                    'help_text' => __('shipment_options_only_recipient_help_text', 'woocommerce-myparcel'),
                    'type'      => 'toggle',
                ];

                break;
            case AbstractConsignment::SHIPMENT_OPTION_RETURN:
                $settings[] = [
                    'name'      => 'export_return_shipments',
                    'label'     => __('shipment_options_return', 'woocommerce-myparcel'),
                    'help_text' => __('shipment_options_return_help_text', 'woocommerce-myparcel'),
                    'type'      => 'toggle',
                ];
                break;
            case AbstractConsignment::SHIPMENT_OPTION_SIGNATURE:
                $settings[] = [
                    'name'      => 'export_signature',
                    'label'     => __('shipment_options_signature', 'woocommerce-myparcel'),
                    'help_text' => __('shipment_options_signature_help_text', 'woocommerce-myparcel'),
                    'type'      => 'toggle',
                ];
                break;
            case AbstractConsignment::SHIPMENT_OPTION_SAME_DAY_DELIVERY:
                $settings[] = [
                    'name'      => 'export_same_day_delivery',
                    'label'     => __('shipment_options_same_day_delivery', 'woocommerce-myparcel'),
                    'help_text' => __('shipment_options_same_day_delivery_help_text', 'woocommerce-myparcel'),
                    'type'      => 'toggle',
                ];
                break;
        }

        return $settings;
    }

    /**
     * @param  string $option
     *
     * @return array
     */
    private function createDeliveryOptionsSettingsArray(string $option): array
    {
        $settings = [];
        switch ($option) {
            case AbstractConsignment::SHIPMENT_OPTION_ONLY_RECIPIENT:
                $settings[] = [
                    'name'      => 'only_recipient_enabled',
                    'condition' => 'delivery_enabled',
                    'label'     => __('shipment_options_only_recipient', 'woocommerce-myparcel'),
                    'type'      => 'toggle',
                ];
                $settings[] = WCMP_Settings_Data::getFeeField(
                    'only_recipient_fee',
                    [
                        'delivery_enabled',
                        'only_recipient_enabled',
                    ]
                );
                break;
            case AbstractConsignment::SHIPMENT_OPTION_SIGNATURE:
                $settings[] = [
                    'name'      => 'signature_enabled',
                    'condition' => 'delivery_enabled',
                    'label'     => __('shipment_options_signature', 'woocommerce-myparcel'),
                    'type'      => 'toggle',
                ];
                $settings[] = WCMP_Settings_Data::getFeeField(
                    'signature_fee',
                    [
                        'delivery_enabled',
                        'signature_enabled',
                    ]
                );
                break;
            case AbstractConsignment::SHIPMENT_OPTION_SAME_DAY_DELIVERY:
                $settings[] = [
                    'name'      => 'same_day_delivery',
                    'condition' => 'delivery_enabled',
                    'help_text' => __('shipment_options_same_day_delivery_help_text', 'woocommerce-myparcel'),
                    'label'     => __('shipment_options_same_day_delivery', 'woocommerce-myparcel'),
                    'type'      => 'toggle',
                ];
                $settings[] = WCMP_Settings_Data::getFeeField(
                    'same_day_delivery_fee',
                    [
                        'delivery_enabled',
                        'same_day_delivery',
                    ]
                );
                $settings[] = [
                    'name'  => 'same_day_delivery_cutoff_time',
                    'type'  => 'time',
                    'class' => ['wcmp__child'],
                    'condition' => [
                        'delivery_enabled',
                        'same_day_delivery',
                    ],
                    'label'     => __('setting_carrier_cut_off_time_title', 'woocommerce-myparcel'),
                    'help_text' => __('shipment_options_same_day_delivery_cutoff_time_help_text', 'woocommerce-myparcel'),
                    'default'   => '09:00',
                    'custom_attributes' => [
                        'min' => '00:00',
                        'max' => '10:00',
                    ],
                ];
                break;
        }

        return $settings;
    }

    /**
     * @param  \MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment $consignment
     *
     * @return array
     */
    private function createEveningDeliveryOptions(AbstractConsignment $consignment): array
    {
        if ($consignment->canHaveDeliveryType(AbstractConsignment::DELIVERY_TYPE_EVENING_NAME)) {
            return [
                [
                    'name'      => 'delivery_evening_enabled',
                    'condition' => 'delivery_enabled',
                    'label'     => __('shipment_options_delivery_evening', 'woocommerce-myparcel'),
                    'type'      => 'toggle',
                ],
                WCMP_Settings_Data::getFeeField(
                    'delivery_evening_fee',
                    [
                        'delivery_enabled',
                        'delivery_evening_enabled',
                    ]
                ),
            ];
        }

        return [];
    }

    /**
     * @param  \MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment $consignment
     *
     * @return void
     */
    private function createExtraDayDeliveryOptions(AbstractConsignment $consignment): array
    {
        $options = [];

        foreach (self::OPTIONS_EXTRA_DELIVERY_DAY_MAP as $consignmentOption => $settings) {
            if (! $consignment->canHaveExtraOption($consignmentOption)) {
                continue;
            }

            $options[] = [
                'name'      => $settings['setting'],
                'condition' => 'delivery_enabled',
                'label'     => sprintf(
                    __('settings_carrier_delivery_day', 'woocommerce-myparcel'),
                    ucfirst(__($settings['day']))
                ),
                'help_text' => strtr(
                    __('settings_carrier_delivery_day_help_text', 'woocommerce-myparcel'),
                    [
                        ':delivery_days' => strtolower(
                            __('setting_carrier_drop_off_days_title', 'woocommerce-myparcel')
                        ),
                        ':cutoff_day'    => __($settings['cut_off_time_day']),
                        ':delivery_day'  => __($settings['day']),
                    ]
                ),
                'type'      => 'toggle',
            ];
            $options[] = [
                'name'              => $settings['cut_off_time_setting'],
                'type'              => 'time',
                'condition'         => [
                    'delivery_enabled',
                    $settings['setting'],
                ],
                'class'             => ['wcmp__child'],
                'label'             => sprintf(
                    __('setting_carrier_cut_off_time_day_title', 'woocommerce-myparcel'),
                    __($settings['cut_off_time_day'])
                ),
                'default'           => '15:00',
                'help_text'         => __('setting_carrier_cut_off_time_help_text', 'woocommerce-myparcel'),
                'custom_attributes' => [
                    'min' => '00:00',
                    'max' => '15:00',
                ],
            ];

            if (isset($settings['fee'])) {
                $options[] = WCMP_Settings_Data::getFeeField(
                    $settings['fee'],
                    [
                        'delivery_enabled',
                        $settings['setting'],
                    ]
                );
            }
        }

        return $options;
    }

    /**
     * @param  \MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment $consignment
     *
     * @return array
     */
    private function createMorningDeliveryOptions(AbstractConsignment $consignment): array
    {
        if ($consignment->canHaveDeliveryType(AbstractConsignment::DELIVERY_TYPE_MORNING_NAME)) {
            return [
                [
                    'name'      => 'delivery_morning_enabled',
                    'condition' => 'delivery_enabled',
                    'label'     => __('shipment_options_delivery_morning', 'woocommerce-myparcel'),
                    'type'      => 'toggle',
                ],
                WCMP_Settings_Data::getFeeField(
                    'delivery_morning_fee',
                    [
                        'delivery_enabled',
                        'delivery_morning_enabled',
                    ]
                ),
            ];
        }

        return [];
    }

    /**
     * @param  \MyParcelNL\Sdk\src\Model\Carrier\AbstractCarrier         $carrier
     * @param  \MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment $consignment
     *
     * @return array
     */
    private function getCarrierDeliveryOptionsSection(AbstractCarrier $carrier, AbstractConsignment $consignment): array
    {
        $deliveryOptionsEnabled = SettingsCollection::getInstance()
            ->isEnabled('delivery_options_enabled');

        if (! $deliveryOptionsEnabled) {
            return [];
        }

        return [
            'name'     => 'delivery_options',
            'label'    => sprintf(
                __('setting_delivery_options', 'woocommerce-myparcel'),
                $carrier->getHuman()
            ),
            'settings' => $this->getCarrierDeliveryOptionsSettings($carrier, $consignment),
        ];
    }

    /**
     * @param  \MyParcelNL\Sdk\src\Model\Carrier\AbstractCarrier         $carrier
     * @param  \MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment $consignment
     *
     * @return array
     */
    private function getCarrierDeliveryOptionsSettings(
        AbstractCarrier     $carrier,
        AbstractConsignment $consignment
    ): array {
        $settingsKey = sprintf('woocommerce_myparcel_%s_settings', $carrier->getName());
        $dropOffDays = get_option($settingsKey)['drop_off_days'] ?? [];

        $settings = [
            [
                'name'  => 'delivery_enabled',
                'label' => sprintf(
                    __('setting_carrier_delivery_enabled', 'woocommerce-myparcel'),
                    $carrier->getHuman()
                ),
                'type'  => 'toggle',
            ],
            WCMP_Settings_Data::getFeeField(
                'delivery_standard_fee',
                ['delivery_enabled']
            ),
            [
                'name'      => 'drop_off_days',
                'condition' => 'delivery_enabled',
                'label'     => __('setting_carrier_drop_off_days_title', 'woocommerce-myparcel'),
                'callback'  => [WCMP_Settings_Callbacks::class, 'enhanced_select'],
                'options'   => WCMP_Settings_Data::getWeekdays(),
                'default'   => $dropOffDays ? [] : array_keys(WCMP_Settings_Data::getWeekdays([0, 6])),
                'help_text' => sprintf(
                    __('setting_carrier_drop_off_days_help_text', 'woocommerce-myparcel'),
                    $carrier->getHuman()
                ),
            ],
            [
                'name'      => 'cutoff_time',
                'type'      => 'time',
                'condition' => 'delivery_enabled',
                'label'     => __('setting_carrier_cut_off_time_title', 'woocommerce-myparcel'),
                'help_text' => __('setting_carrier_cut_off_time_help_text', 'woocommerce-myparcel'),
                'default'   => '17:00',
            ],
            [
                'name'      => 'drop_off_delay',
                'condition' => 'delivery_enabled',
                'label'     => __('setting_carrier_drop_off_delay_title', 'woocommerce-myparcel'),
                'type'      => 'number',
                'max'       => 14,
                'help_text' => __('setting_carrier_drop_off_delay_help_text', 'woocommerce-myparcel'),
            ],
            [
                'name'      => 'digital_stamp_default_weight',
                'condition' => 'delivery_enabled',
                'label'     => __('setting_carrier_digital_stamp', 'woocommerce-myparcel'),
                'type'      => 'select',
                'options'   => [
                    null                                          => __('no_default_weight', 'woocommerce-myparcel'),
                ] + Data::getDigitalStampRangeOptions(),
                'help_text' => __('setting_carrier_digital_stamp_help_text', 'woocommerce-myparcel'),
            ],
        ];

        $settings = $this->addDeliveryDateSettings($consignment, $settings);

        return array_merge(
            $settings,
            $this->createExtraDayDeliveryOptions($consignment),
            $this->createMorningDeliveryOptions($consignment),
            $this->createEveningDeliveryOptions($consignment),
            $this->createCarrierOptions($consignment, [$this, 'createDeliveryOptionsSettingsArray'])
        );
    }

    /**
     * @param  \MyParcelNL\Sdk\src\Model\Carrier\AbstractCarrier $carrier
     *
     * @return array
     */
    private function getCarrierDropOffPointSection(AbstractCarrier $carrier): array
    {
        return
            [
                'name'        => 'drop_off_point',
                'label'       => sprintf(
                    __('setting_drop_off_point', 'woocommerce-myparcel'),
                    $carrier->getHuman()
                ),
                'description' => $this->getDropOffPointDescription($carrier),
                'settings'    => [],
            ];
    }

    /**
     * @throws \Exception
     */
    private function getCarrierExportSettingsSection(AbstractCarrier $carrier, AbstractConsignment $consignment): array
    {
        return [
            'name'        => 'export_defaults',
            'label'       => sprintf(
                __('setting_export_settings', 'woocommerce-myparcel'),
                $carrier->getHuman()
            ),
            'description' => sprintf(
                __('setting_export_settings_description', 'woocommerce-myparcel'),
                $carrier->getHuman()
            ),
            'settings'    => $this->createCarrierOptions($consignment, [$this, 'createDefaultExportSettingsArray']),
        ];
    }

    /**
     * @param  \MyParcelNL\Sdk\src\Model\Carrier\AbstractCarrier         $carrier
     * @param  \MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment $consignment
     *
     * @return array
     */
    private function getCarrierPickupOptionsSection(AbstractCarrier $carrier, AbstractConsignment $consignment): array
    {
        $deliveryOptionsEnabled = SettingsCollection::getInstance()
            ->isEnabled('delivery_options_enabled');

        $carrierHasPickup = $consignment->canHaveDeliveryType(AbstractConsignment::DELIVERY_TYPE_PICKUP_NAME);

        if (! $deliveryOptionsEnabled || ! $carrierHasPickup) {
            return [];
        }

        return [
            'name'     => 'pickup_options',
            'label'    => sprintf(
                __('setting_pickup_options', 'woocommerce-myparcel'),
                $carrier->getHuman()
            ),
            'settings' => $this->getCarrierPickupOptionsSettings($carrier),
        ];
    }

    /**
     * @param  \MyParcelNL\Sdk\src\Model\Carrier\AbstractCarrier $carrier
     *
     * @return array[]
     */
    private function getCarrierPickupOptionsSettings(AbstractCarrier $carrier): array
    {
        return [
            [
                'name'  => 'pickup_enabled',
                'label' => sprintf(
                    __('setting_carrier_pickup_enabled', 'woocommerce-myparcel'),
                    $carrier->getHuman()
                ),
                'type'  => 'toggle',
            ],
            WCMP_Settings_Data::getFeeField(
                'pickup_fee',
                [
                    'pickup_enabled',
                ]
            ),
        ];
    }
}

