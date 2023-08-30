<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Facade;

use MyParcelNL\Pdk\Base\Facade;
use MyParcelNL\WooCommerce\Contract\WordPressServiceInterface;

/**
 * @method static bool getVersion() Get the WordPress version.
 * @method static void renderTable(array $rows) Renders a set of rows as a table.
 * @see \MyParcelNL\WooCommerce\Contract\WordPressServiceInterface
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
