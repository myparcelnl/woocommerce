<?php

use MyParcelNL\Pdk\Base\Service\WeightService;
use MyParcelNL\Sdk\src\Factory\ConsignmentFactory;
use MyParcelNL\Sdk\src\Model\Carrier\AbstractCarrier;
use MyParcelNL\Sdk\src\Model\Carrier\CarrierPostNL;
use MyParcelNL\Sdk\src\Model\Carrier\CarrierInstabox;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\PostNLConsignment;
use MyParcelNL\Sdk\src\Support\Arr;

defined('ABSPATH') or die();

if (class_exists('Data')) {
    return new Data();
}

class Data
{
    public const DEFAULT_COUNTRY_CODE = 'NL';
    public const DEFAULT_CARRIER_CLASS = CarrierPostNL::class;

    /**
     * @var array
     */
    private static $packageTypesHuman;

    /**
     * @var array
     */
    private static $deliveryTypesHuman;

    public function __construct()
    {
        self::$packageTypesHuman = [
            AbstractConsignment::PACKAGE_TYPE_PACKAGE_NAME       => __("Package", "woocommerce-myparcel"),
            AbstractConsignment::PACKAGE_TYPE_MAILBOX_NAME       => __("Mailbox", "woocommerce-myparcel"),
            AbstractConsignment::PACKAGE_TYPE_LETTER_NAME        => __("Unpaid letter", "woocommerce-myparcel"),
            AbstractConsignment::PACKAGE_TYPE_DIGITAL_STAMP_NAME => __("Digital stamp", "woocommerce-myparcel"),
        ];

        self::$deliveryTypesHuman = [
            AbstractConsignment::DELIVERY_TYPE_MORNING_NAME  => __("shipment_options_delivery_morning", "woocommerce-myparcel"),
            AbstractConsignment::DELIVERY_TYPE_STANDARD_NAME => __("shipment_options_delivery_standard", "woocommerce-myparcel"),
            AbstractConsignment::DELIVERY_TYPE_EVENING_NAME  => __("shipment_options_delivery_evening", "woocommerce-myparcel"),
            AbstractConsignment::DELIVERY_TYPE_PICKUP_NAME   => __("shipment_options_delivery_pickup", "woocommerce-myparcel"),
        ];
    }

    /**
     * Returns the weight in grams.
     *
     * @param  int|float $weight
     *
     * @return int
     */
    public static function convertWeightToGrams($weight): int
    {
        $weightUnit = get_option('woocommerce_weight_unit');
        return WeightService::convertToGrams( (int) $weight, $weightUnit);
    }

    /**
     * @return array
     */
    public static function getPackageTypesHuman(): array
    {
        return self::$packageTypesHuman;
    }

    /**
     * @return array
     */
    public static function getDeliveryTypesHuman(): array
    {
        return self::$deliveryTypesHuman;
    }

    /**
     * @return class-string[]
     */
    public static function getCarriers(): array
    {
        return [
            CarrierPostNL::class,
            CarrierInstabox::class,
        ];
    }

    /**
     * @param int|string $packageType
     *
     * @return string|null
     */
    public static function getPackageTypeHuman($packageType): ?string
    {
        return self::getHuman(
            $packageType,
            AbstractConsignment::PACKAGE_TYPES_NAMES_IDS_MAP,
            self::$packageTypesHuman
        );
    }

    /**
     * @param string $packageType
     *
     * @return int|null
     */
    public static function getPackageTypeId(string $packageType): ?int
    {
        return Arr::get(AbstractConsignment::PACKAGE_TYPES_NAMES_IDS_MAP, $packageType);
    }

    /**
     * @param int $packageType
     *
     * @return string|null
     */
    public static function getPackageTypeName(int $packageType): ?string
    {
        return Arr::get(array_flip(AbstractConsignment::PACKAGE_TYPES_NAMES_IDS_MAP), (string) $packageType, null);
    }

    /**
     * @param  \MyParcelNL\Sdk\src\Model\Carrier\AbstractCarrier $carrier
     *
     * @return bool
     */
    public static function hasCarrier(AbstractCarrier $carrier): bool
    {
        return in_array(get_class($carrier), self::getCarriers());
    }

    /**
     * @param string|int $key
     * @param array      $map
     * @param array      $humanMap
     *
     * @return string|null
     */
    private static function getHuman($key, array $map, array $humanMap): ?string
    {
        if (is_numeric($key)) {
            $integerMap = array_flip($map);
            $key        = (int) $key;

            if (! array_key_exists($key, $integerMap)) {
                return null;
            }

            $key = $integerMap[$key];
        }

        return $humanMap[$key] ?? null;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public static function getInsuranceAmounts(): array
    {
        $amounts = [];

        /**
         * @type PostNLConsignment
         */
        $carrier             = ConsignmentFactory::createByCarrierName(CarrierPostNL::NAME);
        $amountPossibilities = $carrier->getInsurancePossibilities();

        foreach ($amountPossibilities as $key => $value) {
            $amounts[$value] = $value;
        }

        return $amounts;
    }

    /**
     * Check if a given cc matches the default country code.
     *
     * @param string $country
     *
     * @return bool
     */
    public static function isHomeCountry(string $country): bool
    {
        return self::DEFAULT_COUNTRY_CODE === $country;
    }

    /**
     * @return array
     */
    public static function getDigitalStampRangeOptions(): array
    {
        $options = [];

        foreach (WeightService::DIGITAL_STAMP_RANGES as $tierRange) {
            $options[$tierRange['average']] = sprintf('%s - %s gram', $tierRange['min'], $tierRange['max']);
        }

        return $options;
    }
}

return new Data();
