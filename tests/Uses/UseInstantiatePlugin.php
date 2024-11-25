<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Uses;

use MyParcelNL\Pdk\Tests\Uses\BaseMock;
use MyParcelNL\WooCommerce\PluginLoader;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcPdkBootstrapper;

final class UseInstantiatePlugin implements BaseMock
{
    public function afterEach(): void
    {
        MockWcPdkBootstrapper::reset();
    }

    public function beforeEach(): void
    {
        define('MYPARCELNL_FILE', __FILE__ . '../../woocommerce-myparcel.php');
        define('MYPARCELNL_DIR', __DIR__ . '/../../');

        $pluginLoader = new PluginLoader();
        $pluginLoader->load();
    }
}
