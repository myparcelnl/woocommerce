<?php
/** @noinspection PhpUnhandledExceptionInspection,StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Context\Service;

use MyParcelNL\Pdk\App\Cart\Contract\PdkCartRepositoryInterface;
use MyParcelNL\Pdk\App\ShippingMethod\Model\PdkShippingMethod;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcSession;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use WC_Product;
use WC_Shipping_Flat_Rate;
use WC_Shipping_Method;
use WP_Term;
use function MyParcelNL\Pdk\Tests\factory;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\wpFactory;
use function Spatie\Snapshots\assertMatchesJsonSnapshot;

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

it('creates the checkout context for flexible shipping', function () {
    $contextService = Pdk::get(WcContextService::class);
    $cartRepository = Pdk::get(PdkCartRepositoryInterface::class);

    $wcCart  = WC()->cart;
    $session = Pdk::get(MockWcSession::class);
    $session->set('chosen_shipping_methods', ['flat_rate:456']);

    $wcProduct = wpFactory(WC_Product::class)
        ->withId(6789)
        ->withShippingClassId(12)
        ->make();

    wpFactory(WP_Term::class)
        ->withId(12)
        ->withTermId(12)
        ->withName('shipping class')
        ->withSlug('shipping-class')
        ->store();

    $wcCart->add_to_cart($wcProduct->get_id());

    $pdkCart = $cartRepository->get($wcCart);

    $pdkShippingMethod       = factory(PdkShippingMethod::class)
        ->withId('flat_rate:123')
        ->make();
    $pdkCart->shippingMethod = $pdkShippingMethod;

    wpFactory(WC_Shipping_Method::class)
        ->withId(123)
        ->store();

    $checkoutContext = $contextService->createCheckoutContext($pdkCart);

    assertMatchesJsonSnapshot(json_encode($checkoutContext->toArray()));
    // ik denk dat je hier moet checken op de highest shipping class om je test te laten slagen?
    //    expect($checkoutContext)->toBeInstanceOf(CheckoutContext::class);
});

it('creates the checkout context for flat rate shipping', function () {
    $contextService = Pdk::get(WcContextService::class);
    $cartRepository = Pdk::get(PdkCartRepositoryInterface::class);

    $wcCart  = WC()->cart;
    $session = Pdk::get(MockWcSession::class);
    $session->set('chosen_shipping_methods', ['flat_rate:456']);

    $wcProduct = wpFactory(WC_Product::class)
        ->withId(6789)
        ->withShippingClassId(12)
        ->make();

    //    wpFactory(WP_Term::class)
    //        ->withId(12)
    //        ->withTermId(12)
    //        ->withName('shipping class')
    //        ->withSlug('shipping-class')
    //        ->store();

    //stap 1 is de class handmatig aanmaken en dat in de cache te stoppen.
    // Daarna kan je kijken of je het in een factory kan doen.

    $wpTerm          = new WP_Term();
    $wpTerm->term_id = 12;
    $wpTerm->name    = 'shipping class';
    $wpTerm->slug    = 'shipping-class';

    wp_cache_add((string) $wpTerm->term_id, $wpTerm, 'terms');

    $wcCart->add_to_cart($wcProduct->get_id());

    $pdkCart = $cartRepository->get($wcCart);

    $pdkShippingMethod       = factory(PdkShippingMethod::class)
        ->withId('flat_rate:123')
        ->make();
    $pdkCart->shippingMethod = $pdkShippingMethod;

    wpFactory(WC_Shipping_Flat_Rate::class)
        ->withId(123)
        ->store();

    $checkoutContext = $contextService->createCheckoutContext($pdkCart);

    assertMatchesJsonSnapshot(json_encode($checkoutContext->toArray()));
    // ik denk dat je hier moet checken op de highest shipping class om je test te laten slagen?
    //    expect($checkoutContext)->toBeInstanceOf(CheckoutContext::class);
});
