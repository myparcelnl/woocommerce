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
            // TODO
        }

        /**
         * @param array $legacyCheckoutSettings
         * @param array $newDefaultSettings
         *
         * @return array
         */
        public function migrateDeliveryOptionsSettings(array $legacyCheckoutSettings, array $newDefaultSettings)
        {
            $bpostSettings         = $legacyCheckoutSettings;
            $singleCarrierDefaults = $newDefaultSettings;

            $bpostSettings         = $this->setFromCheckoutToBpostSettings($bpostSettings, $legacyCheckoutSettings);
            $singleCarrierDefaults = $this->setFromDefaultToBpostSettings($singleCarrierDefaults, $newDefaultSettings);

            return [$bpostSettings, $singleCarrierDefaults];
        }

        public function renamedSettings()
        {
            $general = [
                "custom_css" => "delivery_options_custom_css",
            ];
        }

        public function setFromDefaultToBpostSettings($bpostSettings, $defaultSettings)
        {
            $bpost = WCMP_Settings::SETTINGS_BPOST;

            $fromDefaultToBpost = [
                "insured"   => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_INSURED,
                "signature" => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_SIGNATURE_ENABLED,
            ];

            foreach ($fromDefaultToBpost as $carrierSettings) {
                $bpostSettings[$carrierSettings] = $defaultSettings[$carrierSettings];
            }

            return $bpostSettings;
        }

        /**
         * @param array $checkoutSettings
         * @param array $generalSettings
         *
         * @return array
         */
        public function setFromCheckoutToGeneralSettings(array $checkoutSettings, array $generalSettings)
        {
            $fromCheckoutToGeneral = [
                "use_split_address_fields"      => "use_split_address_fields",
                "delivery_options_display"      => "delivery_options_display",
                "checkout_position"             => "delivery_options_position",
                "header_delivery_options_title" => "header_delivery_options_title",
                "customs_css"                   => "header_delivery_options_title",
                "myparcelbe_checkout"           => WCMP_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
            ];

            foreach ($fromCheckoutToGeneral as $generalCarrierSettings) {
                $generalSettings[$generalCarrierSettings] = $checkoutSettings[$generalCarrierSettings];
            }

            return $generalSettings;
        }

        public function setFromCheckoutToBpostSettings($bpostSettings, $checkoutSettings)
        {
            $bpost = WCMP_Settings::SETTINGS_BPOST;

            $fromCheckoutToBpost = [
                "dropoff_days"        => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_DROP_OFF_DAYS,
                "cutoff_time"         => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_CUTOFF_TIME,
                "dropoff_delay"       => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_DROP_OFF_DELAY,
                "deliverydays_window" => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_DELIVERY_DAYS_WINDOW,
                "signature_enabled"   => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_SIGNATURE_ENABLED,
                "signature_title"     => "{$bpost}_" . WCMP_Settings::SETTING_SIGNATURE_TITLE,
                "signature_fee"       => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_SIGNATURE_FEE,
                "delivery_enabled"    => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "pickup_enabled"      => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_PICKUP_ENABLED,
                "pickup_title"        => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_PICKUP_TITLE,
                "pickup_fee"          => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_PICKUP_FEE,
            ];

            foreach ($fromCheckoutToBpost as $singleCarrierSettings => $multiCarrierSettings) {
                $bpostSettings[$multiCarrierSettings] = $checkoutSettings[$singleCarrierSettings];
                unset($bpostSettings[$singleCarrierSettings]);
            }

            return $bpostSettings;
        }
    }

endif;

new wcmp_installation_migration_v4_0_0();
