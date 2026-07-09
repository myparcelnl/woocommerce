<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Service;

use MyParcelNL\Pdk\Facade\Pdk;
use WP_Term;

/**
 * Resolves a WooCommerce shipping class against the delivery-options "allowed shipping methods"
 * matrix (CheckoutSettings::ALLOWED_SHIPPING_METHODS).
 *
 * The matrix maps each package type (or TriStateService::INHERIT) to a list of identifiers, which
 * mix shipping-method rate ids (e.g. "flat_rate:1") and shipping-class names (e.g.
 * "shipping_class:12"). This service owns the shipping-class side of that lookup so both the
 * checkout context (which decides whether to render the widget) and the cart-fee guard (which
 * decides whether to charge the delivery-option fee) share one source of truth — a product whose
 * shipping class maps to no package type disables delivery options entirely, and the fee must
 * follow that same rule.
 */
final class WcShippingClassMatrixService
{
    /**
     * Build the matrix name ("shipping_class:<termId>") for a WooCommerce shipping-class slug.
     * Returns null when the slug is empty or its term can't be resolved.
     *
     * @param  string $shippingClassSlug
     *
     * @return null|string
     */
    public function resolveShippingClassName(string $shippingClassSlug): ?string
    {
        if ('' === $shippingClassSlug) {
            return null;
        }

        $termId = $this->getShippingClassId($shippingClassSlug);

        if (null === $termId) {
            return null;
        }

        return Pdk::get('createShippingClassName')($termId);
    }

    /**
     * The package type a shipping-class name maps to in the matrix, or null when the class is
     * assigned to no package type (delivery options disabled). The returned value may be a concrete
     * package type name or TriStateService::INHERIT — callers interpret it.
     *
     * @param  string $shippingClassName
     * @param  array  $allowedShippingMethods
     *
     * @return null|string
     */
    public function getAssociatedPackageType(string $shippingClassName, array $allowedShippingMethods): ?string
    {
        foreach ($allowedShippingMethods as $packageType => $methods) {
            if (is_array($methods) && in_array($shippingClassName, $methods, true)) {
                return (string) $packageType;
            }
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
        return $this->getTermId(get_term_by('slug', $shippingClass, 'product_shipping_class'));
    }

    /**
     * @param  WP_Term|array|false|null $term
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
}
