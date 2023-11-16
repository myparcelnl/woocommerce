<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;

class RanWebhookActions implements WordPressHooksInterface
{
    /**
     * @return void
     */
    public function apply(): void
    {
        $this->removeRanActions();
    }

    /**
     * @return void
     */
    private function removeRanActions(): void
    {
        $actions = get_option(Pdk::get('webhookAddActions'), []);

        foreach (array_keys($actions) as $hook) {
            if (! did_action($hook)) {
                continue;
            }

            unset($actions[$hook]);
        }

        update_option(Pdk::get('webhookAddActions'), $actions);
    }
}
