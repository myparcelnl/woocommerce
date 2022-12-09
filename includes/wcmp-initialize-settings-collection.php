<?php

declare(strict_types=1);

use MyParcelNL\Sdk\src\Model\Carrier\AbstractCarrier;
use WPO\WC\MyParcel\Collections\SettingsCollection;

defined('ABSPATH') or die();

class WCMP_Initialize_Settings_Collection
{
    /**
     * @return \WPO\WC\MyParcel\Collections\SettingsCollection
     */
    public function initialize(): SettingsCollection
    {
        $settings = SettingsCollection::getInstance();

        $settings->setSettingsByType($this->getOption('woocommerce_myparcel_general_settings'), 'general');
        $settings->setSettingsByType($this->getOption('woocommerce_myparcel_checkout_settings'), 'checkout');
        $settings->setSettingsByType($this->getOption('woocommerce_myparcel_export_defaults_settings'), 'export');

        foreach (WCMP_Data::getCarriers() as $carrier) {
            $this->setCarrierSettings($settings, new $carrier());
        }

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

    /**
     * @param  \WPO\WC\MyParcel\Collections\SettingsCollection   $settings
     * @param  \MyParcelNL\Sdk\src\Model\Carrier\AbstractCarrier $carrier
     *
     * @return void
     */
    private function setCarrierSettings(SettingsCollection $settings, AbstractCarrier $carrier): void
    {
        $carrierName = $carrier->getName();

        $settings->setSettingsByType(
            $this->getOption(sprintf("woocommerce_myparcel_%s_settings", $carrierName)),
            'carrier',
            $carrierName
        );
    }
}
