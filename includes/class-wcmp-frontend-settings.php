<?php

if ( ! defined('ABSPATH')) exit;  // Exit if accessed directly

if ( ! class_exists('WooCommerce_MyParcelBE_Frontend_Settings')) :

/**
 * Frontend settings
 */
class WooCommerce_MyParcelBE_Frontend_Settings {

    const DAYS_SATURDAY = 6;
    const CARRIER_CODE = 2;
    const CARRIER_NAME = "Bpost";
    const BASE_URL = "https://api.myparcel.nl/";

    private static $settings;
    private static $general_settings;

    function __construct() {
        self::$settings = WooCommerce_MyParcelBE()->checkout_settings;
        self::$general_settings = WooCommerce_MyParcelBE()->general_settings;
    }

    /**
     * Check if given option is enabled
     * @return bool
     */
    public static function is_enabled($option) {
        $option = $option . "_enabled";

        if (isset(self::$settings[$option])) {
            return self::$settings[$option] ? 1 : 0;
        }

        return 0;
    }

    /**
     * Get given option title
     *
     * @param $option
     * @param null $general
     *
     * @return string
     */
    public static function get_title($option, $general = null) {

        $option = $option . "_title";

        if ($general) {
            if (isset(self::$settings[$option])) {
                return self::$general_settings[$option];
            }
        }

        if (isset(self::$settings[$option])) {
            return self::$settings[$option];
        }

        return WooCommerce_MyParcelBE_Settings::get_checkout_setting_title($option);
    }

    /**
     * Get price of given option
     * @return float
     */
    public static function get_price($option) {
        $option = $option . "_fee";

        if (isset(WooCommerce_MyParcelBE_Frontend_Settings::$settings[$option])) {
            $price = self::$settings[$option];
            $total_price = self::get_total_price_with_tax($price);

            return $total_price;
        }

        return 0;
    }

    /**
     * @return mixed
     */
    public function get_cutoff_time() {
//        if (date_i18n('w') == self::DAYS_SATURDAY && isset($this->settings['saturday_cutoff_time'])) {
//            return self::$settings['saturday_cutoff_time'];
//        }
        return self::$settings['cutoff_time'];
    }

    /**
     * @return mixed
     */
    public function get_dropoff_delay() {
        if (isset(self::$settings['dropoff_delay'])) {
            return self::$settings['dropoff_delay'];
        }

        return 0;
    }

    /**
     * @return mixed
     */
    public function get_deliverydays_window() {
        if (isset(self::$settings['deliverydays_window'])) {
            return (int) self::$settings['deliverydays_window'];
        }

        return 0;
    }

    /**
     * @return string
     */
    public function get_dropoff_days() {
        return implode(";", self::$settings['dropoff_days']);
    }

    /**
     * @return string
     */
    public function get_country_code() {
        return WC()->customer->get_shipping_country();
    }

    /**
     * @param $price
     *
     * @return string
     */
    public static function get_total_price_with_tax($price) {
        $price = (float) $price;
        $base_tax_rates = WC_Tax::get_base_tax_rates('');
        $base_tax_key = key($base_tax_rates);
        $taxRate = isset($base_tax_key) ? (float) $base_tax_rates[$base_tax_key]['rate'] : 0;
        $tax = $price * $taxRate / 100;
        $total_price = (float) number_format($price + $tax, 2);

        return $total_price;
    }
}

endif; // class_exists