<?php

use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\BpostConsignment;
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
    public const API_URL              = "https://api.sendmyparcel.be/";

    /**
     * @var array
     */
    public const CARRIERS_HUMAN = [
        DPDConsignment::CARRIER_NAME   => 'DPD',
        BpostConsignment::CARRIER_NAME => 'bpost',
    ];

    public const HAS_MULTI_COLLO = false;

    public const DEFAULT_COUNTRY_CODE = "BE";
    public const DEFAULT_CARRIER      = BpostConsignment::CARRIER_NAME;

    /**
     * @var array
     */
    private static $packageTypes;
    /**
     * @var array
     */
    private static $packageTypesHuman;

    public function __construct()
    {
        self::$packageTypes = [
            AbstractConsignment::PACKAGE_TYPE_PACKAGE_NAME,
        ];

        self::$packageTypesHuman = [
            AbstractConsignment::PACKAGE_TYPE_PACKAGE_NAME => __("Parcel", "woocommerce-myparcelbe"),
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
        if (is_numeric($packageType)) {
            $integerMap = array_flip(AbstractConsignment::PACKAGE_TYPES_NAMES_IDS_MAP);
            $packageType = (int) $packageType;

            if (! array_key_exists($packageType, $integerMap)) {
                return (string) $packageType;
            }

            $packageType = $integerMap[$packageType];
        }

        if (! array_key_exists($packageType, self::$packageTypesHuman)) {
            return $packageType;
        }

        return self::$packageTypesHuman[$packageType];
    }

    /**
     * @return array
     */
    public static function getCarriersWithInsurance(): array
    {
        return [
            BpostConsignment::CARRIER_NAME,
        ];
    }

    /**
     * @return array
     */
    public static function getCarriersWithSignature(): array
    {
        return [
            BpostConsignment::CARRIER_NAME,
        ];
    }

    /**
     * @return array
     */
    public static function getCarriersHuman(): array
    {
        return [
            BpostConsignment::CARRIER_NAME => __("bpost", "woocommerce-myparcelbe"),
            DPDConsignment::CARRIER_NAME   => __("dpd", "woocommerce-myparcelbe"),
        ];
    }
}

return new WCMP_Data();
