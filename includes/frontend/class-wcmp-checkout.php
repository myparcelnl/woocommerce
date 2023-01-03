<?php

declare(strict_types=1);

use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\Sdk\src\Model\MyParcelRequest;
use MyParcelNL\Sdk\src\Support\Arr;
use MyParcelNL\Sdk\src\Support\Collection;
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
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts'], 100);

        // Save delivery options data
        add_action('woocommerce_checkout_update_order_meta', [$this, 'save_delivery_options'], 10, 2);

        add_action('wp_ajax_wcmp_get_delivery_options_config', [$this, 'getDeliveryOptionsConfigAjax']);
    }




    /**
     * Echoes the delivery options config as a JSON string for use with AJAX.
     *
     * @throws \Exception
     */
    public function getDeliveryOptionsConfigAjax(): void
    {
        echo json_encode($this->getDeliveryOptionsConfig(), JSON_UNESCAPED_SLASHES);
        die();
    }

    /**
     * @return bool
     */
    public function useTotalPrice(): bool
    {
        $priceFormat = WCMYPA()->settingCollection->getByName('delivery_options_price_format');

        return ! isset($priceFormat) || WCMP_Settings_Data::DISPLAY_TOTAL_PRICE === $priceFormat;
    }

    /**
     * @param string $title
     *
     * @return string
     */
    public static function getDeliveryOptionsTitle(string $title): string
    {
        $settings = WCMYPA()->settingCollection;

        return __(strip_tags($settings->getStringByName($title)), 'woocommerce-myparcel');
    }

    /**
     * @return string
     */
    public static function getPickupLocationsDefaultView(): string
    {
        return WCMYPA()->settingCollection->getStringByName('pickup_locations_default_view');
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
        $shippingMethod       = sanitize_text_field(wp_unslash($_POST['shipping_method'][0]
            ?? WC()->session->get('chosen_shipping_methods')[0]
            ?? ''));
        $highestShippingClass = sanitize_text_field(
            wp_unslash($_POST['myparcel_highest_shipping_class'] ?? $shippingMethod)
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

        $deliveryOptionsFromPost          = Arr::get($_POST, WCMYPA_Admin::META_DELIVERY_OPTIONS);
        $deliveryOptionsFromShippingClass = $highestShippingClass
            ? [
                'packageType' => ExportActions::getPackageTypeFromShippingMethod(
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
             * Store it in the metadata.
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
     * @return \MyParcelNL\Sdk\src\Model\Carrier\AbstractCarrier[]|\MyParcelNL\Sdk\src\Support\Collection
     */
    protected function getCarriersForDeliveryOptions(): Collection
    {
        return AccountSettings::getInstance()
            ->getEnabledCarriers();
    }

    /**
     * Return the names of shipping methods that will show delivery options. If DISPLAY_FOR_ALL_METHODS is enabled it'll
     * return an empty array and the frontend will allow any shipping except any that are specifically disallowed.
     *
     * @return string[]
     * @throws Exception
     * @see ExportActions::DISALLOWED_SHIPPING_METHODS
     */
    private function getShippingMethodsAllowingDeliveryOptions(): array
    {
        $allowedMethods               = [];
        $displayFor                   = WCMYPA()->settingCollection->getByName('delivery_options_display');
        $shippingMethodsByPackageType = WCMYPA()->settingCollection->getByName('shipping_methods_package_types');

        if (WCMP_Settings_Data::DISPLAY_FOR_ALL_METHODS === $displayFor || ! $shippingMethodsByPackageType) {
            return $allowedMethods;
        }

        $shippingMethodsForPackage = $shippingMethodsByPackageType[AbstractConsignment::PACKAGE_TYPE_PACKAGE_NAME];

        foreach ($shippingMethodsForPackage as $shippingMethod) {
            [$methodId] = self::splitShippingMethodString($shippingMethod);

            if (! in_array($methodId, ExportActions::DISALLOWED_SHIPPING_METHODS, true)) {
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
        $display = WCMYPA()->settingCollection->getByName('delivery_options_display');

        return $display === WCMP_Settings_Data::DISPLAY_FOR_ALL_METHODS;
    }

    /**
     * Map keys from the delivery options to the keys used in the adapters.
     *
     * @param array $deliveryOptions
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
     * @return array
     */
    private function getDeliveryOptionsConfigMap(): array
    {
        return [
            'allowDeliveryOptions'  => ['delivery_enabled', 'isEnabled', false],
            'allowEveningDelivery'  => ['delivery_evening_enabled', 'isEnabled', false],
            'allowMondayDelivery'   => ['monday_delivery_enabled', 'isEnabled', false],
            'allowMorningDelivery'  => ['delivery_morning_enabled', 'isEnabled', false],
            'allowOnlyRecipient'    => ['only_recipient_enabled', 'isEnabled', false],
            'allowPickupLocations'  => ['pickup_enabled', 'isEnabled', false],
            'allowSaturdayDelivery' => ['saturday_delivery_enabled', 'isEnabled', false],
            'allowSignature'        => ['signature_enabled', 'isEnabled', false],
            'allowShowDeliveryDate' => ['allow_show_delivery_date', 'isEnabled', false],
            'priceEveningDelivery'  => ['delivery_evening_fee', 'getPriceByName', true],
            'priceMondayDelivery'   => ['monday_delivery_fee', 'getPriceByName', true],
            'priceStandardDelivery' => ['delivery_standard_fee', 'getPriceByName', true],
            'priceMorningDelivery'  => ['delivery_morning_fee', 'getPriceByName', true],
            'priceOnlyRecipient'    => ['only_recipient_fee', 'getPriceByName', false],
            'pricePickup'           => ['pickup_fee', 'getPriceByName', true],
            'priceSaturdayDelivery' => ['saturday_delivery_fee', 'getPriceByName', true],
            'priceSignature'        => ['signature_fee', 'getPriceByName', false],
            'cutoffTime'            => ['cutoff_time', 'getStringByName', false],
            'deliveryDaysWindow'    => ['delivery_days_window', 'getIntegerByName', false],
            'dropOffDays'           => ['drop_off_days', 'getByName', false],
            'dropOffDelay'          => ['drop_off_delay', 'getIntegerByName', false],
            'fridayCutoffTime'      => ['friday_cutoff_time', 'getStringByName', false],
            'saturdayCutoffTime'    => ['saturday_cutoff_time', 'getStringByName', false],
            'cutoffTimeSameDay'     => ['same_day_delivery_cutoff_time', 'getStringByName', false],
            'priceSameDayDelivery'  => ['same_day_delivery_fee', 'getPriceByName', true],
            'allowSameDayDelivery'  => ['same_day_delivery', 'isEnabled', false],
        ];
    }

}

return new WCMP_Checkout();
