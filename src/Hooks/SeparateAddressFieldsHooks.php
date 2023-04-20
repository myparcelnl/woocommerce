<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Facade\LanguageService;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;

class SeparateAddressFieldsHooks implements WordPressHooksInterface
{
    private const FIELD_STREET        = 'street';
    private const FIELD_NUMBER        = 'number';
    private const FIELD_NUMBER_SUFFIX = 'number_suffix';

    /**
     * Make NL checkout fields hidden by default
     *
     * @param  array $fields default checkout fields
     *
     * @return array $fields default + custom checkout fields
     */
    public function addDefaultSeparateAddressFields(array $fields): array
    {
        $customFields = [
            self::FIELD_STREET        => [
                'hidden'   => true,
                'required' => false,
            ],
            self::FIELD_NUMBER        => [
                'hidden'   => true,
                'required' => false,
            ],
            self::FIELD_NUMBER_SUFFIX => [
                'hidden'   => true,
                'required' => false,
            ],
        ];

        return array_merge($fields, $customFields);
    }

    /**
     * @param  array $locale
     *
     * @return array
     */
    public function addSeparateAddressFieldsToLocale(array $locale): array
    {
        $useSeparateAddressFields = Settings::get(CheckoutSettings::USE_SEPARATE_ADDRESS_FIELDS, CheckoutSettings::ID);

        foreach (Pdk::get('countriesWithSeparateAddressFields') as $countryCode) {
            $locale[$countryCode]['address_1'] = [
                'required' => false,
                'hidden'   => $useSeparateAddressFields,
            ];

            $locale[$countryCode]['address_2'] = [
                'hidden' => $useSeparateAddressFields,
            ];

            $locale[$countryCode]['state'] = [
                'hidden'   => $useSeparateAddressFields,
                'required' => false,
            ];

            $locale[$countryCode][self::FIELD_STREET] = [
                'required' => $useSeparateAddressFields,
                'hidden'   => ! $useSeparateAddressFields,
            ];

            $locale[$countryCode][self::FIELD_NUMBER] = [
                'required' => $useSeparateAddressFields,
                'hidden'   => ! $useSeparateAddressFields,
            ];

            $locale[$countryCode][self::FIELD_NUMBER_SUFFIX] = [
                'required' => false,
                'hidden'   => ! $useSeparateAddressFields,
            ];
        }

        return $locale;
    }

    public function apply(): void
    {
        add_filter('woocommerce_get_country_locale', [$this, 'addSeparateAddressFieldsToLocale'], 1);
        add_filter('woocommerce_country_locale_field_selectors', [$this, 'country_locale_field_selectors']);
        add_filter('woocommerce_default_address_fields', [$this, 'addDefaultSeparateAddressFields']);

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
     * @param  array $localeFields
     *
     * @return array
     */
    public function country_locale_field_selectors(array $localeFields): array
    {
        return array_merge($localeFields, [
                self::FIELD_STREET        => '#billing_street_field, #shipping_street_field',
                self::FIELD_NUMBER        => '#billing_number_field, #shipping_number_field',
                self::FIELD_NUMBER_SUFFIX => '#billing_number_suffix_field, #shipping_number_suffix_field',
            ]
        );
    }

    /**
     * @param  array $fields
     *
     * @return array
     */
    public function extendBillingFields(array $fields): array
    {
        return $this->addSeparateAddressFields($fields, 'billing');
    }

    /**
     * @param  array $fields
     *
     * @return array
     */
    public function extendShippingFields(array $fields): array
    {
        return $this->addSeparateAddressFields($fields, 'shipping');
    }

    /**
     * New checkout and account page billing/shipping fields
     *
     * @param  array  $fields Default fields.
     * @param  string $form
     *
     * @return array
     */
    private function addSeparateAddressFields(array $fields, string $form): array
    {
        return array_merge_recursive(
            $fields,
            [
                sprintf('%s_%s', $form, self::FIELD_STREET)        => [
                    'label'    => LanguageService::translate('street'),
                    'class'    => apply_filters('wcmp_custom_address_field_class', ['form-row-third first']),
                    'priority' => 60,
                ],
                sprintf('%s_%s', $form, self::FIELD_NUMBER)        => [
                    'label'    => LanguageService::translate('number'),
                    'class'    => apply_filters('wcmp_custom_address_field_class', ['form-row-third']),
                    'type'     => 'number',
                    'priority' => 61,
                ],
                sprintf('%s_%s', $form, self::FIELD_NUMBER_SUFFIX) => [
                    'label'     => LanguageService::translate('number_suffix'),
                    'class'     => apply_filters('wcmp_custom_address_field_class', ['form-row-third last']),
                    'maxlength' => 6,
                    'priority'  => 62,
                ],
            ]
        );
    }
}
