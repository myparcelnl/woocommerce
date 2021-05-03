<?php

use MyParcelNL\Sdk\src\Model\Consignment\BpostConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\PostNLConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\DPDConsignment;
use WPO\WC\MyParcelBE\Collections\SettingsCollection;

class WCMPBE_Initialize_Settings_Collection
{
    /**
     * Initialize the PHP 7.1+ settings collection.
     */
    public function initialize(): SettingsCollection
    {
        // Load settings
        $settings = new SettingsCollection();

        $settings->setSettingsByType($this->getOption("woocommerce_myparcelbe_general_settings"), "general");
        $settings->setSettingsByType($this->getOption("woocommerce_myparcelbe_checkout_settings"), "checkout");
        $settings->setSettingsByType($this->getOption("woocommerce_myparcelbe_export_defaults_settings"), "export");

        $settings->setSettingsByType(
            $this->getOption("woocommerce_myparcelbe_bpost_settings"),
            "carrier",
            BpostConsignment::CARRIER_NAME
        );
        $settings->setSettingsByType(
            $this->getOption("woocommerce_myparcelbe_dpd_settings"),
            "carrier",
            DPDConsignment::CARRIER_NAME
        );
        $settings->setSettingsByType(
            $this->getOption("woocommerce_myparcelbe_postnl_settings"),
            "carrier",
            PostNLConsignment::CARRIER_NAME
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
