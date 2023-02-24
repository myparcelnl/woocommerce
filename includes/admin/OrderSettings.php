<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\admin;

defined('ABSPATH') or die();

use Exception;
use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter;
use MyParcelNL\Sdk\src\Factory\ConsignmentFactory;
use MyParcelNL\Sdk\src\Model\Carrier\CarrierFactory;
use MyParcelNL\Sdk\src\Model\PickupLocation;
use MyParcelNL\Sdk\src\Model\Recipient;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\Sdk\src\Support\Arr;
use MyParcelNL\WooCommerce\includes\adapter\RecipientFromWCOrder;
use WC_Order;
use WCMP_Country_Codes;
use WCMP_Data;
use WCMP_Export;
use WCMP_Export_Consignments;
use WCMP_Log;
use WCMP_Shipping_Methods;
use WCMYPA_Admin;
use WCMYPA_Settings;
use WPO\WC\MyParcel\Compatibility\Order as WCX_Order;
use WPO\WC\MyParcel\Compatibility\Product as WCX_Product;

class OrderSettings
{
    public const DEFAULT_COLLO_AMOUNT      = 1;
    public const DEFAULT_BELGIAN_INSURANCE = 500;

    public const OPTION_TRANSLATION_STRINGS = [
        AbstractConsignment::SHIPMENT_OPTION_RETURN         => 'shipment_options_return',
        AbstractConsignment::SHIPMENT_OPTION_ONLY_RECIPIENT => 'shipment_options_only_recipient',
    ];

    /**
     * @var \MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter
     */
    private $deliveryOptions;

    /**
     * @var bool
     */
    private $hideSender;

    /**
     * @var bool
     */
    private $extraAssurance;

    /**
     * @var \WC_Order
     */
    private $order;

    /**
     * @var string|null
     */
    private $carrier;

    /**
     * @var \MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractShipmentOptionsAdapter|null
     */
    private $shipmentOptions;

    /**
     * @var float
     */
    private $weight;

    /**
     * @var int
     */
    private $digitalStampRangeWeight;

    /**
     * @var bool
     */
    private $ageCheck;

    /**
     * @var bool
     */
    private $insured;

    /**
     * @var int
     */
    private $insuranceAmount;

    /**
     * @var string
     */
    private $labelDescription;

    /**
     * @var bool
     */
    private $largeFormat;

    /**
     * @var bool
     */
    private $onlyRecipient;

    /**
     * @var string
     */
    private $packageType;

    /**
     * @var bool
     */
    private $returnShipment;

    /**
     * @var bool
     */
    private $sameDayDelivery;

    /**
     * @var bool
     */
    private $signature;

    /**
     * @var int
     */
    private $colloAmount;

    /**
     * @var array
     */
    private $extraOptions;

    /**
     * @var string
     */
    private $shippingCountry;

    /**
     * @var Recipient
     */
    private $shippingRecipient;

    /**
     * @var PickupLocation
     */
    private $pickupLocation;

    /**
     * @var Recipient
     */
    private $billingRecipient;

    /**
     * @var \MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment
     */
    private $consignment;

    /**
     * @param WC_Order                                                                              $order
     * @param \MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter|array|null $deliveryOptions
     *
     * @throws \JsonException
     * @throws \Exception
     */
    public function __construct(
        WC_Order $order,
                 $deliveryOptions = null
    ) {
        $this->order = $order;

        $this->setDeliveryOptions($deliveryOptions);
        $this->shippingCountry = WCX_Order::get_prop($order, 'shipping_country');
        $this->extraOptions    = WCMYPA_Admin::getExtraOptionsFromOrder($order);

        $deliveryOptions       = $this->getDeliveryOptions();
        $this->carrier         = $deliveryOptions->getCarrier() ?? (WCMP_Data::DEFAULT_CARRIER_CLASS)::NAME;
        $this->consignment     = ConsignmentFactory::createFromCarrier(CarrierFactory::create($this->carrier));
        $this->shipmentOptions = $deliveryOptions->getShipmentOptions();

        if ($deliveryOptions->getPackageTypeId()) {
            $this->consignment->setPackageType($deliveryOptions->getPackageTypeId());
        }
        if ($deliveryOptions->getDeliveryTypeId()) {
            $this->consignment->setDeliveryType($deliveryOptions->getDeliveryTypeId());
        }

        $this->setAllData();
    }

