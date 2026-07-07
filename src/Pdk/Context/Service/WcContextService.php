<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Context\Service;

use MyParcelNL\Pdk\App\Cart\Model\PdkCart;
use MyParcelNL\Pdk\Carrier\Service\CapabilitiesValidationService;
use MyParcelNL\Pdk\Context\Model\CheckoutContext;
use MyParcelNL\Pdk\Context\Service\ContextService;
use MyParcelNL\Pdk\Facade\Logger;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;
use MyParcelNL\Pdk\Shipment\Model\DeliveryOptions;
use MyParcelNL\Pdk\Types\Service\TriStateService;
use MyParcelNL\WooCommerce\Pdk\Service\WcShippingClassMatrixService;

final class WcContextService extends ContextService
{
    /**
     * @param  null|\MyParcelNL\Pdk\App\Cart\Model\PdkCart $cart
     *
     * @return \MyParcelNL\Pdk\Context\Model\CheckoutContext
     */
    public function createCheckoutContext(?PdkCart $cart): CheckoutContext
    {
        $allowedShippingMethods = Settings::get(CheckoutSettings::ALLOWED_SHIPPING_METHODS, CheckoutSettings::ID);
        $matrixService          = Pdk::get(WcShippingClassMatrixService::class);

        $disableDeliveryOptions = false;
        // Candidate list of shipping-class → package-type pairs collected from cart products.
        $candidates = [];

        if ($allowedShippingMethods && $cart) {
            /**
             * Remove products with a shipping class that points to a package type from the cart, so they don't affect package type calculation.
             */
            foreach ($cart->lines as $index => $line) {
                $WC_product = wc_get_product($line->product->externalIdentifier);
                if (! $WC_product) {
                    continue;
                }

                $shippingClassName = $matrixService->resolveShippingClassName($WC_product->get_shipping_class());
                if (null === $shippingClassName) {
                    continue;
                }

                $packageType = $matrixService->getAssociatedPackageType($shippingClassName, $allowedShippingMethods);

                if ((string) TriStateService::INHERIT === $packageType) {
                    continue;
                }

                if (null === $packageType) {
                    // A product's shipping class doesn't map to any allowed package type
                    // → disable delivery options entirely. No shipping class is surfaced
                    // to the frontend (the widget won't render anyway).
                    $disableDeliveryOptions = true;
                    break;
                }

                $cart->lines->offsetUnset($index);
                $candidates[] = ['shippingClass' => $shippingClassName, 'packageType' => $packageType];
            }
        }

        $checkoutContext = parent::createCheckoutContext($cart);

        $highestShippingClass = $disableDeliveryOptions
            ? null
            : $this->resolveHighestShippingClass($cart, $candidates, $checkoutContext->config->packageType ?? null);

        $settingsToMerge = [
            'highestShippingClass' => $highestShippingClass ?? '', // frontend expects empty string when not set
        ];

        if ($disableDeliveryOptions) {
            $settingsToMerge[CheckoutSettings::ENABLE_DELIVERY_OPTIONS] = false;
        }

        $checkoutContext->settings = array_merge($checkoutContext->settings, $settingsToMerge);

        return $checkoutContext;
    }

    /**
     * Pick the shipping class whose package type is the largest among cart-derived
     * candidates, using capabilities-based max-weight comparison at the cart's
     * destination. Returns null when the cart's own package type already wins or
     * when no candidates were collected.
     *
     * Falls back to the default package type (with a warning logged) when no
     * destination country is available on the cart.
     *
     * @param  null|\MyParcelNL\Pdk\App\Cart\Model\PdkCart $cart
     * @param  array<int, array{shippingClass: string, packageType: string}> $candidates
     * @param  null|string $cartPackageType
     *
     * @return null|string
     */
    private function resolveHighestShippingClass(?PdkCart $cart, array $candidates, ?string $cartPackageType): ?string
    {
        if (empty($candidates)) {
            return null;
        }

        $cartStillHasLines    = null !== $cart && null !== $cart->lines && $cart->lines->count() > 0;
        $cartPackageTypeReal  = null !== $cartPackageType
            && (string) TriStateService::INHERIT !== $cartPackageType;
        $cc                   = $cart->shippingMethod->shippingAddress->cc ?? null;

        if (null === $cc) {
            Logger::warning(
                'Cannot determine largest package type without destination country; falling back to default',
                [
                    'candidates'      => array_column($candidates, 'packageType'),
                    'cartPackageType' => $cartPackageType,
                ]
            );

            $default = DeliveryOptions::DEFAULT_PACKAGE_TYPE_NAME;
            foreach ($candidates as $candidate) {
                if ($candidate['packageType'] === $default) {
                    return $candidate['shippingClass'];
                }
            }

            return $candidates[0]['shippingClass'];
        }

        /** @var CapabilitiesValidationService $service */
        $service        = Pdk::get(CapabilitiesValidationService::class);
        $candidateTypes = array_values(array_unique(array_column($candidates, 'packageType')));

        // Only include the cart's own package type when other (non-shipping-class) products
        // remain in the cart — that's the "is shipping-class-derived still larger?" gate.
        // INHERIT/null cart types contribute nothing to the comparison (treated as smallest,
        // matching the old explicit isLargerPackageType INHERIT branches).
        $relevant = $cartStillHasLines && $cartPackageTypeReal
            ? array_values(array_unique(array_merge($candidateTypes, [$cartPackageType])))
            : $candidateTypes;

        $typeFilter = array_intersect_key(
            DeliveryOptions::PACKAGE_TYPES_V2_MAP,
            array_flip($relevant)
        );
        $weightMap  = $service->getPackageTypeWeights($cc, $typeFilter);

        $heaviest = $service->resolveHeaviestType($relevant, $weightMap);

        if ($cartStillHasLines && $cartPackageTypeReal && $heaviest === $cartPackageType) {
            // Cart's package type is already largest — no shipping-class override needed.
            return null;
        }

        foreach ($candidates as $candidate) {
            if ($candidate['packageType'] === $heaviest) {
                return $candidate['shippingClass'];
            }
        }

        return null;
    }

}
