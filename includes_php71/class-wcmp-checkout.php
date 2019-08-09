<?php

use WPO\WC\MyParcelBE\Collections\SettingsCollection;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('WooCommerce_MyParcelBE_Checkout')) :

    /**
     * Frontend views
     */
    class WooCommerce_MyParcelBE_Checkout
    {
        /**
         * @var SettingsCollection
         */
        private $settings;

        /**
         * WooCommerce_MyParcelBE_Checkout constructor.
         */
        public function __construct()
        {
            add_action('wp_enqueue_scripts', [$this, 'frontend_scripts_styles']);
            $this->settings = WooCommerce_MyParcelBE()->setting_collection;
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
            if ($this->settings->isEnabled('use_split_address_fields')) {
                wp_enqueue_script(
                    'wcmp-checkout-fields',
                    WooCommerce_MyParcelBE()->plugin_url() . '/assets/js/wcmp-checkout-fields.js',
                    ['wc-checkout'],
                    WC_MYPARCEL_BE_VERSION
                );
            }

            // return if no carriers are enabled
            if (!count($this->settings->like("name", "myparcelbe_carrier_enable_")->toArray())) {
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

            wp_localize_script(
                'wc-myparcelbe-frontend',
                'wcmp_display_settings',
                ['isUsingSplitAddressFields' => $this->settings->isEnabled('use_split_address_fields')]
            );
        }

        /**
         * Output inline script with the variables needed
         */
        public function inject_delivery_options_variables()
        {
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
            $packageTypes = $this->settings->getByName("shipping_methods_package_types");
            $shipping_methods = [];

            if (array_key_exists(WooCommerce_MyParcelBE_Export::PACKAGE, $packageTypes ?? [])) {
                // Shipping methods associated with parcels = enable delivery options
                $shipping_methods = $packageTypes[WooCommerce_MyParcelBE_Export::PACKAGE];
            }

            return json_encode($shipping_methods);
        }

        /**
         * @return bool
         */
        public function get_delivery_options_always_display()
        {
            if ($this->settings->getByName('checkout_display') === 'all_methods') {
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
            $carriers = $this->settings->like("name", "myparcelbe_carrier_enable_")->pluck("carrier")->toArray();

            $myParcelConfig = [
                "config"  => [
                    "apiBaseUrl" => WooCommerce_MyParcelBE_Frontend_Settings::BASE_URL,
                    "carriers"   => $carriers,
                    "platform"   => "belgie",
                    "locale"     => "nl-BE",

                    "allowDelivery"     => $this->settings->getByName('deliver_enabled'),
                    "allowPickupPoints" => $this->settings->getByName('pickup_enabled'),
                ],
                "strings" => [
                    "addressNotFound"       => __('Address details are not entered', 'woocommerce-myparcelbe'),
                    "city"                  => __('City', 'woocommerce-myparcelbe'),
                    "closed"                => __('Closed', 'woocommerce-myparcelbe'),
                    "deliveryTitle"         => __('Standard delivery title', 'woocommerce-myparcelbe'),
                    "headerDeliveryOptions" => strip_tags($this->settings->getByName("header_delivery_options_title")),
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
                $myParcelConfig["carrierSettings"][$carrier] = [];

                foreach ($settingsMap as $jsKey => $settingKey) {
                    $myParcelConfig["carrierSettings"][$carrier][$jsKey] = $this->prepareSettingForConfig(
                        $this->settings->getByName("{$carrier}_{$settingKey}")
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
            $setLocation = $this->settings->getByName("checkout_place");

            return $setLocation ?? 'woocommerce_after_checkout_billing_form';
        }
    }
endif;
