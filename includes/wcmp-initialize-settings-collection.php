<?php

use MyParcelNL\Sdk\src\Model\Consignment\BpostConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\DPDConsignment;
use WPO\WC\MyParcelBE\Collections\SettingsCollection;

class WCMP_Initialize_Settings_Collection
{
    /**
     * Initialize the PHP 7.1+ settings collection.
     */
    public function initialize(): SettingsCollection
    {
        // Load settings
        $settings = new SettingsCollection();
        function getOption($option)
        {
            $option = get_option($option);
            if (! $option) {
                return [];
            }

            return $option;
        }

        $settings->setSettingsByType(getOption('woocommerce_myparcelbe_general_settings'), 'general');
        $settings->setSettingsByType(getOption('woocommerce_myparcelbe_export_defaults_settings'), 'export');
        $settings->setSettingsByType(
            getOption('woocommerce_myparcelbe_bpost_settings'),
            'carrier',
            BpostConsignment::CARRIER_NAME
        );
        $settings->setSettingsByType(
            getOption('woocommerce_myparcelbe_dpd_settings'),
            'carrier',
            DPDConsignment::CARRIER_NAME
        );

        return $settings;
    }
}
