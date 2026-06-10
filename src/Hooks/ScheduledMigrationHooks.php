<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;
use MyParcelNL\WooCommerce\Migration\Migration6_5_1;
use MyParcelNL\WooCommerce\Migration\Pdk\OrdersMigration;
use MyParcelNL\WooCommerce\Migration\Pdk\ProductSettingsMigration;

/**
 * For scheduled migrations that need to be accessible through cron jobs.
 */
final class ScheduledMigrationHooks implements WordPressHooksInterface
{
    public function apply(): void
    {
        $this->addPdkMigrations();
        $this->addMigration651();
    }

    /**
     * Migrations for version 6.5.1
     *
     * @return void
     */
    private function addMigration651(): void
    {
        /** @var \MyParcelNL\WooCommerce\Migration\Migration6_5_1 $migration */
        $migration = Pdk::get(Migration6_5_1::class);

        add_action(
            Pdk::get('migrateAction_6_5_1_Orders'),
            [$migration, 'migrateOrderChunk']
        );

        add_action(
            Pdk::get('migrateAction_6_5_1_Shipments'),
            [$migration, 'migrateShipmentChunk']
        );
    }

    /**
     * Migrations for version 5.0.0
     *
     * @return void
     */
    private function addPdkMigrations(): void
    {
        /** @var \MyParcelNL\WooCommerce\Migration\Pdk\OrdersMigration $ordersMigration */
        $ordersMigration = Pdk::get(OrdersMigration::class);
        /** @var ProductSettingsMigration $productSettingsMigration */
        $productSettingsMigration = Pdk::get(ProductSettingsMigration::class);

        add_action(
            Pdk::get('migrateAction_5_0_0_Orders'),
            [$ordersMigration, 'migrateOrder']
        );

        add_action(
            Pdk::get('migrateAction_5_0_0_ProductSettings'),
            [$productSettingsMigration, 'migrateProductSettings']
        );
    }
}
