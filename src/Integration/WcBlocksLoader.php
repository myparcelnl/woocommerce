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
     * @param  array<string, class-string<\MyParcelNL\WooCommerce\Integration\AbstractBlocksIntegration>> $blocks
     *
     * @return void
     */
    public function registerBlocks(array $blocks): void
    {
        foreach ($blocks as $name => $block) {
            $instance = new $block($name);

            $this->integrationRegistry->register($instance);
        }
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
