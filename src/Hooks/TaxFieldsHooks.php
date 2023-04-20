<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Base\Contract\CountryServiceInterface;
use MyParcelNL\Pdk\Base\Service\CountryCodes;
use MyParcelNL\Pdk\Facade\AccountSettings;
use MyParcelNL\Pdk\Facade\Pdk;

class TaxFieldsHooks extends AbstractFieldsHooks
{
    /**
     * @var \MyParcelNL\Pdk\Base\Contract\CountryServiceInterface
     */
    private $countryService;

    /**
     * @param  \MyParcelNL\Pdk\Base\Contract\CountryServiceInterface $countryService
     */
    public function __construct(CountryServiceInterface $countryService)
    {
        $this->countryService = $countryService;
    }

    public function apply(): void
    {
        if (! AccountSettings::hasTaxFields()) {
            return;
        }

        add_filter('woocommerce_get_country_locale', [$this, 'extendLocaleWithTaxFields']);
        add_filter('woocommerce_country_locale_field_selectors', [$this, 'extendSelectorsWithTaxFields']);
        add_filter('woocommerce_default_address_fields', [$this, 'extendDefaultsWithTaxFields']);

        add_filter(
            'woocommerce_billing_fields',
            [$this, 'extendBillingFields'],
            apply_filters('wcmp_checkout_fields_priority', 10, 'billing'),
            2
        );

        add_filter(
            'woocommerce_shipping_fields',
            [$this, 'extendShippingFields'],
            apply_filters('wcmp_checkout_fields_priority', 10, 'shipping'),
            2
        );
    }

    /**
     * @param  array $fields
     *
     * @return array
     */
    public function extendBillingFields(array $fields): array
    {
        return $this->extendWithTaxFields($fields, Pdk::get('wcAddressTypeBilling'));
    }

    /**
     * @param  array $fields
     *
     * @return array
     */
    public function extendDefaultsWithTaxFields(array $fields): array
    {
        return array_merge($fields, [
            Pdk::get('fieldEoriNumber') => [
                'hidden'   => true,
                'required' => false,
            ],
            Pdk::get('fieldVatNumber')  => [
                'hidden'   => true,
                'required' => false,
            ],
        ]);
    }

    /**
     * @param  array $locale
     *
     * @return array
     */
    public function extendLocaleWithTaxFields(array $locale): array
    {
        $countries = array_filter(CountryCodes::ALL, function (string $countryCode) {
            return $this->countryService->isRow($countryCode);
        });

        foreach ($countries as $countryCode) {
            foreach ([Pdk::get('fieldEoriNumber'), Pdk::get('fieldVatNumber')] as $field) {
                $locale[$countryCode][$field] = [
                    'hidden'   => true,
                    'required' => false,
                ];
            }
        }

        return $locale;
    }

    /**
     * @param  array $localeFields
     *
     * @return array
     */
    public function extendSelectorsWithTaxFields(array $localeFields): array
    {
        return array_merge(
            $localeFields,
            $this->createSelectorFor('fieldEoriNumber'),
            $this->createSelectorFor('fieldVatNumber')
        );
    }

    public function extendShippingFields(array $fields): array
    {
        return $this->extendWithTaxFields($fields, Pdk::get('wcAddressTypeShipping'));
    }

    /**
     * New checkout and account page billing/shipping fields
     *
     * @param  array  $fields Default fields.
     * @param  string $form
     *
     * @return array
     */
    private function extendWithTaxFields(array $fields, string $form): array
    {
        return array_merge_recursive(
            $fields,
            $this->createField($form, 'fieldEoriNumber', 'eori'),
            $this->createField($form, 'fieldVatNumber', 'vat')
        );
    }
}
