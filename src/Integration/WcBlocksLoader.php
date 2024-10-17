<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Integration;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use MyParcelNL\Pdk\Facade\Pdk;

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
        /**
         * @type class-string<\MyParcelNL\WooCommerce\Integration\AbstractBlocksIntegration> $block
         */
        foreach (Pdk::get('wooCommerceBlocksCheckout') as $name => $block) {
            $this->integrationRegistry->register(new $block($name));
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
