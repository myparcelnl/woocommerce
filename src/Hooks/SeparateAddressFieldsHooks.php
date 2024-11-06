<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use Exception;
use MyParcelNL\Pdk\Base\Support\Arr;
use MyParcelNL\Pdk\Facade\Logger;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Facade\Settings;
use MyParcelNL\Pdk\Settings\Model\CheckoutSettings;
use MyParcelNL\WooCommerce\Contract\WooCommerceServiceInterface;
use MyParcelNL\WooCommerce\Facade\Filter;
use MyParcelNL\WooCommerce\WooCommerce\Address\Contract\AddressFieldInterface;
use MyParcelNL\WooCommerce\WooCommerce\Contract\WcAddressFieldsServiceInterface;

/**
 * Adds separate address fields to the WooCommerce order fields.
 */
class SeparateAddressFieldsHooks extends AbstractFieldsHooks
{
    /**
     * @var \MyParcelNL\WooCommerce\WooCommerce\Contract\WcAddressFieldsServiceInterface
     */
    private $addressFieldsService;

    /**
     * @var \MyParcelNL\WooCommerce\Contract\WooCommerceServiceInterface
     */
    private $wooCommerceService;

    /**
     * @param  \MyParcelNL\WooCommerce\Contract\WooCommerceServiceInterface                 $wooCommerceService
     * @param  \MyParcelNL\WooCommerce\WooCommerce\Contract\WcAddressFieldsServiceInterface $addressFieldsService
     */
    public function __construct(
        WooCommerceServiceInterface     $wooCommerceService,
        WcAddressFieldsServiceInterface $addressFieldsService
    ) {
        $this->wooCommerceService   = $wooCommerceService;
        $this->addressFieldsService = $addressFieldsService;
    }

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
        return array_reduce(
            $this->addressFieldsService->getSeparateAddressFields(),
            static function (array $allFields, AddressFieldInterface $field) {
                $allFields[$field->getId()] = [
                    'required' => $field->isRequired(),
                    'hidden'   => false,
                ];

                return $allFields;
            },
            $fields
        );
    }

    /**
     * @param  array $locale
     *
     * @return array
     */
    public function extendLocaleWithSeparateAddressFields(array $locale): array
    {
        $usesSeparateAddressFields = $this->usesSeparateAddressFields();
        $isUsingBlocksCheckout     = $this->wooCommerceService->isUsingBlocksCheckout();
        $separateAddressFields     = $this->addressFieldsService->getSeparateAddressFields();

        // When using blocks checkout, don't hide address 1. It's not possible to modify its value otherwise.
        $hideAddress1 = $usesSeparateAddressFields && is_checkout() && ! $isUsingBlocksCheckout;

        foreach (Pdk::get('countriesWithSeparateAddressFields') as $countryCode) {
            Arr::set($locale[$countryCode], sprintf('%s.required', Pdk::get('fieldAddress1')), true);
            Arr::set($locale[$countryCode], sprintf('%s.hidden', Pdk::get('fieldAddress1')), $hideAddress1);

            foreach ($separateAddressFields as $field) {
                $locale[$countryCode][$field->getId()] = [
                    'required' => $field->isRequired() && $usesSeparateAddressFields,
                    'hidden'   => ! $usesSeparateAddressFields,
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
        $selectors = array_map(function (AddressFieldInterface $field) {
            return $this->createSelectorFor($field->getId());
        }, $this->addressFieldsService->getSeparateAddressFields());

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

        foreach ($this->addressFieldsService->getSeparateAddressFields() as $field) {
            $id = sprintf('%s_%s', $form, $field->getName());

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
            'id'         => $field->getBlocksCheckoutId(),
            'label'      => $field->getTranslatedLabel(),
            /**
             * @see \Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields::$supported_field_types
             */
            'type'       => 'text',
            'required'   => $field->isRequired(),
            'location'   => 'address',
            'attributes' => $field->getBlocksCheckoutAttributes(),
            /**
             * In blocks checkout, the order is determined by 'index' and not 'priority'.
             */
            'index'      => Filter::apply(sprintf('%sIndex', $field->getName())),
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
            foreach ($this->addressFieldsService->getSeparateAddressFields() as $field) {
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
}
