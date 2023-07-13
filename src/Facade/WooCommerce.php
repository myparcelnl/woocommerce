<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Facade;

use MyParcelNL\Pdk\Base\Facade;
use MyParcelNL\WooCommerce\Contract\WooCommerceServiceInterface;

/**
 * @method static string getVersion() Get the WooCommerce version.
 * @method static bool isActive() Check if WooCommerce is active.
 * @method static bool isUsingHpos() Check if WooCommerce is using the HPOS feature.
 * @implements WooCommerceServiceInterface
 */
final class WooCommerce extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return WooCommerceServiceInterface::class;
    }
}
