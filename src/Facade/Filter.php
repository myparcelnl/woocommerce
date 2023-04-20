<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Facade;

use MyParcelNL\Pdk\Base\Facade;
use MyParcelNL\WooCommerce\Service\WpFilterService;

/**
 * @method static mixed apply(string $name, ...$args)
 * @implements \MyParcelNL\WooCommerce\Service\WpFilterService
 */
class Filter extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return WpFilterService::class;
    }
}
