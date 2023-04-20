<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Service;

use MyParcelNL\Pdk\Base\Support\Arr;
use MyParcelNL\Pdk\Facade\Pdk;

class WpFilterService
{
    /**
     * @param  string $name
     * @param  mixed  ...$args
     *
     * @return mixed
     */
    public function apply(string $name, ...$args)
    {
        $filter = Arr::get(Pdk::get('filters'), $name);
        $value  = Arr::get(Pdk::get('filterDefaults'), $name);

        return apply_filters($filter, $value, ...$args);
    }
}
