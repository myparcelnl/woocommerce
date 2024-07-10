<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Context\Service;

use MyParcelNL\Pdk\App\Cart\Model\PdkCart;
use MyParcelNL\Pdk\Context\Model\CheckoutContext;
use MyParcelNL\Pdk\Context\Service\ContextService;
use MyParcelNL\WooCommerce\Repository\WcShippingMethodRepository;

final class WcContextService extends ContextService
{
    /**
     * @var \MyParcelNL\WooCommerce\Repository\WcShippingMethodRepository
     */
    private $shippingMethodRepository;

    public function __construct(WcShippingMethodRepository $shippingMethodRepository)
    {
        $this->shippingMethodRepository = $shippingMethodRepository;
    }

    /**
     * @param  null|\MyParcelNL\Pdk\App\Cart\Model\PdkCart $cart
     *
     * @return \MyParcelNL\Pdk\Context\Model\CheckoutContext
     */
    public function createCheckoutContext(?PdkCart $cart): CheckoutContext
    {
        $checkoutContext      = parent::createCheckoutContext($cart);
        $highestShippingClass = $this->shippingMethodRepository->getHighestShippingClass($cart);

        $checkoutContext->settings = array_merge($checkoutContext->settings, [
            'highestShippingClass' => $highestShippingClass ?? '',
        ]);

        return $checkoutContext;
    }
}
