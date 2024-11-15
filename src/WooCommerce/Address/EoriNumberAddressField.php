<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\WooCommerce\Address;

class EoriNumberAddressField extends AbstractAddressField
{
    /**
     * @return string
     */
    public function getLabel(): string
    {
        return 'eori_number';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'fieldEoriNumber';
    }
}