<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;
use MyParcelNL\WooCommerce\Facade\Filter;
use MyParcelNL\WooCommerce\Facade\WooCommerce;
use MyParcelNL\WooCommerce\Hooks\Contract\WooCommerceInitCallbacksInterface;
use MyParcelNLWooCommerce;
use WC_Customer;
use WC_Order;

/**
 * Adds separate address fields to the WooCommerce order fields.
 */
class SeparateAddressFieldsHooks extends AbstractFieldsHooks implements WooCommerceInitCallbacksInterface
{
    public function onWoocommerceInit(): void
    {
        $this->registerAdditionalBlocksCheckoutFields();

        // This action only fires for blocks checkout.
        add_action(
            'woocommerce_set_additional_field_value',
            [$this, 'storeBlockSeparateAddressFields'],
            10,
            4
        );

        // This action only fires for blocks checkout.
        add_action(
            'woocommerce_store_api_checkout_update_order_meta',
            [$this, 'setAddress1ForBlocksCheckout'],
            10,
            1
        );
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
     * Save unprefixed street, number, and suffix for compatibility with classic checkout.
     * @param string $key (namespaced) field ID
     * @param string $value value for the field being saved
     * @param string $type shipping or billing
     * @param WC_Order | WC_Customer $wc_object
     * @return void
     */
    public function storeBlockSeparateAddressFields(string $key, string $value, string $type, object $wc_object)
    {
        $prefix = $type === 'billing' ? Pdk::get('wcAddressTypeBilling') : Pdk::get('wcAddressTypeShipping');
        if ($key === $this->getBlockFieldId('fieldStreet')) {
            $wc_object->set_meta_data(
                $prefix . '_' . Pdk::get('fieldStreet'),
                $value,
                true
            );
        }
        if ($key === $this->getBlockFieldId('fieldNumber')) {
            $wc_object->set_meta_data(
                $prefix . '_' . Pdk::get('fieldNumber'),
                $value,
                true
            );
        }
        if ($key === $this->getBlockFieldId('fieldNumberSuffix')) {
            $wc_object->set_meta_data(
                $prefix . '_' . Pdk::get('fieldNumberSuffix'),
                $value,
                true
            );
        }
    }

    /**
     * Set the address1 field for separated fields for blocks checkout.
     * @param WC_Order $order
     * @return void
     */
    public function setAddress1ForBlocksCheckout(WC_Order $order): void
    {
        foreach (['billing', 'shipping'] as $type) {
            $metaKeyPrefix = '_wc_' . $type . '/' . MyParcelNLWooCommerce::PLUGIN_NAMESPACE . '/';
            $street      = $order->get_meta($metaKeyPrefix . Pdk::get('fieldStreet'), true);
            $number      = $order->get_meta($metaKeyPrefix . Pdk::get('fieldNumber'), true);
            $numberSuffix = $order->get_meta($metaKeyPrefix . Pdk::get('fieldNumberSuffix'), true);
            $address1 = implode(' ', array_filter([$street, $number, $numberSuffix]));
            if ($type === 'billing') {
                $order->set_billing_address_1($address1);
            } else {
                $order->set_shipping_address_1($address1);
            }
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
                    'required' => false,
                ];

                $locale[$countryCode][Pdk::get('fieldAddress2')] = [
                    'hidden'   => true,
                    'required' => false,
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
                    'required' => false,
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
