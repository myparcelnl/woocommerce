<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!class_exists('wcmp_checkout')) :

    /**
     * Frontend views
     */
    class wcmp_checkout
    {
        /**
         * WooCommerce_MyParcelBE_Checkout constructor.
         */
        public function __construct()
        {
            add_action('wp_enqueue_scripts', [$this, 'frontend_scripts_styles']);
        }

        /**
         * Load styles & scripts
         */
        public function frontend_scripts_styles()
        {
            // return if not checkout or order received page
            if (!is_checkout() && !is_order_received_page()) {
                return;
            }

            // if using split fields
            if (WooCommerce_MyParcelBE()->setting_collection->getByName('use_split_address_fields')) {
                wp_enqueue_script(
                    'wcmp-checkout-fields',
                    WooCommerce_MyParcelBE()->plugin_url() . '/assets/js/wcmp-checkout-fields.js',
                    ['wc-checkout'],
                    WC_MYPARCEL_BE_VERSION
                );
            }

            // Don't load any further checkout data if no carrier is active
            if (!WooCommerce_MyParcelBE()->setting_collection->getByName('myparcelbe_carrier_enable_dpd')
                && !WooCommerce_MyParcelBE()->setting_collection->getByName('myparcelbe_carrier_enable_bpost')) {
                return;
            }

            wp_enqueue_script(
                'wc-myparcelbe',
                WooCommerce_MyParcelBE()->plugin_url() . '/assets/js/myparcel.js',
                [],
                WC_MYPARCEL_BE_VERSION
            );

            wp_enqueue_script(
                'wc-myparcelbe-frontend',
                WooCommerce_MyParcelBE()->plugin_url() . '/assets/js/wcmp-frontend.js',
                ['wc-myparcelbe'],
                WC_MYPARCEL_BE_VERSION
            );

            $this->inject_delivery_options_variables();
        }

        /**
         * Localize variables into the checkout scripts.
         */
        public function inject_delivery_options_variables()
        {
            wp_localize_script(
                'wc-myparcelbe-frontend',
                'wcmp_display_settings',
                [
                    'isUsingSplitAddressFields' => WooCommerce_MyParcelBE()->setting_collection->getByName(
                        'use_split_address_fields'
                    ),
                ]
            );

            wp_localize_script(
                'wc-myparcelbe',
                'wcmp_delivery_options',
                [
                    'shipping_methods' => $this->get_delivery_options_shipping_methods(),
                    'always_display'   => $this->get_delivery_options_always_display(),
                ]
            );

            wp_localize_script(
                'wc-myparcelbe',
                'MyParcelConfig',
                $this->get_checkout_config()
            );
        }

        /**
         * @return string
         */
        public function get_delivery_options_shipping_methods()
        {
            $packageTypes     =
                WooCommerce_MyParcelBE()->setting_collection->getByName("shipping_methods_package_types");
            $shipping_methods = [];

            if (array_key_exists(wcmp_export::PACKAGE, $packageTypes ?? [])) {
                // Shipping methods associated with parcels = enable delivery options
                $shipping_methods = $packageTypes[wcmp_export::PACKAGE];
            }

            return json_encode($shipping_methods);
        }

        /**
         * @return bool
         */
        public function get_delivery_options_always_display()
        {
            if (WooCommerce_MyParcelBE()->setting_collection->getByName('checkout_display') === 'all_methods') {
                return true;
            }

            return false;
        }

        /**
         * Get the checkout config in JSON for passing to JavaScript.
         *
         * @return false|mixed|string|void
         */
        public function get_checkout_config()
        {
            $carriers = WooCommerce_MyParcelBE()->setting_collection->like("name", "myparcelbe_carrier_enable_")->pluck(
                "carrier"
            )->toArray();

            $myParcelConfig = [
                "config"  => [
                    "carriers" => $carriers,
                    "platform" => "belgie",
                    "locale"   => "nl-BE",
                    "currency" => get_woocommerce_currency(),

                    "allowDelivery"     => WooCommerce_MyParcelBE()->setting_collection->getByName('deliver_enabled'),
                    "allowPickupPoints" => WooCommerce_MyParcelBE()->setting_collection->getByName('pickup_enabled'),
                ],
                "strings" => [
                    "addressNotFound"       => __('Address details are not entered', 'woocommerce-myparcelbe'),
                    "city"                  => __('City', 'woocommerce-myparcelbe'),
                    "closed"                => __('Closed', 'woocommerce-myparcelbe'),
                    "deliveryTitle"         => __('Standard delivery title', 'woocommerce-myparcelbe'),
                    "headerDeliveryOptions" => strip_tags(
                        WooCommerce_MyParcelBE()->setting_collection->getByName("header_delivery_options_title")
                    ),
                    "houseNumber"           => __('House number', 'woocommerce-myparcelbe'),
                    "openingHours"          => __('Opening hours', 'woocommerce-myparcelbe'),
                    "pickUpFrom"            => __('Pick up from', 'woocommerce-myparcelbe'),
                    "pickupTitle"           => __('bpost pickup', 'woocommerce-myparcelbe'),
                    "postcode"              => __('Postcode', 'woocommerce-myparcelbe'),
                    "retry"                 => __('Retry', 'woocommerce-myparcelbe'),
                    "wrongHouseNumberCity"  => __('Postcode/city combination unknown', 'woocommerce-myparcelbe'),
                ],
            ];

            $settingsMap = [
                "allowSignature"     => "signature_enabled",
                "cutoffTime"         => "cutoff_time",
                "deliveryDaysWindow" => "deliverydays_window",
                "dropOffDays"        => "dropoff_days",
                "dropOffDelay"       => "dropoff_delay",
                "pricePickup"        => "pickup_fee",
                "priceSignature"     => "signature_fee",
                "signatureTitle"     => "priceStandardDelivery",
            ];

            foreach ($carriers as $carrier) {
                $myParcelConfig["config"]["carrierSettings"][$carrier] = [];

                foreach ($settingsMap as $jsKey => $settingKey) {
                    $myParcelConfig["config"]["carrierSettings"][$carrier][$jsKey] = $this->prepareSettingForConfig(
                        WooCommerce_MyParcelBE()->setting_collection->getByName("{$carrier}_{$settingKey}")
                    );
                }
            }

            return json_encode($myParcelConfig);
        }

        /**
         * @param $setting
         *
         * @return string
         */
        public function prepareSettingForConfig($setting)
        {
            if (is_array($setting)) {
                $setting = implode(';', $setting);
            }

            return $setting;
        }

        /**
         * Output some stuff.
         * Return to hide delivery options
         */
        public function output_delivery_options()
        {
            do_action('woocommerce_myparcelbe_before_delivery_options');
            require_once(WooCommerce_MyParcelBE()->includes . '/views/wcmp-checkout-template.php');
            do_action('woocommerce_myparcelbe_after_delivery_options');
        }

        /**
         * Get the location where the checkout should be rendered.
         *
         * @return string
         */
        public function get_checkout_place(): string
        {
            $setLocation = WooCommerce_MyParcelBE()->setting_collection->getByName("checkout_place");

            return $setLocation ?? 'woocommerce_after_checkout_billing_form';
        }
    }
endif;
