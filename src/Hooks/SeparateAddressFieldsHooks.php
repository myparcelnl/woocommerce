<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use Exception;
use MyParcelNL\Pdk\Base\Support\Arr;
use MyParcelNL\Pdk\Facade\Language;
use MyParcelNL\Pdk\Facade\Logger;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;
use MyParcelNL\Sdk\src\Support\Str;
use MyParcelNL\WooCommerce\Facade\Filter;

/**
 * Adds separate address fields to the WooCommerce order fields.
 */
class SeparateAddressFieldsHooks extends AbstractFieldsHooks
{
    /**
     * Copied from WooCommerce
     *
     * @see \Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields::register_field_attributes
     */
    private const ALLOWED_BLOCKS_FIELD_ATTRIBUTES = [
        'maxLength',
        'readOnly',
        'pattern',
        'autocomplete',
        'autocapitalize',
        'title',
    ];

    public function apply(): void
    {
        add_filter('woocommerce_get_country_locale', [$this, 'extendLocaleWithSeparateAddressFields'], 1);
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

        /**
         * The function that's called inside handles using the correct action itself. It doesn't work if we use add_action here.
         */
        $this->loadWooCommerceBlocks();
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
        $usesSeparateAddressFields = $this->usesSeparateAddressFields();

        foreach (Pdk::get('countriesWithSeparateAddressFields') as $countryCode) {
            $locale[$countryCode][Pdk::get('fieldAddress1')] = [
                'required' => true,
                'hidden'   => $usesSeparateAddressFields,
            ];

            $locale[$countryCode][Pdk::get('fieldAddress2')] = [
                'hidden' => $usesSeparateAddressFields,
            ];

            $locale[$countryCode][Pdk::get('fieldStreet')] = [
                'required' => $usesSeparateAddressFields,
                'hidden'   => ! $usesSeparateAddressFields,
            ];

            $locale[$countryCode][Pdk::get('fieldNumber')] = [
                'required' => $usesSeparateAddressFields,
                'hidden'   => ! $usesSeparateAddressFields,
            ];

            $locale[$countryCode][Pdk::get('fieldNumberSuffix')] = [
                'required' => false,
                'hidden'   => ! $usesSeparateAddressFields,
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
        $additionalFields = [];

        foreach ($this->getFields() as $field) {
            $label = $field['label'];
            $name  = $field['id'];
            $id    = sprintf('%s_%s', $form, Pdk::get($name));

            $additionalFields[$id] = array_replace([
                'class'    => Filter::apply("{$name}Class"),
                'label'    => Language::translate($label),
                'priority' => Filter::apply("{$name}Priority"),
            ], $field['attributes']);
        }

        return array_merge($fields, $additionalFields);
    }

    /**
     * @return array
     */
    private function getFields(): array
    {
        $baseFields = Pdk::get('separateAddressFields');

        return array_map(static function (array $field) {
            $field['attributes'] = $field['attributes'] ?? [];

            $field['attributes']['data-field-id'] = $field['id'];

            return $field;
        }, $baseFields);
    }

    /**
     * @return void
     */
    private function loadWooCommerceBlocks(): void
    {
        if (! $this->usesSeparateAddressFields()) {
            return;
        }

        $appInfo = Pdk::getAppInfo();

        try {
            foreach ($this->getFields() as $field) {
                $filteredAttributes = Arr::where($field['attributes'] ?? [], static function ($value, $key) {
                    return in_array($key, self::ALLOWED_BLOCKS_FIELD_ATTRIBUTES, true)
                        || Str::startsWith($key, 'data-')
                        || Str::startsWith($key, 'aria-');
                });

                $options = array_replace($field, [
                    'id'         => sprintf('%s/%s', $appInfo->name, $field['id']),
                    'label'      => Language::translate($field['label']),
                    'location'   => 'address',
                    'type'       => 'text',
                    'attributes' => $filteredAttributes,
                ]);

                woocommerce_register_additional_checkout_field($options);
            }
        } catch (Exception $e) {
            Logger::error('Failed to register additional checkout fields', ['error' => $e->getMessage()]);
        }
    }

    /**
     * @return bool
     */
    private function usesSeparateAddressFields(): bool
    {
        return (bool) Settings::get(CheckoutSettings::USE_SEPARATE_ADDRESS_FIELDS, CheckoutSettings::ID);
    }
}
