<?php
/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpEnqueue;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use WC_Product;
use function MyParcelNL\Pdk\Tests\factory;
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
            ->withDeliveryOptionsPosition('woocommerce_after_checkout_billing_form')
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
                'toContain'    => ['myparcelnl-delivery-options'],
                'notToContain' => [],
            ],
        ],
        'enable delivery options, on backorder' => [
            'enableDeliveryOptions'               => true,
            'enableDeliveryOptionsWhenNotInStock' => false,
            'productData'                         => ['id' => 1, 'is_on_backorder' => true],
            'expected'                            => [
                'toContain'    => [],
                'notToContain' => ['myparcelnl-delivery-options'],
            ],
        ],
        'enable all, on backorder'              => [
            'enableDeliveryOptions'               => true,
            'enableDeliveryOptionsWhenNotInStock' => true,
            'productData'                         => ['id' => 1, 'is_on_backorder' => true],
            'expected'                            => [
                'toContain'    => ['myparcelnl-delivery-options'],
                'notToContain' => [],
            ],
        ],
    ]);
