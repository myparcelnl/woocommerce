<?php

declare(strict_types=1);

use MyParcelNL\Pdk\App\Installer\Migration\AbstractTimestampedMigration;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Migration\Pdk\OrdersMigration;

/**
 * Before this fix, OrdersMigration found no orders on a shop that upgraded from 4.x, and could not read
 * the JSON strings that 4.x stored. Migration5_0_0 is already marked as applied on those shops, so
 * schedule the order migration again. It skips each order that already has shipments in the current
 * format, so labels created after the upgrade stay as they are.
 */
return new class extends AbstractTimestampedMigration {
    public function up(): void
    {
        if (! function_exists('wc_get_orders')) {
            return;
        }

        try {
            Pdk::get(OrdersMigration::class)->up();
        } catch (Throwable $exception) {
            $this->markFailed('Could not schedule the migration of 4.x orders; it will be retried.', [
                'exception' => $exception->getMessage(),
            ]);
        }
    }
};
