<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Base\Service\CountryCodes;
use MyParcelNL\Pdk\Facade\AccountSettings;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Facade\Filter;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\WooCommerce\Hooks\Contract\WooCommerceInitCallbacksInterface;
use WC_Customer;
use WC_Order;

class TaxFieldsHooks extends AbstractFieldsHooks implements WooCommerceInitCallbacksInterface
{
    public function onWoocommerceInit(): void
    {
        if (version_compare(\WC()->version, '8.9', '>=')) {
            $this->registerAdditionalBlocksCheckoutFields();
        }
    }

    public function apply(): void
    {
        add_filter('woocommerce_get_country_locale', [$this, 'extendLocaleWithTaxFields'], 1);
        add_filter('woocommerce_country_locale_field_selectors', [$this, 'extendSelectorsWithTaxFields']);
        add_filter('woocommerce_default_address_fields', [$this, 'extendDefaultsWithTaxFields']);

        add_filter(
            'woocommerce_billing_fields',
            [$this, 'extendBillingFields'],
            Filter::apply('taxFieldsPriority'),
            2
        );

        add_filter(
            'woocommerce_shipping_fields',
            [$this, 'extendShippingFields'],
            Filter::apply('taxFieldsPriority'),
            2
        );

        // Blocks checkout hooks
        if (version_compare(\WC()->version, '8.9', '>=')) {
            add_action(
                'woocommerce_set_additional_field_value',
                [$this, 'storeTaxFieldsForBlocksCheckout'],
                10,
                4
            );
        }
    }

    /**
     * Register additional blocks checkout fields for VAT and EORI.
     * @see https://developer.woocommerce.com/docs/block-development/cart-and-checkout-blocks/additional-checkout-fields/
     * @since WooCommerce 8.9.0
     * @return void
     */
    public function registerAdditionalBlocksCheckoutFields(): void
    {
        if (! $this->shouldRender()) {
            return;
        }

        \woocommerce_register_additional_checkout_field(
            $this->createBlocksCheckoutAddressField(
                'fieldEoriNumber',
                'eori',
                'text'
            ),
        );

        \woocommerce_register_additional_checkout_field(
            $this->createBlocksCheckoutAddressField(
                'fieldVatNumber',
                'vat',
                'text'
            ),
        );
    }

    /**
     * Save VAT and EORI fields for blocks checkout without namespace prefix, for compatibility with classic checkout.
     * @param string $key (namespaced) field ID
     * @param string $value value for the field being saved
     * @param string $type shipping or billing
     * @param WC_Order|WC_Customer $wc_object
     * @return void
     */
    public function storeTaxFieldsForBlocksCheckout(string $key, string $value, string $type, object $wc_object): void
    {
        if (! $this->shouldRender()) {
            return;
        }

        $prefix = $type === 'billing' ? Pdk::get('wcAddressTypeBilling') : Pdk::get('wcAddressTypeShipping');
        $fields = [
            'fieldEoriNumber',
            'fieldVatNumber',
        ];

        foreach ($fields as $field) {
            // Check if the key matches the blocks checkout field ID
            if ($key === $this->getBlockFieldId($field)) {
                // Save with the same meta key format as classic checkout for compatibility
                $wc_object->update_meta_data(
                    '_' . $prefix . '_' . Pdk::get($field),
                    $value
                );
            }
        }
        // This function checks whether meta data has changed and only saves if necessary.
        $wc_object->save_meta_data();
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
                'required' => false,
            ],
            Pdk::get('fieldVatNumber')  => [
                'hidden'   => false,
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
        return AccountSettings::hasTaxFields() && Settings::get('checkout.showTaxFields');
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
