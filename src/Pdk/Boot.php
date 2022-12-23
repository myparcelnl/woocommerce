<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk;

use MyParcelNL;
use MyParcelNL\Pdk\Base\Factory\PdkFactory;
use MyParcelNL\Pdk\Base\Pdk;
use function DI\value;

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
    public static function setupPdk(MyParcelNL $plugin): ?Pdk
    {
        if (! self::$initialized) {
            self::$initialized = true;
            self::$pdk         = PdkFactory::create($plugin->getPluginPath() . '/config/pdk.php', [
                'pluginName'    => value(MyParcelNL::NAME),
                'platform'      => 'myparcel',
                'userAgent'     => value([
                    'MyParcelNL-WooCommerce' => $plugin->version,
                    'Woocommerce'            => WOOCOMMERCE_VERSION,
                ]),
                'pluginRootDir' => value($plugin->getPluginPath()),
            ]);
        }

        return self::$pdk;
    }
}
