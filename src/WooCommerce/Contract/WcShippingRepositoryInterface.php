<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\WooCommerce\Contract;

use MyParcelNL\Pdk\Base\Support\Collection;

interface WcShippingRepositoryInterface
{
    /**
     * @return \MyParcelNL\Pdk\Base\Support\Collection
     */
    public function getShippingClasses(): Collection;

    /**
     * @return \MyParcelNL\Pdk\Base\Support\Collection<\WC_Shipping_Method>
     */
    public function getShippingMethods(): Collection;
}
