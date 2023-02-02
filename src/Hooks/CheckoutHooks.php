<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Base\Service\CountryService;
use MyParcelNL\Pdk\Facade\Actions;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Plugin\Api\PdkActions;
use MyParcelNL\Pdk\Plugin\Model\Context\DeliveryOptionsContext;
use MyParcelNL\Pdk\Plugin\Model\PdkOrder;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;
use MyParcelNL\Pdk\Settings\Model\GeneralSettings;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\WooCommerce\Service\ScriptService;
use WC_Product;

class CheckoutHooks implements WordPressHooksInterface
{
    public const  META_DELIVERY_OPTIONS            = '_myparcel_delivery_options';
    public const  META_HIGHEST_SHIPPING_CLASS      = '_myparcel_highest_shipping_class';
    private const DISALLOWED_SHIPPING_METHODS      = [
        'local_pickup',
    ];
    private const SCRIPT_SPLIT_ADDRESS_FIELDS      = 'myparcelnl-checkout-split-address-fields';
    private const SCRIPT_CHECKOUT_DELIVERY_OPTIONS = 'myparcelnl-checkout-delivery-options';

    /**
     * @var \MyParcelNL\WooCommerce\Service\ScriptService
     */
    private $service;

    /**
     * @param  \MyParcelNL\WooCommerce\Service\ScriptService $service
     */
    public function __construct(ScriptService $service)
    {
        $this->service = $service;
    }

