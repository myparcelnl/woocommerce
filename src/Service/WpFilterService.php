<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Service;

use InvalidArgumentException;
use MyParcelNL\Pdk\Base\Support\Arr;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Contract\WpFilterServiceInterface;

class WpFilterService implements WpFilterServiceInterface
{
    /**
     * @template-covariant T of mixed
     * @param  string $name
     * @param  T      $value
     * @param  mixed  ...$args
     *
     * @return T
     */
    public function apply(string $name, $value = null, ...$args)
    {
        $filter = Arr::get(Pdk::get('filters'), $name);

        if (! $filter) {
            throw new InvalidArgumentException("Filter '$name' not found in the PDK configuration.");
        }

        $filterDefaults = Pdk::get('filterDefaults');

        if (null === $value && Arr::has($filterDefaults, $name)) {
            $value = Arr::get($filterDefaults, $name);
        }

        return apply_filters($filter, $value, ...$args);
    }
}
