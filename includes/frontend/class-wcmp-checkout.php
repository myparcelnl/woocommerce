<?php

use MyParcelNL\Sdk\src\Factory\DeliveryOptionsAdapterFactory;
use MyParcelNL\Sdk\src\Model\Consignment\BpostConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\DPDConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\PostNLConsignment;
use MyParcelNL\Sdk\src\Support\Arr;
use WPO\WC\MyParcelBE\Compatibility\Order as WCX_Order;
use WPO\WC\MyParcelBE\Compatibility\WC_Core as WCX;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('WCMP_Checkout')) {
    return new WCMP_Checkout();
}

/**
 * Frontend views
 */
class WCMP_Checkout
{
    /**
     * WCMP_Checkout constructor.
     */
    public function __construct()
    {
        add_action("wp_enqueue_scripts", [$this, "enqueue_frontend_scripts"], 100);

        // Save delivery options data
        add_action("woocommerce_checkout_update_order_meta", [$this, "save_delivery_options"], 10, 2);
    }

    /**
     * Load styles & scripts
     */
    public function enqueue_frontend_scripts()
    {
        // return if not checkout or order received page
        if (! is_checkout() && ! is_order_received_page()) {
            return;
        }

        // if using split address fields
        $useSplitAddressFields = WCMP()->setting_collection->isEnabled(WCMP_Settings::SETTING_USE_SPLIT_ADDRESS_FIELDS);
        if ($useSplitAddressFields) {
            wp_enqueue_script(
                "wcmp-checkout-fields",
                WCMP()->plugin_url() . "/assets/js/wcmp-checkout-fields.js",
                ["wc-checkout"],
                WC_MYPARCEL_BE_VERSION,
                true
            );
        }

        // Don"t load the delivery options scripts if it"s disabled
        if (! WCMP()->setting_collection->isEnabled(WCMP_Settings::SETTING_DELIVERY_OPTIONS_ENABLED)) {
            return;
        }

        /**
         * JS dependencies array
         */
        $deps = ["wc-checkout"];

        /**
         * If split address fields are enabled add the checkout fields script as an additional dependency.
         */
        if ($useSplitAddressFields) {
            array_push($deps, "wcmp-checkout-fields");
        }

        wp_enqueue_script(
            "wc-myparcelbe",
            WCMP()->plugin_url() . "/assets/js/myparcel.js",
            $deps,
            WC_MYPARCEL_BE_VERSION,
            true
        );

        wp_enqueue_script(
            "wc-myparcelbe-frontend",
            WCMP()->plugin_url() . "/assets/js/wcmp-frontend.js",
            array_merge($deps, ["wc-myparcelbe", "jquery"]),
            WC_MYPARCEL_BE_VERSION,
            true
        );

        $this->inject_delivery_options_variables();
    }

    /**
     * Localize variables into the delivery options scripts.
     */
    public function inject_delivery_options_variables()
    {
        wp_localize_script(
            "wc-myparcelbe-frontend",
            "MyParcelDisplaySettings",
            [
                // Convert true/false to int for JavaScript
                "isUsingSplitAddressFields" => (int) WCMP()->setting_collection->isEnabled(
                    WCMP_Settings::SETTING_USE_SPLIT_ADDRESS_FIELDS
                ),
            ]
        );

        wp_localize_script(
            "wc-myparcelbe",
            "MyParcelDeliveryOptions",
            [
                "allowedShippingMethods"    => json_encode($this->getShippingMethodsForDeliveryOptions()),
                "disallowedShippingMethods" => json_encode(["local_pickup"]),
                "alwaysShow"                => $this->alwaysDisplayDeliveryOptions(),
                "hiddenInputName"           => WCMP_Admin::META_DELIVERY_OPTIONS,
            ]
        );

        wp_localize_script(
            'wc-myparcelbe',
            'MyParcelConfig',
            $this->get_delivery_options_config()
        );

        // Load the checkout template.
        add_action(
            apply_filters(
                'wc_wcmp_delivery_options_location',
                WCMP()->setting_collection->getByName(WCMP_Settings::SETTING_DELIVERY_OPTIONS_POSITION)
            ),
            [$this, 'output_delivery_options'],
            10
        );
    }

    /**
     * @return string
     */
    public function get_delivery_options_shipping_methods()
    {
        $packageTypes = WCMP()->setting_collection->getByName(WCMP_Settings::SETTING_SHIPPING_METHODS_PACKAGE_TYPES);

        if (! is_array($packageTypes)) {
            $packageTypes = [];
        }

        $shipping_methods = [];

        if (array_key_exists(WCMP_Export::PACKAGE, $packageTypes ?? [])) {
            // Shipping methods associated with parcels = enable delivery options
            $shipping_methods = $packageTypes[WCMP_Export::PACKAGE];
        }

        return json_encode($shipping_methods);
    }

