<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;
use MyParcelNL\WooCommerce\Migration\Pdk\OrdersMigration;
use MyParcelNL\WooCommerce\Migration\Pdk\ProductSettingsMigration;

/**
 * For scheduled migrations that need to be accessible through cron jobs.
 */
final class ScheduledMigrationHooks implements WordPressHooksInterface
{
    public function apply(): void
    {
        add_action('myparcelnl_migrate_order_to_pdk_5_0_0', [new OrdersMigration(), 'migrateOrder']);
        add_action(
            'myparcelnl_migrate_product_settings_to_pdk_5_0_0',
            [new ProductSettingsMigration(), 'migrateProductSettings']
        );
    }
}
