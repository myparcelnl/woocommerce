<?php

if (! defined("ABSPATH")) {
    exit;
} // Exit if accessed directly

if (class_exists("WCMP_Country_Codes")) {
    return new WCMP_Country_Codes();
}

class WCMP_Country_Codes
{

    private const EURO_COUNTRIES = [
        "AT",
        "NL",
        "BG",
        "CZ",
        "DK",
        "EE",
        "FI",
        "FR",
        "DE",
        "GR",
        "HU",
        "IE",
        "IT",
        "LV",
        "LT",
        "LU",
        "PL",
        "PT",
        "RO",
        "SK",
        "SI",
        "ES",
        "SE",
        "MC",
        "AL",
        "AD",
        "BA",
        "IC",
        "FO",
        "GI",
        "GL",
        "GG",
        "JE",
        "HR",
        "LI",
        "MK",
        "MD",
        "ME",
        "UA",
        "SM",
        "RS",
        "VA",
        "BY",
    ];

    private const WORLD_COUNTRIES = [
        "AF",
        "AQ",
        "DZ",
        "VI",
        "AO",
        "AG",
        "AR",
        "AM",
        "AW",
        "AU",
        "AZ",
        "BS",
        "BH",
        "BD",
        "BB",
        "BZ",
        "BJ",
        "BM",
        "BT",
        "BO",
        "BW",
        "BR",
        "VG",
        "BN",
        "BF",
        "BI",
        "KH",
        "CA",
        "KY",
        "CF",
        "CL",
        "CN",
        "CO",
        "KM",
        "CG",
        "CD",
        "CR",
        "CU",
        "DJ",
        "DM",
        "DO",
        "EC",
        "EG",
        "SV",
        "GQ",
        "ER",
        "ET",
        "FK",
        "FJ",
        "PH",
        "GF",
        "PF",
        "GA",
        "GB",
        "GM",
        "GE",
        "GH",
        "GD",
        "GP",
        "GT",
        "GN",
        "GW",
        "GY",
        "HT",
        "HN",
        "HK",
        "IN",
        "ID",
        "IS",
        "IQ",
        "IR",
        "IL",
        "CI",
        "JM",
        "JP",
        "YE",
        "JO",
        "CV",
        "CM",
        "KZ",
        "KE",
        "KG",
        "KI",
        "KW",
        "LA",
        "LS",
        "LB",
        "LR",
        "LY",
        "MO",
        "MG",
        "MW",
        "MV",
        "MY",
        "ML",
        "MA",
        "MQ",
        "MR",
        "MU",
        "MX",
        "MN",
        "MS",
        "MZ",
        "MM",
        "NA",
        "NR",
        "NP",
        "NI",
        "NC",
        "NZ",
        "NE",
        "NG",
        "KP",
        "UZ",
        "OM",
        "TL",
        "PK",
        "PA",
        "PG",
        "PY",
        "PE",
        "PN",
        "PR",
        "QA",
        "RE",
        "RU",
        "RW",
        "KN",
        "LC",
        "VC",
        "PM",
        "WS",
        "ST",
        "SA",
        "SN",
        "SC",
        "SL",
        "SG",
        "SO",
        "LK",
        "SD",
        "SR",
        "SZ",
        "SY",
        "TJ",
        "TW",
        "TZ",
        "TH",
        "TG",
        "TO",
        "TT",
        "TD",
        "TN",
        "TM",
        "TC",
        "TV",
        "UG",
        "UY",
        "VU",
        "VE",
        "AE",
        "US",
        "VN",
        "ZM",
        "ZW",
        "ZA",
        "KR",
        "AN",
        "BQ",
        "CW",
        "SX",
        "XK",
        "IM",
        "MT",
        "CY",
        "CH",
        "TR",
        "NO",
    ];

    /**
     * @param string $country_code
     *
     * @return bool
     */
    public static function isEuCountry(string $country_code): bool
    {
        return in_array($country_code, self::EURO_COUNTRIES);
    }

    /**
     * @param $country_code
     *
     * @return bool
     */
    public static function isWorldShipmentCountry(string $country_code): bool
    {
        return in_array($country_code, self::WORLD_COUNTRIES);
    }

    /**
     * @param $country_code
     *
     * @return bool
     */
    public static function isAllowedDestination(string $country_code): bool
    {
        return ($country_code === WCMP_Data::DEFAULT_COUNTRY_CODE
                || WCMP_Country_Codes::isEuCountry($country_code)
                || WCMP_Country_Codes::isWorldShipmentCountry(
                $country_code
            ));
    }
}

return new WCMP_Country_Codes();
