<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\WooCommerce\Address;

class StreetAddressField extends AbstractAddressField
{
    /**
     * @return string
     */
    public function getLabel(): string
    {
        return 'street';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'fieldStreet';
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return true;
    }
}
