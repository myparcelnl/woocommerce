<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Repository;

use MyParcelNL\Pdk\App\Cart\Model\PdkCart;
use MyParcelNL\Pdk\Base\Repository\Repository;
use WC_Shipping_Method;
use WC_Shipping_Zones;
use WP_Term;

final class WcShippingMethodRepository extends Repository
{
    /**
     * @param  null|\MyParcelNL\Pdk\App\Cart\Model\PdkCart $cart
     *
     * @return null|string
     */
    public function getHighestShippingClass(?PdkCart $cart): ?string
    {
        $shippingMethod    = $this->getCurrentShippingMethod($cart->shippingMethod->id ?? null);
        $cartShippingClass = $this->getCartShippingClass();

        if (! $cartShippingClass || ! $shippingMethod) {
            return null;
        }

        $shippingClasses = $this->getShippingClasses($shippingMethod);

        foreach ($shippingClasses as $shippingClass) {
            if (! $this->hasShippingClassCost($shippingClass, $shippingMethod)) {
                continue;
            }

            return $shippingClass;
        }

        return null;
    }

    /**
     * @param  string $key
     *
     * @return string
     */
    private function createCacheKey(string $key): string
    {
        $sessionId = WC()->session->get_id();

        return "$sessionId:$key";
    }

    /**
     * @return null|string
     */
    private function getCartShippingClass(): ?string
    {
        $cart = WC()->cart->get_cart();

        $highest = null;

        foreach ($cart as $cartItem) {
            $data          = $cartItem['data'];
            $shippingClass = $data->get_shipping_class();

            if ($shippingClass) {
                $highest = $shippingClass;
            }
        }

        return $highest;
    }

    /**
     * @param  null|string $method
     *
     * @return null|\WC_Shipping_Method
     */
    private function getCurrentShippingMethod(?string $method): ?WC_Shipping_Method
    {
        //$methodString = $method ?? WC()->session->get('chosen_shipping_methods')[0];

        //todo: dit is nog allemaal stom omdat methodString soms een array is en soms een string en soms null
        // ook heb ik geen idee waar deze functie voor is.
        // Zorg eerst maar dat de explode werkt.

        $chosenShippingMethods = WC()->session->get('chosen_shipping_methods');

        $chosenShippingMethod = array_keys($chosenShippingMethods['rates'] ?? []);

        $methodString = $method ?? $chosenShippingMethod[0] ?? null;
        if ($methodString === null) {
            return null;
        }

        $parts      = explode(':', $methodString);
        $instanceId = $parts[1] ?? null;

        /** @var \WC_Shipping_Method|null $shippingMethod */
        $shippingMethod = WC_Shipping_Zones::get_shipping_method($instanceId) ?: null;

        return $shippingMethod;
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

        if (! $term) {
            return null;
        }

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
        $term   = get_term_by('slug', $shippingClass, 'product_shipping_class');
        $termId = $this->getTermId($term);

        $classCost = $shippingMethod->get_option("class_cost_$shippingClass");
        $termCost  = $shippingMethod->get_option("class_cost_$termId");

        return (bool) ($termCost ?: $classCost);
    }
}
