<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Facade\LanguageService;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;

class TaxFieldsHooks implements WordPressHooksInterface
{
    private const FIELD_EORI       = 'eori';
    private const FIELD_VAT        = 'vat';

    public function apply(): void
    {
//        add_filter('woocommerce_get_country_locale', [$this, 'addSplitAddressFieldsToLocale'], 1);
        add_filter('woocommerce_country_locale_field_selectors', [$this, 'country_locale_field_selectors']);
//        add_filter('woocommerce_default_address_fields', [$this, 'addDefaultSeparateAddressFields']);

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
                self::FIELD_EORI       => '#billing_eori_field, #shipping_eori_field',
                self::FIELD_VAT        => '#billing_vat_field, #shipping_vat_field',
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
        return $this->addTaxFields($fields, 'billing');
    }

    public function extendShippingFields(array $fields): array
    {
        return $this->addTaxFields($fields, 'shipping');
    }

    /**
     * New checkout and account page billing/shipping fields
     *
     * @param  array  $fields Default fields.
     * @param  string $form
     *
     * @return array
     */
    private function addTaxFields(array $fields, string $form): array
    {
        return array_merge_recursive(
            $fields,
            [
                sprintf('%s_%s', $form, self::FIELD_EORI)        => [
                    'label'    => LanguageService::translate('eori'),
                    'class'    => apply_filters('wcmp_custom_eori_field_class', ['form-row']),
                    'type'     => 'text',
                    'priority' => 100,
                ],
                sprintf('%s_%s', $form, self::FIELD_VAT)        => [
                    'label'    => LanguageService::translate('vat'),
                    'class'    => apply_filters('wcmp_custom_vat_field_class', ['form-row']),
                    'type'     => 'text',
                    'priority' => 101,
                ],
            ]
        );
    }
}
