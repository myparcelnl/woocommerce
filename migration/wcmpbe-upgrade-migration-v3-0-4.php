<?php

if (! defined('ABSPATH')) {
    exit;
}

if (class_exists('WCMPBE_Upgrade_Migration_v3_0_4')) {
    return new WCMPBE_Upgrade_Migration_v3_0_4();
}

/**
 * Migrates pre v3.0.4 settings
 */
class WCMPBE_Upgrade_Migration_v3_0_4
{
    public function __construct()
    {
        $old_settings = get_option('woocommerce_myparcelbe_checkout_settings');
        $new_settings = $old_settings;

        // Add/replace new settings
        $new_settings['use_split_address_fields'] = '1';

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

new WCMPBE_Upgrade_Migration_v3_0_4();