    /**
     * @param bool $inGrams
     *
     * @return float
     */
    public function getWeight(bool $inGrams = false): float
    {
        return $inGrams
            ? WCMP_Export::convertWeightToGrams($this->weight)
            : $this->weight;
    }

    /**
     * @return int
     */
    public function getDigitalStampRangeWeight(): int
    {
        return $this->digitalStampRangeWeight;
    }

    /**
     * @return bool
     */
    public function hasAgeCheck(): bool
    {
        $recipient = $this->getShippingRecipient();

        if ($recipient && AbstractConsignment::CC_NL !== $recipient->getCc()) {
            $this->ageCheck = false;
        }

        return $this->ageCheck;
    }

    /**
     * @return int
     */
    public function getColloAmount(): int
    {
        return $this->colloAmount;
    }

    /**
     * @return bool
     */
    public function hasHideSender(): bool
    {
        return $this->hideSender ?? false;
    }

    /**
     * @return bool
     */
    public function hasSameDayDelivery(): bool
    {
        return $this->sameDayDelivery ?? false;
    }

    /**
     * @return bool
     */
    public function hasExtraAssurance(): bool
    {
        return $this->extraAssurance ?? false;
    }

    /**
     * @return bool
     */
    public function isInsured(): bool
    {
        return $this->insured;
    }

    /**
     * @return int
     */
    public function getInsuranceAmount(): int
    {
        return $this->insuranceAmount;
    }

    /**
     * @return mixed|string
     */
    public function getLabelDescription(): string
    {
        return $this->labelDescription;
    }

    /**
     * @return bool
     */
    public function hasLargeFormat(): bool
    {
        return $this->largeFormat;
    }

    /**
     * @return bool
     */
    public function hasOnlyRecipient(): bool
    {
        return $this->onlyRecipient;
    }

    /**
     * @return string
     */
    public function getPackageType(): string
    {
        return $this->packageType;
    }

    /**
     * @return bool
     */
    public function hasReturnShipment(): bool
    {
        return $this->returnShipment;
    }

    /**
     * @return bool
     */
    public function isSameDayDelivery(): bool
    {
        return $this->sameDayDelivery;
    }

    /**
     * @return bool
     */
    public function hasSignature(): bool
    {
        return $this->signature;
    }

    /**
     * @throws \Exception
     */
    private function setAllData(): void
    {
        $this->setBillingRecipient();
        $this->setShippingRecipient();

        $this->setPackageType();
        $this->setColloAmount();
        $this->setLabelDescription();

        $this->setShipmentOptions();
        $this->setInsuranceData();
        $this->setSameDayDelivery();

        $this->setWeight();
        $this->setDigitalStampRangeWeight();

        $this->setPickupLocation();
    }

    private function setShipmentOptions(): void
    {
        foreach ($this->allShipmentOptions() as $property => $item) {
            $this->{$property} = $this->determineShipmentOption(
                $item['method'],
                $item['setting'],
                $item['consignment_option'],
                $item['default_when_false']
            );
        }
    }

