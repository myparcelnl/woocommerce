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
    private static $carriers;
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
        self::$carriers = [
            DPDConsignment::CARRIER_NAME,
            BpostConsignment::CARRIER_NAME,
        ];

        self::$packageTypes = [
            AbstractConsignment::PACKAGE_TYPE_PACKAGE,
        ];

        self::$packageTypesHuman = [
            AbstractConsignment::PACKAGE_TYPE_PACKAGE => _wcmp("Parcel"),
        ];
    }

    /**
     * @return array
     */
    public static function getCarriers(): array
    {
        return self::$carriers;
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

}

return new WCMP_Data();
