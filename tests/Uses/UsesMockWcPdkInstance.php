<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Uses;

use MyParcelNL\Pdk\Tests\Bootstrap\MockPdkConfig;
use MyParcelNL\Pdk\Tests\Uses\UsesEachMockPdkInstance;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcPdkBootstrapper;

final class UsesMockWcPdkInstance extends UsesEachMockPdkInstance
{
    /**
     * @return void
     */
    public function afterEach(): void
    {
        MockWcPdkBootstrapper::reset();

        parent::afterEach();
    }

    /**
     * @throws \Exception
     */
    protected function setup(): void
    {
        $pluginFile = __DIR__ . '/../../woocommerce-myparcel.php';

        MockWcPdkBootstrapper::setConfig(MockPdkConfig::create($this->config));

        MockWcPdkBootstrapper::boot(
            'myparcelnl',
            'MyParcel [TEST]',
            '0.0.1',
            sprintf('%s/', dirname($pluginFile)),
            'https://my-site/'
        );
    }
}
