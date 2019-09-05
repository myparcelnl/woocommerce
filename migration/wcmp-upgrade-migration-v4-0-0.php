<?php

if (! defined('ABSPATH')) {
    exit;
}

if (! class_exists('wcmp_installation_migration_v4_0_0')) :

    /**
     * Migrates pre v4.0.0 settings
     */
    class wcmp_installation_migration_v4_0_0
    {
        public function __construct()
        {
            $old_settings = get_option('woocommerce_myparcelbe_checkout_settings');
            $new_settings = $old_settings;

            // Rename signed to signature for consistency
            $new_settings['signature_enabled'] = $old_settings['signed_enabled'];
            $new_settings['signature_title']   = $old_settings['signed_title'];
            $new_settings['signature_fee']     = $old_settings['signed_fee'];

            // Remove old settings
            unset($new_settings['signed_enabled']);
            unset($new_settings['signed_title']);
            unset($new_settings['signed_fee']);

            update_option('woocommerce_myparcelbe_checkout_settings', $new_settings);
        }
    }

endif;

new wcmp_installation_migration_v4_0_0();
