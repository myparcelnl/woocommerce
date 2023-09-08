<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Uses;

use MyParcelNL\Pdk\App\Order\Contract\PdkOrderNoteRepositoryInterface;
use MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface;
use MyParcelNL\Pdk\Base\Contract\CronServiceInterface;
use MyParcelNL\Pdk\Tests\Bootstrap\MockPdkConfig;
use MyParcelNL\Pdk\Tests\Uses\UsesEachMockPdkInstance;
use MyParcelNL\WooCommerce\Pdk\Plugin\Repository\PdkOrderRepository;
use MyParcelNL\WooCommerce\Pdk\Plugin\Repository\WcOrderNoteRepository;
use MyParcelNL\WooCommerce\Pdk\Product\Repository\WcPdkProductRepository;
use MyParcelNL\WooCommerce\Service\WpCronService;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcPdkBootstrapper;
use function DI\get;

final class UsesMockWcPdkInstance extends UsesEachMockPdkInstance
{
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
                CronServiceInterface::class => get(WpCronService::class),
                PdkOrderNoteRepositoryInterface::class => get(WcOrderNoteRepository::class),
                PdkOrderRepositoryInterface::class => get(PdkOrderRepository::class),
                PdkProductRepositoryInterface::class => get(WcPdkProductRepository::class),
            ]
        );
    }
}
