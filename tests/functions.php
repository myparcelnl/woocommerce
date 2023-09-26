<?php
/** @noinspection PhpUnhandledExceptionInspection,PhpDocMissingThrowsInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests;

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
