<?php

use MyParcelNL\Sdk\src\Factory\ConsignmentFactory;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\PostNLConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\DPDConsignment;
use MyParcelNL\Sdk\src\Support\Arr;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('WCMP_Data')) {
    return new WCMP_Data();
}

class WCMP_Data
{
    public const API_URL = "https://api.myparcel.nl/";

    /**
     * @var array
     */
    public const CARRIERS_HUMAN = [
        PostNLConsignment::CARRIER_NAME => 'PostNL',
        DPDConsignment::CARRIER_NAME    => 'DPD',
    ];

    /**
     * @var array
     */
    public const DIGITAL_STAMP_RANGES = [
        1 => [
            'min'     => 0,
            'max'     => 20,
            'average' => 15
        ],
        2 => [
            'min'     => 20,
            'max'     => 50,
            'average' => 35
        ],
        3 => [
            'min'     => 50,
            'max'     => 100,
            'average' => 75
        ],
        4 => [
            'min'     => 100,
            'max'     => 350,
            'average' => 225
        ],
        5 => [
            'min'     => 350,
            'max'     => 2000,
            'average' => 1175
        ],
    ];

    public const DEFAULT_COUNTRY_CODE = "NL";
    public const DEFAULT_CARRIER      = PostNLConsignment::CARRIER_NAME;

    /**
     * @var array
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
        ];

        self::$packageTypesHuman = [
            AbstractConsignment::PACKAGE_TYPE_PACKAGE_NAME       => __("Package", "woocommerce-myparcel"),
            AbstractConsignment::PACKAGE_TYPE_MAILBOX_NAME       => __("Mailbox", "woocommerce-myparcel"),
            AbstractConsignment::PACKAGE_TYPE_LETTER_NAME        => __("Unpaid letter", "woocommerce-myparcel"),
            AbstractConsignment::PACKAGE_TYPE_DIGITAL_STAMP_NAME => __("Digital stamp", "woocommerce-myparcel"),
        ];

        self::$deliveryTypesHuman = [
            AbstractConsignment::DELIVERY_TYPE_MORNING  => __("Morning delivery", "woocommerce-myparcel"),
            AbstractConsignment::DELIVERY_TYPE_STANDARD => __("Standard delivery", "woocommerce-myparcel"),
            AbstractConsignment::DELIVERY_TYPE_EVENING  => __("Evening delivery", "woocommerce-myparcel"),
            AbstractConsignment::DELIVERY_TYPE_PICKUP   => __("Pickup", "woocommerce-myparcel"),
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
    public static function getDigitalStampWeight(): array
    {
        return self::DIGITAL_STAMP_RANGES;
    }

    /**
     * @param int|string $packageType
     *
     * @return string
     */
    public static function getPackageTypeHuman($packageType): string
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
     * @return int
     */
    public static function getPackageTypeId(string $packageType): int
    {
        return AbstractConsignment::PACKAGE_TYPES_NAMES_IDS_MAP[$packageType] ?? AbstractConsignment::PACKAGE_TYPE_PACKAGE;
    }

    /**
     * @param string|int $key
     * @param array      $map
     * @param array      $humanMap
     *
     * @return string
     */
    private static function getHuman($key, array $map, array $humanMap): string
    {
        if (is_numeric($key)) {
            $integerMap = array_flip($map);
            $key        = (int) $key;

            if (! array_key_exists($key, $integerMap)) {
                return (string) $key;
            }

            $key = $integerMap[$key];
        }

        if (! array_key_exists($key, $humanMap)) {
            return $key;
        }

        return $humanMap[$key];
    }

    /**
     * @return array
     */
    public static function getInsuranceAmount(): array
    {
        $amount = [];

        /**
         * @type PostNLConsignment
         */
        $carrier             = ConsignmentFactory::createByCarrierName(WCMP_Settings::SETTINGS_POSTNL);
        $amountPossibilities = $carrier::INSURANCE_POSSIBILITIES_LOCAL;

        foreach ($amountPossibilities as $key => $value) {
            $amount[$value] = $value;
        }

        return $amount;
    }

    /**
     * @return array
     */
    public static function getPostnlName(): array
    {
        return [
            PostNLConsignment::CARRIER_NAME,
        ];
    }

    /**
     * @return array
     */
    public static function getCarriersHuman(): array
    {
        return [
            PostNLConsignment::CARRIER_NAME => __("PostNL", "woocommerce-myparcel"),
            DPDConsignment::CARRIER_NAME    => __("DPD", "woocommerce-myparcel"),
        ];
    }
}

return new WCMP_Data();
