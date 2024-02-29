<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Integration;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;

final class WcBlocksLoader
{
    /**
     * @var \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry
     * @phpstan-ignore-next-line
     */
    private $integrationRegistry;

    /**
     * @return void
     */
    public function registerCheckoutBlocks(): void
    {
        /** @phpstan-ignore-next-line */
        $this->integrationRegistry->register(new DeliveryOptionsBlocksIntegration());
    }

    /**
     * @param  \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry $integrationRegistry
     *
     * @return void
     * @phpstan-ignore-next-line
     */
    public function setRegistry(IntegrationRegistry $integrationRegistry): void
    {
        $this->integrationRegistry = $integrationRegistry;
    }
}
