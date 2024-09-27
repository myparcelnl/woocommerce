<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Mock;

use WC_Shipping_Method;

/**
 * @extends \WC_Shipping_Method
 */
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
        $foundShippingClasses = [];

        foreach ($package['contents'] as $itemId => $values) {
            if ($values['data']->needs_shipping()) {
                $foundClass = $values['data']->get_shipping_class();

                if (! isset($foundShippingClasses[$foundClass])) {
                    $foundShippingClasses[$foundClass] = [];
                }

                $foundShippingClasses[$foundClass][$itemId] = $values;
            }
        }

        return $foundShippingClasses;
    }
}
