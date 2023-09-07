<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Factory;

use MyParcelNL\Pdk\Tests\Factory\Contract\FactoryInterface;
use MyParcelNL\Pdk\Tests\Factory\Exception\InvalidFactoryException;
use Throwable;

final class WpFactoryFactory
{
    /**
     * @param  class-string<\WC_Data> $class
     * @param  mixed                  ...$args
     *
     * @return \MyParcelNL\Pdk\Tests\Factory\Contract\FactoryInterface
     * @throws \MyParcelNL\Pdk\Tests\Factory\Exception\InvalidFactoryException
     */
    public static function create(string $class, ...$args): FactoryInterface
    {
        $factory = "{$class}_Factory";

        try {
            return new $factory(...$args);
        } catch (Throwable $e) {
            throw new InvalidFactoryException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
