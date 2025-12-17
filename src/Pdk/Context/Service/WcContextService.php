<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Context\Service;

use MyParcelNL\Pdk\App\Cart\Model\PdkCart;
use MyParcelNL\Pdk\Context\Model\CheckoutContext;
use MyParcelNL\Pdk\Context\Service\ContextService;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;
use MyParcelNL\Pdk\Shipment\Collection\PackageTypeCollection;
use MyParcelNL\Pdk\Types\Service\TriStateService;
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
        $currentShippingClass    = ['shippingClass' => null, 'packageType' => null];
        $allowedShippingMethods  = Settings::get(CheckoutSettings::ALLOWED_SHIPPING_METHODS, CheckoutSettings::ID);
        $createShippingClassName = Pdk::get('createShippingClassName');

        if ($allowedShippingMethods && $cart) {
            /**
             * Remove products with a shipping class that points to a package type from the cart, so they don't affect package type calculation.
             */
            foreach ($cart->lines as $index => $line) {
                $WC_product = wc_get_product($line->product->externalIdentifier);
                if (! $WC_product) {
                    continue;
                }

                $shippingClass = $WC_product->get_shipping_class();
                if (! $shippingClass) {
                    continue;
                }

                $shippingClassName = $createShippingClassName($this->getShippingClassId($shippingClass));

                $packageType = $this->getAssociatedPackageType($shippingClassName, $allowedShippingMethods);

                if (TriStateService::INHERIT === $packageType) {
                    continue;
                }

                if (null === $packageType) {
                    $currentShippingClass = [
                        'shippingClass' => $shippingClassName,
                        'packageType'   => 'disable_delivery_options',
                    ];
                    break;
                }

                $cart->lines->offsetUnset($index);

                // Remember the largest package type from the shipping classes in the cart
                if ($this->isLargerPackageType($packageType, $currentShippingClass['packageType'])) {
                    $currentShippingClass = ['shippingClass' => $shippingClassName, 'packageType' => $packageType];
                }
            }
        }

        $checkoutContext = parent::createCheckoutContext($cart);

        /**
         * Use the shipping class calculated package type when there are no products in the cart without shipping
         * classes, or when the shipping class yields a larger package type than the cart-calculated one.
         */
        if (
            null === $cart->lines || 0 === $cart->lines->count()
            || $this->isLargerPackageType($currentShippingClass['packageType'], $checkoutContext->config->packageType)
        ) {
            $highestShippingClass = $currentShippingClass['shippingClass'];
        }

        $settingsToMerge = [
            'highestShippingClass' => $highestShippingClass ?? '', // frontend expects empty string when not set
        ];

        if (isset($currentShippingClass['packageType']) && 'disable_delivery_options' === $currentShippingClass['packageType']) {
            $settingsToMerge[CheckoutSettings::ENABLE_DELIVERY_OPTIONS] = false;
        }

        $checkoutContext->settings = array_merge($checkoutContext->settings, $settingsToMerge);

        return $checkoutContext;
    }

    /**
     * @param  string $shippingClassName
     * @param  array  $allowedShippingMethods
     *
     * @return null|string|int the package type name or null if none is associated
     */
    protected function getAssociatedPackageType(string $shippingClassName, array $allowedShippingMethods)
    {
        foreach ($allowedShippingMethods as $packageType => $methods) {
            if (in_array($shippingClassName, $methods, true)) {
                return $packageType;
            }
        }

        // No package type associated with this shipping class, this means don’t display
        // delivery options.
        return null;
    }

    /**
     * @param  null|string $packageTypeA
     * @param  null|string $packageTypeB
     *
     * @return bool whether package type A is larger than package type B
     */
    protected function isLargerPackageType(?string $packageTypeA, ?string $packageTypeB): bool
    {
        if (! isset($packageTypeA, $packageTypeB)) {
            return (null === $packageTypeB); // null is always the smallest package type
        }

        if ('disable_delivery_options' === $packageTypeA) {
            return true;
        }

        if ('disable_delivery_options' === $packageTypeB) {
            return false;
        }

        $sortedPackages = array_column(
            array_values(
                PackageTypeCollection::fromAll()
                    ->sortBySize(true)
                    ->toArrayWithoutNull()
            ),
            'name'
        );

        foreach ($sortedPackages as $packageType) {
            if ($packageType === $packageTypeA) {
                return false;
            }

            if ($packageType === $packageTypeB) {
                return true;
            }
        }

        throw new \InvalidArgumentException('One of the package types is invalid.');
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
}
