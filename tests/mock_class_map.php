<?php
/** @noinspection PhpIllegalPsrClassPathInspection */

declare(strict_types=1);

use MyParcelNL\WooCommerce\Tests\Mock\MockWcCart;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcCustomer;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcOrder;

class WC_Order extends MockWcOrder { }

class WC_Customer extends MockWcCustomer { }

class WC_Cart extends MockWcCart { }

function get_bloginfo(string $name): string
{
    return '';
}

function get_option(string $name)
{
    switch ($name) {
        case 'woocommerce_weight_unit':
            return 'kg';
    }
    return null;
}

const WP_DEBUG = true;
