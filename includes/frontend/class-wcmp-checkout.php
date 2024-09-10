<?php

declare(strict_types=1);

use MyParcelNL\Sdk\src\Model\Carrier\AbstractCarrier;
use MyParcelNL\Sdk\src\Model\Carrier\CarrierDHLForYou;
use MyParcelNL\Sdk\src\Model\Carrier\CarrierInstabox;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\Sdk\src\Model\MyParcelRequest;
use MyParcelNL\Sdk\src\Support\Arr;
use MyParcelNL\Sdk\src\Support\Collection;
use MyParcelNL\Sdk\src\Support\Str;
use MyParcelNL\WooCommerce\includes\Settings\Api\AccountSettings;
use WPO\WC\MyParcel\Compatibility\Order as WCX_Order;
use WPO\WC\MyParcel\Compatibility\WC_Core as WCX;

defined('ABSPATH') or die();

if (class_exists('WCMP_Checkout')) {
    return new WCMP_Checkout();
}

/**
 * Frontend views
 */
class WCMP_Checkout
{
    private const DELIVERY_OPTIONS_KEY_MAP = [
        'deliveryType'                   => 'delivery_type',
        'isPickup'                       => 'is_pickup',
        'labelDescription'               => 'label_description',
        'pickupLocation'                 => 'pickup_location',
        'packageType'                    => 'package_type',
        'shipmentOptions'                => 'shipment_options',
        'shipmentOptions.ageCheck'       => 'shipment_options.age_check',
        'shipmentOptions.insuredAmount'  => 'shipment_options.insured_amount',
        'shipmentOptions.largeFormat'    => 'shipment_options.large_format',
        'shipmentOptions.onlyRecipient'  => 'shipment_options.only_recipient',
        'shipmentOptions.returnShipment' => 'shipment_options.return',
    ];

    public function __construct()
    {
        add_action('wp', [$this, 'enqueue_frontend_scripts'], 100);

        // Save delivery options data
        add_action('woocommerce_checkout_update_order_meta', [$this, 'save_delivery_options'], 10, 2);

        add_action('wp_ajax_wcmp_get_delivery_options_config', [$this, 'getDeliveryOptionsConfigAjax']);
    }

    /**
     * @param  string $title
     *
     * @return string
     */
    public static function getDeliveryOptionsTitle(string $title): string
    {
        $settings = WCMYPA()->setting_collection;

        return __(strip_tags($settings->getStringByName($title)), 'woocommerce-myparcel');
    }

    /**
     * @return string
     */
    public static function getPickupLocationsDefaultView(): string
    {
        $settings = WCMYPA()->setting_collection;

        return $settings->getStringByName(WCMYPA_Settings::SETTING_PICKUP_LOCATIONS_DEFAULT_VIEW);
    }

