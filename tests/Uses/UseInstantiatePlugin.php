<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Uses;

use MyParcelNL\Pdk\Tests\Uses\BaseMock;
use MyParcelNLWooCommerce;

final class UseInstantiatePlugin implements BaseMock
{
    public function beforeEach(): void
    {
        if (class_exists(MyParcelNLWooCommerce::class)) {
            new MyParcelNLWooCommerce();

            return;
        }

        require __DIR__ . '/../../woocommerce-myparcel.php';
    }
}
