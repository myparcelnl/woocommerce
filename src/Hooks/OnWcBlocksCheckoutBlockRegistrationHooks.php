<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;

class OnWcBlocksCheckoutBlockRegistrationHooks implements WordPressHooksInterface
{
    public function apply(): void
    {
        $this->registerBlocks(Pdk::get('wooCommerceBlocks'));
    }

    /**
     * @param  array<string, class-string<\MyParcelNL\WooCommerce\WooCommerce\Blocks\AbstractBlocksIntegration>> $blocks
     *
     * @return void
     */
    public function registerBlocks(array $blocks): void
    {
        $integrationRegistry = new IntegrationRegistry();

        foreach ($blocks as $name => $block) {
            $instance = new $block($name);

            $integrationRegistry->register($instance);
        }
    }
}
