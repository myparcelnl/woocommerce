<?php

if (! defined('ABSPATH')) {
    exit;
}

if (class_exists('WCMP_Installation_Migration_v2_0_0')) {
    return new WCMP_Installation_Migration_v2_0_0();
}

/**
 * Migrates pre v2.0 settings
 */
class WCMP_Installation_Migration_v2_0_0
{
    /**
     * Copy old settings if available (pre 2.0 didn't store the version, so technically, this is a new install)
     */
    public function __construct()
    {
        $old_settings = get_option('wcmyparcel_settings');

        // map old key => new_key
        $general_settings_keys = [
            'api_key'              => 'api_key',
            'download_display'     => 'download_display',
            'email_tracktrace'     => 'email_tracktrace',
            'myaccount_tracktrace' => 'myaccount_tracktrace',
            'process'              => 'process_directly',
            'barcode_in_note'      => 'barcode_in_note',
            'keep_consignments'    => 'keep_shipments',
            'error_logging'        => 'error_logging',
        ];

        $general_settings = [];
        foreach ($general_settings_keys as $old_key => $new_key) {
            if (! empty($old_settings[$old_key])) {
                $general_settings[$new_key] = $old_settings[$old_key];
            }
        }
        // auto_complete breaks down into:
        // order_status_automation & automatic_order_status
        if (! empty($old_settings['auto_complete'])) {
            $general_settings['order_status_automation'] = 1;
            $general_settings['automatic_order_status']  = 'completed';
        }

        // map old key => new_key
        $defaults_settings_keys = [
            'email'           => 'connect_email',
            'telefoon'        => 'connect_phone',
            'handtekening'    => 'signature',
            'retourbgg'       => 'return',
            'kenmerk'         => 'label_description',
            'verzekerd'       => 'insured',
            'verzekerdbedrag' => 'insured_amount',
        ];
        $defaults_settings      = [];
        foreach ($defaults_settings_keys as $old_key => $new_key) {
            if (! empty($old_settings[$old_key])) {
                $defaults_settings[$new_key] = $old_settings[$old_key];
            }
        }
        // set custom insurance amount
        if (! empty($defaults_settings['insured']) && (int) $defaults_settings['insured_amount'] > 249) {
            $defaults_settings['insured_amount']        = 0;
            $defaults_settings['insured_amount_custom'] = $old_settings['verzekerdbedrag'];
        }

        // add options
        update_option('woocommerce_myparcel_general_settings', $general_settings);
        update_option('woocommerce_myparcel_export_defaults_settings', $defaults_settings);
    }
}

new WCMP_Installation_Migration_v2_0_0();
