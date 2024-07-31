<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\App\Cart\Contract\PdkCartRepositoryInterface;
use MyParcelNL\Pdk\Facade\AccountSettings;
use MyParcelNL\Pdk\Facade\Frontend;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Frontend\Contract\ViewServiceInterface;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;
use MyParcelNL\WooCommerce\Facade\Filter;
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
        add_action('wp', [$this, 'enqueueFrontendScripts'], 100);
    }

    /**
     * Load styles & scripts on the checkout page.
     *
     * @throws \Exception
     */
    public function enqueueFrontendScripts(): void
    {
        /** @var ViewServiceInterface $viewService */
        $viewService = Pdk::get(ViewServiceInterface::class);

        if (! $viewService->isCheckoutPage()) {
            return;
        }

        $this->loadCoreScripts();
        $this->loadSeparateAddressFieldsScripts();
        $this->loadDeliveryOptionsScripts();
        $this->loadTaxFieldsScripts();
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

        /** @var PdkCartRepositoryInterface $repository */
        $repository = Pdk::get(PdkCartRepositoryInterface::class);

        echo Frontend::renderDeliveryOptions($repository->get($wcCart));
    }

    /**
     * @return string
     */
    private function getDeliveryOptionsPosition(): string
    {
        return Filter::apply(
            'deliveryOptionsPosition',
            Settings::get(CheckoutSettings::DELIVERY_OPTIONS_POSITION, CheckoutSettings::ID)
        );
    }

    /**
     * @return array
     */
    private function getWcCheckoutDependencies(): array
    {
        return [
            WpScriptService::HANDLE_JQUERY,
            WpScriptService::HANDLE_WC_CHECKOUT,
        ];
    }

    /**
     * @return void
     */
    private function loadCoreScripts(): void
    {
        $this->service->enqueueLocalScript(
            WpScriptService::HANDLE_CHECKOUT_CORE,
            'views/frontend/checkout-core/dist/checkout-core',
            $this->getWcCheckoutDependencies()
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

        add_action($this->getDeliveryOptionsPosition(), [$this, 'renderDeliveryOptions']);

        $this->service->enqueueDeliveryOptions();

        $this->service->enqueueLocalScript(
            WpScriptService::HANDLE_CHECKOUT_DELIVERY_OPTIONS,
            'views/frontend/checkout-delivery-options/dist/delivery-options',
            $this->getWcCheckoutDependencies() + [
                WpScriptService::HANDLE_CHECKOUT_CORE,
                WpScriptService::HANDLE_DELIVERY_OPTIONS,
            ]
        );

        $this->service->enqueueLocalStyle(
            WpScriptService::HANDLE_CHECKOUT_DELIVERY_OPTIONS,
            'views/frontend/checkout-delivery-options/dist/style.css'
        );
    }

    /**
     * @return void
     */
    private function loadSeparateAddressFieldsScripts(): void
    {
        if (! $this->useSeparateAddressFields()) {
            return;
        }

        $this->service->enqueueLocalScript(
            WpScriptService::HANDLE_SEPARATE_ADDRESS_FIELDS,
            'views/frontend/checkout-separate-address-fields/dist/separate-address-fields',
            $this->getWcCheckoutDependencies() + [
                WpScriptService::HANDLE_CHECKOUT_CORE,
            ]
        );

        $this->service->enqueueLocalStyle(
            WpScriptService::HANDLE_SEPARATE_ADDRESS_FIELDS,
            'views/frontend/checkout-separate-address-fields/dist/style.css'
        );
    }

    /**
     * @return void
     */
    private function loadTaxFieldsScripts(): void
    {
        if (! AccountSettings::hasTaxFields()) {
            return;
        }

        $this->service->enqueueLocalScript(
            WpScriptService::HANDLE_TAX_FIELDS,
            'views/frontend/checkout-tax-fields/dist/tax-fields',
            array_merge(
                $this->getWcCheckoutDependencies(),
                [
                    WpScriptService::HANDLE_CHECKOUT_CORE,
                ],
                $this->shouldShowDeliveryOptions() ? [WpScriptService::HANDLE_CHECKOUT_DELIVERY_OPTIONS] : []
            )
        );
    }

    /**
     * Returns true if any product in the loop is physical and not on backorder
     *
     * @return bool
     */
    private function shouldShowDeliveryOptions(): bool
    {
        if (! Settings::get(CheckoutSettings::ENABLE_DELIVERY_OPTIONS, CheckoutSettings::ID)) {
            return false;
        }

        $showDeliveryOptions = false;

        foreach (WC()->cart->get_cart() as $cartItem) {
            /**  @var WC_Product $product */
            $product = $cartItem['data'];

            $productIsNotVirtual = ! $product->is_virtual();

            $deliveryOptionsEnabledWhenNotInStock = Settings::get(
                CheckoutSettings::ENABLE_DELIVERY_OPTIONS_WHEN_NOT_IN_STOCK,
                CheckoutSettings::ID
            );

            $backorderEnabledOrNotOnBackorder =
                $deliveryOptionsEnabledWhenNotInStock || ! $product->is_on_backorder($cartItem['quantity']);

            if ($productIsNotVirtual && $backorderEnabledOrNotOnBackorder) {
                $showDeliveryOptions = true;
                break;
            }
        }

        return Filter::apply('showDeliveryOptions', $showDeliveryOptions);
    }

    /**
     * @return bool
     */
    private function useSeparateAddressFields(): bool
    {
        return Settings::get(CheckoutSettings::USE_SEPARATE_ADDRESS_FIELDS, CheckoutSettings::ID) ?? false;
    }
}
