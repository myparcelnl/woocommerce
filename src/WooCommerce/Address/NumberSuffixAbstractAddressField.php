<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\WooCommerce\Address;

use MyParcelNL\Pdk\Facade\Pdk;

class NumberSuffixAbstractAddressField extends AbstractAddressField
{
    /**
     * @return array<string, scalar>
     */
    public function getAttributes(): array
    {
        return [
            'maxlength' => Pdk::get('numberSuffixMaxLength'),
        ];
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return 'number_suffix';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'fieldNumberSuffix';
    }
}