    /**
     * Save delivery options to order when used
     *
     * @param  int $orderId
     *
     * @return void
     * @throws \Exception
     */
    public static function save_delivery_options(int $orderId): void
    {
        $order                = WCX::get_order($orderId);
        $shippingMethod       = sanitize_text_field(
            wp_unslash(
                $_POST['shipping_method'][0]
                ?? WC()->session->get('chosen_shipping_methods')[0]
                ?? ''
            )
        );
        $highestShippingClass = sanitize_text_field(
            wp_unslash(filter_input(INPUT_POST, 'myparcel_highest_shipping_class') ?? $shippingMethod)
        );

        /**
         * Save the current version of our plugin to the order.
         */
        WCX_Order::update_meta_data(
            $order,
            WCMYPA_Admin::META_ORDER_VERSION,
            WCMYPA()->version
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
        }

        $deliveryOptionsFromPost          = filter_input(INPUT_POST, WCMYPA_Admin::META_DELIVERY_OPTIONS);
        $deliveryOptionsFromShippingClass = $highestShippingClass
            ? [
                'packageType' => WCMP_Export::getPackageTypeFromShippingMethod(
                    $shippingMethod,
                    $highestShippingClass
                ),
            ]
            : null;

        $deliveryOptions = empty($deliveryOptionsFromPost)
            ? $deliveryOptionsFromShippingClass
            : stripslashes($deliveryOptionsFromPost);

        if ($deliveryOptions) {
            if (! is_array($deliveryOptions)) {
                $deliveryOptions = json_decode($deliveryOptions, true);
            }
            $deliveryOptions = self::convertDeliveryOptionsForAdapter($deliveryOptions);
            $deliveryOptions = WCMYPA_Admin::removeDisallowedDeliveryOptions(
                $deliveryOptions,
                $order->get_shipping_country()
            );

            /*
             * Create a new DeliveryOptions class from the data.
             */
            $deliveryOptions = new WCMP_DeliveryOptionsFromOrderAdapter(null, $deliveryOptions);
            $deliveryOptions = apply_filters('wc_myparcel_order_delivery_options', $deliveryOptions, $order);

            /*
             * Store it in the meta data.
             */
            WCX_Order::update_meta_data(
                $order,
                WCMYPA_Admin::META_DELIVERY_OPTIONS,
                $deliveryOptions->toArray()
            );

            /**
             * Save delivery date in meta for use in order grid filter.
             */
            $deliveryDate = $deliveryOptions->getDate();
            WCX_Order::update_meta_data(
                $order,
                WCMYPA_Admin::META_DELIVERY_DATE,
                $deliveryDate ? wc_format_datetime(new WC_DateTime($deliveryOptions->getDate()), 'Y-m-d') : null
            );
        }
    }

    /**
     * Split a <rateId>:<instanceId> string into an array. If there is no instanceId, the second array element will be
     * null.
     *
     * @param  string $shippingMethod
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
     * Map keys from the delivery options to the keys used in the adapters.
     *
     * @param  array $deliveryOptions
     *
     * @return array
     */
    private static function convertDeliveryOptionsForAdapter(array $deliveryOptions): array
    {
        foreach (self::DELIVERY_OPTIONS_KEY_MAP as $camel => $snake) {
            $value = Arr::get($deliveryOptions, $camel);
            if (isset($value)) {
                Arr::set($deliveryOptions, $snake, $value);
                Arr::forget($deliveryOptions, $camel);
            }
        }

        return $deliveryOptions;
    }

    /**
     * Load styles & scripts on the checkout page.
     *
     * @throws \Exception
     */
    public function enqueue_frontend_scripts(): void
    {
        // The order received page has the same page id as the checkout so `is_checkout()` returns true on both...
        if (! is_checkout() || is_order_received_page() || is_checkout_pay_page()) {
            return;
        }

        // if using split address fields
        $useSplitAddressFields = WCMYPA()->setting_collection->isEnabled(
            WCMYPA_Settings::SETTING_USE_SPLIT_ADDRESS_FIELDS
        );
        if ($useSplitAddressFields) {
            wp_enqueue_script(
                'wcmp-checkout-fields',
                WCMYPA()->plugin_url() . '/assets/js/wcmp-checkout-fields.js',
                ['wc-checkout'],
                WC_MYPARCEL_NL_VERSION,
                true
            );
        }

        // Don't load the delivery options scripts if it's disabled
        if (! WCMYPA()->setting_collection->isEnabled(WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_ENABLED)) {
            return;
        }

        /**
         * JS dependencies array
         */
        $deps = ['wc-checkout'];

        /**
         * If split address fields are enabled add the checkout fields script as an additional dependency.
         */
        if ($useSplitAddressFields) {
            $deps[] = 'wcmp-checkout-fields';
        }

        if (! $this->shouldShowDeliveryOptions()) {
            return;
        }

        wp_enqueue_script(
            'wc-myparcel',
            WCMYPA()->plugin_url() . '/assets/js/myparcel.js',
            $deps,
            WC_MYPARCEL_NL_VERSION,
            true
        );

        wp_enqueue_script(
            'wc-myparcel-frontend',
            WCMYPA()->plugin_url() . '/assets/js/wcmp-frontend.js',
            array_merge($deps, ['wc-myparcel', 'jquery']),
            WC_MYPARCEL_NL_VERSION,
            true
        );

        $this->inject_delivery_options_variables();
    }

