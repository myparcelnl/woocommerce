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
        DPDConsignment::CARRIER_NAME    => 'DPD',
        PostNLConsignment::CARRIER_NAME => 'PostNL',
    ];

    public const HAS_MULTI_COLLO = false;

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
            AbstractConsignment::PACKAGE_TYPE_PACKAGE_NAME       => __("Parcel", "woocommerce-myparcel"),
            AbstractConsignment::PACKAGE_TYPE_MAILBOX_NAME       => __("Mailbox package", "woocommerce-myparcel"),
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
            PostNLConsignment::CARRIER_NAME,
        ];
    }

    /**
     *
     * @return array
     */
    public static function getInsuranceAmount(): array
    {
        $carrier = ConsignmentFactory::createByCarrierName(WCMP_Settings::SETTINGS_POSTNL);
        $amountPossibilities = $carrier->getInsurancePossibilities();

        foreach ($amountPossibilities as $key => $value) {
            $amount[$value] = $value;
        }

        return $amount;
    }

    /**
     * @return array
     */
    public static function getCarriersWithSignature(): array
    {
        return [
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
    public static function getCarriersHuman(): array
    {
        return [
            PostNLConsignment::CARRIER_NAME => __("PostNL", "woocommerce-myparcel"),
            DPDConsignment::CARRIER_NAME    => __("DPD", "woocommerce-myparcel"),
        ];
    }
}

return new WCMP_Data();
