<?php

use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\BpostConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\DPDConsignment;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('WCMP_Data')) {
    return new WCMP_Data();
}

class WCMP_Data
{
    /**
     * @var array
     */
    public const CARRIERS_HUMAN = [
        DPDConsignment::CARRIER_NAME   => 'DPD',
        BpostConsignment::CARRIER_NAME => 'bpost',
    ];

    public const HAS_MULTI_COLLO = false;

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
            AbstractConsignment::PACKAGE_TYPE_PACKAGE_NAME => _wcmp("Parcel"),
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
            BpostConsignment::CARRIER_NAME => _wcmp('bpost'),
            DPDConsignment::CARRIER_NAME   => _wcmp('dpd'),
        ];
    }
}

return new WCMP_Data();
