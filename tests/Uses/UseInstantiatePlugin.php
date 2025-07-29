<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Uses;

use MyParcelNL\Pdk\Tests\Uses\BaseMock;
use MyParcelNL\WooCommerce\Tests\Mock\WordPressOptions;
use MyParcelNLWooCommerce;

final class UseInstantiatePlugin implements BaseMock
{
    public function beforeEach(): void
    {
        WordPressOptions::reset();
        WordPressOptions::updateOption('active_plugins', ['woocommerce/woocommerce.php']);

        if (class_exists(MyParcelNLWooCommerce::class)) {
            new MyParcelNLWooCommerce();

            return;
        }

        require __DIR__ . '/../../woocommerce-myparcel.php';
    }
}
