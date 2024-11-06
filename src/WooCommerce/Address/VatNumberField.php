<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\WooCommerce\Address;

class VatNumberField extends AbstractAddressField
{
    /**
     * @return string
     */
    public function getLabel(): string
    {
        return 'vat_number';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'fieldVatNumber';
    }
}

