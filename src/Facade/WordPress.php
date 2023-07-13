<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Facade;

use MyParcelNL\Pdk\Base\Facade;
use MyParcelNL\WooCommerce\Contract\WordPressServiceInterface;

/**
 * @method static bool getVersion() Get the WordPress version.
 * @implements WordPressServiceInterface
 */
final class WordPress extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return WordPressServiceInterface::class;
    }
}
