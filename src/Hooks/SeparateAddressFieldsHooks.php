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
use WC_Blocks_Utils;

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
        if (! $this->usesSeparateAddressFields()) {
            return;
        }

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
        $this->registerWcBlocksCheckoutFields();
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
        return array_reduce($this->getCustomFields(), static function (array $allFields, array $field) {
            $allFields[$field['id']] = [
                'required' => $field['required'] ?? false,
                'hidden'   => true,
            ];

            return $allFields;
        }, $fields);
    }

    /**
     * @param  array $locale
     *
     * @return array
     */
    public function extendLocaleWithSeparateAddressFields(array $locale): array
    {
        $usesSeparateAddressFields = $this->usesSeparateAddressFields();
        $usesWcBlocksCheckout      = $this->usesWcBlocksCheckout();

        // When using blocks checkout, don't hide address 1. It's not possible to modify its value otherwise.
        $hideAddress1 = $usesSeparateAddressFields && is_checkout() && ! $usesWcBlocksCheckout;

        foreach (Pdk::get('countriesWithSeparateAddressFields') as $countryCode) {
            Arr::set($locale[$countryCode], sprintf('%s.required', Pdk::get('fieldAddress1')), true);
            Arr::set($locale[$countryCode], sprintf('%s.hidden', Pdk::get('fieldAddress1')), $hideAddress1);

            foreach ($this->getCustomFields() as $customField) {
                $locale[$countryCode][$customField['id']] = array_replace($customField, [
                    'required' => $usesSeparateAddressFields && ($customField['required'] ?? false),
                    'hidden'   => ! $usesSeparateAddressFields,
                ]);
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
        $selectors = array_map(function (array $field) {
            return $this->createSelectorFor($field['id']);
        }, $this->getCustomFields());

        return array_replace($localeFields, ...$selectors);
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
     * @param  string $fieldName
     *
     * @return string
     */
    private function createBlocksCheckoutFieldId(string $fieldName): string
    {
        $appInfo = Pdk::getAppInfo();

        return sprintf('%s/%s', $appInfo->name, $fieldName);
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

        foreach ($this->getCustomFields() as $field) {
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
    private function getCustomFields(): array
    {
        $baseFields         = Pdk::get('separateAddressFields');
        $isWcBlocksCheckout = $this->usesWcBlocksCheckout();

        return array_map(function (array $field) use ($isWcBlocksCheckout) {
            $fieldName = $field['name'];
            $id        = Pdk::get($fieldName);

            return array_replace(
                ['attributes' => []],
                $field,
                ['id' => $isWcBlocksCheckout ? $this->createBlocksCheckoutFieldId($id) : $id]
            );
        }, $baseFields);
    }

    /**
     * @param  array $field
     *
     * @return void
     * @throws \Exception
     */
    private function registerWcBlocksCheckoutField(array $field): void
    {
        $filteredAttributes = Arr::where($field['attributes'] ?? [], static function ($value, $key) {
            return in_array($key, self::ALLOWED_BLOCKS_FIELD_ATTRIBUTES, true)
                || Str::startsWith($key, 'data-')
                || Str::startsWith($key, 'aria-');
        });

        woocommerce_register_additional_checkout_field([
            'id'         => $field['id'],
            'label'      => Language::translate($field['label']),

            /**
             * @see \Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields::$supported_field_types
             */
            'type'       => 'text',
            'required'   => $field['required'] ?? false,
            'location'   => 'address',
            'attributes' => $filteredAttributes,

            /**
             * In blocks checkout, the order is determined by 'index' and not 'priority'.
             */
            'index'      => Filter::apply('' . $field['id'] . 'Index'),
        ]);
    }

    /**
     * @return void
     */
    private function registerWcBlocksCheckoutFields(): void
    {
        if (! $this->usesSeparateAddressFields()) {
            return;
        }

        try {
            foreach ($this->getCustomFields() as $field) {
                $this->registerWcBlocksCheckoutField($field);
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

    /**
     * @return bool
     * @see https://stackoverflow.com/a/77950175
     */
    private function usesWcBlocksCheckout(): bool
    {
        return WC_Blocks_Utils::has_block_in_page(wc_get_page_id('checkout'), 'woocommerce/checkout');
    }
}
