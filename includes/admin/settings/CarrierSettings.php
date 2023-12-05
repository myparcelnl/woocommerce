<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\admin\settings;

defined('ABSPATH') or die();

use MyParcelNL\Sdk\src\Factory\ConsignmentFactory;
use MyParcelNL\Sdk\src\Model\Carrier\AbstractCarrier;
use MyParcelNL\Sdk\src\Model\Carrier\CarrierDHLForYou;
use MyParcelNL\Sdk\src\Model\Carrier\CarrierDHLParcelConnect;
use MyParcelNL\Sdk\src\Model\Carrier\CarrierPostNL;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\WooCommerce\includes\Settings\Api\AccountSettings;
use WCMP_Data;
use WCMP_Settings_Callbacks;
use WCMP_Settings_Data;
use WCMYPA_Settings;
use WPO\WC\MyParcel\Collections\SettingsCollection;

class CarrierSettings
{
    /**
     * Map consignment options to correct settings and translations.
     */
    private const OPTIONS_EXTRA_DELIVERY_DAY_MAP = [
        AbstractConsignment::EXTRA_OPTION_DELIVERY_MONDAY   => [
            'cut_off_time_day'     => 'Saturday',
            'cut_off_time_setting' => WCMYPA_Settings::SETTING_CARRIER_SATURDAY_CUTOFF_TIME,
            'day'                  => 'Monday',
            'setting'              => WCMYPA_Settings::SETTING_CARRIER_MONDAY_DELIVERY_ENABLED,
        ],
        AbstractConsignment::EXTRA_OPTION_DELIVERY_SATURDAY => [
            'cut_off_time_day'     => 'Friday',
            'cut_off_time_default' => '',
            'cut_off_time_setting' => WCMYPA_Settings::SETTING_CARRIER_FRIDAY_CUTOFF_TIME,
            'day'                  => 'Saturday',
            'fee'                  => WCMYPA_Settings::SETTING_CARRIER_SATURDAY_DELIVERY_FEE,
            'setting'              => WCMYPA_Settings::SETTING_CARRIER_SATURDAY_DELIVERY_ENABLED,
        ],
    ];

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
     * @param  \MyParcelNL\Sdk\src\Model\Carrier\AbstractCarrier $carrier
     *
     * @return array
     * @throws \Exception
     */
    public function getCarrierSection(AbstractCarrier $carrier): array
    {
        $consignment = ConsignmentFactory::createFromCarrier($carrier)
            ->setPackageType(AbstractConsignment::PACKAGE_TYPE_PACKAGE);

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
     * @param  \MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment $consignment
     * @param  array                                                     $settings
     *
     * @return array
     */
    private function addDeliveryDateSettings(AbstractConsignment $consignment, array $settings): array
    {
        if ($consignment->canHaveExtraOption(AbstractConsignment::EXTRA_OPTION_DELIVERY_DATE)) {
            $settings[] = [
                'name'      => WCMYPA_Settings::SETTING_CARRIER_ALLOW_SHOW_DELIVERY_DATE,
                'condition' => WCMYPA_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                'label'     => __('feature_allow_show_delivery_date_title', 'woocommerce-myparcel'),
                'type'      => 'toggle',
                'default'   => WCMP_Settings_Data::ENABLED,
                'help_text' => __('feature_allow_show_delivery_date_help_text', 'woocommerce-myparcel'),
            ];
            $settings[] = [
                'name'      => WCMYPA_Settings::SETTING_CARRIER_DELIVERY_DAYS_WINDOW,
                'condition' => [
                    WCMYPA_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                    WCMYPA_Settings::SETTING_CARRIER_ALLOW_SHOW_DELIVERY_DATE,
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
            $settings[] = $callback($option, $consignment->getCarrier());
        }

        return array_merge(...$settings);
    }

    /**
     * @param  string                                            $option
     * @param  \MyParcelNL\Sdk\src\Model\Carrier\AbstractCarrier $carrier
     *
     * @return array
     * @throws \Exception
     */
    private function createDefaultExportSettingsArray(string $option, AbstractCarrier $carrier): array
    {
        $settings           = [];
        $euInsuranceAmounts =
            [0 => __('no_insurance', 'woocommerce-myparcel')] + WCMP_Data::getInsuranceAmounts(
                'FR',
                $carrier->getName()
            );
        $nlInsuranceAmounts = WCMP_Data::getInsuranceAmounts(AbstractConsignment::CC_NL, $carrier->getName());

        switch ($option) {
            case AbstractConsignment::SHIPMENT_OPTION_AGE_CHECK:
                $settings[] = [
                    'name'      => WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_AGE_CHECK,
                    'label'     => __('shipment_options_age_check', 'woocommerce-myparcel'),
                    'type'      => 'toggle',
                    'help_text' => __('shipment_options_age_check_help_text', 'woocommerce-myparcel'),
                ];
                break;
            case AbstractConsignment::SHIPMENT_OPTION_INSURANCE:
                $settings[] = [
                    'name'      => WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED,
                    'label'     => __('shipment_options_insured', 'woocommerce-myparcel'),
                    'help_text' => __('shipment_options_insured_help_text', 'woocommerce-myparcel'),
                    'type'      => 'toggle',
                ];
                $settings[] = [
                    'name'      => WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED_FROM_PRICE,
                    'condition' => WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED,
                    'label'     => __('shipment_options_insured_from_price', 'woocommerce-myparcel'),
                    'help_text' => __('shipment_options_insured_from_price_help_text', 'woocommerce-myparcel'),
                    'type'      => 'number',
                ];

                if ($nlInsuranceAmounts) {
                    $settings[] = [
                        'name'      => WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED_AMOUNT,
                        'condition' => WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED,
                        'label'     => __('shipment_options_insured_amount', 'woocommerce-myparcel'),
                        'help_text' => __('shipment_options_insured_amount_help_text', 'woocommerce-myparcel'),
                        'type'      => 'select',
                        'options'   => WCMP_Data::getInsuranceAmounts(AbstractConsignment::CC_NL, $carrier->getName()),
                    ];
                }

                $settings[] = [
                    'name'      => WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED_EU_AMOUNT,
                    'condition' => WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED,
                    'label'     => __('shipment_options_insured_eu_amount', 'woocommerce-myparcel'),
                    'help_text' => __('shipment_options_insured_eu_amount_help_text', 'woocommerce-myparcel'),
                    'type'      => 'select',
                    'options'   => $euInsuranceAmounts,
                ];

                if (CarrierDHLParcelConnect::NAME === $carrier->getName()) {
                    break;
                }

                $settings[] = [
                    'name'      => WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED_FOR_BE,
                    'condition' => WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED,
                    'label'     => __('shipment_options_insured_for_be', 'woocommerce-myparcel'),
                    'default'   => WCMP_Settings_Data::ENABLED,
                    'help_text' => __('shipment_options_insured_for_be_help_text', 'woocommerce-myparcel'),
                    'type'      => 'toggle',
                ];
                break;
            case AbstractConsignment::SHIPMENT_OPTION_LARGE_FORMAT:
                $settings[] = [
                    'name'      => WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_LARGE_FORMAT,
                    'label'     => __('shipment_options_large_format', 'woocommerce-myparcel'),
                    'help_text' => __('shipment_options_large_format_help_text', 'woocommerce-myparcel'),
                    'type'      => 'toggle',
                ];
                break;
            case AbstractConsignment::SHIPMENT_OPTION_ONLY_RECIPIENT:
                $settings[] = [
                    'name'      => WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_ONLY_RECIPIENT,
                    'label'     => __('shipment_options_only_recipient', 'woocommerce-myparcel'),
                    'help_text' => __('shipment_options_only_recipient_help_text', 'woocommerce-myparcel'),
                    'type'      => 'toggle',
                ];

                break;
            case AbstractConsignment::SHIPMENT_OPTION_RETURN:
                $settings[] = [
                    'name'      => WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_RETURN,
                    'label'     => __('shipment_options_return', 'woocommerce-myparcel'),
                    'help_text' => __('shipment_options_return_help_text', 'woocommerce-myparcel'),
                    'type'      => 'toggle',
                ];
                break;
            case AbstractConsignment::SHIPMENT_OPTION_SIGNATURE:
                $settings[] = [
                    'name'      => WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_SIGNATURE,
                    'label'     => __('shipment_options_signature', 'woocommerce-myparcel'),
                    'help_text' => __('shipment_options_signature_help_text', 'woocommerce-myparcel'),
                    'type'      => 'toggle',
                ];
                break;
            case AbstractConsignment::SHIPMENT_OPTION_SAME_DAY_DELIVERY:
                $settings[] = [
                    'name'      => WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_SAME_DAY_DELIVERY,
                    'label'     => __('shipment_options_same_day_delivery', 'woocommerce-myparcel'),
                    'help_text' => __('shipment_options_same_day_delivery_help_text', 'woocommerce-myparcel'),
                    'type'      => 'toggle',
                ];
                break;
            case AbstractConsignment::SHIPMENT_OPTION_HIDE_SENDER:
                $settings[] = [
                    'name'      => WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_HIDE_SENDER,
                    'label'     => __('shipment_options_hide_sender', 'woocommerce-myparcel'),
                    'help_text' => __('shipment_options_hide_sender_help_text', 'woocommerce-myparcel'),
                    'type'      => 'toggle',
                ];
                break;
            case AbstractConsignment::SHIPMENT_OPTION_EXTRA_ASSURANCE:
                $settings[] = [
                    'name'      => WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_EXTRA_ASSURANCE,
                    'label'     => __('shipment_options_extra_assurance', 'woocommerce-myparcel'),
                    'help_text' => __('shipment_options_extra_assurance_help_text', 'woocommerce-myparcel'),
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
                    'name'      => WCMYPA_Settings::SETTING_CARRIER_ONLY_RECIPIENT_ENABLED,
                    'condition' => WCMYPA_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                    'label'     => __('shipment_options_only_recipient', 'woocommerce-myparcel'),
                    'type'      => 'toggle',
                ];
                $settings[] = WCMP_Settings_Data::getFeeField(
                    WCMYPA_Settings::SETTING_CARRIER_ONLY_RECIPIENT_FEE,
                    [
                        WCMYPA_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                        WCMYPA_Settings::SETTING_CARRIER_ONLY_RECIPIENT_ENABLED,
                    ]
                );
                break;
            case AbstractConsignment::SHIPMENT_OPTION_SIGNATURE:
                $settings[] = [
                    'name'      => WCMYPA_Settings::SETTING_CARRIER_SIGNATURE_ENABLED,
                    'condition' => WCMYPA_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                    'label'     => __('shipment_options_signature', 'woocommerce-myparcel'),
                    'type'      => 'toggle',
                ];
                $settings[] = WCMP_Settings_Data::getFeeField(
                    WCMYPA_Settings::SETTING_CARRIER_SIGNATURE_FEE,
                    [
                        WCMYPA_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                        WCMYPA_Settings::SETTING_CARRIER_SIGNATURE_ENABLED,
                    ]
                );
                break;
                //            case AbstractConsignment::SHIPMENT_OPTION_SAME_DAY_DELIVERY:
                //                $settings[] = [
                //                    'name'      => WCMYPA_Settings::SETTING_CARRIER_SAME_DAY_DELIVERY,
                //                    'condition' => WCMYPA_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                //                    'help_text' => __('shipment_options_same_day_delivery_help_text', 'woocommerce-myparcel'),
                //                    'label'     => __('shipment_options_same_day_delivery', 'woocommerce-myparcel'),
                //                    'type'      => 'toggle',
                //                ];
                //                $settings[] = WCMP_Settings_Data::getFeeField(
                //                    WCMYPA_Settings::SETTING_CARRIER_SAME_DAY_DELIVERY_FEE,
                //                    [
                //                        WCMYPA_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                //                        WCMYPA_Settings::SETTING_CARRIER_SAME_DAY_DELIVERY,
                //                    ]
                //                );
                //                $settings[] = [
                //                    'name'              => WCMYPA_Settings::SETTING_CARRIER_SAME_DAY_DELIVERY_CUTOFF_TIME,
                //                    'type'              => 'time',
                //                    'class'             => ['wcmp__child'],
                //                    'condition'         => [
                //                        WCMYPA_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                //                        WCMYPA_Settings::SETTING_CARRIER_SAME_DAY_DELIVERY,
                //                    ],
                //                    'label'             => __('setting_carrier_cut_off_time_title', 'woocommerce-myparcel'),
                //                    'help_text'         => __(
                //                        'shipment_options_same_day_delivery_cutoff_time_help_text',
                //                        'woocommerce-myparcel'
                //                    ),
                //                    'default'           => '09:00',
                //                    'custom_attributes' => [
                //                        'min' => '00:00',
                //                        'max' => '10:00',
                //                    ],
                //                ];
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
                    'name'      => WCMYPA_Settings::SETTING_CARRIER_DELIVERY_EVENING_ENABLED,
                    'condition' => WCMYPA_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                    'label'     => __('shipment_options_delivery_evening', 'woocommerce-myparcel'),
                    'type'      => 'toggle',
                ],
                WCMP_Settings_Data::getFeeField(
                    WCMYPA_Settings::SETTING_CARRIER_DELIVERY_EVENING_FEE,
                    [
                        WCMYPA_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                        WCMYPA_Settings::SETTING_CARRIER_DELIVERY_EVENING_ENABLED,
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
                'condition' => WCMYPA_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
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
                    WCMYPA_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
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
                        WCMYPA_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
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
                    'name'      => WCMYPA_Settings::SETTING_CARRIER_DELIVERY_MORNING_ENABLED,
                    'condition' => WCMYPA_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                    'label'     => __('shipment_options_delivery_morning', 'woocommerce-myparcel'),
                    'type'      => 'toggle',
                ],
                WCMP_Settings_Data::getFeeField(
                    WCMYPA_Settings::SETTING_CARRIER_DELIVERY_MORNING_FEE,
                    [
                        WCMYPA_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                        WCMYPA_Settings::SETTING_CARRIER_DELIVERY_MORNING_ENABLED,
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
            ->isEnabled(WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_ENABLED);

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
                'name'      => WCMYPA_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                'label'     => sprintf(
                    __('setting_carrier_delivery_enabled', 'woocommerce-myparcel'),
                    $carrier->getHuman()
                ),
                'help_text' => CarrierDHLForYou::NAME === $carrier->getName() ? __(
                    'carrier_dhl_for_you_today_help_text',
                    'woocommerce-myparcel'
                ) : '',
                'type'      => 'toggle',
            ],
            WCMP_Settings_Data::getFeeField(
                WCMYPA_Settings::SETTING_CARRIER_DELIVERY_STANDARD_FEE,
                [WCMYPA_Settings::SETTING_CARRIER_DELIVERY_ENABLED]
            ),
            [
                'name'      => WCMYPA_Settings::SETTING_CARRIER_DROP_OFF_DAYS,
                'condition' => WCMYPA_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
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
                'name'      => WCMYPA_Settings::SETTING_CARRIER_CUTOFF_TIME,
                'type'      => 'time',
                'condition' => WCMYPA_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                'label'     => __('setting_carrier_cut_off_time_title', 'woocommerce-myparcel'),
                'help_text' => __('setting_carrier_cut_off_time_help_text', 'woocommerce-myparcel'),
                'default'   => '17:00',
            ],
            [
                'name'      => WCMYPA_Settings::SETTING_CARRIER_DROP_OFF_DELAY,
                'condition' => WCMYPA_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                'label'     => __('setting_carrier_drop_off_delay_title', 'woocommerce-myparcel'),
                'type'      => 'number',
                'max'       => 14,
                'help_text' => __('setting_carrier_drop_off_delay_help_text', 'woocommerce-myparcel'),
            ],
        ];

        if (CarrierPostNL::NAME === $carrier->getName()) {
            $settings[] = [
                'name'      => WCMYPA_Settings::SETTING_CARRIER_DIGITAL_STAMP_DEFAULT_WEIGHT,
                'condition' => WCMYPA_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                'label'     => __('setting_carrier_digital_stamp', 'woocommerce-myparcel'),
                'type'      => 'select',
                'options'   => [
                    null                                          => __('no_default_weight', 'woocommerce-myparcel'),
                    WCMP_Data::DIGITAL_STAMP_RANGES[0]['average'] => '0 - 20 gram',
                    WCMP_Data::DIGITAL_STAMP_RANGES[1]['average'] => '20 - 50 gram',
                    WCMP_Data::DIGITAL_STAMP_RANGES[2]['average'] => '50 - 350 gram',
                    WCMP_Data::DIGITAL_STAMP_RANGES[3]['average'] => '350 - 2000 gram',
                ],
                'help_text' => __('setting_carrier_digital_stamp_help_text', 'woocommerce-myparcel'),
            ];
        }

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
            ->isEnabled(WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_ENABLED);

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
                'name'  => WCMYPA_Settings::SETTING_CARRIER_PICKUP_ENABLED,
                'label' => sprintf(
                    __('setting_carrier_pickup_enabled', 'woocommerce-myparcel'),
                    $carrier->getHuman()
                ),
                'type'  => 'toggle',
            ],
            WCMP_Settings_Data::getFeeField(
                WCMYPA_Settings::SETTING_CARRIER_PICKUP_FEE,
                [
                    WCMYPA_Settings::SETTING_CARRIER_PICKUP_ENABLED,
                ]
            ),
        ];
    }
}

