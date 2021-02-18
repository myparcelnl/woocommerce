<?php

if (! defined("ABSPATH")) {
    exit;
} // Exit if accessed directly

if (class_exists("WCMP_Country_Codes")) {
    return new WCMP_Country_Codes();
}

use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;

class WCMP_Country_Codes
{
    /**
     * @return bool
     */
    public static function isEuCountry(): bool
    {
        $AbstractConsignment = (new AbstractConsignment());
        return  $AbstractConsignment->isEuCountry();
    }

    /**
     * @return bool
     */
    public static function isWorldShipmentCountry(): bool
    {
        $AbstractConsignment = (new AbstractConsignment());
        return  $AbstractConsignment->isCdCountry();
    }

    /**
     * @param $countryCode
     *
     * @return bool
     */
    public static function isAllowedDestination(string $countryCode): bool
    {
        $isHomeCountry          = WCMP_Data::isHomeCountry($countryCode);
        $isEuCountry            = self::isEuCountry();
        $isWorldShipmentCountry = self::isWorldShipmentCountry();

        return $isHomeCountry || $isEuCountry || $isWorldShipmentCountry;
    }
}

return new WCMP_Country_Codes();
