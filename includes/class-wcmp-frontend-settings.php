<?php

use WPO\WC\MyParcel\Compatibility\WC_Core as WCX;
use WPO\WC\MyParcel\Compatibility\Order as WCX_Order;
use WPO\WC\MyParcel\Compatibility\Product as WCX_Product;

/**
 * Frontend views
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

if ( ! class_exists( 'WooCommerce_MyParcel_Frontend_Settings' ) ) :

    class WooCommerce_MyParcel_Frontend_Settings {

        const DAYS_SATURDAY = 6;

        const CARRIER_CODE = 1;
        const CARRIER_NAME = "PostNL";
        const BASE_URL = "https://api.myparcel.nl/";

        private $settings;

        function __construct() {

            $this->settings = WooCommerce_MyParcel()->checkout_settings;
       }


        /* Settings only_recipient */

        /**
         * @return int
         */
        public function is_only_recipient_enabled() {
            return $this->settings['only_recipient_enabled'] ? 1 : 0;
        }

        /**
         * @return mixed
         */
        public function only_recipient_titel() {
            return $this->settings['only_recipient_titel'];
        }

        /**
         * @return string
         */
        public function get_price_only_recipient() {
            $price = $this->settings['only_recipient_fee'];
            $total_price = $this->get_total_price_with_tax($price);
            return $total_price;
        }


        /* Settings signature */

        /**
         * @return int
         */
        public function is_signature_enabled() {
            return $this->settings['signed_enabled'] ? 1 : 0;
        }

        /**
         * @return mixed
         */
        public function signature_titel() {
            return $this->settings['signed_titel'];
        }
        /**
         * @return string
         */
        public function get_price_signature() {
            $price = $this->settings['signed_fee'];
            $total_price = $this->get_total_price_with_tax($price);
            return $total_price;
        }


        /* Settings morning delivery */

        /**
         * @return int
         */
        public function is_morning_enabled() {
            return $this->settings['morning_enabled'] ? 1 : 0;
        }

        /**
         * @return mixed
         */
        public function morning_titel() {
            return $this->settings['morning_titel'];
        }
        /**
         * @return string
         */
        public function get_price_morning() {
            $price = $this->settings['morning_fee'];
            $total_price = $this->get_total_price_with_tax($price);
            return $total_price;
        }

        /* Settings standard delivery */

        /**
         * @return int
         */
        public function is_standard_enabled() {
            return $this->settings['standard_enabled'] ? 1 : 0;
        }

        /**
         * @return mixed
         */
        public function standard_titel() {
            return $this->settings['standard_titel'];
        }


        /* Settings evening delivery */

        /**
         * @return int
         */
        public function is_evening_enabled() {
            return $this->settings['night_enabled'] ? 1 : 0;
        }

        /**
         * @return mixed
         */
        public function evening_titel() {
            return $this->settings['night_titel'];
        }
        /**
         * @return string
         */
        public function get_price_evening() {
            $price = $this->settings['night_fee'];
            $total_price = $this->get_total_price_with_tax($price);
            return $total_price;
        }



        /* Settings pickup delivery */

        /**
         * @return bool
         */
        public function is_pickup_enabled() {
            return (bool) $this->settings['pickup_enabled'];
        }

        /**
         * @return mixed
         */
        public function at_home_delivery_titel() {
            return $this->settings['at_home_delivery_titel'];
        }

        /**
         * @return mixed
         */
        public function pickup_titel() {
            return $this->settings['pickup_titel'];
        }

        /**
         * @return string
         */
        public function get_price_pickup() {
            $price = $this->settings['pickup_fee'];
            $total_price = $this->get_total_price_with_tax($price);
            return $total_price;
        }

        /* Settings pickup express delivery */

        /**
         * @return bool
         */
        public function is_pickup_express_enabled() {
            return (bool) $this->settings['pickup_express_enabled'];
        }
        /**
         * @return mixed
         */
        public function pickup_express_titel() {
            return $this->settings['pickup_express_titel'];
        }

        /**
         * @return string
         */
        public function get_price_pickup_express() {
            $price = $this->settings['pickup_express_fee'];
            $total_price = $this->get_total_price_with_tax($price);
            return $total_price;
        }


        /* Settings Monday delivery */
        /**
         * @return int
         */
        public function is_monday_enabled() {
            return $this->settings['monday_delivery'] ? 1 : 0;
        }

        /**
         * @return mixed
         *
         * Cut-off time for monday delivery
         */
        public function get_saturday_cutoff_time() {
            return $this->settings['saturday_cutoff_time'];
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
            return $this->settings['dropoff_delay'];
        }

        /**
         * @return mixed
         */
        public function get_deliverydays_window() {
            return $this->settings['deliverydays_window'];
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
        public function get_api_url() {
            return self::BASE_URL;
        }

        /**
         * @return string
         */
        public function get_country_code() {
            return WC()->customer->get_shipping_country();
        }

        /**
         * @return null|string
         */
        public function get_checkout_display() {
            if ( isset( $this->settings['checkout_display'] ) ) {
                return $this->settings['checkout_display'];
            }

            return null;
        }

        /**
         * @param $price
         *
         * @return string
         */
        public function get_total_price_with_tax($price){
            $base_tax_rates     = WC_Tax::get_base_tax_rates( '');
            $base_tax_key       = key($base_tax_rates);
            $taxRate            = $base_tax_rates[$base_tax_key]['rate'];
            $tax                = $price * $taxRate / 100;
            $total_price        = money_format('%.2n', $price + $tax);

            return $total_price;
        }

    }

endif; // class_exists

return new WooCommerce_MyParcel_Frontend_Settings();
