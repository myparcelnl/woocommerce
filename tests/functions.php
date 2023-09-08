<?php
/** @noinspection PhpUnhandledExceptionInspection,PhpDocMissingThrowsInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Tests\Factory\WpFactoryFactory;
use WC_Order;
use WC_Product;

/**
 * @param  class-string<\WC_Data> $class
 * @param  mixed                  ...$args
 */
function wpFactory(string $class, ...$args)
{
    return WpFactoryFactory::create($class, ...$args);
}

function createDeliveryOptionsMeta(array $deliveryOptions = []): array
{
    return [
        'meta' => [
            Pdk::get('metaKeyOrderData') => [
                'deliveryOptions' => array_replace_recursive([
                    'carrier'         => 'dhlforyou',
                    'deliveryType'    => 'morning',
                    'date'            => '2024-12-31 12:00:00',
                    'shipmentOptions' => [
                        'signature' => true,
                    ],
                ], $deliveryOptions),
            ],
        ],
    ];
}

function createNotesMeta(array $notes = []): array
{
    return [
        'meta' => [
            Pdk::get('metaKeyOrderData') => [
                'notes' => $notes,
            ],
        ],
    ];
}

/** @deprecated use factory directly */
function createWcOrder(array $data = []): WC_Order
{
    return wpFactory(WC_Order::class)
        ->with($data)
        ->make();
}

function createWcProduct(array $data = []): WC_Product
{
    return wpFactory(WC_Product::class)
        ->with($data)
        ->make();
}

namespace MyParcelNL\WooCommerce;

use MyParcelNL\WooCommerce\Tests\Mock\MockWcPdkBootstrapper;

if (! function_exists('\MyParcelNL\WooCommerce\bootPdk')) {
    function bootPdk(): void
    {
        MockWcPdkBootstrapper::boot(...func_get_args());
    }
}
