<?php

namespace MyParcelNL\WooCommerce\Tests\Mock;

use WC_Shipping_Method;

/**
 * @extends \WC_Shipping_Zone
 */
class MockWcShippingZone extends MockWcClass
{
    /**
     * Get shipping methods linked to this zone.
     *
     * @param  bool   $enabledOnly Only return enabled methods.
     * @param  string $context     Getting shipping methods for what context. Valid values, admin, json.
     *
     * @return array of objects
     */
    public function get_shipping_methods(bool $enabledOnly = false, string $context = 'admin'): array
    {
        $methods = MockWcData::getByClass(WC_Shipping_Method::class);

        $filteredMethods = [];
        foreach ($methods as $method) {
            if ($method->get_supports()['settings']['shipping_zone_id'] === $this->get_id()) {
                if ($enabledOnly && $method->get_enabled === 'no') {
                    continue;
                }
                $filteredMethods[] = $method;
            }
        }

        return $filteredMethods;
    }
}