    /**
     * Get the delivery options config in JSON for passing to JavaScript.
     *
     * @param  null|string $country
     *
     * @return array
     * @throws \Exception
     */
    public function getDeliveryOptionsConfig(?string $country = null): array
    {
        $country = $country ?? WC()->customer->get_shipping_country();

        $cartTotals                = WC()->session->get('cart_totals');
        $chosenShippingMethodPrice = (float) $cartTotals['shipping_total'];
        $displayIncludingTax       = WC()->cart->display_prices_including_tax();

        $priceFormat    = self::getDeliveryOptionsTitle(
            WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_PRICE_FORMAT
        );
        $shippingMethod = WC()->session->get('chosen_shipping_methods')[0] ?? false;
        $shippingClass  = WCMP_Frontend::get_cart_shipping_class();
        $packageType    = ($shippingMethod)
            ? WCMP_Export::getPackageTypeFromShippingMethod($shippingMethod, $shippingClass)
            : null;

        if ($displayIncludingTax) {
            $chosenShippingMethodPrice += (float) $cartTotals['shipping_tax'];
        }

        return [
            'config'  => [
                'apiBaseUrl'                 => getenv('MYPARCEL_API_BASE_URL', true) ?: MyParcelRequest::REQUEST_URL,
                'currency'                   => get_woocommerce_currency(),
                'packageType'                => $packageType,
                'locale'                     => 'nl-NL',
                'platform'                   => 'myparcel',
                'basePrice'                  => $chosenShippingMethodPrice,
                'showPriceSurcharge'         => WCMP_Settings_Data::DISPLAY_SURCHARGE_PRICE === $priceFormat,
                'pickupLocationsDefaultView' => self::getPickupLocationsDefaultView(),
                'allowRetry'                 => false,
                'priceStandardDelivery'      => $this->useTotalPrice() ? $chosenShippingMethodPrice : null,
                'carrierSettings'            => $this->createCarrierSettings($country, $chosenShippingMethodPrice),
            ],
            'strings' => [
                'addressNotFound'       => self::getDeliveryOptionsTitle(
                    WCMYPA_Settings::SETTING_ADDRESS_NOT_FOUND_TITLE
                ),
                'city'                  => __('City', 'woocommerce-myparcel'),
                'closed'                => __('Closed', 'woocommerce-myparcel'),
                'deliveryEveningTitle'  => self::getDeliveryOptionsTitle(
                    WCMYPA_Settings::SETTING_EVENING_DELIVERY_TITLE
                ),
                'deliveryMorningTitle'  => self::getDeliveryOptionsTitle(
                    WCMYPA_Settings::SETTING_MORNING_DELIVERY_TITLE
                ),
                'deliveryStandardTitle' => self::getDeliveryOptionsTitle(WCMYPA_Settings::SETTING_STANDARD_TITLE),
                'deliverySameDayTitle'  => self::getDeliveryOptionsTitle(WCMYPA_Settings::SETTING_SAME_DAY_TITLE),
                'deliveryTitle'         => self::getDeliveryOptionsTitle(WCMYPA_Settings::SETTING_DELIVERY_TITLE),
                'headerDeliveryOptions' => self::getDeliveryOptionsTitle(
                    WCMYPA_Settings::SETTING_HEADER_DELIVERY_OPTIONS_TITLE
                ),
                'houseNumber'           => __('House number', 'woocommerce-myparcel'),
                'onlyRecipientTitle'    => self::getDeliveryOptionsTitle(WCMYPA_Settings::SETTING_ONLY_RECIPIENT_TITLE),
                'openingHours'          => __('Opening hours', 'woocommerce-myparcel'),
                'pickUpFrom'            => __('Pick up from', 'woocommerce-myparcel'),
                'pickupTitle'           => self::getDeliveryOptionsTitle(WCMYPA_Settings::SETTING_PICKUP_TITLE),
                'postcode'              => __('Postcode', 'woocommerce-myparcel'),
                'retry'                 => __('Retry', 'woocommerce-myparcel'),
                'signatureTitle'        => self::getDeliveryOptionsTitle(WCMYPA_Settings::SETTING_SIGNATURE_TITLE),
                'wrongHouseNumberCity'  => __('Postcode/city combination unknown', 'woocommerce-myparcel'),
            ],
        ];
    }

