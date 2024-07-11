<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\WooCommerce\Repository;

use MyParcelNL\Pdk\Base\Repository\Repository;
use MyParcelNL\Pdk\Base\Support\Collection;
use MyParcelNL\WooCommerce\WooCommerce\Contract\WcShippingRepositoryInterface;
use WC_Shipping;
use WC_Shipping_Method;
use WC_Shipping_Zones;

class WcShippingRepository extends Repository implements WcShippingRepositoryInterface
{
    /**
     * @return \MyParcelNL\Pdk\Base\Support\Collection
     */
    public function getShippingClasses(): Collection
    {
        return $this->retrieve(__METHOD__, function () {
            /** @var WC_Shipping $wcShipping */
            $wcShipping = WC_Shipping::instance();
            $classes    = $wcShipping->get_shipping_classes();

            return new Collection($classes);
        });
    }

    /**
     * @return \MyParcelNL\Pdk\Base\Support\Collection<\WC_Shipping_Method>
     */
    public function getShippingMethods(): Collection
    {
        return $this->retrieve(__METHOD__, function () {
            // The "0" zone is the "Rest of the World" zone in WooCommerce.
            $zoneIds         = array_merge([0], array_keys(WC_Shipping_Zones::get_zones()));
            $shippingMethods = [];

            foreach ($zoneIds as $zoneId) {
                $zoneInstance = WC_Shipping_Zones::get_zone($zoneId);

                /** @var WC_Shipping_Method[] $shippingMethods */
                $zoneShippingMethods = $zoneInstance->get_shipping_methods(true);

                foreach ($zoneShippingMethods as $shippingMethod) {
                    $shippingMethods[] = $shippingMethod;
                }
            }

            return new Collection($shippingMethods);
        });
    }

    protected function getKeyPrefix(): string
    {
        return static::class;
    }
}
