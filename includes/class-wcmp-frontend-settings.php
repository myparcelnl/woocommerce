<?php

if ( ! defined('ABSPATH') ) exit;  // Exit if accessed directly

if ( ! class_exists('WooCommerce_MyParcel_Frontend_Settings') ) :

/**
 * Frontend settings
 */
class WooCommerce_MyParcel_Frontend_Settings {

    const DAYS_SATURDAY = 6;

    const CARRIER_CODE = 1;
    const CARRIER_NAME = "PostNL";
    const BASE_URL = "https://api.myparcel.nl/";

    private $settings;

    function __construct() {
        $this->settings = WooCommerce_MyParcel()->checkout_settings;
   }

    /**
     * Check if given option is enabled
     *
     * @return bool
     */
    public function is_enabled($option) {
        $option = $option . "_enabled";

        if (isset($this->settings[$option])) {
            return $this->settings[$option] ? 1 : 0;
        }

        return 0;
    }

    /**
     * Get given option title
     *
     * @return string
     */
    public function get_title($option) {
        $option = $option . "_title";


        if (isset($this->settings[$option])) {
            return $this->settings[$option];
        }

        return WooCommerce_MyParcel_Settings::get_checkout_setting_title($option);
    }

    /**
     * Get price of given option
     *
     * @return float
     */
    public function get_price($option) {
        $option = $option . "_fee";

        if (isset($this->settings[$option])) {
            $price       = $this->settings[$option];
            $total_price = $this->get_total_price_with_tax( $price );

            return $total_price;
        }

        return 0;
    }

    /**
     * @return mixed
     *
     * cut-off time for monday delivery
     */
    public function get_saturday_cutoff_time() {
        if (isset($this->settings['saturday_cutoff_time'])) {
            return $this->settings['saturday_cutoff_time'];
        }
    }

    /**
     * @return mixed
     */
    public function get_cutoff_time() {
        if (
            date_i18n( 'w' ) == self::DAYS_SATURDAY &&
            isset( $this->settings['saturday_cutoff_time'] )
        ) {
            return $this->settings['saturday_cutoff_time'];
        }

        return $this->settings['cutoff_time'];
    }

    /**
     * @return mixed
     */
    public function get_dropoff_delay() {
        if (isset($this->settings['dropoff_delay'])) {
            return $this->settings['dropoff_delay'];
        }
        return 0;
    }

    /**
     * @return mixed
     */
    public function get_deliverydays_window() {
        if (isset($this->settings['deliverydays_window'])) {
            return $this->settings['deliverydays_window'];
        }
        return 0;
    }

    /**
     * @return string
     */
    public function get_dropoff_days() {
        return implode( ";", $this->settings['dropoff_days'] );
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
    public function get_total_price_with_tax($price){
        $price              = (float)$price;
        $base_tax_rates     = WC_Tax::get_base_tax_rates( '');
        $base_tax_key       = key($base_tax_rates);
        $taxRate            = (float)$base_tax_rates[$base_tax_key]['rate'];
        $tax                = $price * $taxRate / 100;
        $total_price        = (float)number_format($price + $tax, 2);

        return $total_price;
    }
}

endif; // class_exists