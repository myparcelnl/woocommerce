<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Base\Support\Arr;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;
use MyParcelNL\WooCommerce\Contract\WooCommerceServiceInterface;
use MyParcelNL\WooCommerce\WooCommerce\Address\NumberAbstractAddressField;
use MyParcelNL\WooCommerce\WooCommerce\Address\NumberSuffixAbstractAddressField;
use MyParcelNL\WooCommerce\WooCommerce\Address\StreetAbstractAddressField;

/**
 * Adds separate address fields to the WooCommerce order fields.
 */
class SeparateAddressFieldsHooks extends AbstractFieldsHooks
{
    /**
     * @var \MyParcelNL\WooCommerce\Contract\WooCommerceServiceInterface
     */
    private $wooCommerceService;

    /**
     * @param  \MyParcelNL\WooCommerce\Contract\WooCommerceServiceInterface $wooCommerceService
     */
    public function __construct(WooCommerceServiceInterface $wooCommerceService)
    {
        $this->wooCommerceService = $wooCommerceService;
    }

    /**
     * @param  array $locale
     *
     * @return array
     */
    public function callbackWcCountryLocale(array $locale): array
    {
        $extendedLocale = parent::callbackWcCountryLocale($locale);

        // When using blocks checkout, don't hide address 1. It's not possible to modify its value otherwise.
        $hideAddress1 = is_checkout() && ! $this->wooCommerceService->isUsingBlocksCheckout();

        foreach ($this->getApplicableCountries() as $countryCode) {
            Arr::set($extendedLocale[$countryCode], sprintf('%s.required', Pdk::get('fieldAddress1')), true);
            Arr::set($extendedLocale[$countryCode], sprintf('%s.hidden', Pdk::get('fieldAddress1')), $hideAddress1);
        }

        return $extendedLocale;
    }

    /**
     * @return string[]
     */
    protected function getApplicableCountries(): array
    {
        return Pdk::get('countriesWithSeparateAddressFields');
    }

    /**
     * @return \MyParcelNL\WooCommerce\WooCommerce\Address\Contract\AddressFieldInterface[]
     */
    protected function getCustomFields(): array
    {
        return [
            new StreetAbstractAddressField(),
            new NumberAbstractAddressField(),
            new NumberSuffixAbstractAddressField(),
        ];
    }

    /**
     * @return string
     */
    protected function getName(): string
    {
        return 'separateAddressFields';
    }

    /**
     * @return bool
     */
    protected function isEnabled(): bool
    {
        return (bool) Settings::get(CheckoutSettings::USE_SEPARATE_ADDRESS_FIELDS, CheckoutSettings::ID);
    }
}
