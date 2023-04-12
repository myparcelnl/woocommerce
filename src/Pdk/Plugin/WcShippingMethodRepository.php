<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin;

use InvalidArgumentException;
use MyParcelNL\Pdk\Plugin\Collection\PdkShippingMethodCollection;
use MyParcelNL\Pdk\Plugin\Model\PdkShippingMethod;
use MyParcelNL\Pdk\Plugin\Repository\AbstractPdkShippingMethodRepository;
use WC_Shipping;
use WC_Shipping_Method;
use WC_Shipping_Zones;

class WcShippingMethodRepository extends AbstractPdkShippingMethodRepository
{
    /**
     * Get all available shipping methods from WooCommerce, with shipping classes.
     */
    public function all(): PdkShippingMethodCollection
    {
        $wcShipping = $this->wcShipping();

        $shippingMethods = $wcShipping->get_shipping_methods();

        $zones = WC_Shipping_Zones::get_zones();

        foreach ($zones as $zone) {
            $zoneInstance = WC_Shipping_Zones::get_zone($zone['zone_id']);

            array_push($shippingMethods, ...$zoneInstance->get_shipping_methods());
        }

        return new PdkShippingMethodCollection(
            array_values(
                array_map(function ($shippingMethod) {
                    return $this->get($shippingMethod);
                }, $shippingMethods)
            )
        );
    }

    /**
     * @param  \WC_Shipping_Method|string $input
     *
     * @return \MyParcelNL\Pdk\Plugin\Model\PdkShippingMethod
     */
    public function get($input): PdkShippingMethod
    {
        if ($input instanceof WC_Shipping_Method) {
            $method = $input;
        } else {
            $wcShipping = $this->wcShipping();

            $method = $wcShipping->get_shipping_methods()[$input] ?? null;
        }

        if (! $method) {
            throw new InvalidArgumentException('Shipping method not found');
        }

        return new PdkShippingMethod([
            'id'        => $method->id,
            'name'      => $method->get_method_title(),
            'isEnabled' => $method->enabled === 'yes',
        ]);
    }

    /**
     * @return \WC_Shipping
     */
    public function wcShipping(): WC_Shipping
    {
        return WC()->shipping;
    }
}
