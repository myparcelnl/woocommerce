<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin;

use InvalidArgumentException;
use MyParcelNL\Pdk\App\ShippingMethod\Collection\PdkShippingMethodCollection;
use MyParcelNL\Pdk\App\ShippingMethod\Contract\PdkShippingMethodRepositoryInterface;
use MyParcelNL\Pdk\App\ShippingMethod\Model\PdkShippingMethod;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\WooCommerce\Contract\WcShippingRepositoryInterface;
use WC_Shipping_Local_Pickup;
use WC_Shipping_Method;
use WP_Term;

class WcShippingMethodRepository implements PdkShippingMethodRepositoryInterface
{
    /**
     * @var \MyParcelNL\WooCommerce\WooCommerce\Contract\WcShippingRepositoryInterface
     */
    private $wcShippingRepository;

    public function __construct(WcShippingRepositoryInterface $wcShippingRepository)
    {
        $this->wcShippingRepository = $wcShippingRepository;
    }

    /**
     * Get all available shipping methods from WooCommerce.
     *
     * @return \MyParcelNL\Pdk\App\ShippingMethod\Collection\PdkShippingMethodCollection
     */
    public function all(): PdkShippingMethodCollection
    {
        // The "0" zone is the "Rest of the World" zone in WooCommerce.
        $wcShippingMethods = $this->wcShippingRepository->getShippingMethods();
        $wcShippingClasses = $this->wcShippingRepository->getShippingClasses();

        $createdShippingMethods = $wcShippingMethods
            ->merge($wcShippingClasses)
            ->map(function ($method) {
                if ($method instanceof WC_Shipping_Method) {
                    return $this->createFromWcShippingMethod($method);
                }

                if ($method instanceof WP_Term) {
                    return $this->createFromWcShippingClass($method);
                }

                throw new InvalidArgumentException('Unknown shipping method type');
            });

        return new PdkShippingMethodCollection($createdShippingMethods);
    }

    /**
     * @param  \WP_Term $shippingClass
     *
     * @return \MyParcelNL\Pdk\App\ShippingMethod\Model\PdkShippingMethod
     */
    public function createFromWcShippingClass(WP_Term $shippingClass): PdkShippingMethod
    {
        $id = Pdk::get('createShippingClassName')($shippingClass->term_id);

        return new PdkShippingMethod([
            'id'          => $id,
            'name'        => "📦️ $shippingClass->name (Shipping class)",
            'description' => "ID: $id",
            'isEnabled'   => true,
        ]);
    }

    /**
     * @param  \WC_Shipping_Method $method
     *
     * @return \MyParcelNL\Pdk\App\ShippingMethod\Model\PdkShippingMethod
     */
    private function createFromWcShippingMethod(WC_Shipping_Method $method): PdkShippingMethod
    {
        return new PdkShippingMethod([
            'id'          => $method->get_rate_id(),
            'name'        => $this->getShippingMethodTitle($method),
            'description' => "ID: {$method->get_rate_id()}",
            'isEnabled'   => 'yes' === $method->enabled && ! $method instanceof WC_Shipping_Local_Pickup,
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
}
