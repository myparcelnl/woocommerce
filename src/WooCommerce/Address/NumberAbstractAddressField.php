<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\WooCommerce\Address;

class NumberAbstractAddressField extends AbstractAddressField
{
    /**
     * @return string
     */
    public function getLabel(): string
    {
        return 'number';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'fieldNumber';
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return true;
    }
}

