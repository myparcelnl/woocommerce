<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\WooCommerce\Service;

use MyParcelNL\WooCommerce\WooCommerce\Address\EoriNumberField;
use MyParcelNL\WooCommerce\WooCommerce\Address\NumberAbstractAddressField;
use MyParcelNL\WooCommerce\WooCommerce\Address\NumberSuffixAbstractAddressField;
use MyParcelNL\WooCommerce\WooCommerce\Address\StreetAbstractAddressField;
use MyParcelNL\WooCommerce\WooCommerce\Address\VatNumberField;
use MyParcelNL\WooCommerce\WooCommerce\Contract\WcAddressFieldsServiceInterface;

class WcAddressFieldsService implements WcAddressFieldsServiceInterface
{
    /**
     * @return array<\MyParcelNL\WooCommerce\WooCommerce\Address\Contract\AddressFieldInterface>
     */
    public function getSeparateAddressFields(): array
    {
        return [
            new StreetAbstractAddressField(),
            new NumberAbstractAddressField(),
            new NumberSuffixAbstractAddressField(),
        ];
    }

    /**
     * @return array<\MyParcelNL\WooCommerce\WooCommerce\Address\Contract\AddressFieldInterface>
     */
    public function getTaxFields(): array
    {
        return [
            new VatNumberField(),
            new EoriNumberField(),
        ];
    }
}