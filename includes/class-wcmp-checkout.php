<?php

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (! class_exists('wcmp_checkout')) :

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
            if (! is_checkout() && ! is_order_received_page()) {
                return;
            }

            // if using split address fields
            if (WooCommerce_MyParcelBE()->setting_collection->isEnabled('use_split_address_fields')) {
                wp_enqueue_script(
                    'wcmp-checkout-fields',
                    WooCommerce_MyParcelBE()->plugin_url() . '/assets/js/wcmp-checkout-fields.js',
                    ['wc-checkout'],
                    WC_MYPARCEL_BE_VERSION
                );
            }

            // Don't load any further checkout data if no carrier is active
            if (! WooCommerce_MyParcelBE()->setting_collection->isEnabled('delivery_options_enabled')) {
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
                    // Convert true/false to int for JavaScript
                    'isUsingSplitAddressFields' => WooCommerce_MyParcelBE()
                        ->setting_collection->isEnabled('use_split_address_fields')
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

            // Load the checkout template.
            add_action(
                apply_filters(
                    'wc_myparcel_delivery_options_location',
                    $this->get_checkout_position()
                ),
                array($this, 'output_delivery_options'),
                10
            );
        }

        /**
         * @return string
         */
        public function get_delivery_options_shipping_methods()
        {
            $packageTypes = WooCommerce_MyParcelBE()->setting_collection->getByName(
                "shipping_methods_package_types"
            );

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
            $settings = WooCommerce_MyParcelBE()->setting_collection;

            $carriers = $this->get_carriers();

            $myParcelConfig = [
                "config"  => [
                    "apiBaseUrl" => "https://edie.api.staging.myparcel.nl", // todo remove
                    "carriers"   => $carriers,
                    "platform"   => "belgie",
                    "locale"     => "nl-BE",
                    "currency"   => get_woocommerce_currency(),
                ],
                "strings" => [
                    "addressNotFound"       => __('Address details are not entered', 'woocommerce-myparcelbe'),
                    "city"                  => __('City', 'woocommerce-myparcelbe'),
                    "closed"                => __('Closed', 'woocommerce-myparcelbe'),
                    "deliveryTitle"         => __('Standard delivery title', 'woocommerce-myparcelbe'),
                    "headerDeliveryOptions" => strip_tags(
                        $settings->getStringByName("header_delivery_options_title")
                    ),
                    "houseNumber"           => __('House number', 'woocommerce-myparcelbe'),
                    "openingHours"          => __('Opening hours', 'woocommerce-myparcelbe'),
                    "pickUpFrom"            => __('Pick up from', 'woocommerce-myparcelbe'),
                    "pickupTitle"           => __('Pickup', 'woocommerce-myparcelbe'),
                    "postcode"              => __('Postcode', 'woocommerce-myparcelbe'),
                    "retry"                 => __('Retry', 'woocommerce-myparcelbe'),
                    "wrongHouseNumberCity"  => __('Postcode/city combination unknown', 'woocommerce-myparcelbe'),
                    "signatureTitle"        => $settings->getStringByName("signature_title"),
                ],
            ];

            foreach ($carriers as $carrier) {
                $myParcelConfig["config"]["carrierSettings"][$carrier] = [
                    "allowDeliveryOptions" => $settings->isEnabled("{$carrier}_delivery_enabled"),
                    "allowPickupLocations" => $settings->isEnabled("{$carrier}_pickup_enabled"),
                    "allowSignature"       => $settings->getBooleanByName("{$carrier}_signature_enabled"),
                    "cutoffTime"           => $settings->getStringByName("{$carrier}_cutoff_time"),
                    "deliveryDaysWindow"   => $settings->getIntegerByName("{$carrier}_delivery_days_window"),
                    "dropOffDays"          => $settings->getByName("{$carrier}_drop_off_days"),
                    "dropOffDelay"         => $settings->getIntegerByName("{$carrier}_drop_off_delay"),
                    "pricePickup"          => $settings->getIntegerByName("{$carrier}_pickup_fee"),
                    "priceSignature"       => $settings->getIntegerByName("{$carrier}_signature_fee"),
                ];
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
            if (null === $setting) {
                $setting = 0;
            } else if (is_array($setting)) {
                $setting = implode(';', $setting);
            }

            return $setting;
        }

        /**
         * Output the delivery options template.
         */
        public function output_delivery_options()
        {
            do_action('woocommerce_myparcelbe_before_delivery_options');
            require_once(WooCommerce_MyParcelBE()->includes . '/views/wcmp-checkout-template.php');
            do_action('woocommerce_myparcelbe_after_delivery_options');
        }

        /**
         * Get the position where the checkout should be rendered.
         *
         * @return string
         */
        public function get_checkout_position(): string
        {
            $setLocation = WooCommerce_MyParcelBE()->setting_collection->getByName("checkout_position");

            return $setLocation ?? 'woocommerce_after_checkout_billing_form';
        }

        /**
         * Get the array of enabled carriers by checking if they have either delivery or pickup enabled.
         *
         * @return array
         */
        private function get_carriers(): array
        {
            $settings = WooCommerce_MyParcelBE()->setting_collection;
            $carriers = [];

            foreach (["bpost", "dpd"] as $carrier) {
                if ($settings->getByName("{$carrier}_pickup_enabled") ||
                    $settings->getByName("{$carrier}_delivery_enabled")
                ) {
                    $carriers[] = $carrier;
                }
            }

            return $carriers;
        }
    }
endif;

return new wcmp_checkout();