    public function apply(): void
    {
        // Add the checkout scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueueFrontendScripts'], 100);
        add_action('woocommerce_payment_complete', [$this, 'automaticExportOrder'], 1000);
        add_action('woocommerce_order_status_changed', [$this, 'automaticExportOrder'], 1000, 3);
        add_action('wp_ajax_myparcelnl_get_delivery_options_config', [$this, 'getDeliveryOptionsConfigAjax']);
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
     * @param  int $orderId
     *
     * @return void
     */
    public function automaticExportOrder(int $orderId): void
    {
        if (! Settings::get(GeneralSettings::ORDER_MODE, GeneralSettings::ID)) {
            return;
        }

        Actions::execute(PdkActions::EXPORT_ORDERS, [
            'orderIds' => [$orderId],
        ]);
    }

    /**
     * Load styles & scripts on the checkout page.
     *
     * @throws \Exception
     */
    public function enqueueFrontendScripts(): void
    {
        // The order received page has the same page id as the checkout so `is_checkout()` returns true on both.
        if (! is_checkout() || is_order_received_page()) {
            return;
        }

        if ($this->useSeparateAddressFields()) {
            $this->service->enqueueLocalScript(
                self::SCRIPT_SPLIT_ADDRESS_FIELDS,
                'views/checkout-split-address-fields/lib/index.js',
                [ScriptService::HANDLE_WC_CHECKOUT]
            );
        }

        // Don't load the delivery options scripts if it's disabled
        //        if (Settings::get(CheckoutSettings::DELIVERY_OPTIONS_DISPLAY, CheckoutSettings::ID)) {
        add_action($this->getDeliveryOptionsPosition(), [$this, 'renderDeliveryOptions']);

        $this->loadDeliveryOptionsScripts();

        //        }
    }

    /**
     * Get the delivery options config in JSON for passing to JavaScript.
     *
     * @return array
     * @throws \Exception
     * @todo
     */
    public function getDeliveryOptionsConfig(): array
    {
        $cartTotals                = WC()->session->get('cart_totals');
        $chosenShippingMethodPrice = (float) $cartTotals['shipping_total'];

        //        $settings                  = WCMYPA()->settingCollection;
        //        $displayIncludingTax       = WC()->cart->display_prices_including_tax();
        //        $priceFormat               = self::getDeliveryOptionsTitle('delivery_options_price_format');
        //        $shippingMethod            = WC()->session->get('chosen_shipping_methods')[0] ?? false;
        //        $shippingClass             = WCMP_Frontend::get_cart_shipping_class();
        //
        //        $packageType = ($shippingMethod)
        //            ? ExportActions::getPackageTypeFromShippingMethod($shippingMethod, $shippingClass)
        //            : null;
        //
        //        if ($displayIncludingTax) {
        //            $chosenShippingMethodPrice += (float) $cartTotals['shipping_tax'];
        //        }
        //
        //        $carrierSettings = [];
        //
        //        foreach ($this->getCarriersForDeliveryOptions() as $carrier) {
        //            $carrierName = $carrier->getName();
        //
        //            if (! AccountSettings::getInstance()
        //                ->isEnabledCarrier($carrierName)) {
        //                continue;
        //            }
        //
        //            $settingsByCarrier = $settings->where('carrier', $carrierName);
        //
        //            foreach ($this->getDeliveryOptionsConfigMap() as $key => $setting) {
        //                [$settingName, $function, $addBasePrice] = $setting;
        //
        //                $value = $settingsByCarrier->{$function}($settingName);
        //
        //                if ($addBasePrice && is_numeric($value) && $this->useTotalPrice()) {
        //                    $value += $chosenShippingMethodPrice;
        //                }
        //
        //                Arr::set($carrierSettings, "$carrierName.$key", $value);
        //            }
        //        }

        $order = new PdkOrder([
            'deliveryOptions' => [
                'packageType' => 'package',
            ],
            'shipmentPriceAfterVat' => $chosenShippingMethodPrice,
        ]);

        return (new DeliveryOptionsContext(['order' => $order]))->toArray();
    }

    /**
     * Output the delivery options template.
     */
    public function renderDeliveryOptions(): void
    {
        do_action('woocommerce_myparcel_before_delivery_options');

        $customCss = Settings::get(CheckoutSettings::DELIVERY_OPTIONS_CUSTOM_CSS, CheckoutSettings::ID);

        echo sprintf(
            '<div class="woocommerce-myparcel__delivery-options">%s<div id="myparcel-delivery-options"></div></div>',
            $customCss ? sprintf('<style>%s</style>', $customCss) : ''
        );

        do_action('woocommerce_myparcel_after_delivery_options');
    }

    /**
     * @return string
     */
    private function getDeliveryOptionsPosition(): string
    {
        $position = Settings::get(CheckoutSettings::DELIVERY_OPTIONS_POSITION, CheckoutSettings::ID);

        return apply_filters(
            'wc_wcmp_delivery_options_location',
            $position ?? 'woocommerce_checkout_after_customer_details'
        );
    }

    /**
     * Return the names of shipping methods that will show delivery options. If DISPLAY_FOR_ALL_METHODS is enabled it'll
     * return an empty array and the frontend will allow any shipping except any that are specifically disallowed.
     *
     * @return string[]
     * @see ExportActions::DISALLOWED_SHIPPING_METHODS
     */
    private function getShippingMethodsAllowingDeliveryOptions(): array
    {
        $allowedMethods               = [];
        $displayFor                   = Settings::get(CheckoutSettings::DELIVERY_OPTIONS_DISPLAY, CheckoutSettings::ID);
        $shippingMethodsByPackageType = []; // todo

        if ('always' === $displayFor || ! $shippingMethodsByPackageType) {
            return $allowedMethods;
        }

        $shippingMethodsForPackage = $shippingMethodsByPackageType[AbstractConsignment::PACKAGE_TYPE_PACKAGE_NAME];

        foreach ($shippingMethodsForPackage as $shippingMethod) {
            [$methodId] = $this->splitShippingMethodString($shippingMethod);

            if (! in_array($methodId, self::DISALLOWED_SHIPPING_METHODS, true)) {
                $allowedMethods[] = $shippingMethod;
            }
        }

        return $allowedMethods;
    }

    /**
     * @throws \Exception
     */
    private function loadDeliveryOptionsScripts(): void
    {
        $dependencies = [ScriptService::HANDLE_WC_CHECKOUT];

        /**
         * If split address fields are enabled add the checkout fields script as an additional dependency.
         */
        if ($this->useSeparateAddressFields()) {
            $dependencies[] = 'wcmp-checkout-fields';
        }

        if (! $this->shouldShowDeliveryOptions()) {
            return;
        }

        $this->service->enqueueLocalScript(
            self::SCRIPT_CHECKOUT_DELIVERY_OPTIONS,
            'views/frontend/checkout-delivery-options/lib/index.iife.js',
            $dependencies + [ScriptService::HANDLE_DELIVERY_OPTIONS, ScriptService::HANDLE_JQUERY]
        );

        wp_localize_script(
            self::SCRIPT_CHECKOUT_DELIVERY_OPTIONS,
            'MyParcelNLData',
            [
                'ajaxUrl'                     => admin_url('admin-ajax.php'),
                'allowedShippingMethods'      => $this->getShippingMethodsAllowingDeliveryOptions(),
                'alwaysShow'                  => true,
                'disallowedShippingMethods'   => self::DISALLOWED_SHIPPING_METHODS,
                'hiddenInputName'             => self::META_DELIVERY_OPTIONS,
                'isUsingSplitAddressFields'   => (int) Settings::get('checkout.useSeparateAddressFields'),
                'splitAddressFieldsCountries' => [CountryService::CC_NL, CountryService::CC_BE],
            ]
        );

        wp_localize_script(
            self::SCRIPT_CHECKOUT_DELIVERY_OPTIONS,
            'MyParcelConfig',
            $this->getDeliveryOptionsConfig()
        );

        $this->service->enqueueDeliveryOptions();
    }

    /**
     * Returns true if any product in the loop is physical and not on backorder
     *
     * @return bool
     */
    private function shouldShowDeliveryOptions(): bool
    {
        $showDeliveryOptions = false;

        foreach (WC()->cart->get_cart() as $cartItem) {
            /**  @var WC_Product $product */
            $product = $cartItem['data'];

            if (! $product->is_virtual() && ! $product->is_on_backorder($cartItem['quantity'])) {
                $showDeliveryOptions = true;
                break;
            }
        }

        return apply_filters('wc_myparcel_show_delivery_options', $showDeliveryOptions);
    }

    /**
     * Split a <rateId>:<instanceId> string into an array. If there is no instanceId, the second array element will be
     * null.
     *
     * @param  string $shippingMethod
     *
     * @return array
     */
    private function splitShippingMethodString(string $shippingMethod): array
    {
        $split = explode(':', $shippingMethod, 2);

        if (count($split) === 1) {
            $split[] = null;
        }

        return $split;
    }

    /**
     * @return bool
     */
    private function useSeparateAddressFields(): bool
    {
        return Settings::get(CheckoutSettings::USE_SEPARATE_ADDRESS_FIELDS, CheckoutSettings::ID) ?? false;
    }
}
