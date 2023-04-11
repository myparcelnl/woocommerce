<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\RenderService;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Plugin\Contract\PdkCartRepositoryInterface;
use MyParcelNL\Pdk\Plugin\Contract\ViewServiceInterface;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;
use MyParcelNL\WooCommerce\Service\WpScriptService;
use WC_Product;

final class CheckoutScriptHooks implements WordPressHooksInterface
{
    /**
     * @var \MyParcelNL\WooCommerce\Service\WpScriptService
     */
    private $service;

    /**
     * @param  \MyParcelNL\WooCommerce\Service\WpScriptService $service
     */
    public function __construct(WpScriptService $service)
    {
        $this->service = $service;
    }

    public function apply(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueFrontendScripts'], 100);
    }

    /**
     * Load styles & scripts on the checkout page.
     *
     * @throws \Exception
     */
    public function enqueueFrontendScripts(): void
    {
        /** @var \MyParcelNL\Pdk\Plugin\Contract\ViewServiceInterface $viewService */
        $viewService = Pdk::get(ViewServiceInterface::class);

        if ($viewService->isCheckoutPage()) {
            return;
        }

        if ($this->useSeparateAddressFields()) {
            $this->loadSeparateAddressFieldsScripts();
        }

        $this->loadTaxFieldsScripts();

        // Don't load the delivery options scripts if it's disabled
        //        if (Settings::get(CheckoutSettings::DELIVERY_OPTIONS_DISPLAY, CheckoutSettings::ID)) {
        add_action($this->getDeliveryOptionsPosition(), [$this, 'renderDeliveryOptions']);

        $this->loadDeliveryOptionsScripts();
        //        }
    }

    /**
     * Output the delivery options template.
     *
     * @throws \Exception
     */
    public function renderDeliveryOptions(): void
    {
        $wcCart = WC()->cart;

        if (! $wcCart || ! $wcCart->needs_shipping()) {
            return;
        }

        /** @var \MyParcelNL\Pdk\Plugin\Contract\PdkCartRepositoryInterface $repository */
        $repository = Pdk::get(PdkCartRepositoryInterface::class);

        echo RenderService::renderDeliveryOptions($repository->get($wcCart));
    }

    /**
     * @return string
     */
    private function getDeliveryOptionsPosition(): string
    {
        $position = Settings::get(CheckoutSettings::DELIVERY_OPTIONS_POSITION, CheckoutSettings::ID);

        return apply_filters(
            'wc_wcmp_delivery_options_location',
            $position ?? 'woocommerce_after_checkout_billing_form'
        );
    }

    /**
     * @throws \Exception
     */
    private function loadDeliveryOptionsScripts(): void
    {
        if (! $this->shouldShowDeliveryOptions()) {
            return;
        }

        $dependencies = [
            WpScriptService::HANDLE_JQUERY,
            WpScriptService::HANDLE_WC_CHECKOUT,
            WpScriptService::HANDLE_DELIVERY_OPTIONS,
        ];

        /**
         * If split address fields are enabled add its script as an additional dependency.
         */
        if ($this->useSeparateAddressFields()) {
            $dependencies[] = WpScriptService::HANDLE_SPLIT_ADDRESS_FIELDS;
        }

        $this->service->enqueueDeliveryOptions();

        $this->service->enqueueLocalScript(
            WpScriptService::HANDLE_CHECKOUT_DELIVERY_OPTIONS,
            'views/frontend/checkout-delivery-options/lib/delivery-options',
            $dependencies
        );

        $this->service->enqueueStyle(
            WpScriptService::HANDLE_SPLIT_ADDRESS_FIELDS,
            'views/frontend/checkout-delivery-options/lib/style.css'
        );
    }

    /**
     * @return void
     */
    private function loadSeparateAddressFieldsScripts(): void
    {
        $this->service->enqueueLocalScript(
            WpScriptService::HANDLE_SPLIT_ADDRESS_FIELDS,
            'views/frontend/checkout-split-address-fields/lib/split-address-fields',
            [WpScriptService::HANDLE_WC_CHECKOUT]
        );

        $this->service->enqueueStyle(
            WpScriptService::HANDLE_SPLIT_ADDRESS_FIELDS,
            'views/frontend/checkout-split-address-fields/lib/style.css'
        );
    }

    private function loadTaxFieldsScripts(): void
    {
        $this->service->enqueueLocalScript(
            WpScriptService::HANDLE_TAX_FIELDS,
            'views/frontend/checkout-tax-fields/lib/tax-fields',
            [WpScriptService::HANDLE_WC_CHECKOUT]
        );

        $this->service->enqueueStyle(
            WpScriptService::HANDLE_TAX_FIELDS,
            'views/frontend/checkout-tax-fields/lib/style.css'
        );
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
     * @return bool
     */
    private function useSeparateAddressFields(): bool
    {
        return Settings::get(CheckoutSettings::USE_SEPARATE_ADDRESS_FIELDS, CheckoutSettings::ID) ?? false;
    }
}
