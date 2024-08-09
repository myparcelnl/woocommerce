<?php
/** @noinspection PhpUnhandledExceptionInspection,StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Context\Service;

use MyParcelNL\Pdk\App\Cart\Contract\PdkCartRepositoryInterface;
use MyParcelNL\Pdk\App\ShippingMethod\Model\PdkShippingMethod;
use MyParcelNL\Pdk\Context\Model\CheckoutContext;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcSession;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use WC_Product;
use function MyParcelNL\Pdk\Tests\factory;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\wpFactory;

usesShared(new UsesMockWcPdkInstance());

//     // ik moet denk ik een wc cart maken. en van die moet weer een pdk cart gemaakt worden?
//    // Dit is omdat de getHighestShippingClass functie een PdkCart verwacht.
//    // Maar diezelfde class zoekt ook de wc cart op.
//
//    $wcShippingMethodRepository = Pdk::get(PdkShippingMethodRepositoryInterface::class);
//    $pdkCartRepository          = Pdk::get(PdkCartRepositoryInterface::class);
//
//    // maak de WcCart met producten;
//    // het product moet een shipping class id hebben. De shipping class moet een prijs en een slug hebben.
//
//    // dit mag allemaal niet, gebruik factories. Gebruik ook camelcase
//    $wcProduct = wpFactory(WC_Product::class)
//        ->withId(6789)
//        ->make();
//    WC()->cart->add_to_cart($wcProduct->get_id());
//    $pdkCart = $pdkCartRepository->get(WC()->cart);
//
//    $highestShippingClass = $wcShippingMethodRepository->getHighestShippingClass($pdkCart);
//
//    expect($highestShippingClass)->toBe('bbp');

it('creates the checkout context', function () {
    $contextService = Pdk::get(WcContextService::class);
    $cartRepository = Pdk::get(PdkCartRepositoryInterface::class);

    $wcCart  = WC()->cart;
    $session = Pdk::get(MockWcSession::class);
    $session->set('chosen_shipping_methods', ['flat_rate:1234']);

    $wcProduct = wpFactory(WC_Product::class)
        ->withId(6789)
        ->make();

    $wcCart->add_to_cart($wcProduct->get_id());

    $pdkCart = $cartRepository->get($wcCart);

    $pdkShippingMethod       = factory(PdkShippingMethod::class)
        ->withId('flat_rate:123')
        ->make();
    $pdkCart->shippingMethod = $pdkShippingMethod;

    $checkoutContext = $contextService->createCheckoutContext($pdkCart);

    expect($checkoutContext)->toBeInstanceOf(CheckoutContext::class);
});