    /**
     * @return array[]
     */
    private function allShipmentOptions(): array
    {
        return [
            'largeFormat'     => [
                'method'             => [$this, 'determineLargeFormat'],
                'setting'            => WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_LARGE_FORMAT,
                'consignment_option' => AbstractConsignment::SHIPMENT_OPTION_LARGE_FORMAT,
                'default_when_false' => false,
            ],
            'returnShipment'  => [
                'method'             => [$this->shipmentOptions, 'isReturn'],
                'setting'            => WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_RETURN,
                'consignment_option' => AbstractConsignment::SHIPMENT_OPTION_RETURN,
                'default_when_false' => true,
            ],
            'onlyRecipient'   => [
                'method'             => [$this->shipmentOptions, 'hasOnlyRecipient'],
                'setting'            => WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_ONLY_RECIPIENT,
                'consignment_option' => AbstractConsignment::SHIPMENT_OPTION_ONLY_RECIPIENT,
                'default_when_false' => true,
            ],
            'signature'       => [
                'method'             => [$this->shipmentOptions, 'hasSignature'],
                'setting'            => WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_SIGNATURE,
                'consignment_option' => AbstractConsignment::SHIPMENT_OPTION_SIGNATURE,
                'default_when_false' => true,
            ],
            'sameDayDelivery' => [
                'method'             => [$this->shipmentOptions, 'isSameDayDelivery'],
                'setting'            => WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_SAME_DAY_DELIVERY,
                'consignment_option' => AbstractConsignment::SHIPMENT_OPTION_SAME_DAY_DELIVERY,
                'default_when_false' => true,
            ],
            'ageCheck'        => [
                'method'             => [$this, 'getAgeCheckFromOptionsOrOrder'],
                'setting'            => WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_AGE_CHECK,
                'consignment_option' => AbstractConsignment::SHIPMENT_OPTION_AGE_CHECK,
                'default_when_false' => false,
            ],
            'hideSender'        => [
                'method'             => [$this->shipmentOptions, 'hasHideSender'],
                'setting'            => WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_HIDE_SENDER,
                'consignment_option' => AbstractConsignment::SHIPMENT_OPTION_HIDE_SENDER,
                'default_when_false' => false,
            ],
            'extraAssurance'        => [
                'method'             => [$this->shipmentOptions, 'hasExtraAssurance'],
                'setting'            => WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_EXTRA_ASSURANCE,
                'consignment_option' => AbstractConsignment::SHIPMENT_OPTION_EXTRA_ASSURANCE,
                'default_when_false' => false,
            ],
        ];
    }

    /**
     * @return self
     * @throws \Exception
     */
    public function setShippingRecipient(): self
    {
        $this->shippingRecipient = $this->createRecipientFromWCOrder();

        return $this;
    }

    /**
     * @return \MyParcelNL\Sdk\src\Model\Recipient|null
     */
    public function getShippingRecipient(): ?Recipient
    {
        return $this->shippingRecipient;
    }

    /**
     * @return self
     * @throws \Exception
     */
    public function setBillingRecipient(): self
    {
        $this->billingRecipient = $this->createRecipientFromWCOrder(RecipientFromWCOrder::BILLING);

        return $this;
    }

    /**
     * @return bool
     */
    public function hasLocalPickup(): bool
    {
        $shippingMethods  = $this->order->get_shipping_methods();
        $shippingMethod   = array_shift($shippingMethods);
        $shippingMethodId = $shippingMethod ? $shippingMethod->get_method_id() : null;

        return WCMP_Shipping_Methods::LOCAL_PICKUP === $shippingMethodId;
    }

    /**
     * @param string $type
     *
     * @return \MyParcelNL\Sdk\src\Model\Recipient|null
     */
    private function createRecipientFromWCOrder(string $type = RecipientFromWCOrder::SHIPPING): ?Recipient
    {
        try {
            $consignment             = ConsignmentFactory::createByCarrierName($this->carrier);
            $localCountryCode        = $consignment->getLocalCountryCode();

            return (new RecipientFromWCOrder(
                $this->order,
                $localCountryCode,
                $type
            ));
        } catch (Exception $exception) {
            WCMP_Log::add(
                sprintf(
                    'Failed to create recipient from order %d',
                    $this->order->get_id()
                ),
                $exception->getMessage()
            );
        }

        return null;
    }

    /**
     * @return \MyParcelNL\Sdk\src\Model\Recipient|null
     */
    public function getBillingRecipient(): ?Recipient
    {
        return $this->billingRecipient;
    }

