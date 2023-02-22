<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin;

use MyParcelNL\Pdk\Plugin\Model\PdkShippingMethod;
use MyParcelNL\Pdk\Plugin\Repository\AbstractPdkShippingMethodRepository;

class WcShippingMethodRepository extends AbstractPdkShippingMethodRepository
{
    public function get($input): PdkShippingMethod
    {
        // TODO: Implement get() method.
        return new PdkShippingMethod();
    }
}
