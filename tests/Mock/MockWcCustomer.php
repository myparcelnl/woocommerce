<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

/**
 * @extends \WC_Customer
 */
class MockWcCustomer extends MockWcClass
{
    /**
     * @return bool
     */
    public function has_shipping_address(): bool
    {
        return isset($this->attributes['shipping_address_1']);
    }
}