    /**
     * @return self
     */
    public function setPickupLocation(): self
    {
        $pickupLocation = $this->deliveryOptions->getPickupLocation();

        if (! $this->deliveryOptions->isPickup() || ! $pickupLocation) {
            return $this;
        }

        $this->pickupLocation = (new PickupLocation())
            ->setCc($pickupLocation->getCountry())
            ->setCity($pickupLocation->getCity())
            ->setLocationName($pickupLocation->getLocationName())
            ->setStreet($pickupLocation->getStreet())
            ->setNumber($pickupLocation->getNumber())
            ->setPostalCode($pickupLocation->getPostalCode())
            ->setRetailNetworkId($pickupLocation->getRetailNetworkId())
            ->setLocationCode($pickupLocation->getLocationCode());

        return $this;
    }

    /**
     * @return \MyParcelNL\Sdk\src\Model\PickupLocation|null
     */
    public function getPickupLocation(): ?PickupLocation
    {
        return $this->pickupLocation;
    }

    /**
     * @return void
     */
    private function setWeight(): void
    {
        $weight = $this->extraOptions['weight'] ?? null;

        if (null === $weight && $this->order->meta_exists(WCMYPA_Admin::META_ORDER_WEIGHT)) {
            $weight = $this->order->get_meta(WCMYPA_Admin::META_ORDER_WEIGHT);
        }

        $this->weight = (float) $weight;
    }

    /**
     * Gets age check from shipment options when set, otherwise gets age check from the order
     *
     * @return bool|null
     * @throws \JsonException
     */
    private function getAgeCheckFromOptionsOrOrder(): ?bool
    {
        return $this->shipmentOptions->hasAgeCheck() ?? $this->getAgeCheckFromOrder();
    }

    /**
     * Gets product age check value based on if it was explicitly set to either true or false. It defaults to
     * inheriting from the default export settings.
     *
     * @return bool|null
     * @throws \JsonException
     */
    private function getAgeCheckFromOrder(): ?bool
    {
        $hasAgeCheck = false;

        foreach ($this->order->get_items() as $item) {
            $product = $item->get_product();

            if (! $product) {
                continue;
            }

            $productAgeCheck = WCX_Product::get_meta($product, WCMYPA_Admin::META_AGE_CHECK, true);

            if (! $productAgeCheck) {
                $hasAgeCheck = WCMYPA_Admin::PRODUCT_OPTIONS_DEFAULT;
            } elseif (WCMYPA_Admin::PRODUCT_OPTIONS_ENABLED === $productAgeCheck) {
                return true;
            }
        }

        return $hasAgeCheck;
    }

    /**
     * @return void
     */
    private function setColloAmount(): void
    {
        $this->colloAmount = (int) ($this->extraOptions['collo_amount'] ?? self::DEFAULT_COLLO_AMOUNT);
    }

    /**
     * @return array
     */
    public function getDigitalStampRange(): array
    {
        $emptyWeight   = (float) WCMYPA()->setting_collection->getByName(
            WCMYPA_Settings::SETTING_EMPTY_DIGITAL_STAMP_WEIGHT
        );
        $savedWeight   = $this->extraOptions['digital_stamp_weight'] ?? null;
        $orderWeight   = $this->getWeight(true) + $emptyWeight;
        $defaultWeight = WCMYPA()->setting_collection->getByName(
            WCMYPA_Settings::SETTING_CARRIER_DIGITAL_STAMP_DEFAULT_WEIGHT
        ) ?: null;
        $weight        = (float) ($savedWeight ?? $defaultWeight ?? $orderWeight);

        $results = Arr::where(
            WCMP_Data::getDigitalStampRanges(),
            static function ($range) use ($weight) {
                return $weight > $range['min'];
            }
        );

        if (empty($results)) {
            return Arr::first(WCMP_Data::getDigitalStampRanges());
        }

        return Arr::last($results);
    }

    /**
     * @return void
     */
    private function setDigitalStampRangeWeight(): void
    {
        $range = $this->getDigitalStampRange();

        $this->digitalStampRangeWeight = $range['average'];
    }

