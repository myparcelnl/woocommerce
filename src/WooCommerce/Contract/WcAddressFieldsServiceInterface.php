<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\WooCommerce\Contract;

interface WcAddressFieldsServiceInterface
{
    /**
     * @return array<\MyParcelNL\WooCommerce\WooCommerce\Address\Contract\AddressFieldInterface>
     */
    public function getSeparateAddressFields(): array;

    /**
     * @return array<\MyParcelNL\WooCommerce\WooCommerce\Address\Contract\AddressFieldInterface>
     */
    public function getTaxFields(): array;
}