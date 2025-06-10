<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;
use MyParcelNL\WooCommerce\Facade\Filter;
use MyParcelNL\WooCommerce\Facade\WooCommerce;
use MyParcelNL\WooCommerce\Hooks\Contract\WooCommerceInitCallbacksInterface;

/**
 * Adds separate address fields to the WooCommerce order fields.
 */
class SeparateAddressFieldsHooks extends AbstractFieldsHooks implements WooCommerceInitCallbacksInterface
{
    public function onWoocommerceInit(): void
    {
        $this->registerAdditionalBlocksCheckoutFields();
    }

    public function apply(): void
    {
        // Blocks only
        add_filter('woocommerce_get_country_locale', [$this, 'extendLocaleWithSeparateAddressFields'], 1);

        // Classic only
        add_filter('woocommerce_country_locale_field_selectors', [$this, 'extendSelectorsWithSeparateAddressFields']);
        add_filter('woocommerce_default_address_fields', [$this, 'extendDefaultsWithSeparateAddressFields']);

        add_filter(
            'woocommerce_billing_fields',
            [$this, 'extendBillingFields'],
            Filter::apply('separateAddressFieldsPriority'),
            2
        );

        add_filter(
            'woocommerce_shipping_fields',
            [$this, 'extendShippingFields'],
            Filter::apply('separateAddressFieldsPriority'),
            2
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
     * @param  array $fields
     *
     * @return array
     */
    public function extendBillingFields(array $fields): array
    {
        return $this->extendWithSeparateAddressFields($fields, Pdk::get('wcAddressTypeBilling'));
    }

    /**
     * Register additional blocks checkout fields.
     * @see https://developer.woocommerce.com/docs/block-development/cart-and-checkout-blocks/additional-checkout-fields/
     * @since WooCommerce 8.9.0
     * @return void
     */
    public function registerAdditionalBlocksCheckoutFields(): void
    {
        if (version_compare(WooCommerce::getVersion(), '8.9', '>=')) {
            // Note: These fields are further modified in the extendLocaleWithSeparateAddressFields() method.
            \woocommerce_register_additional_checkout_field(
                $this->createBlocksCheckoutAddressField(
                    'fieldStreet',
                    'street',
                    'text',
                    ['required' => true]
                ),
            );

            \woocommerce_register_additional_checkout_field(
                $this->createBlocksCheckoutAddressField(
                    'fieldNumber',
                    'number',
                    'text',
                    ['required' => true]
                ),
            );


            \woocommerce_register_additional_checkout_field(
                $this->createBlocksCheckoutAddressField(
                    'fieldNumberSuffix',
                    'number_suffix',
                    'text',
                    ['required' => false]
                )
            );
        }
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
     * Hide the default address fields and add the separate address fields for blocks checkout.
     *
     * @param  array $locale
     *
     * @return array
     */
    public function extendLocaleWithSeparateAddressFields(array $locale): array
    {
        $useSeparateAddressFields = Settings::get(CheckoutSettings::USE_SEPARATE_ADDRESS_FIELDS, CheckoutSettings::ID);
        if (! $useSeparateAddressFields) {
            return $locale;
        }


        // We cannot define our fields for specific locales, so we need to remove them here by default.
        foreach ($locale as $countryCode => $fields) {
            if (in_array($countryCode, Pdk::get('countriesWithSeparateAddressFields'), true)) {
                // Hide the default address fields.
                $locale[$countryCode][Pdk::get('fieldAddress1')] = [
                    'hidden'   => true,
                ];

                $locale[$countryCode][Pdk::get('fieldAddress2')] = [
                    'hidden'   => true,
                ];

                // Modify our own separate address fields.
                $postalCodePosition = $fields['postcode']['priority'];
                $locale[$countryCode][$this->getBlockFieldId('fieldStreet')] = [
                    'priority' => $postalCodePosition - 1,
                    'required' => true,
                    'hidden'   => false,
                ];

                $locale[$countryCode][$this->getBlockFieldId('fieldNumber')] = [
                    'priority' => $postalCodePosition + 1,
                    'required' => true,
                    'hidden'   => false,
                ];

                $locale[$countryCode][$this->getBlockFieldId('fieldNumberSuffix')] = [
                    'priority' => $postalCodePosition + 2,
                    'required' => true,
                    'hidden'   => false,
                ];
            } else {
                // Hide our custom address fields if the country does not use separate address fields.
                $locale[$countryCode][$this->getBlockFieldId('fieldStreet')] = [
                    'hidden' => true,
                    'required' => false,
                ];

                $locale[$countryCode][$this->getBlockFieldId('fieldNumber')] = [
                    'hidden' => true,
                    'required' => false,
                ];

                $locale[$countryCode][$this->getBlockFieldId('fieldNumberSuffix')] = [
                    'hidden' => true,
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
