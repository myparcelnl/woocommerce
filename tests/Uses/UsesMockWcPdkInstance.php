<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Uses;

use MyParcelNL\Pdk\Tests\Uses\UsesMockPdkInstance;
use MyParcelNL\WooCommerce\Pdk\WcPdkBootstrapper;

final class UsesMockWcPdkInstance extends UsesMockPdkInstance
{
    /**
     * @throws \Exception
     */
    protected function setup(): void
    {
        $pluginFile = __DIR__ . '/../../woocommerce-myparcel.php';

        WcPdkBootstrapper::boot(
            'myparcelnl',
            'MyParcel [TEST]',
            '0.0.1',
            sprintf('%s/', dirname($pluginFile)),
            'https://my-site/'
        );
    }
}
