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

const WP_DEBUG = true;
