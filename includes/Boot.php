<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes;

use MyParcelNL\Pdk\Base\Factory\PdkFactory;
use MyParcelNL\Pdk\Base\Pdk;
use WCMYPA;

/**
 *
 */
class Boot
{
    /**
     * @var bool
     */
    private static $initialized = false;

    /**
     * @var \MyParcelNL\Pdk\Base\Pdk
     */
    private static $pdk;

    /**
     * @return void
     * @throws \Throwable
     */
    public static function setupPdk(WCMYPA $module): ?Pdk
    {
        if (! self::$initialized) {
            self::$initialized = true;
            self::$pdk         = PdkFactory::create($module->plugin_path() . '/config/pdk.php');
        }

        return self::$pdk;
    }
}
