<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Base\Service\CountryCodes;
use MyParcelNL\Pdk\Facade\AccountSettings;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Facade\Filter;

class TaxFieldsHooks extends AbstractFieldsHooks
{
    public function apply(): void
    {
        add_filter('woocommerce_get_country_locale', [$this, 'extendLocaleWithTaxFields'], 1);
        add_filter('woocommerce_country_locale_field_selectors', [$this, 'extendSelectorsWithTaxFields']);
        add_filter('woocommerce_default_address_fields', [$this, 'extendDefaultsWithTaxFields']);

        add_filter(
            'woocommerce_billing_fields',
            [$this, 'extendBillingFields'],
            Filter::apply('taxFieldsPriority', Pdk::get('wcAddressTypeBilling')),
            2
        );

        add_filter(
            'woocommerce_shipping_fields',
            [$this, 'extendShippingFields'],
            Filter::apply('taxFieldsPriority', Pdk::get('wcAddressTypeShipping')),
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
        if (! $this->shouldRender()) {
            return $fields;
        }

        return array_merge($fields, [
            Pdk::get('fieldEoriNumber') => [
                'hidden'   => false,
                'required' => true,
            ],
            Pdk::get('fieldVatNumber')  => [
                'hidden'   => false,
                'required' => true,
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
        if (! $this->shouldRender()) {
            return $locale;
        }

        foreach (CountryCodes::EU_COUNTRIES as $countryCode) {
            foreach (Pdk::get('taxFields') as $field) {
                $locale[$countryCode][Pdk::get($field)] = [
                    'required' => false,
                    'hidden'   => true,
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
        if (! $this->shouldRender()) {
            return $localeFields;
        }

        return array_replace(
            $localeFields,
            $this->createSelectorFor('fieldEoriNumber'),
            $this->createSelectorFor('fieldVatNumber')
        );
    }

    /**
     * @param  array $fields
     *
     * @return array
     */
    public function extendShippingFields(array $fields): array
    {
        return $this->extendWithTaxFields($fields, Pdk::get('wcAddressTypeShipping'));
    }

    /**
     * @return bool
     */
    protected function shouldRender(): bool
    {
        return AccountSettings::hasTaxFields();
    }

    /**
     * @param  array  $fields
     * @param  string $form
     *
     * @return array
     */
    private function extendWithTaxFields(array $fields, string $form): array
    {
        if (! $this->shouldRender()) {
            return $fields;
        }

        return array_replace(
            $fields,
            $this->createField($form, 'fieldEoriNumber', 'eori'),
            $this->createField($form, 'fieldVatNumber', 'vat')
        );
    }
}
