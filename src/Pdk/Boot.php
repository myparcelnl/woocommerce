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
    public static function setupPdk(string $version): ?Pdk
    {
        if (! self::$initialized) {
            self::$initialized = true;

            $pluginPath = untrailingslashit(plugin_dir_path(MyParcelNL::ROOT_FILE));
            $pluginUrl  = untrailingslashit(plugins_url('/', MyParcelNL::ROOT_FILE));

            self::$pdk = PdkFactory::create("$pluginPath/config/pdk.php", [
                'appInfo'  => value([
                    'name'    => MyParcelNL::NAME,
                    'title'   => 'MyParcel',
                    'path'    => $pluginPath,
                    'url'     => $pluginUrl,
                    'version' => $version,
                ]),

                // todo: support myparcel be
                'platform' => value('myparcel'),

                'userAgent' => value([
                    'MyParcelNL-WooCommerce' => $version,
                    'WooCommerce'            => defined('WOOCOMMERCE_VERSION') ? constant('WOOCOMMERCE_VERSION') : '?',
                    'WordPress'              => get_bloginfo('version'),
                ]),
            ]);
        }

        return self::$pdk;
    }
}
