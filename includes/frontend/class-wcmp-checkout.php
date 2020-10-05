<?php

use MyParcelNL\Sdk\src\Factory\DeliveryOptionsAdapterFactory;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\DPDConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\PostNLConsignment;
use MyParcelNL\Sdk\src\Support\Arr;
use WPO\WC\MyParcel\Compatibility\Order as WCX_Order;
use WPO\WC\MyParcel\Compatibility\WC_Core as WCX;

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
        $useSplitAddressFields = WCMYPA()->setting_collection->isEnabled(WCMYPA_Settings::SETTING_USE_SPLIT_ADDRESS_FIELDS);
        if ($useSplitAddressFields) {
            wp_enqueue_script(
                "wcmp-checkout-fields",
                WCMYPA()->plugin_url() . "/assets/js/wcmp-checkout-fields.js",
                ["wc-checkout"],
                WC_MYPARCEL_NL_VERSION,
                true
            );
        }

        // Don"t load the delivery options scripts if it"s disabled
        if (! WCMYPA()->setting_collection->isEnabled(WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_ENABLED)) {
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


        /*
         * Show delivery options also for shipments on backorder
         */
        $shouldShow = $this->shouldShowDeliveryOptions();

        if (! $shouldShow) {
            return;
        }

        wp_enqueue_script(
            "wc-myparcel",
            WCMYPA()->plugin_url() . "/assets/js/myparcel.js",
            $deps,
            WC_MYPARCEL_NL_VERSION,
            true
        );

        wp_enqueue_script(
            "wc-myparcel-frontend",
            WCMYPA()->plugin_url() . "/assets/js/wcmp-frontend.js",
            array_merge($deps, ["wc-myparcel", "jquery"]),
            WC_MYPARCEL_NL_VERSION,
            true
        );

        $this->inject_delivery_options_variables();
    }

    /**
     * Localize variables into the delivery options scripts.
     *
     * @throws Exception
     */
    public function inject_delivery_options_variables()
    {
        wp_localize_script(
            'wc-myparcel-frontend',
            'wcmp',
            [
                "ajax_url" => admin_url("admin-ajax.php"),
            ]
        );

        wp_localize_script(
            "wc-myparcel-frontend",
            "MyParcelDisplaySettings",
            [
                // Convert true/false to int for JavaScript
                "isUsingSplitAddressFields" => (int) WCMYPA()->setting_collection->isEnabled(
                    WCMYPA_Settings::SETTING_USE_SPLIT_ADDRESS_FIELDS
                ),
            ]
        );

        wp_localize_script(
            "wc-myparcel",
            "MyParcelDeliveryOptions",
            [
                "allowedShippingMethods"    => json_encode($this->getShippingMethodsAllowingDeliveryOptions()),
                "disallowedShippingMethods" => json_encode(WCMP_Export::DISALLOWED_SHIPPING_METHODS),
                "alwaysShow"                => $this->alwaysDisplayDeliveryOptions(),
                "hiddenInputName"           => WCMYPA_Admin::META_DELIVERY_OPTIONS,
            ]
        );

        wp_localize_script(
            'wc-myparcel',
            'MyParcelConfig',
            $this->get_delivery_options_config()
        );

        // Load the checkout template.
        add_action(
            apply_filters(
                'wc_wcmp_delivery_options_location',
                WCMYPA()->setting_collection->getByName(WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_POSITION)
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
        $packageTypes = WCMYPA()->setting_collection->getByName(WCMYPA_Settings::SETTING_SHIPPING_METHODS_PACKAGE_TYPES);

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
        $settings = WCMYPA()->setting_collection;

        $carriers = $this->get_carriers();

        $myParcelConfig = [
            "config"  => [
                "carriers" => $carriers,
                "platform" => "myparcel",
                "locale"   => "nl-NL",
                "currency" => get_woocommerce_currency(),
            ],
            "strings" => [
                "addressNotFound"       => __("Address details are not entered", "woocommerce-myparcel"),
                "city"                  => __("City", "woocommerce-myparcel"),
                "closed"                => __("Closed", "woocommerce-myparcel"),
                "deliveryStandardTitle" => $this->getDeliveryOptionsTitle(WCMYPA_Settings::SETTING_STANDARD_TITLE),
                "deliveryTitle"         => $this->getDeliveryOptionsTitle(WCMYPA_Settings::SETTING_DELIVERY_TITLE),
                "deliveryMorningTitle"  => $this->getDeliveryOptionsTitle(WCMYPA_Settings::SETTING_MORNING_DELIVERY_TITLE),
                "deliveryEveningTitle"  => $this->getDeliveryOptionsTitle(WCMYPA_Settings::SETTING_EVENING_DELIVERY_TITLE),
                "headerDeliveryOptions" => $this->getDeliveryOptionsTitle(WCMYPA_Settings::SETTING_HEADER_DELIVERY_OPTIONS_TITLE),
                "houseNumber"           => __("House number", "woocommerce-myparcel"),
                "openingHours"          => __("Opening hours", "woocommerce-myparcel"),
                "pickUpFrom"            => __("Pick up from", "woocommerce-myparcel"),
                "pickupTitle"           => $this->getDeliveryOptionsTitle(WCMYPA_Settings::SETTING_PICKUP_TITLE),
                "postcode"              => __("Postcode", "woocommerce-myparcel"),
                "retry"                 => __("Retry", "woocommerce-myparcel"),
                "wrongHouseNumberCity"  => __("Postcode/city combination unknown", "woocommerce-myparcel"),
                "signatureTitle"        => $this->getDeliveryOptionsTitle(WCMYPA_Settings::SETTING_SIGNATURE_TITLE),
                "onlyRecipientTitle"    => $this->getDeliveryOptionsTitle(WCMYPA_Settings::SETTING_ONLY_RECIPIENT_TITLE),
            ],
        ];

        foreach ($carriers as $carrier) {
            $allowMorningDeliveryOptions = "{$carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DELIVERY_MORNING_ENABLED;
            $allowDeliveryOptions        = "{$carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DELIVERY_ENABLED;
            $allowEveningDeliveryOptions = "{$carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DELIVERY_EVENING_ENABLED;
            $allowPickupLocations        = "{$carrier}_" . WCMYPA_Settings::SETTING_CARRIER_PICKUP_ENABLED;
            $allowSignature              = "{$carrier}_" . WCMYPA_Settings::SETTING_CARRIER_SIGNATURE_ENABLED;
            $allowOnlyRecipient          = "{$carrier}_" . WCMYPA_Settings::SETTING_CARRIER_ONLY_RECIPIENT_ENABLED;
            $allowMondayDelivery         = "{$carrier}_" . WCMYPA_Settings::SETTING_CARRIER_MONDAY_DELIVERY_ENABLED;
            $cutoffTime                  = "{$carrier}_" . WCMYPA_Settings::SETTING_CARRIER_CUTOFF_TIME;
            $deliveryDaysWindow          = "{$carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DELIVERY_DAYS_WINDOW;
            $dropOffDays                 = "{$carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DROP_OFF_DAYS;
            $dropOffDelay                = "{$carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DROP_OFF_DELAY;
            $pricePickup                 = "{$carrier}_" . WCMYPA_Settings::SETTING_CARRIER_PICKUP_FEE;
            $priceSignature              = "{$carrier}_" . WCMYPA_Settings::SETTING_CARRIER_SIGNATURE_FEE;
            $priceOnlyRecipient          = "{$carrier}_" . WCMYPA_Settings::SETTING_CARRIER_ONLY_RECIPIENT_FEE;
            $priceEveningDelivery        = "{$carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DELIVERY_EVENING_FEE;
            $priceMorningDelivery        = "{$carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DELIVERY_MORNING_FEE;
            $priceSaturdayDelivery       = "{$carrier}_" . WCMYPA_Settings::SETTING_CARRIER_SATURDAY_DELIVERY_FEE;

            $myParcelConfig["config"] = [
                "cutoffTime"         => $settings->getStringByName($cutoffTime),
                "deliveryDaysWindow" => $settings->getIntegerByName($deliveryDaysWindow),
                "dropOffDays"        => $settings->getByName($dropOffDays),
                "dropOffDelay"       => $settings->getIntegerByName($dropOffDelay)
            ];

            $myParcelConfig["config"]["carrierSettings"][$carrier] = [
                "allowDeliveryOptions" => $settings->isEnabled($allowDeliveryOptions),
                "allowEveningDelivery" => $settings->isEnabled($allowEveningDeliveryOptions),
                "allowMorningDelivery" => $settings->isEnabled($allowMorningDeliveryOptions),
                "allowPickupLocations" => $settings->isEnabled($allowPickupLocations),
                "allowSignature"       => $settings->isEnabled($allowSignature),
                "allowOnlyRecipient"   => $settings->isEnabled($allowOnlyRecipient),
                "allowMondayDelivery"  => $settings->isEnabled($allowMondayDelivery),

                "pricePickup"           => $settings->getFloatByName($pricePickup),
                "priceSignature"        => $settings->getFloatByName($priceSignature),
                "priceOnlyRecipient"    => $settings->getFloatByName($priceOnlyRecipient),
                "priceEveningDelivery"  => $settings->getFloatByName($priceEveningDelivery),
                "priceMorningDelivery"  => $settings->getFloatByName($priceMorningDelivery),
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
        $settings = WCMYPA()->setting_collection;

        return __(strip_tags($settings->getStringByName($title)), "woocommerce-myparcel");
    }

    /**
     * Output the delivery options template.
     */
    public function output_delivery_options()
    {
        do_action('woocommerce_myparcel_before_delivery_options');
        require_once(WCMYPA()->includes . '/views/html-delivery-options-template.php');
        do_action('woocommerce_myparcel_after_delivery_options');
    }

    /**
     * Get the array of enabled carriers by checking if they have either delivery or pickup enabled.
     *
     * @return array
     */
    private function get_carriers(): array
    {
        $settings = WCMYPA()->setting_collection;
        $carriers = [];

        foreach ([PostNLConsignment::CARRIER_NAME, DPDConsignment::CARRIER_NAME] as $carrier) {
            if ($settings->getByName("{$carrier}_" . WCMYPA_Settings::SETTING_CARRIER_PICKUP_ENABLED)
                || $settings->getByName(
                    "{$carrier}_" . WCMYPA_Settings::SETTING_CARRIER_DELIVERY_ENABLED
                )) {
                $carriers[] = $carrier;
            }
        }

        return $carriers;
    }

    /**
     * Save delivery options to order when used
     *
     * @param int   $order_id
     * @param array $posted
     *
     * @return void
     * @throws Exception
     */
    public static function save_delivery_options($order_id)
    {
        $order = WCX::get_order($order_id);

        $highestShippingClass = Arr::get($_POST, "myparcel_highest_shipping_class");
        $shippingMethod       = Arr::get($_POST, "shipping_method");

        /**
         * Save the current version of our plugin to the order.
         */
        WCX_Order::update_meta_data(
            $order,
            WCMYPA_Admin::META_ORDER_VERSION,
            WCMYPA()->version
        );

        /**
         * Save the order weight here because it's easier than digging through order data after creating it.
         *
         * @see https://businessbloomer.com/woocommerce-save-display-order-total-weight/
         */
        WCX_Order::update_meta_data(
            $order,
            WCMYPA_Admin::META_ORDER_WEIGHT,
            WC()->cart->get_cart_contents_weight()
        );

        WCX_Order::update_meta_data(
            $order,
            WCMYPA_Admin::META_SHIPMENT_OPTIONS_EXTRA,
            [
                'collo_amount' => 1,
                'weight'       => WC()->cart->get_cart_contents_weight(),
            ]
        );

        if ($highestShippingClass) {
            WCX_Order::update_meta_data(
                $order,
                WCMYPA_Admin::META_HIGHEST_SHIPPING_CLASS,
                $highestShippingClass
            );
        } elseif ($shippingMethod) {
            WCX_Order::update_meta_data(
                $order,
                WCMYPA_Admin::META_HIGHEST_SHIPPING_CLASS,
                $shippingMethod[0]
            );
        }

        $deliveryOptions = stripslashes(Arr::get($_POST, WCMYPA_Admin::META_DELIVERY_OPTIONS));

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
                WCMYPA_Admin::META_DELIVERY_OPTIONS,
                $deliveryOptions
            );
        }
    }

    /**
     * Return the names of shipping methods that will show delivery options. If DISPLAY_FOR_ALL_METHODS is enabled it'll
     * return an empty array and the frontend will allow any shipping except any that are specifically disallowed.
     *
     * @return string[]
     * @throws Exception
     * @see WCMP_Export::DISALLOWED_SHIPPING_METHODS
     */
    private function getShippingMethodsAllowingDeliveryOptions(): array
    {
        $allowedMethods = [];
        $displayFor     = WCMYPA()->setting_collection->getByName(WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_DISPLAY);

        if ($displayFor === WCMP_Settings_Data::DISPLAY_FOR_ALL_METHODS) {
            return $allowedMethods;
        }

        $shippingMethodsByPackageType = WCMYPA()->setting_collection->getByName(WCMYPA_Settings::SETTING_SHIPPING_METHODS_PACKAGE_TYPES);
        $shippingMethodsForPackage    = $shippingMethodsByPackageType[AbstractConsignment::PACKAGE_TYPE_PACKAGE_NAME];

        foreach ($shippingMethodsForPackage as $shippingMethod) {
            [$methodId] = self::splitShippingMethodString($shippingMethod);

            if (!in_array($methodId, WCMP_Export::DISALLOWED_SHIPPING_METHODS)) {
                $allowedMethods[] = $shippingMethod;
            }
        }

        return $allowedMethods;
    }

    /**
     * @return bool
     */
    private function alwaysDisplayDeliveryOptions(): bool
    {
        $display = WCMYPA()->setting_collection->getByName(WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_DISPLAY);

        return $display === WCMP_Settings_Data::DISPLAY_FOR_ALL_METHODS;
    }

    /**
     * Split a <rateId>:<instanceId> string into an array. If there is no instanceId, the second array element will be
     * null.
     *
     * @param $shippingMethod
     *
     * @return array
     */
    public static function splitShippingMethodString(string $shippingMethod): array
    {
        $split = explode(':', $shippingMethod, 2);

        if (count($split) === 1) {
            $split[] = null;
        }

        return $split;
    }

    /**
     * Show delivery options also for shipments on backorder
     * @return bool
     */
    private function shouldShowDeliveryOptions(): bool
    {
        $showAnyway = WCMYPA()->setting_collection->isEnabled(WCMYPA_Settings::SETTING_BACKORDERS);
        $show       = true;

        if ($showAnyway) {
            return $show;
        }

        foreach (WC()->cart->get_cart() as $cartItem) {
            /**
             * @var WC_Product $product
             */
            $product       = $cartItem['data'];
            $isOnBackorder = $product->is_on_backorder($cartItem['quantity']);

            if ($isOnBackorder) {
                $show = false;
                break;
            }
        }

        return $show;
    }
}

return new WCMP_Checkout();
