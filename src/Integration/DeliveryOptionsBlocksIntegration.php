<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Integration;

use MyParcelNL\Pdk\App\Cart\Contract\PdkCartRepositoryInterface;
use MyParcelNL\Pdk\Context\Context;
use MyParcelNL\Pdk\Context\Contract\ContextServiceInterface;
use MyParcelNL\Pdk\Facade\Logger;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;
use MyParcelNL\Pdk\Base\Support\Arr;
use Throwable;

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
     * Building the context calls the MyParcel API, which can fail for reasons the shop cannot
     * control. This method feeds the block script data, so an exception here breaks the whole
     * blocks checkout. Render the checkout without delivery options instead.
     *
     * @return string
     */
    private function getCartContext(): string
    {
        /** @var \MyParcelNL\Pdk\Context\Contract\ContextServiceInterface $contextService */
        $contextService = Pdk::get(ContextServiceInterface::class);
        /** @var \MyParcelNL\Pdk\App\Cart\Contract\PdkCartRepositoryInterface $cartRepository */
        $cartRepository = Pdk::get(PdkCartRepositoryInterface::class);

        $cart = WC()->cart;

        try {
            $context = $contextService->createContexts(
                [Context::ID_CHECKOUT],
                ['cart' => ! empty($cart->cart_contents) ? $cartRepository->get($cart) : null]
            );
        } catch (Throwable $throwable) {
            Logger::error(
                'Failed to create the delivery options context for the blocks checkout.',
                ['exception' => $throwable]
            );

            return '';
        }

        if (false === Arr::get($context, Context::ID_CHECKOUT . '.settings.' . CheckoutSettings::ENABLE_DELIVERY_OPTIONS)) {
            return '';
        }

        return htmlspecialchars(json_encode(array_filter($context->toArrayWithoutNull())), 0, 'UTF-8');
    }
}
