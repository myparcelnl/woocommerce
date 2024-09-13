<?php
/** @noinspection PhpUnhandledExceptionInspection,StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Context\Service;

use MyParcelNL\Pdk\App\Cart\Contract\PdkCartRepositoryInterface;
use MyParcelNL\Pdk\App\ShippingMethod\Model\PdkShippingMethod;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use WC_Product;
use WC_Shipping_Flat_Rate;
use WC_Shipping_Method;
use WP_Term;
use function MyParcelNL\Pdk\Tests\factory;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\wpFactory;

usesShared(new UsesMockWcPdkInstance());

function add_term_to_cache(WP_Term $wpTerm, bool $asArray = false): void
{
    if ($asArray) {
        wp_cache_add((string) $wpTerm->term_id, [
            'term_id' => $wpTerm->term_id,
            'slug'    => $wpTerm->slug,
            'name'    => $wpTerm->name,
        ], 'terms');

        return;
    }
    wp_cache_add((string) $wpTerm->term_id, $wpTerm, 'terms');
}

function add_product_to_cart(?int $shippingClassId = null)
{
    $product_id = 6789;
    $wcProduct  = wpFactory(WC_Product::class)
        ->withId($product_id);
    if (null !== $shippingClassId) {
        $wcProduct->withShippingClassId($shippingClassId);
    }
    $wcProduct->make();

    WC()->cart->add_to_cart($product_id);
}

it('creates checkout context', function ($input, $expected) {
    $contextService = Pdk::get(WcContextService::class);
    $cartRepository = Pdk::get(PdkCartRepositoryInterface::class);

    factory(CheckoutSettings::class)
        ->withAllowedShippingMethods(['-1' => ['shipping_class:12']])
        ->store();

    $shippingMethodClassName = $input['shippingMethod'];
    $shippingClassId = $input['shippingClassId'] ?? null;
    $shippingPrice   = $input['shippingPrice'] ?? 0;
    $termAsArray     = $input['termAsArray'] ?? false;

    if ($shippingClassId) {
        $wpTerm          = new WP_Term();
        $wpTerm->term_id = 12;
        $wpTerm->name    = 'shipping class package';
        $wpTerm->slug    = 'shipping-class-package';

        add_product_to_cart($wpTerm->term_id);

        add_term_to_cache($wpTerm, $termAsArray);
    } else {
        add_product_to_cart();
    }

    $pdkCart = $cartRepository->get(WC()->cart);

    $pdkShippingMethod       = factory(PdkShippingMethod::class)
        ->withId('flexible_shipping:456')
        ->make();
    $pdkCart->shippingMethod = $pdkShippingMethod;

    $shippingMethod = wpFactory($shippingMethodClassName)
        ->withId(456);
    if ($shippingPrice) {
        $shippingMethod->withInstanceSettings(['class_cost_12' => $shippingPrice]);
    }
    $shippingMethod->store();

    $checkoutContext = $contextService->createCheckoutContext($pdkCart);

    expect($checkoutContext->config->basePrice)
        ->toBe($expected['basePrice'])
        ->and($checkoutContext->settings['highestShippingClass'])
        ->toBe($expected['highestShippingClass']);
})->with([
    'flexible shipping'              => [
        'input'    => [
            'shippingMethod' => WC_Shipping_Method::class,
            'shippingClassId' => 12,
        ],
        'expected' => [
            'basePrice'            => 0.0,
            'highestShippingClass' => '',
        ],
    ],
    'flat rate with price'           => [
        'input'    => [
            'shippingMethod' => WC_Shipping_Flat_Rate::class,
            'shippingClassId' => 12,
            'shippingPrice'   => 5.12,
        ],
        'expected' => [
            'basePrice'            => 0.0,
            'highestShippingClass' => 'shipping_class:12',
        ],
    ],
    'product without shipping class' => [
        'input'    => [
            'shippingMethod' => WC_Shipping_Flat_Rate::class,
        ],
        'expected' => [
            'basePrice'            => 0.0,
            'highestShippingClass' => '',
        ],
    ],
    'flat rate without price'        => [
        'input'    => [
            'shippingMethod' => WC_Shipping_Flat_Rate::class,
            'shippingClassId' => 12,
        ],
        'expected' => [
            'basePrice'            => 0.0,
            'highestShippingClass' => '',
        ],
    ],
    'term as array'                  => [
        'input'    => [
            'shippingMethod' => WC_Shipping_Flat_Rate::class,
            'shippingClassId' => 12,
            'termAsArray'     => true,
        ],
        'expected' => [
            'basePrice'            => 0.0,
            'highestShippingClass' => '',
        ],
    ],
]);
