<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Service;

use MyParcelNL\Pdk\App\Cart\Model\PdkCart;
use MyParcelNL\Pdk\App\Order\Model\PdkProduct;
use MyParcelNL\Pdk\Context\Context;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Frontend\Service\FrontendRenderService;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;

class WcFrontendRenderService extends FrontendRenderService
{
    /**
     * @param  \MyParcelNL\Pdk\App\Cart\Model\PdkCart $cart
     *
     * @return string
     * @throws \MyParcelNL\Pdk\Base\Exception\InvalidCastException
     */
    public function renderDeliveryOptions(PdkCart $cart): string
    {
        ob_start();

        do_action('woocommerce_myparcel_before_delivery_options');

        $customCss = Settings::get(CheckoutSettings::DELIVERY_OPTIONS_CUSTOM_CSS, CheckoutSettings::ID);
        $context   = $this->contextService->createContexts([Context::ID_CHECKOUT], ['cart' => $cart]);

        printf(
            '<div id="mypa-delivery-options-wrapper" class="woocommerce-myparcel__delivery-options" data-context="%s">%s<div id="myparcel-delivery-options"></div></div>',
            $this->encodeContext($context),
            $customCss ? sprintf('<style>%s</style>', $customCss) : ''
        );

        do_action('woocommerce_myparcel_after_delivery_options');

        return ob_get_clean();
    }

    /**
     * @param  \MyParcelNL\Pdk\App\Order\Model\PdkProduct $product
     *
     * @return string
     */
    public function renderProductSettings(PdkProduct $product): string
    {
        $appInfo = Pdk::getAppInfo();

        return sprintf(
            '<div id="%s" class="panel woocommerce_options_panel">%s</div>',
            "{$appInfo->name}_product_data",
            parent::renderProductSettings($product)
        );
    }
}
