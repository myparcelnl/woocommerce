<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

/**
 * @extends \WC_Shipping_Method
 */
class MockWcShippingMethodClass extends MockWcClass
{
    /**
     * @param $key
     * @param $empty_value
     *
     * @return mixed
     */
    public function get_option($key, $empty_value = null)
    {
        return $this->attributes['instance_settings'][$key];
    }

    /**
     * @return bool
     */
    public function is_enabled(): bool
    {
        return 'yes' === $this->attributes['enabled'];
    }
}
