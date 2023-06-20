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
     * Get all available shipping methods from WooCommerce.
     */
    public function all(): PdkShippingMethodCollection
    {
        // The "0" zone is the "Rest of the World" zone in WooCommerce.
        $zoneIds = array_merge([0], array_keys(WC_Shipping_Zones::get_zones()));

        return new PdkShippingMethodCollection(
            array_reduce(
                $zoneIds,
                function (array $carry, $zoneId): array {
                    $zoneInstance = WC_Shipping_Zones::get_zone($zoneId);

                    foreach ($zoneInstance->get_shipping_methods() as $shippingMethod) {
                        $carry[] = $this->get($shippingMethod);
                    }

                    return $carry;
                },
                []
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
