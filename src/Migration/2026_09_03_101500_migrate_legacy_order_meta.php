<?php

declare(strict_types=1);

use MyParcelNL\Pdk\App\Installer\Migration\AbstractTimestampedMigration;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Migration\Migration6_5_1;

/**
 * Migration6_0_0 renamed options, but left order meta under the old namespaces.
 * Reuse the 6.5.1 cron chunks to copy and normalise that data before saving it.
 */
return new class extends AbstractTimestampedMigration {
    public function up(): void
    {
        if (! function_exists('wc_get_orders')) {
            return;
        }

        try {
            Pdk::get(Migration6_5_1::class)->updateLegacyOrderMeta();
        } catch (Throwable $exception) {
            $this->markFailed('Could not schedule legacy order meta migration; it will be retried.', [
                'exception' => $exception->getMessage(),
            ]);
        }
    }
};