    /**
     * Echoes the delivery options config as a JSON string for use with AJAX.
     *
     * @throws \Exception
     */
    public function getDeliveryOptionsConfigAjax(): void
    {
        $input = filter_input_array(INPUT_GET);

        echo json_encode($this->getDeliveryOptionsConfig($input['cc']), JSON_UNESCAPED_SLASHES);
        die();
    }

    /**
     * Localize variables into the delivery options scripts.
     *
     * @throws Exception
     */
    public function inject_delivery_options_variables(): void
    {
        wp_localize_script(
            'wc-myparcel-frontend',
            'wcmp',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
            ]
        );

        wp_localize_script(
            'wc-myparcel-frontend',
            'MyParcelDisplaySettings',
            [
                // Convert true/false to int for JavaScript
                'isUsingSplitAddressFields'   => (int) WCMYPA()->setting_collection->isEnabled(
                    WCMYPA_Settings::SETTING_USE_SPLIT_ADDRESS_FIELDS
                ),
                'splitAddressFieldsCountries' => WCMP_NL_Postcode_Fields::COUNTRIES_WITH_SPLIT_ADDRESS_FIELDS,
            ]
        );

        wp_localize_script(
            'wc-myparcel',
            'MyParcelDeliveryOptions',
            [
                'allowedShippingMethods'    => json_encode($this->getShippingMethodsAllowingDeliveryOptions()),
                'disallowedShippingMethods' => json_encode(WCMP_Export::DISALLOWED_SHIPPING_METHODS),
                'alwaysShow'                => $this->alwaysDisplayDeliveryOptions(),
                'hiddenInputName'           => WCMYPA_Admin::META_DELIVERY_OPTIONS,
            ]
        );

