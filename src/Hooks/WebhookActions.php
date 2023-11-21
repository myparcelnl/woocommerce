<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;

final class WebhookActions implements WordPressHooksInterface
{
    /**
     * @return void
     */
    public function apply(): void
    {
        $this->registerWebhookCallbackActions();
    }

    /**
     * @return void
     */
    private function registerWebhookCallbackActions(): void
    {
        $actions = get_option(Pdk::get('webhookAddActions'), []);

        if (empty($actions)) {
            return;
        }

        foreach ($actions as $hook => $callback) {
            add_action($hook, $callback);
        }

        update_option(Pdk::get('webhookAddActions'), $actions);
    }
}
