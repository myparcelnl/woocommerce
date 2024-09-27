<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

/**
 * @extends \WC_Shipping_Method
 */
class MockWcShippingMethodClass extends MockWcClass
{
    public function get_option($key, $empty_value = null)
    {
        return $this->attributes['instance_settings'][$key];
    }
}