    /**
     * Sets insured and insuranceAmount.
     *
     * @return void
     * @throws \Exception
     */
    private function setInsuranceData(): void
    {
        $isInsured       = false;
        $insuranceAmount = 0;

        $isDefaultInsured                  = (bool) $this->getCarrierSetting(WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED);
        $isDefaultInsuredFromPrice         = $this->getCarrierSetting(WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED_FROM_PRICE);
        $isDefaultInsuredForBE             = (bool) $this->getCarrierSetting(WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED_FOR_BE);
        $insuranceFromDeliveryOptions      = $this->shipmentOptions->getInsurance();
        $cc                                = $this->getShippingCountry();
        $isBe                              = AbstractConsignment::CC_BE === $cc;
        $isNl                              = AbstractConsignment::CC_NL === $cc;
        $isRowOrEu                         = (WCMP_Country_Codes::isEuCountry($cc) ||
            WCMP_Country_Codes::isWorldShipmentCountry($cc)) && ! $isNl;
        $orderTotalExceedsInsuredFromPrice = $this->order->get_total() >= (float) $isDefaultInsuredFromPrice || $isRowOrEu;


        $consignment             = ConsignmentFactory::createByCarrierName($this->carrier);
        $amountPossibilities = $consignment->getInsurancePossibilities($this->getShippingCountry());

        if ($insuranceFromDeliveryOptions && $insuranceFromDeliveryOptions >= reset($amountPossibilities)) {
            $isInsured       = (bool) $insuranceFromDeliveryOptions;
            $insuranceAmount = $insuranceFromDeliveryOptions;
        } elseif ($isDefaultInsured && $isBe) {
            $isInsured       = 0 === $insuranceFromDeliveryOptions ? false : $isDefaultInsuredForBE;
            $insuranceAmount = $isInsured ? self::DEFAULT_BELGIAN_INSURANCE : 0;
        } elseif ($isDefaultInsured && $orderTotalExceedsInsuredFromPrice && 0 !== $insuranceFromDeliveryOptions) {
            $insuranceAmount = $isRowOrEu ? $this->getCarrierSetting(WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED_EU_AMOUNT) : $this->getCarrierSetting(
                WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED_AMOUNT
            );
            $isInsured       = (bool) $insuranceAmount;
        }

        if (! $isNl && ! $isInsured) {
            $this->signature = false;
        }

        $consignmentSettingName = AbstractConsignment::SHIPMENT_OPTION_INSURANCE;
        if ($isInsured
            && $this->deliveryOptions->isPickup()
            && ! $this->consignment->canHaveShipmentOption($consignmentSettingName)
        ) {
            $this->showAdminNoticeOptionRemoved(
                __(
                    self::OPTION_TRANSLATION_STRINGS[$consignmentSettingName] ?? $consignmentSettingName,
                    'woocommerce-myparcel'
                )
            );
            $this->insured         = false;
            $this->insuranceAmount = 0;

            return;
        }

        $this->insured         = $isInsured;
        $this->insuranceAmount = (int) $insuranceAmount;
    }

    /**
     * @return void
     */
    private function setLabelDescription(): void
    {
        $defaultValue     = "Order: " . $this->order->get_id();
        $valueFromSetting = WCMYPA()->setting_collection->getByName(WCMYPA_Settings::SETTING_LABEL_DESCRIPTION);
        $valueFromOrder   = $this->shipmentOptions->getLabelDescription();

        $this->labelDescription = (string) ($valueFromOrder ?? $valueFromSetting ?? $defaultValue);
    }

    /**
     * @return bool
     */
    private function determineLargeFormat(): bool
    {
        $weightFromOrder    = WCMP_Export::convertWeightToGrams($this->extraOptions['weight'] ?? 0);
        $weightFromSettings = (int) $this->getCarrierSetting(WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_LARGE_FORMAT_FROM_WEIGHT);
        $optionFromSettings = (bool) $this->getCarrierSetting(WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_LARGE_FORMAT);
        $defaultLargeFormat = $this->shipmentOptions->hasLargeFormat() ?? $optionFromSettings;

        return $defaultLargeFormat && $weightFromOrder >= $weightFromSettings;
    }

