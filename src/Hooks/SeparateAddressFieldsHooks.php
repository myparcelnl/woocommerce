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
    protected const ADDRESS_WIDGET_FIELDTYPE = 'MyParcelAddressWidget';

    public function apply(): void
    {
        // Hide existing address fields
        //        add_filter('woocommerce_get_country_locale', [$this, 'extendLocaleWithSeparateAddressFields'], 1);
//        add_filter('woocommerce_country_locale_field_selectors', [$this, 'extendSelectorsWithSeparateAddressFields']);
        add_filter('woocommerce_default_address_fields', [$this, 'hideDefaultAddressFields']);

        // Add new address fields
        add_filter('woocommerce_checkout_fields', [$this, 'addAddressWidgetToCheckout'], Filter::apply('separateAddressFieldsPriority'), 2);
//        add_filter('woocommerce_shipping_fields', [$this, 'addAddressWidgetToShipping'], Filter::apply('separateAddressFieldsPriority'), 2);
//        add_filter(
//            'woocommerce_billing_fields',
//            [$this, 'extendBillingFields'],
//            Filter::apply('separateAddressFieldsPriority'),
//            2
//        );
//
//        add_filter(
//            'woocommerce_shipping_fields',
//            [$this, 'extendShippingFields'],
//            Filter::apply('separateAddressFieldsPriority'),
//            2
//        );

        // Custom field type rendering. Cannot call wooCommerce_form_field_TYPE
        // as we need to be the last to modify the output
        // and woocommerce_form_field is called later.
        add_filter('woocommerce_form_field', [$this, 'renderAddressWidgetContainer'], 1, 4);
    }

    /**
     * Shortcode (non-Blocks) checkout only.
     *
     * @param  array $fields
     *
     * @return array
     */
    public function extendBillingFields(array $fields): array
    {
        return $this->extendWithSeparateAddressFields($fields, Pdk::get('wcAddressTypeBilling'));
    }

    /**
     * Non-blocks (shortcode) checkout only.
     * Hides all default woocommerce address fields.
     *
     * @param  array $fields
     *
     * @return array
     */
    public function hideDefaultAddressFields(array $fields): array
    {
        $fields = array_map(function ($field) {
            $field['hidden'] = true;

            return $field;
        }, $fields);
//        var_dump($fields);
        return $fields;
    }

    /**
     * Blocks checkout only.
     * Conditionally hides fields per locale/country.
     *
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
     * Blocks checkout only.
     * Add JS selectors for the custom fields we add.
     * This may be used by either the WooCommerce core, a custom theme,
     *  or this plugin to manipulate the fields using javascript.
     *
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
     * Shortcode (non-Blocks) checkout only.
     *
     * @param  array $fields
     *
     * @return array
     */
    public function extendShippingFields(array $fields): array
    {
        return $this->extendWithSeparateAddressFields($fields, Pdk::get('wcAddressTypeShipping'));
    }

    /**
     * Shortcode (non-Blocks) checkout only.
     * Adds the scripts, wrapper and fields for the separate address fields.
     *
     * @param  array $fields
     *
     * @return array
     */
    public function addAddressWidgetToCheckout(array $fields): array
    {
        // This custom field is rendered as an empty div, which will be replaced by the Vue component.
        // This is implemented through the filter 'woocommerce_form_field_XXX'.
        $fields['billing']['billing_address_widget'] = [
            'type' => self::ADDRESS_WIDGET_FIELDTYPE,
            'label' => 'mount',
            'id' => 'form',
            'priority' =>  9999,
        ]; // TODO implement with $this->createField instead
        // $fields[] = woocommerce_form_field( 'fieldResolvedAddressBilling', ['type' => 'hidden', 'id' => 'resolvedAddress', 'return' => true, 'label' => 'Hidden field for resolved address']);
        return $fields;
    }

    /**
     * Callback for the 'woocommerce_form_field_XXX' filter.
     * Renders an empty div to be replaced by the Vue component.
     *
     * @see \woocommerce_form_field()
     *
     * @param $field
     * @param $key
     * @param $args
     * @param $value
     *
     * @return string
     */
    public function renderAddressWidgetContainer($field, $key, $args, $value): string
    {
        if ($args['type'] !== self::ADDRESS_WIDGET_FIELDTYPE) {
            return $field;
        }
        // TODO:  ID based on config
        // TODO: Sorting is done through regex or JS by woocommerce and will not work without the correct wrapper
        // Find a better way to get our own HTML without having to duplicate the wapper HTML here
        return '<div class="form-row form-row-first" id="form" data-priority="9999">REPLACE ME</div>';
    }



    public function addAddressWidgetToShipping(array $fields, string $form): array
    {
//        $fields[] = woocommerce_form_field( 'fieldResolvedAddressShipping', ['type' => 'hidden',  'id' => 'resolvedAddress2', 'return' => true, 'label' => 'Hidden field for resolved address']);

        return $fields;
    }

    /**
     * Generic function to append new address fields to the WooCommerce fields.
     *
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
