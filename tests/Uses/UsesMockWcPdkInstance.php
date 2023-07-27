<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Uses;

use MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface;
use MyParcelNL\Pdk\Tests\Bootstrap\MockPdkConfig;
use MyParcelNL\Pdk\Tests\Uses\UsesEachMockPdkInstance;
use MyParcelNL\WooCommerce\Pdk\Plugin\Repository\PdkOrderRepository;
use MyParcelNL\WooCommerce\Pdk\Product\Repository\WcPdkProductRepository;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcPdkBootstrapper;
use MyParcelNL\WooCommerce\Tests\Mock\MockWpMeta;
use function DI\autowire;

final class UsesMockWcPdkInstance extends UsesEachMockPdkInstance
{
    /**
     * @return void
     */
    public function afterEach(): void
    {
        MockWcPdkBootstrapper::reset();
        MockWpMeta::clear();

        parent::afterEach();
    }

    /**
     * @throws \Exception
     */
    protected function setup(): void
    {
        $pluginFile = __DIR__ . '/../../woocommerce-myparcel.php';

        MockWcPdkBootstrapper::setConfig(MockPdkConfig::create($this->getConfig()));

        MockWcPdkBootstrapper::boot(
            'myparcelnl',
            'MyParcel [TEST]',
            '0.0.1',
            sprintf('%s/', dirname($pluginFile)),
            'https://my-site/'
        );
    }

    /**
     * @return array
     */
    private function getConfig(): array
    {
        return array_replace(
            $this->config,
            [
                PdkProductRepositoryInterface::class => autowire(WcPdkProductRepository::class),
                PdkOrderRepositoryInterface::class   => autowire(PdkOrderRepository::class),
            ]
        );
    }
}
