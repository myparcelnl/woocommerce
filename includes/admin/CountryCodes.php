<?php

declare(strict_types=1);

use MyParcelNL\Pdk\Base\Service\CountryService;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('CountryCodes')) {
    return new CountryCodes();
}

class CountryCodes
{
    /**
     * @param string $countryCode
     *
     * @return bool
     */
    public static function isEuCountry(string $countryCode): bool
    {
        return in_array($countryCode, CountryService::EU_COUNTRIES, true);
    }

    /**
     * @param  string $countryCode
     *
     * @return bool
     */
    public static function isAllowedDestination(string $countryCode): bool
    {
        return (new WC_Countries())->country_exists($countryCode);
    }
}

return new CountryCodes();
