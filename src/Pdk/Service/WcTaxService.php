<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Service;

use MyParcelNL\Pdk\Plugin\Service\AbstractTaxService;
use WC_Tax;

class WcTaxService extends AbstractTaxService
{
    public const WC_TAX_CLASS_STANDARD = 'standard';
    public const WC_TAX_CLASS_INHERIT  = 'inherit';

    /**
     * @param  float $basePrice
     *
     * @return float
     */
    public function getShippingDisplayPrice(float $basePrice): float
    {
        $displayIncludingTax = WC()->cart->display_prices_including_tax();

        if ($displayIncludingTax) {
            $taxRates = WC_Tax::get_shipping_tax_rates();
            $taxes    = WC_Tax::calc_shipping_tax($basePrice, $taxRates);

            return $basePrice + array_sum($taxes);
        }

        return $basePrice;
    }

    /**
     * @return string the woocommerce tax class to apply to the shipping prices
     * @todo remove this from the service
     */
    public function getShippingTaxClass(): string
    {
        $shippingTaxClass = get_option('woocommerce_shipping_tax_class');

        if (self::WC_TAX_CLASS_INHERIT !== $shippingTaxClass) {
            return '' === $shippingTaxClass ? self::WC_TAX_CLASS_STANDARD : $shippingTaxClass;
        }

        $address        = WC_Tax::get_tax_location();
        $cartTaxClasses = WC()->cart->get_cart_item_tax_classes();

        if (! is_array($address) || in_array('', $cartTaxClasses, true)) {
            return self::WC_TAX_CLASS_STANDARD;
        }

        // If multiple classes are found, use the first one.
        if (count($cartTaxClasses) > 1) {
            $wcTaxClasses = WC_Tax::get_tax_classes();

            foreach ($wcTaxClasses as $wcTaxClass) {
                $wcTaxClass = sanitize_title($wcTaxClass);

                if (in_array($wcTaxClass, $cartTaxClasses, true)) {
                    break;
                }
            }
        }

        if (1 === count($cartTaxClasses)) {
            $wcTaxClass = reset($cartTaxClasses);
        }

        if (empty($wcTaxClass)) {
            $wcTaxClass = self::WC_TAX_CLASS_STANDARD;
        }

        return $wcTaxClass;
    }
}
