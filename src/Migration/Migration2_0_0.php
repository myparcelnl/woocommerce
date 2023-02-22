<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Migration;

/**
 * Migrates pre v2.0 settings. Pre-2.0.0 did not store a version number.
 */
class Migration2_0_0 implements MigrationInterface
{
    public function down(): void
    {
    }

    public function getVersion(): string
    {
        return '2.0.0';
    }

    public function up(): void
    {
        $oldSettings = get_option('wcmyparcel_settings');

        // map old key => newKey
        $generalSettingsKeys = [
            'api_key'              => 'api_key',
            'download_display'     => 'download_display',
            'email_tracktrace'     => 'email_tracktrace',
            'myaccount_tracktrace' => 'myaccount_tracktrace',
            'process'              => 'process_directly',
            'barcode_in_note'      => 'barcode_in_note',
            'keep_consignments'    => 'keep_shipments',
            'error_logging'        => 'error_logging',
        ];

        $generalSettings = [];

        foreach ($generalSettingsKeys as $oldKey => $newKey) {
            if (! empty($oldSettings[$oldKey])) {
                $generalSettings[$newKey] = $oldSettings[$oldKey];
            }
        }

        // auto_complete breaks down into:
        // order_status_automation & automatic_order_status
        if (! empty($oldSettings['auto_complete'])) {
            $generalSettings['order_status_automation'] = 1;
            $generalSettings['automatic_order_status']  = 'completed';
        }

        // map old key => newKey
        $defaultSettingsKeys = [
            'email'           => 'connect_email',
            'telefoon'        => 'connect_phone',
            'handtekening'    => 'signature',
            'retourbgg'       => 'return',
            'kenmerk'         => 'label_description',
            'verzekerd'       => 'insured',
            'verzekerdbedrag' => 'insured_amount',
        ];

        $defaultSettings = [];

        foreach ($defaultSettingsKeys as $oldKey => $newKey) {
            if (! empty($oldSettings[$oldKey])) {
                $defaultSettings[$newKey] = $oldSettings[$oldKey];
            }
        }
        // set custom insurance amount
        if (! empty($defaultSettings['insured']) && (int) $defaultSettings['insured_amount'] > 249) {
            $defaultSettings['insured_amount']        = 0;
            $defaultSettings['insured_amount_custom'] = $oldSettings['verzekerdbedrag'];
        }

        // add options
        update_option('woocommerce_myparcel_general_settings', $generalSettings);
        update_option('woocommerce_myparcel_export_defaults_settings', $defaultSettings);
    }
}

