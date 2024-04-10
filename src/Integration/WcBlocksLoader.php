<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Integration;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;

final class WcBlocksLoader
{
    /**
     * @var \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry
     */
    private $integrationRegistry;

    /**
     * @return void
     */
    public function registerCheckoutBlocks(): void
    {
        $this->integrationRegistry->register(new DeliveryOptionsBlocksIntegration());
    }

    /**
     * @param  \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry $integrationRegistry
     *
     * @return void
     */
    public function setRegistry(IntegrationRegistry $integrationRegistry): void
    {
        $this->integrationRegistry = $integrationRegistry;
    }
}
