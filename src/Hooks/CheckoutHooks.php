<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Plugin\Service\ViewServiceInterface;
use MyParcelNL\WooCommerce\Service\ScriptService;

final class CheckoutHooks implements WordPressHooksInterface
{
    private const SCRIPT_SPLIT_ADDRESS_FIELDS = 'myparcelnl-checkout-split-address-fields';
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

        add_action('wp_ajax_myparcelnl_get_delivery_options_config', [$this, 'getDeliveryOptionsConfigAjax']);

        add_action('woocommerce_cart_calculate_fees', [$this, 'get_delivery_options_fees'], 20);
    }

    /**
     * Load styles & scripts on the checkout page.
     *
     * @throws \Exception
     */
    public function enqueueFrontendScripts(): void
    {
        /** @var \MyParcelNL\Pdk\Plugin\Service\ViewServiceInterface $viewService */
        $viewService = Pdk::get(ViewServiceInterface::class);

        if ($viewService->isCheckoutPage()) {
            return;
        }

        if ($this->useSeparateAddressFields()) {
            $this->service->enqueueLocalScript(
                self::SCRIPT_SPLIT_ADDRESS_FIELDS,
                'views/checkout-split-address-fields/lib/split-fields',
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
        /** @var PdkCartRepositoryInterface $repository */
        $repository = Pdk::get(PdkCartRepositoryInterface::class);
        $pdkCart    = $repository->get(WC()->cart);

        return (new DeliveryOptionsContext(['cart' => $pdkCart]))->toArray();
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

        /** @var \MyParcelNL\Pdk\Plugin\Repository\PdkCartRepositoryInterface $repository */
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
        $dependencies = [ScriptService::HANDLE_WC_CHECKOUT];

        /**
         * If split address fields are enabled add the checkout fields script as an additional dependency.
         */
        if ($this->useSeparateAddressFields()) {
            $dependencies[] = 'wcmp - checkout - fields';
        }

        if (! $this->shouldShowDeliveryOptions()) {
            return;
        }

        $this->service->enqueueLocalScript(
            self::SCRIPT_CHECKOUT_DELIVERY_OPTIONS,
            'views / frontend / checkout - delivery - options / lib / delivery - options',
            $dependencies + [ScriptService::HANDLE_DELIVERY_OPTIONS, ScriptService::HANDLE_JQUERY]
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
     * @return bool
     */
    private function useSeparateAddressFields(): bool
    {
        return Settings::get(CheckoutSettings::USE_SEPARATE_ADDRESS_FIELDS, CheckoutSettings::ID) ?? false;
    }
}
