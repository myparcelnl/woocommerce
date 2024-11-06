<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Contract;

interface WpFilterServiceInterface
{
    /**
     * @template T of mixed
     * @param  string $name
     * @param  T      $value
     * @param  mixed  ...$args
     *
     * @return T
     */
    public function apply(string $name, $value, ...$args);
}