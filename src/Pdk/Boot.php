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

            $pluginPath    = untrailingslashit(plugin_dir_path(MyParcelNL::ROOT_FILE));
            $pluginUrl     = untrailingslashit(plugins_url('/', MyParcelNL::ROOT_FILE));
            $pluginVersion = $plugin->version;

            self::$pdk = PdkFactory::create($pluginPath . '/config/pdk.php', [
                'platform'      => value('myparcel'),
                'pluginName'    => value(MyParcelNL::NAME),
                'pluginPath'    => value($pluginPath),
                'pluginUrl'     => value($pluginUrl),
                'pluginVersion' => value($pluginVersion),

                'userAgent' => value([
                    'MyParcelNL-WooCommerce' => $pluginVersion,
                    'Woocommerce'            => defined('WOOCOMMERCE_VERSION') ? WOOCOMMERCE_VERSION : '?',
                    'WordPress'              => get_bloginfo('version'),
                ]),
            ]);
        }

        return self::$pdk;
    }
}
