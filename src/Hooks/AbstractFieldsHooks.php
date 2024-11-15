<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use Exception;
use MyParcelNL\Pdk\Facade\Logger;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Facade\Filter;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;
use MyParcelNL\WooCommerce\WooCommerce\Address\Contract\AddressFieldInterface;

abstract class AbstractFieldsHooks implements WordPressHooksInterface
{
    public function apply(): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        add_filter('woocommerce_country_locale_field_selectors', [$this, 'callbackWcCountryLocaleFieldSelectors']);
        add_filter('woocommerce_default_address_fields', [$this, 'callbackWcDefaultAddressFields']);
        add_filter('woocommerce_get_country_locale', [$this, 'callbackWcCountryLocale'], 1);

        $name             = $this->getName();
        $filteredPriority = Filter::apply("{$name}Priority");

        if ($this->addToShipping()) {
            add_filter('woocommerce_shipping_fields', [$this, 'callbackWcShippingFields'], $filteredPriority, 1);
        }

        if ($this->addToBilling()) {
            add_filter('woocommerce_billing_fields', [$this, 'callbackWcBillingFields'], $filteredPriority, 1);
        }

        /**
         * The function that's called inside handles using the correct action itself. It doesn't work if we use the action directly.
         */
        $this->registerWcBlocksCheckoutFields();
    }

    /**
     * @param  array $fields
     *
     * @return array
     */
    public function callbackWcBillingFields(array $fields): array
    {
        return $this->extendWcFields($fields, $this->getCustomFields(), Pdk::get('wcAddressTypeBilling'));
    }

    /**
     * @param  array $locale
     *
     * @return array
     */
    public function callbackWcCountryLocale(array $locale): array
    {
        foreach ($this->getApplicableCountries() as $countryCode) {
            foreach ($this->getCustomFields() as $field) {
                $locale[$countryCode][$field->getId()] = [
                    'label'    => $field->getTranslatedLabel(),
                    'required' => $field->isRequired(),
                    'hidden'   => $field->isHidden(),
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
    public function callbackWcCountryLocaleFieldSelectors(array $localeFields): array
    {
        return array_reduce($this->getCustomFields(), static function (array $allFields, AddressFieldInterface $field) {
            $id           = $field->getId();
            $addressTypes = Pdk::get('wcAddressTypes');

            $selectors = array_map(
                static function (string $addressType) use ($id): string {
                    // Strip the plugin prefix off
                    $baseFieldId = preg_replace('/^(?:\w+\/)?(.+)/', '$1', $id);

                    return sprintf('#%s_%s_field', $addressType, $baseFieldId);
                },
                $addressTypes
            );

            $allFields[$id] = implode(', ', $selectors);

            return $allFields;
        }, $localeFields);
    }

    /**
     * @param  array $fields
     *
     * @return array
     */
    public function callbackWcDefaultAddressFields(array $fields): array
    {
        return array_reduce(
            $this->getCustomFields(),
            static function (array $allFields, AddressFieldInterface $field) {
                $allFields[$field->getId()] = [
                    'required'     => $field->isRequired(),
                    'hidden'       => $field->isHidden(),
                    'autocomplete' => $field->getAttributes()['autocomplete'] ?? null,
                    'label'        => $field->getTranslatedLabel(),
                    'priority'     => $field->getPriority(),
                    'class'        => $field->getClass(),
                ];

                return $allFields;
            },
            $fields
        );
    }

    /**
     * @param  array $fields
     *
     * @return array
     */
    public function callbackWcShippingFields(array $fields): array
    {
        return $this->extendWcFields($fields, $this->getCustomFields(), Pdk::get('wcAddressTypeShipping'));
    }

    /**
     * @return bool
     */
    protected function addToBilling(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    protected function addToShipping(): bool
    {
        return true;
    }

    /**
     * @return string[]
     */
    abstract protected function getApplicableCountries(): array;

    /**
     * @return \MyParcelNL\WooCommerce\WooCommerce\Address\Contract\AddressFieldInterface[]
     */
    abstract protected function getCustomFields(): array;

    /**
     * @return string
     */
    abstract protected function getName(): string;

    /**
     * @return bool
     */
    protected function isEnabled(): bool
    {
        return true;
    }

    /**
     * @param  array  $fields
     * @param  array  $customFields
     * @param  string $addressType
     *
     * @return array
     */
    private function extendWcFields(array $fields, array $customFields, string $addressType): array
    {
        $additionalFields = [];

        foreach ($customFields as $field) {
            $id = sprintf('%s_%s', $addressType, $field->getId());

            $additionalFields[$id] = array_replace([
                'class'    => $field->getClass(),
                'label'    => $field->getTranslatedLabel(),
                'priority' => $field->getPriority(),
            ], $field->getLegacyCheckoutAttributes());
        }

        return array_merge($fields, $additionalFields);
    }

    /**
     * @param  \MyParcelNL\WooCommerce\WooCommerce\Address\Contract\AddressFieldInterface $field
     *
     * @return void
     * @throws \Exception
     */
    private function registerWcBlocksCheckoutField(AddressFieldInterface $field): void
    {
        woocommerce_register_additional_checkout_field([
            'id'         => $field->getId(),
            'label'      => $field->getTranslatedLabel(),
            'type'       => $field->getType(),
            'required'   => $field->isRequired(),
            'location'   => $field->getLocation(),
            'attributes' => $field->getBlocksCheckoutAttributes(),
            'index'      => $field->getIndex(),
        ]);
    }

    /**
     * @return void
     */
    private function registerWcBlocksCheckoutFields(): void
    {
        try {
            foreach ($this->getCustomFields() as $field) {
                $this->registerWcBlocksCheckoutField($field);
            }
        } catch (Exception $e) {
            Logger::error('Failed to register fields', ['error' => $e->getMessage()]);
        }
    }
}
