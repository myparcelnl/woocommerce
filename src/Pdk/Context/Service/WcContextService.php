<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Context\Service;

use MyParcelNL\Pdk\App\Cart\Model\PdkCart;
use MyParcelNL\Pdk\Context\Model\CheckoutContext;
use MyParcelNL\Pdk\Context\Service\ContextService;
use MyParcelNL\Pdk\Facade\Pdk;
use WC_Shipping_Method;
use WC_Shipping_Zones;
use WP_Term;

final class WcContextService extends ContextService
{
    /**
     * @param  null|\MyParcelNL\Pdk\App\Cart\Model\PdkCart $cart
     *
     * @return \MyParcelNL\Pdk\Context\Model\CheckoutContext
     */
    public function createCheckoutContext(?PdkCart $cart): CheckoutContext
    {
        $checkoutContext      = parent::createCheckoutContext($cart);
        $highestShippingClass =
            $this->getHighestShippingClass($cart, $checkoutContext->settings['allowedShippingMethods']);

        $checkoutContext->settings = array_merge($checkoutContext->settings, [
            'highestShippingClass' => $highestShippingClass ?? '',
        ]);

        return $checkoutContext;
    }

    /**
     * @param  array $allowedShippingMethods
     *
     * @return null|string
     */
    private function getCartShippingClass(array $allowedShippingMethods): ?string
    {
        $cart = WC()->cart->get_cart();

        $highest = null;

        foreach ($cart as $cartItem) {
            $data          = $cartItem['data'];
            $shippingClassTermId = $data->get_shipping_class();
            if (! $shippingClassTermId) {
                continue;
            }

            $createShippingClassName         = Pdk::get('createShippingClassName');
            $shippingClassName               =
                $createShippingClassName($this->getShippingClassId($shippingClassTermId));
            $shippingClassHasDeliveryOptions =
                $this->shippingMethodOrClassHasDeliveryOptions($shippingClassName, $allowedShippingMethods);

            if ($shippingClassHasDeliveryOptions) {
                $highest = $shippingClassTermId;
            }
        }

        return $highest;
    }

    /**
     * Checks if a shipping class or method is enabled by it being set in allowed shipping methods.
     *
     * @param  string $methodOrClassName
     * @param  array  $allowedShippingMethods
     *
     * @return bool
     */
    private function shippingMethodOrClassHasDeliveryOptions(
        string $methodOrClassName,
        array  $allowedShippingMethods
    ): bool {
        foreach ($allowedShippingMethods as $packageType) {
            if (in_array($methodOrClassName, $packageType, false)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  null|string $method
     *
     * @return null|\WC_Shipping_Method
     */
    private function getCurrentShippingMethod(?string $method): ?WC_Shipping_Method
    {
        $methodString = $method ?? WC()->session->get('chosen_shipping_methods')[0];

        if (! $methodString) {
            return null;
        }

        $parts      = explode(':', $methodString);
        $instanceId = $parts[1] ?? null;

        /** @var \WC_Shipping_Method|null $shippingMethod */
        $shippingMethod = WC_Shipping_Zones::get_shipping_method((int) $instanceId) ?: null;

        return $shippingMethod;
    }

    /**
     * @param  null|\MyParcelNL\Pdk\App\Cart\Model\PdkCart $cart
     * @param  array $allowedShippingMethods
     *
     * @return null|string
     */
    private function getHighestShippingClass(?PdkCart $cart, ?array $allowedShippingMethods): ?string
    {
        $shippingMethod    = $this->getCurrentShippingMethod($cart->shippingMethod->id ?? null);
        $cartShippingClass = null;
        if ($allowedShippingMethods) {
            $cartShippingClass = $this->getCartShippingClass($allowedShippingMethods);
        }

        if (! $cartShippingClass || ! $shippingMethod) {
            return null;
        }

        $shippingClasses = $this->getShippingClasses($shippingMethod);

        foreach ($shippingClasses as $shippingClass) {
            if (! $this->hasShippingClassCost($shippingClass, $shippingMethod)) {
                continue;
            }

            $createShippingClassName = Pdk::get('createShippingClassName');

            return $createShippingClassName($this->getShippingClassId($shippingClass));
        }

        return null;
    }

    /**
     * @param  string $shippingClass
     *
     * @return null|int
     */
    private function getShippingClassId(string $shippingClass): ?int
    {
        $term = get_term_by('slug', $shippingClass, 'product_shipping_class');

        return $this->getTermId($term);
    }

    /**
     * @param  \WC_Shipping_Method $shippingMethod
     *
     * @return array<string>
     */
    private function getShippingClasses(WC_Shipping_Method $shippingMethod): array
    {
        if (! method_exists($shippingMethod, 'find_shipping_classes')) {
            return [];
        }

        $packages = WC()->cart->get_shipping_packages();
        $package  = current($packages);

        $shippingClasses = $shippingMethod->find_shipping_classes($package);

        return array_filter(array_keys($shippingClasses));
    }

    /**
     * @param  WP_Term|array|null $term
     *
     * @return null|int
     */
    private function getTermId($term): ?int
    {
        $termId = null;

        if ($term instanceof WP_Term) {
            $termId = $term->term_id;
        } elseif (is_array($term)) {
            $termId = $term['term_id'] ?? null;
        }

        return $termId ? (int) $termId : null;
    }

    /**
     * @param  string              $shippingClass
     * @param  \WC_Shipping_Method $shippingMethod
     *
     * @return bool
     */
    private function hasShippingClassCost(string $shippingClass, WC_Shipping_Method $shippingMethod): bool
    {
        $id = $this->getShippingClassId($shippingClass);

        $classCost = $shippingMethod->get_option("class_cost_$shippingClass");
        $termCost  = $shippingMethod->get_option("class_cost_$id");

        return (bool) ($termCost ?: $classCost);
    }
}
