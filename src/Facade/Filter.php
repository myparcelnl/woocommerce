<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Facade;

use MyParcelNL\Pdk\Base\Facade;
use MyParcelNL\WooCommerce\Contract\WpFilterServiceInterface;

/**
 * @method static mixed apply(string $name, ...$args)
 * @see \MyParcelNL\WooCommerce\Contract\WpFilterServiceInterface
 */
class Filter extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return WpFilterServiceInterface::class;
    }
}