    /**
     * Get the delivery options config in JSON for passing to JavaScript.
     *
     * @return false|mixed|string|void
     */
    public function get_delivery_options_config()
    {
        $settings = WCMP()->setting_collection;

        $carriers = $this->get_carriers();

        $myParcelConfig = [
            "config"  => [
                "platform" => "belgie",
                "locale"   => "nl-BE",
                "currency" => get_woocommerce_currency(),
            ],
            "strings" => [
                "addressNotFound"         => __("Address details are not entered", "woocommerce-myparcelbe"),
                "city"                    => __("City", "woocommerce-myparcelbe"),
                "closed"                  => __("Closed", "woocommerce-myparcelbe"),
                "deliveryStandardTitle"   => $this->getDeliveryOptionsTitle(WCMP_Settings::SETTING_STANDARD_TITLE),
                "deliveryTitle"           => $this->getDeliveryOptionsTitle(WCMP_Settings::SETTING_DELIVERY_TITLE),
                "headerDeliveryOptions"   => $this->getDeliveryOptionsTitle(WCMP_Settings::SETTING_HEADER_DELIVERY_OPTIONS_TITLE),
                "houseNumber"             => __("House number", "woocommerce-myparcelbe"),
                "openingHours"            => __("Opening hours", "woocommerce-myparcelbe"),
                "pickUpFrom"              => __("Pick up from", "woocommerce-myparcelbe"),
                "pickupTitle"             => $this->getDeliveryOptionsTitle(WCMP_Settings::SETTING_PICKUP_TITLE),
                "postcode"                => __("Postcode", "woocommerce-myparcelbe"),
                "retry"                   => __("Retry", "woocommerce-myparcelbe"),
                "wrongHouseNumberCity"    => __("Postcode/city combination unknown", "woocommerce-myparcelbe"),
                "signatureTitle"          => $this->getDeliveryOptionsTitle(WCMP_Settings::SETTING_SIGNATURE_TITLE),
                "onlyRecipientTitle"      => $this->getDeliveryOptionsTitle(WCMP_Settings::SETTING_ONLY_RECIPIENT_TITLE),

                "pickupLocationsListButton" => $this->getDeliveryOptionsTitle(WCMP_Settings::PICKUP_LOCATIONS_LIST_BUTTON),
                "pickupLocationsMapButton"  => $this->getDeliveryOptionsTitle(WCMP_Settings::PICKUP_LOCATIONS_MAP_BUTTON),
            ],
        ];

        foreach ($carriers as $carrier) {
            $allowDeliveryOptions  = "{$carrier}_" . WCMP_Settings::SETTING_CARRIER_DELIVERY_ENABLED;
            $allowPickupLocations  = "{$carrier}_" . WCMP_Settings::SETTING_CARRIER_PICKUP_ENABLED;
            $allowSignature        = "{$carrier}_" . WCMP_Settings::SETTING_CARRIER_SIGNATURE_ENABLED;
            $allowOnlyRecipient    = "{$carrier}_" . WCMP_Settings::SETTING_CARRIER_ONLY_RECIPIENT_ENABLED;
            $cutoffTime            = "{$carrier}_" . WCMP_Settings::SETTING_CARRIER_CUTOFF_TIME;
            $deliveryDaysWindow    = "{$carrier}_" . WCMP_Settings::SETTING_CARRIER_DELIVERY_DAYS_WINDOW;
            $dropOffDays           = "{$carrier}_" . WCMP_Settings::SETTING_CARRIER_DROP_OFF_DAYS;
            $dropOffDelay          = "{$carrier}_" . WCMP_Settings::SETTING_CARRIER_DROP_OFF_DELAY;
            $pricePickup           = "{$carrier}_" . WCMP_Settings::SETTING_CARRIER_PICKUP_FEE;
            $priceSignature        = "{$carrier}_" . WCMP_Settings::SETTING_CARRIER_SIGNATURE_FEE;
            $priceOnlyRecipient    = "{$carrier}_" . WCMP_Settings::SETTING_CARRIER_ONLY_RECIPIENT_FEE;
            $priceSaturdayDelivery = "{$carrier}_" . WCMP_Settings::SETTING_CARRIER_SATURDAY_DELIVERY_FEE;
            $largeFormat           = "{$carrier}_" . WCMP_Settings::SETTING_CARRIER_DEFAULT_EXPORT_LARGE_FORMAT;

            $myParcelConfig["config"]["carrierSettings"][$carrier] = [
                "allowDeliveryOptions" => $settings->isEnabled($allowDeliveryOptions),
                "allowPickupLocations" => $settings->isEnabled($allowPickupLocations),
                "largeFormat"          => $settings->isEnabled($largeFormat),
                "allowSignature"       => $settings->getBooleanByName($allowSignature),
                "allowOnlyRecipient"   => $settings->getBooleanByName($allowOnlyRecipient),
                "cutoffTime"           => $settings->getStringByName($cutoffTime),
                "deliveryDaysWindow"   => $settings->getIntegerByName($deliveryDaysWindow),
                "dropOffDays"          => $settings->getByName($dropOffDays),
                "dropOffDelay"         => $settings->getIntegerByName($dropOffDelay),

                "pricePickup"           => $settings->getFloatByName($pricePickup),
                "priceSignature"        => $settings->getFloatByName($priceSignature),
                "priceOnlyRecipient"    => $settings->getFloatByName($priceOnlyRecipient),
                "priceSaturdayDelivery" => $settings->getFloatByName($priceSaturdayDelivery),
            ];
        }

        return json_encode($myParcelConfig, JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param string $title
     *
     * @return string
     */
    public function getDeliveryOptionsTitle(string $title): string
    {
        $settings = WCMP()->setting_collection;

        return __(strip_tags($settings->getStringByName($title)), "woocommerce-myparcelbe");
    }

    /**
     * Output the delivery options template.
     */
    public function output_delivery_options()
    {
        do_action('woocommerce_myparcelbe_before_delivery_options');
        require_once(WCMP()->includes . '/views/html-delivery-options-template.php');
        do_action('woocommerce_myparcelbe_after_delivery_options');
    }

    /**
     * Get the array of enabled carriers by checking if they have either delivery or pickup enabled.
     *
     * @return array
     */
    private function get_carriers(): array
    {
        $settings = WCMP()->setting_collection;
        $carriers = [];

        foreach (
            [
                BpostConsignment::CARRIER_NAME,
                DPDConsignment::CARRIER_NAME,
                PostNLConsignment::CARRIER_NAME
            ] as $carrier
        ) {
            if ($settings->getByName("{$carrier}_" . WCMP_Settings::SETTING_CARRIER_PICKUP_ENABLED)
                || $settings->getByName(
                    "{$carrier}_" . WCMP_Settings::SETTING_CARRIER_DELIVERY_ENABLED
                )) {
                $carriers[] = $carrier;
            }
        }

        return $carriers;
    }

    /**
     * Save delivery options to order when used
     *
     * @param int $order_id
     *
     * @return void
     * @throws Exception
     */
    public static function save_delivery_options($order_id)
    {
        $order = WCX::get_order($order_id);

        $highestShippingClass = Arr::get($_POST, "myparcelbe_highest_shipping_class");
        $shippingMethod       = Arr::get($_POST, "shipping_method");

        /**
         * Save the current version of our plugin to the order.
         */
        WCX_Order::update_meta_data(
            $order,
            WCMP_Admin::META_ORDER_VERSION,
            WCMP()->version
        );

        /**
         * Save the order weight here because it's easier than digging through order data after creating it.
         *
         * @see https://businessbloomer.com/woocommerce-save-display-order-total-weight/
         */
        WCX_Order::update_meta_data(
            $order,
            WCMP_Admin::META_ORDER_WEIGHT,
            WC()->cart->get_cart_contents_weight()
        );

        if ($highestShippingClass) {
            WCX_Order::update_meta_data(
                $order,
                WCMP_Admin::META_HIGHEST_SHIPPING_CLASS,
                $highestShippingClass
            );
        } elseif ($shippingMethod) {
            WCX_Order::update_meta_data(
                $order,
                WCMP_Admin::META_HIGHEST_SHIPPING_CLASS,
                $shippingMethod[0]
            );
        }

        $deliveryOptions = stripslashes(Arr::get($_POST, WCMP_Admin::META_DELIVERY_OPTIONS));

        if ($deliveryOptions) {

            $deliveryOptions = json_decode($deliveryOptions, true);
            /*
             * Create a new DeliveryOptions class from the data.
             */
            $deliveryOptions = DeliveryOptionsAdapterFactory::create($deliveryOptions);

            /*
             * Store it in the meta data. It will be serialized so class references will be kept.
             */
            WCX_Order::update_meta_data(
                $order,
                WCMP_Admin::META_DELIVERY_OPTIONS,
                $deliveryOptions
            );
        }
    }

    /**
     * @return array
     */
    private function getShippingMethodsForDeliveryOptions(): array
    {
        $allowed = [];

        $shippingClass = WCMP_Frontend::get_cart_shipping_class();
        $packageTypes  = WCMP()->setting_collection->getByName(WCMP_Settings::SETTING_SHIPPING_METHODS_PACKAGE_TYPES);
        $displayFor    = WCMP()->setting_collection->getByName(WCMP_Settings::SETTING_DELIVERY_OPTIONS_DISPLAY);

        if ($displayFor === WCMP_Settings_Data::DISPLAY_FOR_SELECTED_METHODS) {
            /**
             *
             */
            foreach ($packageTypes as $packageType => $shippingMethods) {
                /**
                 *
                 */
                foreach ($shippingMethods as $shippingMethod) {
                    if ($shippingClass) {
                        $shippingMethodAndClass = "$shippingMethod:$shippingClass";

                        if (in_array($shippingMethodAndClass, $shippingMethods)) {
                            $allowed[] = $shippingMethodAndClass;
                        }
                    } elseif (in_array($shippingMethod, $shippingMethods)) {
                        $allowed[] = $shippingMethod;
                    }
                }
            }
        }

        return $allowed;
    }

    /**
     * @return bool
     */
    private function alwaysDisplayDeliveryOptions(): bool
    {
        $display = WCMP()->setting_collection->getByName(WCMP_Settings::SETTING_DELIVERY_OPTIONS_DISPLAY);

        return $display === WCMP_Settings_Data::DISPLAY_FOR_ALL_METHODS;
    }
}

return new WCMP_Checkout();
