<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin;

use InvalidArgumentException;
use MyParcelNL\Pdk\App\ShippingMethod\Collection\PdkShippingMethodCollection;
use MyParcelNL\Pdk\App\ShippingMethod\Model\PdkShippingMethod;
use MyParcelNL\Pdk\App\ShippingMethod\Repository\AbstractPdkShippingMethodRepository;
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
     * @param  \WC_Shipping_Method|PdkShippingMethod|string $input
     *
     * @return \MyParcelNL\Pdk\App\ShippingMethod\Model\PdkShippingMethod
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
            'name'      => $this->getShippingMethodTitle($method),
            'isEnabled' => $method->enabled === 'yes',
        ]);
    }

    /**
     * @param  \WC_Shipping_Method $method
     *
     * @return string
     */
    private function getShippingMethodTitle(WC_Shipping_Method $method): string
    {
        $title       = $method->get_title();
        $methodTitle = $method->get_method_title();

        $suffix = $title && trim($title) !== trim($methodTitle) ? " – $title" : '';

        return $methodTitle . $suffix;
    }

    /**
     * @return \WC_Shipping
     * @noinspection PhpUndefinedFieldInspection
     */
    private function wcShipping(): WC_Shipping
    {
        return WC()->shipping;
    }
}
