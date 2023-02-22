<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Service;

use MyParcelNL\Pdk\Base\Service\WeightService;

class WcWeightService extends WeightService
{
    /**
     * @param  int|float   $weight
     * @param  null|string $unit
     *
     * @return int
     */
    public function convertToGrams($weight, ?string $unit = null): int
    {
        return parent::convertToGrams($weight, $unit ?? get_option('woocommerce_weight_unit'));
    }
}
