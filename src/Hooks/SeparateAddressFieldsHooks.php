<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;
use MyParcelNL\Pdk\Base\PdkBootstrapper;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;
use MyParcelNL\WooCommerce\Facade\Filter;
use MyParcelNL\WooCommerce\Facade\WooCommerce;
use MyParcelNL\WooCommerce\Hooks\Contract\WooCommerceInitCallbacksInterface;
use WC_Customer;
use WC_Order;

/**
 * Adds separate address fields to the WooCommerce order fields.
 */
class SeparateAddressFieldsHooks extends AbstractFieldsHooks implements WooCommerceInitCallbacksInterface
{
    public $useSeparateAddressFields;

    public function __construct()
    {
        $this->useSeparateAddressFields = Settings::get(CheckoutSettings::USE_SEPARATE_ADDRESS_FIELDS, CheckoutSettings::ID);
    }

    public function onWoocommerceInit(): void
    {
        $this->registerAdditionalBlocksCheckoutFields();

        // This action only fires for blocks checkout.
        add_action(
            'woocommerce_set_additional_field_value',
            [$this, 'storeStreetAndNumberForBlocksCheckout'],
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
        // Blocks AND Classic checkout
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
        return $this->extendWithSeparateAddressFields($fields, (string) Pdk::get('wcAddressTypeShipping'));
    }

    /**
     * @param  array $fields
     *
     * @return array
     */
    public function extendBillingFields(array $fields): array
    {
        return $this->extendWithSeparateAddressFields($fields, (string) Pdk::get('wcAddressTypeBilling'));
    }

    /**
     * Register additional blocks checkout fields.
     * @see https://developer.woocommerce.com/docs/block-development/cart-and-checkout-blocks/additional-checkout-fields/
     * @since WooCommerce 8.9.0
     * @return void
     */
    public function registerAdditionalBlocksCheckoutFields(): void
    {
        if (! $this->useSeparateAddressFields) {
            return;
        }

        if (version_compare(WooCommerce::getVersion(), '8.9', '>=')) {
            /**
             * Suppress useless notice from woocommerce_register_additional_checkout_field();
             * it says fields cannot be registered with hidden=>true, however they can and must
             * be in order to not show them in the default state (when no country is known).
             */
            add_filter('doing_it_wrong_trigger_error', function($_, $functionName) {
                return 'woocommerce_register_additional_checkout_field' !== $functionName;
            },2, 2);

            // Note: These fields are further modified in the extendLocaleWithSeparateAddressFields() method.
            \woocommerce_register_additional_checkout_field(
                $this->createBlocksCheckoutAddressField(
                    'fieldStreet',
                    'street',
                    'text',
                    ['hidden' => true],
                ),
            );

            \woocommerce_register_additional_checkout_field(
                $this->createBlocksCheckoutAddressField(
                    'fieldNumber',
                    'number',
                    'text',
                    ['hidden' => true],
                ),
            );

            \woocommerce_register_additional_checkout_field(
                $this->createBlocksCheckoutAddressField(
                    'fieldNumberSuffix',
                    'number_suffix',
                    'text',
                    ['hidden' => true],
                )
            );
        }
    }

    /**
     * Save street, number, and suffix without namespace prefix, for compatibility with classic checkout.
     * @param string $key (namespaced) field ID
     * @param string $value value for the field being saved
     * @param string $type shipping or billing
     * @param WC_Order|WC_Customer $wc_object
     * @return void
     */
    public function storeStreetAndNumberForBlocksCheckout(string $key, string $value, string $type, object $wc_object)
    {
        if (! $this->useSeparateAddressFields) {
            return;
        }

        $prefix = $type === 'billing' ? Pdk::get('wcAddressTypeBilling') : Pdk::get('wcAddressTypeShipping');
        $fields = [
            'fieldStreet',
            'fieldNumber',
            'fieldNumberSuffix',
        ];

        foreach ($fields as $field) {
            if ($key === $this->getBlockFieldId($field)) {
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
     * Set the address1 field for separated fields for blocks checkout.
     * @param WC_Order $order
     * @return void
     */
    public function setAddress1ForBlocksCheckout(WC_Order $order): void
    {
        if (! $this->useSeparateAddressFields) {
            return;
        }

        foreach (['billing', 'shipping'] as $type) {
            $countryCode = $type === 'billing' ? $order->get_billing_country() : $order->get_shipping_country();
            if (in_array($countryCode, (array) Pdk::get('countriesWithSeparateAddressFields'), true)) {
                $metaKeyPrefix = "_wc_{$type}/" . PdkBootstrapper::PLUGIN_NAMESPACE . '/';
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
        if (! $this->useSeparateAddressFields) {
            return $locale;
        }

        // We cannot define our fields for specific locales, so we need to remove them here by default.
        foreach ($locale as $countryCode => $fields) {
            if (in_array($countryCode, (array) Pdk::get('countriesWithSeparateAddressFields'), true)) {
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
                if (CartCheckoutUtils::is_checkout_block_default()) {
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
                    $locale[$countryCode][Pdk::get('fieldStreet')] = [
                        'required' => true,
                        'hidden'   => false,
                    ];

                    $locale[$countryCode][Pdk::get('fieldNumber')] = [
                        'required' => true,
                        'hidden'   => false,
                    ];

                    $locale[$countryCode][Pdk::get('fieldNumberSuffix')] = [
                        'required' => false,
                        'hidden'   => false,
                    ];
                }
            } else {
                if (CartCheckoutUtils::is_checkout_block_default()) {
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
                } else {
                    $locale[$countryCode][Pdk::get('fieldStreet')] = [
                        'hidden' => true,
                        'required' => false,
                    ];

                    $locale[$countryCode][Pdk::get('fieldNumber')] = [
                        'hidden' => true,
                        'required' => false,
                    ];

                    $locale[$countryCode][Pdk::get('fieldNumberSuffix')] = [
                        'hidden' => true,
                        'required' => false,
                    ];

                }
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
