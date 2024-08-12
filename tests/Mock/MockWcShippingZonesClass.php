<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use WC_Shipping_Method;

class MockWcShippingZonesClass extends MockWcClass
{
    /**
     * Get shipping zone using it's ID.
     *
     * @param  int $instance_id Instance ID.
     *
     * @return bool|WC_Shipping_Method
     */
    public static function get_shipping_method(int $instance_id)
    {
        /** @var ?WC_Shipping_Method $instance */
        $instance = MockWcData::get($instance_id);
        if ($instance) {
            return $instance;
        }

        return false;
    }
}