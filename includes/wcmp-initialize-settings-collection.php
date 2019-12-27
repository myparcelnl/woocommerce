<?php

use MyParcelNL\Sdk\src\Model\Consignment\PostNLConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\DPDConsignment;
use WPO\WC\MyParcel\Collections\SettingsCollection;

class WCMP_Initialize_Settings_Collection
{
    /**
     * Initialize the PHP 7.1+ settings collection.
     */
    public function initialize(): SettingsCollection
    {
        // Load settings
        $settings = new SettingsCollection();

        $settings->setSettingsByType($this->getOption("woocommerce_myparcel_general_settings"), "general");
        $settings->setSettingsByType($this->getOption("woocommerce_myparcel_checkout_settings"), "checkout");
        $settings->setSettingsByType($this->getOption("woocommerce_myparcel_export_defaults_settings"), "export");

        $settings->setSettingsByType(
            $this->getOption("woocommerce_myparcel_bpost_settings"),
            "carrier",
            PostNLConsignment::CARRIER_NAME
        );
        $settings->setSettingsByType(
            $this->getOption("woocommerce_myparcel_dpd_settings"),
            "carrier",
            DPDConsignment::CARRIER_NAME
        );

        return $settings;
    }

    /**
     * @param $option
     *
     * @return array
     */
    private function getOption($option): array
    {
        $option = get_option($option);

        if (! $option) {
            return [];
        }

        return $option;
    }
}
