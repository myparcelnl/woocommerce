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
        $filter         = Arr::get(Pdk::get('filters'), $name);
        $filterDefaults = Pdk::get('filterDefaults');

        if (Arr::has($filterDefaults, $name)) {
            array_unshift($args, Arr::get($filterDefaults, $name));
        }

        return apply_filters($filter, ...$args);
    }
}
