<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use WC_Shipping_Method;

class MockWcShippingMethodFlatRateClass extends WC_Shipping_Method
{
    /**
     * Finds and returns shipping classes and the products with said class.
     *
     * @param  mixed $package Package of items from cart.
     *
     * @return array
     */
    public function find_shipping_classes($package): array
    {
        $found_shipping_classes = [];

        foreach ($package['contents'] as $item_id => $values) {
            if ($values['data']->needs_shipping()) {
                $found_class = $values['data']->get_shipping_class();

                if (! isset($found_shipping_classes[$found_class])) {
                    $found_shipping_classes[$found_class] = [];
                }

                $found_shipping_classes[$found_class][$item_id] = $values;
            }
        }

        return $found_shipping_classes;
    }
}