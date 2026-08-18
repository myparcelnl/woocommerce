<?php

/** @noinspection StaticClosureCanBeUsedInspection,PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Context\Contract\ContextServiceInterface;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;
use MyParcelNL\WooCommerce\Tests\Mock\MockThrowingContextService;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpEnqueue;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use WC_Product;

use function MyParcelNL\Pdk\Tests\factory;
use function MyParcelNL\Pdk\Tests\mockPdkProperties;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\wpFactory;

usesShared(new UsesMockWcPdkInstance());

it(
    'enqueues frontend scripts',
    function (
        bool  $enableDeliveryOptions,
        bool  $enableDeliveryOptionsWhenNotInStock,
        array $productData,
        array $expected
    ) {
        factory(CheckoutSettings::class)
            ->withEnableDeliveryOptions($enableDeliveryOptions)
            ->withEnableDeliveryOptionsWhenNotInStock($enableDeliveryOptionsWhenNotInStock)
            ->store();

        $product = wpFactory(WC_Product::class)
            ->with($productData)
            ->make();

        WC()->cart->add_to_cart($product->get_id(), 2);

        /** @var \MyParcelNL\WooCommerce\Hooks\CheckoutScriptHooks $class */
        $class = Pdk::get(CheckoutScriptHooks::class);

        $class->enqueueFrontendScripts();

        $all =
            MockWpEnqueue::all()
                ->all();

        expect($all)
            ->toHaveKeys($expected['toContain'])
            ->and($all)->not->toHaveKeys($expected['notToContain']);

        WC()->cart->empty_cart();
    }
)
    ->with([
        'enable all, in stock'                  => [
            'enableDeliveryOptions'               => true,
            'enableDeliveryOptionsWhenNotInStock' => true,
            'productData'                         => ['id' => 1, 'is_on_backorder' => false],
            'expected'                            => [
                'toContain'    => ['myparcel-delivery-options'],
                'notToContain' => [],
            ],
        ],
        'enable delivery options, on backorder' => [
            'enableDeliveryOptions'               => true,
            'enableDeliveryOptionsWhenNotInStock' => false,
            'productData'                         => ['id' => 1, 'is_on_backorder' => true],
            'expected'                            => [
                'toContain'    => [],
                'notToContain' => ['myparcel-delivery-options'],
            ],
        ],
        'enable all, on backorder'              => [
            'enableDeliveryOptions'               => true,
            'enableDeliveryOptionsWhenNotInStock' => true,
            'productData'                         => ['id' => 1, 'is_on_backorder' => true],
            'expected'                            => [
                'toContain'    => ['myparcel-delivery-options'],
                'notToContain' => [],
            ],
        ],
    ]);

it('renders nothing when the delivery options template fails', function () {
    mockPdkProperties([ContextServiceInterface::class => new MockThrowingContextService()]);

    $product = wpFactory(WC_Product::class)
        ->withId(1)
        ->make();

    WC()->cart->add_to_cart($product->get_id());
    WC()->cart->set_needs_shipping(true);

    /** @var \MyParcelNL\WooCommerce\Hooks\CheckoutScriptHooks $class */
    $class = Pdk::get(CheckoutScriptHooks::class);

    ob_start();
    $class->renderDeliveryOptions();
    $output = ob_get_clean();

    // The checkout must stay usable without delivery options, not die on a white screen.
    expect($output)->toBe('');

    WC()->cart->empty_cart();
});
