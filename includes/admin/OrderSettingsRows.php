<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\admin;

defined('ABSPATH') or die();

use MyParcelNL\Pdk\Base\Service\CountryService;
use MyParcelNL\Pdk\Carrier\Model\CarrierOptions;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Shipment\Model\DeliveryOptions;
use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter;
use MyParcelNL\Sdk\src\Factory\ConsignmentFactory;
use MyParcelNL\Sdk\src\Model\Carrier\AbstractCarrier;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\WooCommerce\includes\Settings\Api\AccountSettings;
use MyParcelNL\WooCommerce\PdkOrderRepository;
use WC_Order;
use CountryCodes;
use Data;
use WCMP_Settings_Data;
use WCMYPA_Admin;
use WCMYPA_Settings;

class OrderSettingsRows
{
    private const HOME_COUNTRY_ONLY_ROWS = [
        self::OPTION_SHIPMENT_OPTIONS_AGE_CHECK,
        self::OPTION_SHIPMENT_OPTIONS_ONLY_RECIPIENT,
        self::OPTION_SHIPMENT_OPTIONS_SIGNATURE,
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

    /**
     * Maps shipment options in this form to their respective name in the SDK.
     */
    private const SHIPMENT_OPTIONS_ROW_MAP = [
        self::OPTION_SHIPMENT_OPTIONS_AGE_CHECK         => AbstractConsignment::SHIPMENT_OPTION_AGE_CHECK,
        self::OPTION_SHIPMENT_OPTIONS_INSURED           => AbstractConsignment::SHIPMENT_OPTION_INSURANCE,
        self::OPTION_SHIPMENT_OPTIONS_LARGE_FORMAT      => AbstractConsignment::SHIPMENT_OPTION_LARGE_FORMAT,
        self::OPTION_SHIPMENT_OPTIONS_ONLY_RECIPIENT    => AbstractConsignment::SHIPMENT_OPTION_ONLY_RECIPIENT,
        self::OPTION_SHIPMENT_OPTIONS_RETURN_SHIPMENT   => AbstractConsignment::SHIPMENT_OPTION_RETURN,
        self::OPTION_SHIPMENT_OPTIONS_SAME_DAY_DELIVERY => AbstractConsignment::SHIPMENT_OPTION_SAME_DAY_DELIVERY,
        self::OPTION_SHIPMENT_OPTIONS_SIGNATURE         => AbstractConsignment::SHIPMENT_OPTION_SIGNATURE,
    ];

    private const CONDITION_DELIVERY_TYPE_DELIVERY = [
        'parent_name'  => self::OPTION_DELIVERY_TYPE,
        'type'         => 'show',
        'parent_value' => [
            DeliveryOptions::DELIVERY_TYPE_MORNING_NAME,
            DeliveryOptions::DELIVERY_TYPE_STANDARD_NAME,
            DeliveryOptions::DELIVERY_TYPE_EVENING_NAME,
        ],
        'set_value'    => WCMP_Settings_Data::DISABLED,
    ];

    private const CONDITION_PACKAGE_TYPE_PACKAGE = [
        'parent_name'  => self::OPTION_PACKAGE_TYPE,
        'type'         => 'show',
        'parent_value' => DeliveryOptions::PACKAGE_TYPE_PACKAGE_NAME,
    ];

    private const CONDITION_FORCE_ENABLED_ON_AGE_CHECK = [
        'parent_name'  => self::OPTION_SHIPMENT_OPTIONS_AGE_CHECK,
        'type'         => 'disable',
        'set_value'    => WCMP_Settings_Data::ENABLED,
        'parent_value' => WCMP_Settings_Data::DISABLED,
    ];

    /**
     * @var \MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter
     */
    private $deliveryOptions;

    /**
     * @var \WC_Order
     */
    private $order;

    /**
     * @param  \MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter $deliveryOptions
     * @param  \WC_Order                                                                  $order
     */
    public function __construct(AbstractDeliveryOptionsAdapter $deliveryOptions, WC_Order $order) {
        $this->deliveryOptions = $deliveryOptions;
        $this->order = $order;
    }

    /**
     * @return array[]
     * @throws \JsonException
     * @throws \Exception
     */
    public function getOptionsRows(): array
    {
        $orderRepository    = (Pdk::get(PdkOrderRepository::class));
        $pdkOrder           = $orderRepository->get($this->order->get_id());
        $shippingCountry    = $pdkOrder->recipient->cc;
        $isEuCountry        = CountryCodes::isEuCountry($shippingCountry);
        $isHomeCountry      = Data::isHomeCountry($shippingCountry);
        $isBelgium          = CountryService::CC_BE === $shippingCountry;
        $packageTypeOptions = array_combine(DeliveryOptions::PACKAGE_TYPES_NAMES, Data::getPackageTypesHuman());

        $this->deliveryOptions = WCMYPA_Admin::getDeliveryOptionsFromOrder($this->order);

        // Remove mailbox and digital stamp, because this is not possible for international shipments
        if (! $isHomeCountry) {
            unset($packageTypeOptions['mailbox'], $packageTypeOptions['digital_stamp']);
        }

        $rows = [
            [
                'name'    => self::OPTION_CARRIER,
                'label'   => __('Carrier', 'woocommerce-myparcel'),
                'type'    => 'select',
                'options' => $this->getAvailableCarriers($shippingCountry),
                'value'   => $this->deliveryOptions->getCarrier() ?? CarrierOptions::CARRIER_POSTNL_NAME,
            ],
            [
                'name'              => self::OPTION_DELIVERY_TYPE,
                'label'             => __('Delivery type', 'woocommerce-myparcel'),
                'type'              => 'select',
                'options'           => Data::getDeliveryTypesHuman(),
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
                'value'             => $orderRepository->getColloAmount(),
                'custom_attributes' => [
                    'min' => '1',
                    'max' => '10',
                ],
            ],
        ];

        // Only add extra options and shipment options to home country shipments.
        if ($isHomeCountry) {
            $rows = array_merge($rows, $this->getAdditionalOptionsRows($orderRepository));
        }

        if ($isBelgium) {
            $rows[] = [
                'name'      => self::OPTION_SHIPMENT_OPTIONS_INSURED,
                'type'      => 'toggle',
                'label'     => __('insured', 'woocommerce-myparcel'),
                'value'     => (bool) $this->deliveryOptions->getShipmentOptions()->getInsurance(),
                'condition' => [
                    self::CONDITION_PACKAGE_TYPE_PACKAGE,
                    $this->getCarriersWithFeatureCondition(self::OPTION_SHIPMENT_OPTIONS_INSURED),
                ],
            ];

            $rows[] = [
                'name'      => self::OPTION_SHIPMENT_OPTIONS_INSURED_AMOUNT,
                'type'      => 'select',
                'label'     => __('insured_amount', 'woocommerce-myparcel'),
                'options'   => [WCMYPA_Settings::DEFAULT_BELGIAN_INSURANCE => WCMYPA_Settings::DEFAULT_BELGIAN_INSURANCE],
                'value'     => $this->deliveryOptions->getShipmentOptions()->getInsurance(),
                'condition' => [
                    self::OPTION_SHIPMENT_OPTIONS_INSURED,
                    self::CONDITION_PACKAGE_TYPE_PACKAGE,
                ],
            ];
        }

        if ($isEuCountry) {
            $rows[] = [
                'name'      => self::OPTION_SHIPMENT_OPTIONS_LARGE_FORMAT,
                'type'      => 'toggle',
                'label'     => __('shipment_options_large_format', 'woocommerce-myparcel'),
                'help_text' => __('shipment_options_large_format_help_text', 'woocommerce-myparcel'),
                'value'     => $this->deliveryOptions->getShipmentOptions()->hasLargeFormat(),
                'condition' => [
                    self::CONDITION_PACKAGE_TYPE_PACKAGE,
                    $this->getCarriersWithFeatureCondition(self::OPTION_SHIPMENT_OPTIONS_LARGE_FORMAT),
                ],
            ];
        }

        $rows[] = [
            'name'  => self::OPTION_SHIPMENT_OPTIONS_LABEL_DESCRIPTION,
            'type'  => 'text',
            'label' => __('Custom ID (top left on label)', 'woocommerce-myparcel'),
            'value' => $orderRepository->getLabelDescription($this->deliveryOptions),
        ];

        return $rows;
    }

    /**
     * Filters out rows that should not be shown if the shipment is sent to the home country.
     *
     * @param  string $cc
     * @param  array  $rows
     *
     * @return array
     */
    public function filterRowsByCountry(string $cc, array $rows): array
    {
        if (Data::DEFAULT_COUNTRY_CODE === $cc) {
            return $rows;
        }

        return array_filter($rows, static function ($row) {
            return ! in_array($row['name'], self::HOME_COUNTRY_ONLY_ROWS, true);
        });
    }

    /**
     * @param  \MyParcelNL\WooCommerce\PdkOrderRepository $orderRepository
     *
     * @return array[]
     * @throws \JsonException
     * @throws \MyParcelNL\Sdk\src\Exception\ValidationException
     * @throws \Exception
     */
    private function getAdditionalOptionsRows(PdkOrderRepository $orderRepository): array
    {
        $shipmentOptions = $this->deliveryOptions->getShipmentOptions();

        return [
            [
                'name'        => self::OPTION_EXTRA_OPTIONS_DIGITAL_STAMP_WEIGHT,
                'type'        => 'select',
                'label'       => __('weight', 'woocommerce-myparcel'),
                'description' => sprintf(
                    __('calculated_order_weight', 'woocommerce-myparcel'),
                    wc_format_weight($orderRepository->getWeight())
                ),
                'options'     => Data::getDigitalStampRangeOptions(),
                'value'       => $orderRepository->getDigitalStampRangeWeight(),
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
                'value'     => $shipmentOptions->hasOnlyRecipient(),
                'condition' => [
                    self::CONDITION_PACKAGE_TYPE_PACKAGE,
                    self::CONDITION_DELIVERY_TYPE_DELIVERY,
                    $this->getCarriersWithFeatureCondition(self::OPTION_SHIPMENT_OPTIONS_ONLY_RECIPIENT),
                    self::CONDITION_FORCE_ENABLED_ON_AGE_CHECK,
                ],
            ],
            [
                'name'      => self::OPTION_SHIPMENT_OPTIONS_SIGNATURE,
                'type'      => 'toggle',
                'label'     => __('shipment_options_signature', 'woocommerce-myparcel'),
                'help_text' => __('shipment_options_signature_help_text', 'woocommerce-myparcel'),
                'value'     => $shipmentOptions->hasSignature(),
                'condition' => [
                    self::CONDITION_PACKAGE_TYPE_PACKAGE,
                    self::CONDITION_DELIVERY_TYPE_DELIVERY,
                    $this->getCarriersWithFeatureCondition(self::OPTION_SHIPMENT_OPTIONS_SIGNATURE),
                    self::CONDITION_FORCE_ENABLED_ON_AGE_CHECK,
                ],
            ],
            [
                'name'      => self::OPTION_SHIPMENT_OPTIONS_AGE_CHECK,
                'type'      => 'toggle',
                'label'     => __('shipment_options_age_check', 'woocommerce-myparcel'),
                'help_text' => __('shipment_options_age_check_help_text', 'woocommerce-myparcel'),
                'value'     => $shipmentOptions->hasAgeCheck(),
                'condition' => [
                    self::CONDITION_PACKAGE_TYPE_PACKAGE,
                    $this->getCarriersWithFeatureCondition(self::OPTION_SHIPMENT_OPTIONS_AGE_CHECK),
                ],
            ],
            [
                'name'      => self::OPTION_SHIPMENT_OPTIONS_RETURN_SHIPMENT,
                'type'      => 'toggle',
                'label'     => __('shipment_options_return', 'woocommerce-myparcel'),
                'help_text' => __('shipment_options_return_help_text', 'woocommerce-myparcel'),
                'value'     => $shipmentOptions->isReturn(),
                'condition' => [
                    self::CONDITION_PACKAGE_TYPE_PACKAGE,
                    self::CONDITION_DELIVERY_TYPE_DELIVERY,
                    $this->getCarriersWithFeatureCondition(self::OPTION_SHIPMENT_OPTIONS_RETURN_SHIPMENT),
                ],
            ],
            [
                'name'      => self::OPTION_SHIPMENT_OPTIONS_SAME_DAY_DELIVERY,
                'type'      => 'toggle',
                'label'     => __('shipment_options_same_day_delivery', 'woocommerce-myparcel'),
                'help_text' => __('shipment_options_same_day_delivery_help_text', 'woocommerce-myparcel'),
                'value'     => $shipmentOptions->isSameDayDelivery(),
                'condition' => [
                    $this->getCarriersWithFeatureCondition(self::OPTION_SHIPMENT_OPTIONS_SAME_DAY_DELIVERY),
                ],
            ],
            [
                'name'      => self::OPTION_SHIPMENT_OPTIONS_INSURED,
                'type'      => 'toggle',
                'label'     => __('insured', 'woocommerce-myparcel'),
                'value'     => (bool) $shipmentOptions->getInsurance(),
                'condition' => [
                    self::CONDITION_PACKAGE_TYPE_PACKAGE,
                    $this->getCarriersWithFeatureCondition(self::OPTION_SHIPMENT_OPTIONS_INSURED),
                ],
            ],
            [
                'name'      => self::OPTION_SHIPMENT_OPTIONS_INSURED_AMOUNT,
                'type'      => 'select',
                'label'     => __('insured_amount', 'woocommerce-myparcel'),
                'options'   => Data::getInsuranceAmounts(),
                'value'     => $shipmentOptions->getInsurance(),
                'condition' => [
                    self::CONDITION_PACKAGE_TYPE_PACKAGE,
                    $this->getCarriersWithFeatureCondition(self::OPTION_SHIPMENT_OPTIONS_INSURED),
                    self::OPTION_SHIPMENT_OPTIONS_INSURED,
                ],
            ],
        ];
    }

    /**
     * @param  string $country
     *
     * @return array
     */
    private function getAvailableCarriers(string $country): array
    {
        $accountSettings = AccountSettings::getInstance();
        $carriers        = $accountSettings->getEnabledCarriers();
        $carriersOptions = [];

        foreach ($carriers as $carrier) {
            if (CarrierOptions::CARRIER_INSTABOX_ID === $carrier->getId() && ! Data::isHomeCountry($country)) {
                continue;
            }

            $carriersOptions[$carrier->getName()] = $carrier->getHuman();
        }

        return $carriersOptions;
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

        foreach ($carriers as $carrier) {
            $shipmentOptions = ConsignmentFactory::createFromCarrier($carrier)
                ->getAllowedShipmentOptions();

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

    /**
     * @return array
     */
    private function getCarrierPackageTypesCondition(): array
    {
        return [
            'parent_name' => self::OPTION_CARRIER,
            'type' => 'options',
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
            'set_value' => DeliveryOptions::DEFAULT_PACKAGE_TYPE_NAME,
        ];
    }
}
