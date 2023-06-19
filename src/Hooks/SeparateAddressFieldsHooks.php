<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;
use MyParcelNL\WooCommerce\Facade\Filter;

/**
 * Adds separate address fields to the WooCommerce order fields.
 */
class SeparateAddressFieldsHooks extends AbstractFieldsHooks
{
    public function apply(): void
    {
        add_filter('woocommerce_get_country_locale', [$this, 'extendLocaleWithSeparateAddressFields'], 1);
        add_filter('woocommerce_country_locale_field_selectors', [$this, 'extendSelectorsWithSeparateAddressFields']);
        add_filter('woocommerce_default_address_fields', [$this, 'extendDefaultsWithSeparateAddressFields']);

        add_filter(
            'woocommerce_billing_fields',
            [$this, 'extendBillingFields'],
            Filter::apply('separateAddressFieldsPriority', Pdk::get('wcAddressTypeBilling')),
            2
        );

        add_filter(
            'woocommerce_shipping_fields',
            [$this, 'extendShippingFields'],
            Filter::apply('separateAddressFieldsPriority', Pdk::get('wcAddressTypeShipping')),
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
        return $this->extendWithSeparateAddressFields($fields, Pdk::get('wcAddressTypeBilling'));
    }

    /**
     * @param  array $fields
     *
     * @return array
     */
    public function extendDefaultsWithSeparateAddressFields(array $fields): array
    {
        return array_merge($fields, [
            Pdk::get('fieldStreet') => [
                'hidden'   => true,
                'required' => false,
            ],

            Pdk::get('fieldNumber') => [
                'hidden'   => true,
                'required' => false,
            ],

            Pdk::get('fieldNumberSuffix') => [
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
    public function extendLocaleWithSeparateAddressFields(array $locale): array
    {
        $useSeparateAddressFields = Settings::get(CheckoutSettings::USE_SEPARATE_ADDRESS_FIELDS, CheckoutSettings::ID);

        foreach (Pdk::get('countriesWithSeparateAddressFields') as $countryCode) {
            $locale[$countryCode][Pdk::get('fieldAddress1')] = [
                'required' => true,
                'hidden'   => $useSeparateAddressFields,
            ];

            $locale[$countryCode][Pdk::get('fieldAddress2')] = [
                'hidden' => $useSeparateAddressFields,
            ];

            $locale[$countryCode][Pdk::get('fieldStreet')] = [
                'required' => $useSeparateAddressFields,
                'hidden'   => ! $useSeparateAddressFields,
            ];

            $locale[$countryCode][Pdk::get('fieldNumber')] = [
                'required' => $useSeparateAddressFields,
                'hidden'   => ! $useSeparateAddressFields,
            ];

            $locale[$countryCode][Pdk::get('fieldNumberSuffix')] = [
                'required' => false,
                'hidden'   => ! $useSeparateAddressFields,
            ];
        }

        return $locale;
    }

    /**
     * @param  array $localeFields
     *
     * @return array
     */
    public function extendSelectorsWithSeparateAddressFields(array $localeFields): array
    {
        return array_replace(
            $localeFields,
            $this->createSelectorFor('fieldStreet'),
            $this->createSelectorFor('fieldNumber'),
            $this->createSelectorFor('fieldNumberSuffix')
        );
    }

    /**
     * @param  array $fields
     *
     * @return array
     */
    public function extendShippingFields(array $fields): array
    {
        return $this->extendWithSeparateAddressFields($fields, Pdk::get('wcAddressTypeShipping'));
    }

    /**
     * @param  array  $fields
     * @param  string $form
     *
     * @return array
     */
    private function extendWithSeparateAddressFields(array $fields, string $form): array
    {
        return array_merge(
            $fields,
            $this->createField($form, 'fieldStreet', 'street'),
            $this->createField($form, 'fieldNumber', 'number', ['type' => 'number']),
            $this->createField(
                $form,
                'fieldNumberSuffix',
                'number_suffix',
                ['maxlength' => Pdk::get('numberSuffixMaxLength')]
            )
        );
    }
}
