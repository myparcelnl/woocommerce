<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Integration;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use MyParcelNL\Pdk\App\Cart\Contract\PdkCartRepositoryInterface;
use MyParcelNL\Pdk\Context\Context;
use MyParcelNL\Pdk\Context\Contract\ContextServiceInterface;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;

class DeliveryOptionsBlocksIntegration implements IntegrationInterface
{
    /**
     * @return string[]
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    public function get_editor_script_handles(): array
    {
        return ['myparcelnl-delivery-options-block-view-script'];
    }

    /**
     * @return string
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    public function get_name(): string
    {
        return 'myparcelnl-delivery-options';
    }

    /**
     * @return array
     * @throws \MyParcelNL\Pdk\Base\Exception\InvalidCastException
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    public function get_script_data(): array
    {
        return [
            'context' => $this->getCartContext(),
            'style'   => Settings::get(CheckoutSettings::DELIVERY_OPTIONS_CUSTOM_CSS, CheckoutSettings::ID),
        ];
    }

    /**
     * @return string[]
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    public function get_script_handles(): array
    {
        return ['myparcelnl-delivery-options-block-view-script', 'myparcelnl-delivery-options-block-editor-script'];
    }

    /**
     * @return void
     */
    public function initialize(): void {}

    /**
     * @return string
     * @throws \MyParcelNL\Pdk\Base\Exception\InvalidCastException
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

        return htmlspecialchars(
            json_encode(array_filter($context->toArrayWithoutNull())),
            0,
            'UTF-8'
        );
    }
}
