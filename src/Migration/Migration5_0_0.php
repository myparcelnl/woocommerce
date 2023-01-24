<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\WooCommerce\Migration\Pdk\ProductSettingsMigration;
use MyParcelNL\WooCommerce\Migration\Pdk\SettingsMigration;
use MyParcelNL\WooCommerce\Migration\Pdk\OrdersMigration;

/**
 * The PDK upgrade.
 */
class Migration5_0_0 implements Migration
{
    public function down(): void
    {
        // Implement down() method.
    }

    public function getVersion(): string
    {
        return '5.0.0';
    }

    public function up(): void
    {
        $settingsMigration = new SettingsMigration();
        $settingsMigration->run();

        $ordersMigration = new OrdersMigration();
        $ordersMigration->run();

        $productSettingsMigration = new ProductSettingsMigration();
        $productSettingsMigration->run();
    }
}
