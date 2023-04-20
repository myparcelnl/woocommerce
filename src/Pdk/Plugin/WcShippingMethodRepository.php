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
        $shippingMethods = $this->wcShipping()
            ->get_shipping_methods();

        foreach (WC_Shipping_Zones::get_zones() as $zone) {
            $zoneInstance = WC_Shipping_Zones::get_zone($zone['zone_id']);

            /** @var WC_Shipping_Method $shippingMethod */
            foreach ($zoneInstance->get_shipping_methods() as $shippingMethod) {
                $shippingMethods[] = $shippingMethod;
            }
        }

        return new PdkShippingMethodCollection(
            array_values(
                array_map(
                    function ($shippingMethod) {
                        return $this->get($shippingMethod);
                    },
                    $shippingMethods
                )
            )
        );
    }

    /**
     * @param  \WC_Shipping_Method|\MyParcelNL\Pdk\Plugin\Model\PdkShippingMethod|string $input
     *
     * @return \MyParcelNL\Pdk\Plugin\Model\PdkShippingMethod
     */
    public function get($input): PdkShippingMethod
    {
        if ($input instanceof PdkShippingMethod) {
            return $input;
        }

        if ($input instanceof WC_Shipping_Method) {
            $method = $input;
        } else {
            $wcShipping = $this->wcShipping();
            $method     = $wcShipping->get_shipping_methods()[$input] ?? null;
        }

        if (! $method) {
            throw new InvalidArgumentException('Shipping method not found');
        }

        return new PdkShippingMethod([
            'id'        => "$method->id:$method->instance_id",
            'name'      => $method->get_method_title(),
            'isEnabled' => $method->enabled === 'yes',
        ]);
    }

    /**
     * @return \WC_Shipping
     * @noinspection PhpUndefinedFieldInspection
     */
    public function wcShipping(): WC_Shipping
    {
        return WC()->shipping;
    }
}
