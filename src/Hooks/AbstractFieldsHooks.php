<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Facade\Language;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Facade\Filter;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;

abstract class AbstractFieldsHooks implements WordPressHooksInterface
{
    /**
     * Generate a namespaced ID for a block field.
     * @param mixed $name
     * @return string
     */
    protected function getBlockFieldId($name): string
    {
        return sprintf('myparcelnl/%s', Pdk::get($name));
    }

    /**
     * @param  string $form
     * @param  string $name
     * @param  string $label
     * @param  array  $additionalFields
     *
     * @return array[]
     */
    protected function createField(
        string $form,
        string $name,
        string $label,
        array  $additionalFields = []
    ): array {
        return [
            sprintf('%s_%s', $form, Pdk::get($name)) => array_merge(
                [
                    'class'    => Filter::apply("{$name}Class"),
                    'label'    => Language::translate($label),
                    'priority' => Filter::apply("{$name}Priority"),
                ],
                $additionalFields
            ),
        ];
    }

    /**
     * Returns an array suitable for woocommerce_register_additional_checkout_field().
     *
     * @param string $name
     * @param string $label
     * @param string $type The field type: either text, checkbox, select.
     * @param array $additionalOptions @see https://developer.woocommerce.com/docs/block-development/cart-and-checkout-blocks/additional-checkout-fields/#options
     * @param array $attributes An array of HTML attributes to add to the field.
     * @return void
     */
    protected function createBlocksCheckoutAddressField(
        string $name,
        string $label,
        string $type = 'text',
        array $additionalOptions = [],
        array $attributes = []
    ) {

        return array_merge([
            'id' => $this->getBlockFieldId($name),
            'label' => Language::translate($label),
            'location' => 'address',
            'type' => $type,
        ], $additionalOptions, $attributes);
    }

    /**
     * Creates the selectors for the given field.
     *
     * @param  string $field
     *
     * @return array
     * @example $this->createSelectorFor('fieldVatNumber') returns:
     *  [
     *      'fieldVatNumber' => '#billing_field_vat_number_field,#shipping_field_vat_number_field',
     *  ]
     */
    protected function createSelectorFor(string $field): array
    {
        $resolvedField = Pdk::get($field);

        return [
            $resolvedField => implode(
                ', ',
                array_map(
                    static function (string $addressType) use ($resolvedField): string {
                        return sprintf('#%s_%s_field', $addressType, $resolvedField);
                    },
                    Pdk::get('wcAddressTypes')
                )
            ),
        ];
    }
}
