<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Context\Service;

use MyParcelNL\Pdk\App\Cart\Model\PdkCart;
use MyParcelNL\Pdk\Context\Model\CheckoutContext;
use MyParcelNL\Pdk\Context\Service\ContextService;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Shipment\Collection\PackageTypeCollection;
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
        $shippingClasses = [];
        if ($cart) {
            /**
             * Remove products with a shipping class from the cart, remembering their shipping classes.
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

                $cart->lines->offsetUnset($index);
                $shippingClasses[] = $shippingClass;
            }
        }

        /**
         * Also determines the package type for the products left in the cart.
         */
        $checkoutContext = parent::createCheckoutContext($cart);

        /**
         * If there are products with a shipping class, determine the highest shipping class if applicable.
         */
        if (! empty($shippingClasses)) {
            $highestShippingClass =
                $this->getHighestShippingClass(
                    $shippingClasses,
                    $checkoutContext,
                    0 < $cart->lines->count()
                );
        }

        $checkoutContext->settings = array_merge($checkoutContext->settings, [
            'highestShippingClass' => $highestShippingClass ?? '', // frontend expects empty string when not set
        ]);

        return $checkoutContext;
    }

    /**
     * @param  array                                         $shippingClasses
     * @param  \MyParcelNL\Pdk\Context\Model\CheckoutContext $checkoutContext
     * @param  bool                                          $hasItemsWithoutShippingClass
     *
     * @return null|string use package type for this shipping class, null means automatic package type calculation
     */
    private function getHighestShippingClass(
        array           $shippingClasses,
        CheckoutContext $checkoutContext,
        bool            $hasItemsWithoutShippingClass
    ): ?string {
        $allowedShippingMethods = $checkoutContext->settings['allowedShippingMethods'] ?? [];

        if (empty($shippingClasses) || empty($allowedShippingMethods)) {
            return null;
        }

        $createShippingClassName = Pdk::get('createShippingClassName');

        // key=>value array holding the MyParcel packageTypeNames with their corresponding shipping class
        $packageClasses = [];

        foreach ($shippingClasses as $shippingClass) {
            $class = $createShippingClassName($this->getShippingClassId($shippingClass));

            foreach ($allowedShippingMethods as $packageType => $methods) {
                if (in_array($class, $methods, true)) {
                    $packageClasses[$packageType] = $class;
                    break;
                }
            }
        }

        $sortedPackages = array_values(
            PackageTypeCollection::fromAll()
                ->sortBySize(true)
                ->toArrayWithoutNull()
        );

        $cartPackageType = null;
        if ($hasItemsWithoutShippingClass && $checkoutContext->config) {
            $cartPackageType = $checkoutContext->config->packageType;
        }

        /**
         * Have the frontend select the largest package type, either from the shipping class
         * or the calculated package type. Return null if the calculated package type is the
         * largest, for the frontend will not use the shipping class in that case.
         */
        $highest = null;

        foreach ($sortedPackages as $package) {
            $packageName = $package['name'] ?? '';

            if (isset($packageClasses[$packageName])) {
                $highest = $packageClasses[$packageName];
            }

            if ($cartPackageType === $packageName) {
                $highest = null;
            }
        }

        return $highest;
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