        wp_localize_script(
            'wc-myparcel',
            'MyParcelConfig',
            $this->getDeliveryOptionsConfig()
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
     * Output the delivery options template.
     */
    public function output_delivery_options(): void
    {
        do_action('woocommerce_myparcel_before_delivery_options');
        require_once(WCMYPA()->includes . '/views/html-delivery-options-template.php');
        do_action('woocommerce_myparcel_after_delivery_options');
    }

    /**
     * @return bool
     */
    public function useTotalPrice(): bool
    {
        $priceFormat = WCMYPA()->setting_collection->getByName(WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_PRICE_FORMAT);

        return ! isset($priceFormat) || WCMP_Settings_Data::DISPLAY_TOTAL_PRICE === $priceFormat;
    }

    /**
     * @param  array $dhlForYouSettings
     *
     * @return array
     * @throws \Exception
     */
    private function adjustDhlForYouDeliverySettings(array $dhlForYouSettings): array
    {
        $weekDay           = date('N', strtotime(date('Y-m-d')));
        $now               = new DateTime();
        $cutOffTime        = DateTime::createFromFormat('H:i', $dhlForYouSettings['cutoffTime']);
        $weekDay           = $now < $cutOffTime ? $weekDay : $weekDay + 1;
        $weekDay           = ($weekDay + $dhlForYouSettings['dropOffDelay']) % 7;
        $todayIsDropOffDay = in_array((string) $weekDay, $dhlForYouSettings['dropOffDays'], true);

        $dhlForYouSettings['allowDeliveryOptions'] = $todayIsDropOffDay;
        $dhlForYouSettings['allowSameDayDelivery'] = WCMYPA()->setting_collection->where(
            'carrier',
            CarrierDHLForYou::NAME
        )
            ->getByName(WCMYPA_Settings::SETTING_CARRIER_SAME_DAY_DELIVERY);

        return $dhlForYouSettings;
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
     * @param  string $country
     * @param  float  $chosenShippingMethodPrice
     *
     * @return array
     * @throws \Exception
     */
    private function createCarrierSettings(string $country, float $chosenShippingMethodPrice): array
    {
        $carrierSettings = [];
        $accountSettings = AccountSettings::getInstance();

        $settings      = WCMYPA()->setting_collection;
        $useTotalPrice = $this->useTotalPrice();

        /** @var AbstractCarrier $carrier */
        foreach ($accountSettings->getCarriersForCountry($country) as $carrier) {
            $carrierName       = $carrier->getName();
            $settingsByCarrier = $settings->where('carrier', $carrierName);

            foreach ($this->getDeliveryOptionsConfigMap() as $key => $setting) {
                [$settingName, $function, $addBasePrice] = $setting;

                $value = $settingsByCarrier->{$function}($settingName);

                if ($useTotalPrice && $addBasePrice && Str::endsWith($settingName, '_fee')) {
                    $value += $chosenShippingMethodPrice;
                }

                Arr::set($carrierSettings, "$carrierName.$key", $value);
            }

            if (CarrierDHLForYou::NAME === $carrierName && ($carrierSettings['dhlforyou']['allowDeliveryOptions'] ?? false)) {
                $carrierSettings['dhlforyou'] = $this->adjustDhlForYouDeliverySettings($carrierSettings['dhlforyou']);
            }
        }

        return $carrierSettings;
    }

    /**
     * @return array
     */
    private function getDeliveryOptionsConfigMap(): array
    {
        return [
            'allowDeliveryOptions'  => [WCMYPA_Settings::SETTING_CARRIER_DELIVERY_ENABLED, 'isEnabled', false],
            'allowEveningDelivery'  => [WCMYPA_Settings::SETTING_CARRIER_DELIVERY_EVENING_ENABLED, 'isEnabled', false],
            'allowMondayDelivery'   => [WCMYPA_Settings::SETTING_CARRIER_MONDAY_DELIVERY_ENABLED, 'isEnabled', false],
            'allowMorningDelivery'  => [WCMYPA_Settings::SETTING_CARRIER_DELIVERY_MORNING_ENABLED, 'isEnabled', false],
            'allowOnlyRecipient'    => [WCMYPA_Settings::SETTING_CARRIER_ONLY_RECIPIENT_ENABLED, 'isEnabled', false],
            'allowPickupLocations'  => [WCMYPA_Settings::SETTING_CARRIER_PICKUP_ENABLED, 'isEnabled', false],
            'allowSaturdayDelivery' => [WCMYPA_Settings::SETTING_CARRIER_SATURDAY_DELIVERY_ENABLED, 'isEnabled', false],
            'allowSignature'        => [WCMYPA_Settings::SETTING_CARRIER_SIGNATURE_ENABLED, 'isEnabled', false],
            'allowShowDeliveryDate' => [WCMYPA_Settings::SETTING_CARRIER_ALLOW_SHOW_DELIVERY_DATE, 'isEnabled', false],
            'priceEveningDelivery'  => [WCMYPA_Settings::SETTING_CARRIER_DELIVERY_EVENING_FEE, 'getPriceByName', true],
            'priceMondayDelivery'   => [WCMYPA_Settings::SETTING_CARRIER_MONDAY_DELIVERY_FEE, 'getPriceByName', true],
            'priceStandardDelivery' => [WCMYPA_Settings::SETTING_CARRIER_DELIVERY_STANDARD_FEE, 'getPriceByName', true],
            'priceMorningDelivery'  => [WCMYPA_Settings::SETTING_CARRIER_DELIVERY_MORNING_FEE, 'getPriceByName', true],
            'priceOnlyRecipient'    => [WCMYPA_Settings::SETTING_CARRIER_ONLY_RECIPIENT_FEE, 'getPriceByName', false],
            'pricePickup'           => [WCMYPA_Settings::SETTING_CARRIER_PICKUP_FEE, 'getPriceByName', true],
            'priceSaturdayDelivery' => [WCMYPA_Settings::SETTING_CARRIER_SATURDAY_DELIVERY_FEE, 'getPriceByName', true],
            'priceSignature'        => [WCMYPA_Settings::SETTING_CARRIER_SIGNATURE_FEE, 'getPriceByName', false],
            'cutoffTime'            => [WCMYPA_Settings::SETTING_CARRIER_CUTOFF_TIME, 'getStringByName', false],
            'deliveryDaysWindow'    => [
                WCMYPA_Settings::SETTING_CARRIER_DELIVERY_DAYS_WINDOW,
                'getIntegerByName',
                false,
            ],
            'dropOffDays'           => [WCMYPA_Settings::SETTING_CARRIER_DROP_OFF_DAYS, 'getByName', false],
            'dropOffDelay'          => [WCMYPA_Settings::SETTING_CARRIER_DROP_OFF_DELAY, 'getIntegerByName', false],
            'fridayCutoffTime'      => [WCMYPA_Settings::SETTING_CARRIER_FRIDAY_CUTOFF_TIME, 'getStringByName', false],
            'saturdayCutoffTime'    => [
                WCMYPA_Settings::SETTING_CARRIER_SATURDAY_CUTOFF_TIME,
                'getStringByName',
                false,
            ],
            'cutoffTimeSameDay'     => [
                WCMYPA_Settings::SETTING_CARRIER_SAME_DAY_DELIVERY_CUTOFF_TIME,
                'getStringByName',
                false,
            ],
            'priceSameDayDelivery'  => [WCMYPA_Settings::SETTING_CARRIER_SAME_DAY_DELIVERY_FEE, 'getPriceByName', true],
            'allowSameDayDelivery'  => [WCMYPA_Settings::SETTING_CARRIER_SAME_DAY_DELIVERY, 'isEnabled', false],
        ];
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
        $allowedMethods               = [];
        $displayFor                   = WCMYPA()->setting_collection->getByName(
            WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_DISPLAY
        );
        $shippingMethodsByPackageType = WCMYPA()->setting_collection->getByName(
            WCMYPA_Settings::SETTING_SHIPPING_METHODS_PACKAGE_TYPES
        );

        if (WCMP_Settings_Data::DISPLAY_FOR_ALL_METHODS === $displayFor || ! $shippingMethodsByPackageType) {
            return $allowedMethods;
        }

        $shippingMethodsForPackage = $shippingMethodsByPackageType[AbstractConsignment::PACKAGE_TYPE_PACKAGE_NAME];

        foreach ($shippingMethodsForPackage as $shippingMethod) {
            [$methodId] = self::splitShippingMethodString($shippingMethod);

            if (! in_array($methodId, WCMP_Export::DISALLOWED_SHIPPING_METHODS, true)) {
                $allowedMethods[] = $shippingMethod;
            }
        }

        return $allowedMethods;
    }

    /**
     * Returns true if any product in the loop is:
     *  - physical
     *  - not on backorder OR user allows products on backorder to have delivery options
     *
     * @return bool
     */
    private function shouldShowDeliveryOptions(): bool
    {
        $showForBackorders   = WCMYPA()->setting_collection->isEnabled(
            WCMYPA_Settings::SETTINGS_SHOW_DELIVERY_OPTIONS_FOR_BACKORDERS
        );
        $showDeliveryOptions = false;

        foreach (WC()->cart->get_cart() as $cartItem) {
            /**
             * @var WC_Product $product
             */
            $product = $cartItem['data'];

            if (! $product->is_virtual()) {
                $isOnBackOrder = $product->is_on_backorder($cartItem['quantity']);
                if (! $showForBackorders && $isOnBackOrder) {
                    $showDeliveryOptions = false;
                    break;
                }

                $showDeliveryOptions = true;
            }
        }

        return apply_filters('wc_myparcel_show_delivery_options', $showDeliveryOptions);
    }
}

return new WCMP_Checkout();
