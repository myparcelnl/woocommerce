<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin;

use InvalidArgumentException;
use MyParcelNL\Pdk\App\ShippingMethod\Collection\PdkShippingMethodCollection;
use MyParcelNL\Pdk\App\ShippingMethod\Contract\PdkShippingMethodRepositoryInterface;
use MyParcelNL\Pdk\App\ShippingMethod\Model\PdkShippingMethod;
use WC_Shipping;
use WC_Shipping_Local_Pickup;
use WC_Shipping_Method;
use WC_Shipping_Zone;
use WC_Shipping_Zones;
use function implode;

class WcShippingMethodRepository implements PdkShippingMethodRepositoryInterface
{
    /**
     * Get all available shipping methods from WooCommerce.
     */
    public function all(): PdkShippingMethodCollection
    {
        /** @var WC_Shipping $wcShipping */
        $wcShipping = WC_Shipping::instance();

        // The "0" zone is the "Rest of the World" zone in WooCommerce.
        $zoneIds = array_merge([0], array_keys(WC_Shipping_Zones::get_zones()));

        $array = array_reduce($zoneIds, function (array $carry, $zoneId): array {
            $zoneInstance = WC_Shipping_Zones::get_zone($zoneId);
            /** @var WC_Shipping_Method[] $shippingMethods */
            $shippingMethods = $zoneInstance->get_shipping_methods(true);

            foreach ($shippingMethods as $shippingMethod) {
                $carry[] = $this->createPdkShippingMethod($shippingMethod, $zoneInstance);
            }

            return $carry;
        }, []);

        /** @var \WP_Term $shippingClass */
        foreach ($wcShipping->get_shipping_classes() as $shippingClass) {
            $array[] = new PdkShippingMethod([
                'id'          => $shippingClass->slug,
                'name'        => "📦️ $shippingClass->name (Shipping class)",
                'description' => $shippingClass->description,
                'isEnabled'   => true,
            ]);
        }

        return new PdkShippingMethodCollection($array);
    }

    /**
     * @param  \WC_Shipping_Method|string $input
     * @param  \WC_Shipping_Zone          $zone
     *
     * @return \MyParcelNL\Pdk\App\ShippingMethod\Model\PdkShippingMethod
     */
    private function createPdkShippingMethod($input, WC_Shipping_Zone $zone): PdkShippingMethod
    {
        $method   = $this->get($input);
        $zoneName = $zone->get_data()['zone_name'];

        $description = [
            'ID: ' . $method->get_rate_id(),
            'Zone: ' . $zoneName,
        ];

        return new PdkShippingMethod([
            'id'          => $method->get_rate_id(),
            'name'        => $this->getShippingMethodTitle($method),
            'description' => implode('<br>', $description),
            'isEnabled'   => 'yes' === $method->enabled && ! $method instanceof WC_Shipping_Local_Pickup,
        ]);
    }

    /**
     * @param  \WC_Shipping_Method|string $input
     *
     * @return \WC_Shipping_Method
     */
    private function get($input): WC_Shipping_Method
    {
        if ($input instanceof WC_Shipping_Method) {
            $method = $input;
        } else {
            $wcShipping = $this->wcShipping();
            $method     = $wcShipping->get_shipping_methods()[$input] ?? null;
        }

        if (! $method) {
            throw new InvalidArgumentException('Shipping method not found');
        }

        return $method;
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
