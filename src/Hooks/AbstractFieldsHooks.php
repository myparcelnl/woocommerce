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
