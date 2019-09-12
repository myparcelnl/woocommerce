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
            $fromDefaultToBpost = [
                "signature" => "bpost_signature",
                "insured"   => "insured",
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
                "myparcelbe_checkout"           => "delivery_options_enabled",
            ];

            foreach ($fromCheckoutToGeneral as $generalCarrierSettings) {
                $generalSettings[$generalCarrierSettings] = $checkoutSettings[$generalCarrierSettings];
            }

            return $generalSettings;
        }

        public function setFromCheckoutToBpostSettings($bpostSettings, $checkoutSettings)
        {
            $fromCheckoutToBpost = [
                'dropoff_days'        => 'bpost_drop_off_days',
                'cutoff_time'         => 'bpost_cutoff_time',
                'dropoff_delay'       => 'bpost_drop_off_delay',
                'deliverydays_window' => 'bpost_delivery_days_window',
                'signature_enabled'   => 'bpost_signature_enabled',
                'signature_title'     => 'bpost_signature_title',
                'signature_fee'       => 'bpost_signature_fee',
                'delivery_enabled'    => 'bpost_delivery_enabled',
                'pickup_enabled'      => 'bpost_pickup_enabled',
                'pickup_title'        => 'bpost_pickup_title',
                'pickup_fee'          => 'bpost_pickup_fee',
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
