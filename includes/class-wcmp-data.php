<?php

use MyParcelNL\Sdk\src\Factory\ConsignmentFactory;
use MyParcelNL\Sdk\src\Model\Carrier\AbstractCarrier;
use MyParcelNL\Sdk\src\Model\Carrier\CarrierDHLEuroplus;
use MyParcelNL\Sdk\src\Model\Carrier\CarrierDHLForYou;
use MyParcelNL\Sdk\src\Model\Carrier\CarrierDHLParcelConnect;
use MyParcelNL\Sdk\src\Model\Carrier\CarrierPostNL;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\PostNLConsignment;
use MyParcelNL\Sdk\src\Support\Arr;

defined('ABSPATH') or die();

if (class_exists('WCMP_Data')) {
    return new WCMP_Data();
}

class WCMP_Data
{
    /**
     * @var array
     */
    public const DIGITAL_STAMP_RANGES = [
        [
            'min'     => 0,
            'max'     => 20,
            'average' => 15
        ],
        [
            'min'     => 20,
            'max'     => 50,
            'average' => 35
        ],
        [
            'min'     => 50,
            'max'     => 350,
            'average' => 200,
        ],
        [
            'min'     => 350,
            'max'     => 2000,
            'average' => 1175
        ],
    ];

    public const MAX_COLLO_WEIGHT_PER_PACKAGE_DEFAULT = 30000;

    public const MAX_COLLO_WEIGHT_PER_PACKAGE_TYPE = [
        AbstractConsignment::PACKAGE_TYPE_PACKAGE       => 30000,
        AbstractConsignment::PACKAGE_TYPE_MAILBOX       => 2000,
        AbstractConsignment::PACKAGE_TYPE_LETTER        => 2000,
        AbstractConsignment::PACKAGE_TYPE_DIGITAL_STAMP => 2000,
        AbstractConsignment::PACKAGE_TYPE_PACKAGE_SMALL => 5000,
    ];

    public const DEFAULT_COUNTRY_CODE = 'NL';
    public const DEFAULT_CARRIER_CLASS = CarrierPostNL::class;

    /**
     * @var string[]
     */
    private static $packageTypes;

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
        self::$packageTypes = [
            AbstractConsignment::PACKAGE_TYPE_PACKAGE_NAME,
            AbstractConsignment::PACKAGE_TYPE_MAILBOX_NAME,
            AbstractConsignment::PACKAGE_TYPE_LETTER_NAME,
            AbstractConsignment::PACKAGE_TYPE_DIGITAL_STAMP_NAME,
            AbstractConsignment::PACKAGE_TYPE_PACKAGE_SMALL_NAME,
        ];

        self::$packageTypesHuman = [
            AbstractConsignment::PACKAGE_TYPE_PACKAGE_NAME       => __('Package', 'woocommerce-myparcel'),
            AbstractConsignment::PACKAGE_TYPE_MAILBOX_NAME       => __('Mailbox', 'woocommerce-myparcel'),
            AbstractConsignment::PACKAGE_TYPE_LETTER_NAME        => __('Unpaid letter', 'woocommerce-myparcel'),
            AbstractConsignment::PACKAGE_TYPE_DIGITAL_STAMP_NAME => __('Digital stamp', 'woocommerce-myparcel'),
            AbstractConsignment::PACKAGE_TYPE_PACKAGE_SMALL_NAME => __('Small package', 'woocommerce-myparcel'),
        ];

        self::$deliveryTypesHuman = [
            AbstractConsignment::DELIVERY_TYPE_MORNING_NAME  => __('shipment_options_delivery_morning', 'woocommerce-myparcel'),
            AbstractConsignment::DELIVERY_TYPE_STANDARD_NAME => __('shipment_options_delivery_standard', 'woocommerce-myparcel'),
            AbstractConsignment::DELIVERY_TYPE_EVENING_NAME  => __('shipment_options_delivery_evening', 'woocommerce-myparcel'),
            AbstractConsignment::DELIVERY_TYPE_PICKUP_NAME   => __('shipment_options_delivery_pickup', 'woocommerce-myparcel'),
        ];
    }

    /**
     * @return array
     */
    public static function getPackageTypes(): array
    {
        return self::$packageTypes;
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
     * @return array
     */
    public static function getDigitalStampRanges(): array
    {
        return self::DIGITAL_STAMP_RANGES;
    }

    /**
     * @return class-string[]
     */
    public static function getCarriers(): array
    {
        return [
            CarrierPostNL::class,
            CarrierDHLForYou::class,
            CarrierDHLEuroplus::class,
            CarrierDHLParcelConnect::class
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
        return Arr::get(AbstractConsignment::PACKAGE_TYPES_NAMES_IDS_MAP, $packageType, null);
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
     * @param string|null $deliveryType
     *
     * @return int|null
     */
    public static function getDeliveryTypeId(?string $deliveryType): ?int
    {
        if (! $deliveryType) {
            return AbstractConsignment::DELIVERY_TYPE_STANDARD;
        }

        return Arr::get(AbstractConsignment::DELIVERY_TYPES_NAMES_IDS_MAP, $deliveryType, null);
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
    public static function getInsuranceAmounts($cc, $carrierName): array
    {
        $amounts = [];

        /**
         * @type PostNLConsignment $consignment
         */
        $consignment         = ConsignmentFactory::createByCarrierName($carrierName);
        $amountPossibilities = $consignment->getInsurancePossibilities($cc);

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
}

return new WCMP_Data();
