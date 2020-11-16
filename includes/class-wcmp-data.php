<?php

use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\BpostConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\DPDConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\PostNLConsignment;
use MyParcelNL\Sdk\src\Support\Arr;
use MyParcelNL\Sdk\src\Factory\ConsignmentFactory;

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
        DPDConsignment::CARRIER_NAME    => 'DPD',
        BpostConsignment::CARRIER_NAME  => 'bpost',
        PostNLConsignment::CARRIER_NAME => 'PostNL',
    ];

    public const HAS_MULTI_COLLO = false;

    public const DEFAULT_COUNTRY_CODE = "BE";

    public const DEFAULT_CARRIER = BpostConsignment::CARRIER_NAME;

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
        ];

        self::$packageTypesHuman = [
            AbstractConsignment::PACKAGE_TYPE_PACKAGE_NAME => __("Parcel", "woocommerce-myparcelbe"),
        ];

        self::$deliveryTypesHuman = [
            AbstractConsignment::DELIVERY_TYPE_MORNING        => __("Morning delivery", "woocommerce-myparcelbe"),
            AbstractConsignment::DELIVERY_TYPE_STANDARD       => __("Standard delivery", "woocommerce-myparcelbe"),
            AbstractConsignment::DELIVERY_TYPE_EVENING        => __("Evening delivery", "woocommerce-myparcelbe"),
            AbstractConsignment::DELIVERY_TYPE_PICKUP         => __("Pickup", "woocommerce-myparcelbe"),
            AbstractConsignment::DELIVERY_TYPE_PICKUP_EXPRESS => __("Pickup express", "woocommerce-myparcelbe"),
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
     * @param int|string $deliveryType
     *
     * @return string
     */
    public static function getDeliveryTypeHuman($deliveryType): string
    {
        return self::getHuman(
            $deliveryType,
            AbstractConsignment::DELIVERY_TYPES_NAMES_IDS_MAP,
            self::$deliveryTypesHuman
        );
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
    public static function getCarriersWithInsurance(): array
    {
        return [
            BpostConsignment::CARRIER_NAME,
            PostNLConsignment::CARRIER_NAME,
        ];
    }

    /**
     * @return array
     */
    public static function getCarriersWithSignature(): array
    {
        return [
            BpostConsignment::CARRIER_NAME,
            PostNLConsignment::CARRIER_NAME,
        ];
    }

    /**
     * @return array
     */
    public static function getCarriersWithOnlyRecipient(): array
    {
        return [
            PostNLConsignment::CARRIER_NAME,
        ];
    }

    /**
     * @return array
     */
    public static function getCarriersWithLargeFormat(): array
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
            BpostConsignment::CARRIER_NAME => __("bpost", "woocommerce-myparcelbe"),
            DPDConsignment::CARRIER_NAME   => __("DPD", "woocommerce-myparcelbe"),
            PostNLConsignment::CARRIER_NAME => __("PostNL", "woocommerce-myparcelbe"),
        ];
    }
}

return new WCMP_Data();
