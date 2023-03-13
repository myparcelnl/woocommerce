<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

use MyParcelNL\WooCommerce\Migration\Contract\MigrationInterface;

class Migration3_0_4 implements MigrationInterface
{
    public function down(): void
    {
    }

    public function getVersion(): string
    {
        return '3.0.4';
    }

    public function up(): void
    {
        $oldSettings = get_option('woocommerce_myparcel_checkout_settings');
        $newSettings = $oldSettings;

        // Add/replace new settings
        $newSettings['use_split_address_fields'] = '1';

        // Rename signed to signature for consistency
        $newSettings['signature_enabled'] = $oldSettings['signed_enabled'] ?? null;
        $newSettings['signature_title']   = $oldSettings['signed_title'] ?? null;
        $newSettings['signature_fee']     = $oldSettings['signed_fee'] ?? null;

        // Remove old settings
        unset($newSettings['signed_enabled'], $newSettings['signed_title'], $newSettings['signed_fee']);

        update_option('woocommerce_myparcel_checkout_settings', $newSettings);
    }
}