    /**
     * @param callable $calculateOptionValue
     * @param string   $carrierSettingName
     * @param string   $consignmentSettingName
     * @param bool     $defaultWhenFalse
     *
     * @return bool
     */
    private function determineShipmentOption(
        callable $calculateOptionValue,
        string $carrierSettingName,
        string $consignmentSettingName,
        bool $defaultWhenFalse
    ): bool {
        $returnValue = $calculateOptionValue();

        if (null === $returnValue || (false === $returnValue && $defaultWhenFalse)) {
            $returnValue = (bool) $this->getCarrierSetting($carrierSettingName);
        }

        if ($returnValue
            && $this->deliveryOptions->isPickup()
            && ! $this->consignment->canHaveShipmentOption($consignmentSettingName)
        ) {
            $this->showAdminNoticeOptionRemoved(
                __(
                    self::OPTION_TRANSLATION_STRINGS[$consignmentSettingName] ?? $consignmentSettingName,
                    'woocommerce-myparcel'
                )
            );
            $returnValue = false;
        }

        return $returnValue;
    }

    /**
     * @return void
     */
    private function setSameDayDelivery(): void
    {
        $this->sameDayDelivery = (bool) $this->shipmentOptions->isSameDayDelivery();
    }

    /**
     * @return void
     * @throws \Exception
     */
    private function setPackageType(): void
    {
        $this->packageType = WCMYPA()->export->getPackageTypeFromOrder($this->order, $this->deliveryOptions);
    }

    /**
     * @param string $settingName
     *
     * @return mixed
     */
    private function getCarrierSetting(string $settingName)
    {
        return WCMYPA()->setting_collection->where('carrier', $this->carrier)->getByName($settingName);
    }

    /**
     * @return string
     */
    public function getShippingCountry(): string
    {
        return $this->shippingCountry;
    }

    /**
     * @return \MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter
     */
    public function getDeliveryOptions(): AbstractDeliveryOptionsAdapter
    {
        return $this->deliveryOptions;
    }

    /**
     * @return array
     */
    public function getExtraOptions(): array
    {
        return $this->extraOptions;
    }

    /**
     * Returns the weight of a single collo plus the empty parcel weight.
     *
     * @return int
     */
    public function getColloWeight(): int
    {
        $packageType             = $this->getPackageType();
        $digitalStampRangeWeight = AbstractConsignment::PACKAGE_TYPE_DIGITAL_STAMP_NAME === $packageType
            ? $this->getDigitalStampRangeWeight() : null;
        $weight                  = $this->getWeight() / $this->getColloAmount();

        if (AbstractConsignment::PACKAGE_TYPE_PACKAGE_NAME === $packageType) {
            $emptyParcelWeight = (float) WCMP_Export_Consignments::getSetting(WCMYPA_Settings::SETTING_EMPTY_PARCEL_WEIGHT);
            $weight            += $emptyParcelWeight;
        }

        return $digitalStampRangeWeight ?? WCMP_Export::convertWeightToGrams($weight);
    }

    /**
     * @param \MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter|array|null
     *
     * @throws \Exception
     */
    private function setDeliveryOptions($deliveryOptions = null): void
    {
        if (is_a($deliveryOptions, AbstractDeliveryOptionsAdapter::class)) {
            $this->deliveryOptions = $deliveryOptions;
        } else {
            $this->deliveryOptions = WCMYPA_Admin::getDeliveryOptionsFromOrder($this->order, (array) $deliveryOptions);
        }
    }

    /**
     * @param string $optionName human understandable string denoting the concerned option
     */
    private function showAdminNoticeOptionRemoved(string $optionName): void
    {
        Messages::showAdminNotice(
            sprintf(
                __('warning_removed_disallowed_delivery_option', 'woocommerce-myparcel'),
                $optionName,
                $this->order->get_id(),
                __('shipment_options_delivery_pickup', 'woocommerce-myparcel')
            ),
            Messages::NOTICE_LEVEL_WARNING
        );
    }
}
