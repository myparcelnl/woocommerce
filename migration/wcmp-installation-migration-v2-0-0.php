<?php

if (! defined('ABSPATH')) exit;

if ( ! class_exists('wcmp_installation_migration_v2_0_0')) :

/**
 * Migrates pre v2.0 settings
 */
class wcmp_installation_migration_v2_0_0
{
    /**
     * Copy old settings if available (pre 2.0 didn't store the version, so technically, this is a new install)
     */
    public function __construct()
    {
        // map old key => new_key
        $general_settings_keys = [
            'api_key'              => WCMP_Settings::SETTING_API_KEY,
            'download_display'     => WCMP_Settings::SETTING_DOWNLOAD_DISPLAY,
            'email_tracktrace'     => WCMP_Settings::SETTING_EMAIL_TRACK_TRACE,
            'myaccount_tracktrace' => WCMP_Settings::SETTING_MY_ACCOUNT_TRACK_TRACE,
            'process'              => WCMP_Settings::SETTING_PROCESS_DIRECTLY,
            'barcode_in_note'      => WCMP_Settings::SETTING_BARCODE_IN_NOTE,
            'keep_consignments'    => WCMP_Settings::SETTING_KEEP_SHIPMENTS,
            'error_logging'        => WCMP_Settings::SETTING_ERROR_LOGGING,
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
        update_option('woocommerce_myparcelbe_general_settings', $general_settings);
        update_option('woocommerce_myparcelbe_export_defaults_settings', $defaults_settings);
    }
}

endif;

new wcmp_installation_migration_v2_0_0();
