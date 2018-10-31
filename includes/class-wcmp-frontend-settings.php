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


        /**
         * Start settings only_recipient
         */

        /**
         * @return int
         */
        public function is_only_recipient_enabled() {
            if (isset($this->settings['only_recipient_enabled'])) {
                return $this->settings['only_recipient_enabled'] ? 1 : 0;
            }
            return 0;
        }

        /**
         * @return mixed
         */
        public function only_recipient_title() {
            if (isset($this->settings['only_recipient_title'])) {
                return $this->settings['only_recipient_title'];
            }

            return WooCommerce_MyParcel_Settings::get_one_checkout_setting_title('only_recipient_title');
        }

        /**
         * @return string
         */
        public function get_price_only_recipient() {
            if (isset($this->settings['only_recipient_fee'])) {
                $price       = $this->settings['only_recipient_fee'];
                $total_price = $this->get_total_price_with_tax( $price );

                return $total_price;
            }
        }


        /**
         * End settings only_recipient
         * Start settings signature
         */

        /**
         * @return int
         */
        public function is_signature_enabled() {
            if (isset($this->settings['signed_enabled'])) {
                return $this->settings['signed_enabled'] ? 1 : 0;
            }
            return 0;
        }

        /**
         * @return mixed
         */
        public function signature_title() {
            if (isset($this->settings['signed_title'])) {
                return $this->settings['signed_title'];
            }

            return WooCommerce_MyParcel_Settings::get_one_checkout_setting_title('signed_title');
        }
        /**
         * @return string
         */
        public function get_price_signature() {
            if (isset($this->settings['signed_fee'])) {
                $price       = $this->settings['signed_fee'];
                $total_price = $this->get_total_price_with_tax( $price );

                return $total_price;
            }
        }


        /**
         * End settings signature
         * Start settings morning delivery
         */

        /**
         * @return int
         */
        public function is_morning_enabled() {
            if (isset($this->settings['morning_enabled'])) {
                return $this->settings['morning_enabled'] ? 1 : 0;
            }
            return 0;
        }

        /**
         * @return mixed
         */
        public function morning_title() {
            if (isset($this->settings['morning_title'])) {
                return $this->settings['morning_title'];
            }

            return WooCommerce_MyParcel_Settings::get_one_checkout_setting_title('morning_title');
        }
        /**
         * @return string
         */
        public function get_price_morning() {
            if (isset($this->settings['morning_fee'])) {
                $price       = $this->settings['morning_fee'];
                $total_price = $this->get_total_price_with_tax( $price );

                return $total_price;
            }
        }

        /**
         * End settings morning delivery
         * Start settings standard delivery
         */

        /**
         * @return int
         */
        public function is_standard_enabled() {
            return $this->settings['standard_enabled'] ? 1 : 0;
        }

        /**
         * @return mixed
         */
        public function standard_title() {
            if (isset($this->settings['standard_title'])) {
                return $this->settings['standard_title'];
            }

            return WooCommerce_MyParcel_Settings::get_one_checkout_setting_title('standard_title');
        }

        /**
         * @return mixed
         */
        public function belgium_standard_title() {
            if (isset($this->settings['belgium_standard_title'])) {
                return $this->settings['belgium_standard_title'];
            }
            
            return WooCommerce_MyParcel_Settings::get_one_checkout_setting_title('belgium_standard_title');
        }

        /**
         * End settings standard delivery
         * Start settings evening delivery
         */

        /**
         * @return int
         */
        public function is_evening_enabled() {
            if (isset($this->settings['night_enabled'])) {
                return $this->settings['night_enabled'] ? 1 : 0;
            }
            return 0;
        }

        /**
         * @return mixed
         */
        public function night_title() {
            if (isset($this->settings['night_title'])) {
                return $this->settings['night_title'];
            }

            return WooCommerce_MyParcel_Settings::get_one_checkout_setting_title('night_title');
        }
        /**
         * @return string
         */
        public function get_price_evening() {
            if (isset($this->settings['night_fee'])) {
                $price       = $this->settings['night_fee'];
                $total_price = $this->get_total_price_with_tax( $price );

                return $total_price;
            }
        }

        /**
         * End settings evening delivery
         * Start settings pickup delivery
         */

        /**
         * @return bool
         */
        public function is_pickup_enabled() {
            if (isset($this->settings['pickup_enabled'])) {
                return (bool) $this->settings['pickup_enabled'];
            }
            return false;
        }

	    /**
	     * @return mixed
	     */
	    public function header_delivery_options_title() {
		    if (isset($this->settings['header_delivery_options_title'])) {
			    return $this->settings['header_delivery_options_title'];
		    }

            return WooCommerce_MyParcel_Settings::get_one_checkout_setting_title('header_delivery_options_title');
	    }

        /**
         * @return mixed
         */
        public function at_home_delivery_title() {
            if (isset($this->settings['at_home_delivery_title'])) {
                return $this->settings['at_home_delivery_title'];
            }

            return WooCommerce_MyParcel_Settings::get_one_checkout_setting_title('at_home_delivery_title');
        }

        /**
         * @return mixed
         *
         * Get the at home delivery title for Belgium delivery
         */
        public function belgium_at_home_delivery_title() {
            if (isset($this->settings['belgium_at_home_delivery_title'])) {
                return $this->settings['belgium_at_home_delivery_title'];
            }

            return WooCommerce_MyParcel_Settings::get_one_checkout_setting_title('belgium_at_home_delivery_title');
        }

        /**
         * @return mixed
         */
        public function pickup_title() {
            if (isset($this->settings['pickup_title'])) {
                return $this->settings['pickup_title'];
            }

            return WooCommerce_MyParcel_Settings::get_one_checkout_setting_title('pickup_title');
        }

        /**
         * @return string
         */
        public function get_price_pickup() {
            if (isset($this->settings['pickup_fee'])) {
                $price       = $this->settings['pickup_fee'];
                $total_price = $this->get_total_price_with_tax( $price );

                return $total_price;
            }
        }

        /**
         * End settings pickup delivery
         * Start settings pickup express delivery
         */

        /**
         * @return bool
         */
        public function is_pickup_express_enabled() {
            if(isset($this->settings['pickup_express_enabled'])) {
                return (bool) $this->settings['pickup_express_enabled'];
            }
            return false;
        }
        /**
         * @return mixed
         */
        public function pickup_express_title() {
            if (isset($this->settings['pickup_express_title'])) {
                return $this->settings['pickup_express_title'];
            }

            return WooCommerce_MyParcel_Settings::get_one_checkout_setting_title('pickup_express_title');
        }

        /**
         * @return string
         */
        public function get_price_pickup_express() {
            if (isset($this->settings['pickup_express_fee'])) {
                $price       = $this->settings['pickup_express_fee'];
                $total_price = $this->get_total_price_with_tax( $price );

                return $total_price;
            }
        }

        /**
         * End settings pickup express delivery
         * Start settings monday delivery
         */
        /**
         * @return int
         */
        public function is_monday_enabled() {
            if (isset($this->settings['saturday_cutoff_enabled'])) {
                return $this->settings['saturday_cutoff_enabled'] ? 1 : 0;
            }
            return 0;
        }

        /**
         * @return mixed
         *
         * Cut-off time for monday delivery
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
            $price              = (float)$price;
            $base_tax_rates     = WC_Tax::get_base_tax_rates( '');
            $base_tax_key       = key($base_tax_rates);
            $taxRate            = (float)$base_tax_rates[$base_tax_key]['rate'];
            $tax                = $price * $taxRate / 100;
            $total_price        = money_format('%.2n', $price + $tax);

            return $total_price;
        }

    }

endif; // class_exists

return new WooCommerce_MyParcel_Frontend_Settings();
