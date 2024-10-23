<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Integration;

use MyParcelNL\Pdk\App\Cart\Contract\PdkCartRepositoryInterface;
use MyParcelNL\Pdk\Context\Context;
use MyParcelNL\Pdk\Context\Contract\ContextServiceInterface;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;

class DeliveryOptionsBlocksIntegration extends AbstractBlocksIntegration
{
    /**
     * @return array
     */
    protected function getScriptData(): array
    {
        return [
            'context' => $this->getCartContext(),
            'style'   => Settings::get(CheckoutSettings::DELIVERY_OPTIONS_CUSTOM_CSS, CheckoutSettings::ID),
        ];
    }

    /**
     * @return string
     */
    private function getCartContext(): string
    {
        /** @var \MyParcelNL\Pdk\Context\Contract\ContextServiceInterface $contextService */
        $contextService = Pdk::get(ContextServiceInterface::class);
        /** @var \MyParcelNL\Pdk\App\Cart\Contract\PdkCartRepositoryInterface $cartRepository */
        $cartRepository = Pdk::get(PdkCartRepositoryInterface::class);

        $cart = WC()->cart;
        $context = $contextService->createContexts(
            [Context::ID_CHECKOUT],
            ['cart' => ! empty($cart->cart_contents) ? $cartRepository->get($cart) : null]
        );

        return htmlspecialchars(json_encode(array_filter($context->toArrayWithoutNull())), 0, 'UTF-8');
    }
}
