<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\admin;

use MyParcelNL\Pdk\Service\WeightService as PdkWeightService;

class WeightService extends PdkWeightService
{
    /**
     * @param int|float   $weight
     * @param string|null $unit leave empty, woocommerce configured weight unit will be used
     *
     * @return int
     */
    public static function convertToGrams($weight, string $unit = null): int
    {
        return parent::convertToGrams($weight, strtolower(get_option('woocommerce_weight_unit')));
    }
}
