<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Facade\Filter;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;
use MyParcelNL\WooCommerce\WooCommerce\Address\Contract\AddressFieldInterface;
use RuntimeException;

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
        $customFields = $this->getCustomFields();

        foreach ($this->getApplicableCountries() as $countryCode) {
            foreach ($customFields as $field) {
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
    protected function getCustomFields(): array
    {
        $fields = Pdk::get('customFields')[$this->getName()] ?? null;

        if (null === $fields) {
            throw new RuntimeException("Custom fields for {$this->getName()} are not defined.");
        }

        return array_map(static function (string $class): AddressFieldInterface {
            return new $class();
        }, $fields);
    }

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
     * @param  array                                                                        $fields
     * @param  \MyParcelNL\WooCommerce\WooCommerce\Address\Contract\AddressFieldInterface[] $customFields
     * @param  string                                                                       $addressType
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
}
