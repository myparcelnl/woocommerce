<?php
use \MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;

if (! defined("ABSPATH")) {
    exit;
} // Exit if accessed directly

if (class_exists("WCMP_Country_Codes")) {
    return new WCMP_Country_Codes();
}

class WCMP_Country_Codes
{
    /**
     * @param string $countryCode
     *
     * @return bool
     */
    public static function isEuCountry(string $countryCode): bool
    {
        return in_array($countryCode, AbstractConsignment::EURO_COUNTRIES);
    }

    /**
     * @param string $countryCode
     *
     * @return bool
     */
    public static function isWorldShipmentCountry(string $countryCode): bool
    {
        $countries = new WC_Countries();
        if (! $countries->country_exists($countryCode)) {
            return false;
        }
        return ! self::isEuCountry($countryCode);
    }

    /**
     * @param $countryCode
     *
     * @return bool
     */
    public static function isAllowedDestination(string $countryCode): bool
    {
        $isHomeCountry          = WCMP_Data::isHomeCountry($countryCode);
        $isEuCountry            = self::isEuCountry($countryCode);
        $isWorldShipmentCountry = self::isWorldShipmentCountry($countryCode);

        return $isHomeCountry || $isEuCountry || $isWorldShipmentCountry;
    }
}

return new WCMP_Country_Codes();
