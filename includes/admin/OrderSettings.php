<?php

declare(strict_types=1);

use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter;
use MyParcelNL\Sdk\src\Support\Arr;
use WPO\WC\MyParcel\Compatibility\Order as WCX_Order;
use WPO\WC\MyParcel\Compatibility\Product as WCX_Product;

class OrderSettings
{
    private const DEFAULT_COLLO_AMOUNT = 1;

    /**
     * @var \MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter
     */
    private $deliveryOptions;

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
        $this->carrier         = $this->deliveryOptions->getCarrier() ?? WCMP_Data::DEFAULT_CARRIER;
        $this->shipmentOptions = $this->deliveryOptions->getShipmentOptions();
        $this->shippingCountry = WCX_Order::get_prop($order, 'shipping_country');
        $this->extraOptions    = WCX_Order::get_meta($this->order, WCMYPA_Admin::META_SHIPMENT_OPTIONS_EXTRA);

        $this->setAllData();
    }

    /**
     * @param bool $inGrams
     *
     * @return float
     */
    public function getWeight($inGrams = false): float
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
    public function hasSignature(): bool
    {
        return $this->signature;
    }

    /**
     * @throws \Exception
     */
    private function setAllData(): void
    {
        $this->setPackageType();
        $this->setColloAmount();
        $this->setLabelDescription();

        $this->setAgeCheck();
        $this->setLargeFormat();
        $this->setOnlyRecipient();
        $this->setReturnShipment();
        $this->setSignature();

        $this->setInsuranceData();

        $this->setWeight();
        $this->setDigitalStampRangeWeight();
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
     * @return void
     */
    private function setAgeCheck(): void
    {
        $settingName                 = "{$this->carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_AGE_CHECK;
        $ageCheckFromShipmentOptions = $this->shipmentOptions->hasAgeCheck();
        $ageCheckOfProduct           = $this->getAgeCheckOfProduct();
        $ageCheckFromSettings        = (bool) WCMYPA()->setting_collection->getByName($settingName);

        $this->ageCheck = $ageCheckFromShipmentOptions ?? $ageCheckOfProduct ?? $ageCheckFromSettings;
    }

    /**
     * Gets product age check value based on if it was explicitly set to either true or false. It defaults to inheriting from the default export settings.
     *
     * @return ?bool
     */
    private function getAgeCheckOfProduct(): ?bool
    {
        $hasAgeCheck = null;

        foreach ($this->order->get_items() as $item) {
            $product         = $item->get_product();
            $productAgeCheck = WCX_Product::get_meta($product, WCMYPA_Admin::META_AGE_CHECK, true);

            if ($productAgeCheck === WCMYPA_Admin::PRODUCT_OPTIONS_ENABLED) {
                return true;
            }

            if ($productAgeCheck === WCMYPA_Admin::PRODUCT_OPTIONS_DISABLED) {
                $hasAgeCheck = false;
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
     * @return void
     */
    private function setDigitalStampRangeWeight(): void
    {
        $savedWeight = $this->extraOptions["digital_stamp_weight"] ?? null;
        $orderWeight = $this->getWeight(true);
        $weight      = (float) ($savedWeight ?? $orderWeight);
        $results     = Arr::where(
            WCMP_Data::getDigitalStampRanges(),
            static function ($range) use ($weight) {
                return $weight > $range['min'];
            }
        );

        if (empty($results)) {
            $digitalStampRangeWeight = Arr::first(WCMP_Data::getDigitalStampRanges())['average'];
        } else {
            $digitalStampRangeWeight = Arr::last($results)['average'];
        }

        $this->digitalStampRangeWeight = $digitalStampRangeWeight;
    }

    /**
     * Sets insured and insuranceAmount.
     *
     * @return void
     */
    private function setInsuranceData(): void
    {
        $isInsured       = false;
        $insuranceAmount = 0;

        $isDefaultInsured                  = $this->getCarrierSetting(
            WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED
        );
        $isDefaultInsuredFromPrice         = $this->getCarrierSetting(
            WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED_FROM_PRICE
        );
        $orderTotalExceedsInsuredFromPrice = $this->order->get_total() >= $isDefaultInsuredFromPrice;
        $insuranceFromDeliveryOptions      = $this->shipmentOptions->getInsurance();

        if ($insuranceFromDeliveryOptions) {
            $isInsured       = (bool) $insuranceFromDeliveryOptions;
            $insuranceAmount = $insuranceFromDeliveryOptions;
        } elseif ($isDefaultInsured && $orderTotalExceedsInsuredFromPrice) {
            $isInsured       = true;
            $insuranceAmount = $this->getCarrierSetting(WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED_AMOUNT);
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
     * @return void
     */
    private function setLargeFormat(): void
    {
        $this->largeFormat = (bool) WCMP_Export::getChosenOrDefaultShipmentOption(
            $this->shipmentOptions->hasLargeFormat(),
            "{$this->carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_LARGE_FORMAT
        );
    }

    /**
     * @return void
     */
    private function setOnlyRecipient(): void
    {
        $this->onlyRecipient = (bool) WCMP_Export::getChosenOrDefaultShipmentOption(
            $this->shipmentOptions->hasOnlyRecipient(),
            "{$this->carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_ONLY_RECIPIENT
        );
    }

    /**
     * @return void
     * @throws \Exception
     */
    private function setPackageType(): void
    {
        $packageType = WCMYPA()->export->getPackageTypeFromOrder($this->order, $this->deliveryOptions);
        $this->packageType = $packageType;
    }

    /**
     * @return void
     */
    private function setReturnShipment(): void
    {
        $this->returnShipment = (bool) WCMP_Export::getChosenOrDefaultShipmentOption(
            $this->shipmentOptions->isReturn(),
            "{$this->carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_RETURN
        );
    }

    /**
     * @return void
     */
    private function setSignature(): void
    {
        $this->signature = (bool) WCMP_Export::getChosenOrDefaultShipmentOption(
            $this->shipmentOptions->hasSignature(),
            "{$this->carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_SIGNATURE
        );
    }

    /**
     * @param string $settingName
     *
     * @return mixed
     */
    private function getCarrierSetting(string $settingName)
    {
        return WCMYPA()->setting_collection->getByName("{$this->carrier}_" . $settingName);
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
}
