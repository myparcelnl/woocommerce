<?php

use migration\WCMP_Upgrade_Migration;

if (! defined('ABSPATH')) {
    exit;
}

if (class_exists('WCMP_Upgrade_Migration_v4_0_0')) {
    return new WCMP_Upgrade_Migration_v4_0_0();
}

/**
 * Migrates pre v4.0.0 settings
 */
class WCMP_Upgrade_Migration_v4_0_0 extends WCMP_Upgrade_Migration
{
    /**
     * @var array
     */
    private $newGeneralSettings = [];

    /**
     * @var array
     */
    private $newExportDefaultsSettings = [];

    /**
     * @var array
     */
    private $newCheckoutSettings = [];

    /**
     * @var array
     */
    private $newBpostSettings = [];

    /**
     * @var array
     */
    private $newDpdSettings = [];

    /**
     * @var void
     */
    private $oldCheckoutSettings;

    /**
     * @var void
     */
    private $oldExportDefaultsSettings;

    /**
     * @var void
     */
    private $oldGeneralSettings;

    public function __construct()
    {
        // test old settings
        $oldCheckoutSettings = [
            "use_split_address_fields"      => "1",
            "myparcelbe_checkout"           => "1",
            "checkout_display"              => "selected_methods",
            "checkout_position"             => "woocommerce_after_checkout_billing_form",
            "dropoff_days"                  => [
                "0",
                "1",
                "2",
                "3",
                "4",
                "5",
            ],
            "cutoff_time"                   => "17:00",
            "dropoff_delay"                 => "1",
            "deliverydays_window"           => "1",
            "header_delivery_options_title" => "Delivery options",
            "at_home_delivery_title"        => "Delivered at home or at work",
            "standard_title"                => "Standard delivery",
            "signature_enabled"             => "1",
            "signature_title"               => "Signature on delivery",
            "signature_fee"                 => "0.86",
            "pickup_enabled"                => "1",
            "pickup_title"                  => "bpost Pickup",
            "pickup_fee"                    => "-0.32",
            "custom_css"                    => "aaa",
        ];

        $oldExportDefaultsSettings = [
            "shipping_methods_package_types" => [
                "1" => [ // package
                         "flat_rate",
                         "free_shipping",
                ],
            ],
            "connect_email"                  => "1",
            "connect_phone"                  => "1",
            "signature"                      => "1",
            "insured"                        => "1",
            "label_description"              => "description",
        ];

        $oldGeneralSettings = [
            "api_key"                 => "07e8803229a171cece23dc9fc49186a3f87a7cad",
            "download_display"        => "download",
            "label_format"            => "A4",
            "print_position_offset"   => "1",
            "email_tracktrace"        => "1",
            "myaccount_tracktrace"    => "1",
            "process_directly"        => "1",
            "order_status_automation" => "1",
            "automatic_order_status"  => "on-hold",
            "keep_shipments"          => "1",
            "barcode_in_note"         => "1",
            "barcode_in_note_title"   => "Tracking code:",
            "error_logging"           => "1",
        ];

        $this->oldCheckoutSettings       = $oldCheckoutSettings;
        $this->oldExportDefaultsSettings = $oldExportDefaultsSettings;
        $this->oldGeneralSettings        = $oldGeneralSettings;

//        $this->oldCheckoutSettings       = get_option("woocommerce_myparcelbe_checkout_settings");
//        $this->oldExportDefaultsSettings = get_option("woocommerce_myparcelbe_export_defaults_settings");
//        $this->oldGeneralSettings        = get_option("woocommerce_myparcelbe_general_settings");

        $this->newCheckoutSettings       = $this->oldCheckoutSettings;
        $this->newExportDefaultsSettings = $this->oldExportDefaultsSettings;
        $this->newGeneralSettings        = $this->oldGeneralSettings;

        $this->migrateCheckoutSettings();
        $this->migrateExportDefaultsSettings();
        $this->migrateGeneralSettings();
    }

//    /**
//     * @param array $legacyCheckoutSettings
//     * @param array $newDefaultSettings
//     *
//     * @return array
//     */
//    public function migrateDeliveryOptionsSettings(array $legacyCheckoutSettings, array $newDefaultSettings)
//    {
//        $bpostSettings          = $legacyCheckoutSettings;
//        $exportDefaultsSettings = $newDefaultSettings;
//
//        $this->newBpostSettings = $this->setFromCheckoutToBpostSettings($bpostSettings, $legacyCheckoutSettings);
//        $exportDefaultsSettings =
//            $this->migrateFromExportDefaultsToBpostSettings($exportDefaultsSettings, $newDefaultSettings);
//
//        return [$bpostSettings, $exportDefaultsSettings];
//    }
//
//    public function renamedSettings()
//    {
//        $general = [
//            "custom_css" => "delivery_options_custom_css",
//        ];
//    }

//    public function migrateFromExportDefaultsToBpostSettings($bpostSettings, $exportDefaultsSettings)
//    {
//        $bpost = WCMP_Settings::SETTINGS_BPOST;
//
//        $fromExportDefaultsToBpost = [
//            "insured"   => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_INSURED,
//            "signature" => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_SIGNATURE_ENABLED,
//        ];
//
//        foreach ($fromExportDefaultsToBpost as $exportDefaultSetting => $bpostSetting) {
//            $bpostSettings[$bpostSetting] = $exportDefaultsSettings[$exportDefaultSetting];
//            unset($exportDefaultSettings[$exportDefaultSetting]);
//        }
//
//        return $bpostSettings;
//    }

//    /**
//     * @param array $checkoutSettings
//     *
//     * @return array
//     */
//    public function setFromCheckoutToGeneralSettings(array $checkoutSettings): array
//    {
//        // todo: important note, I moved these to to something that's still called checkout instead of general
//        $renamedCheckoutSettings = [
//            "checkout_position"   => WCMP_Settings::SETTING_DELIVERY_OPTIONS_POSITION,
//            "custom_css"          => WCMP_Settings::SETTING_DELIVERY_OPTIONS_CUSTOM_CSS,
//            "myparcelbe_checkout" => WCMP_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
//        ];
//
//        foreach ($renamedCheckoutSettings as $oldSetting => $newSetting) {
//            $checkoutSettings[$newSetting] = $checkoutSettings[$oldSetting];
//            unset($checkoutSettings[$oldSetting]);
//        }
//
//        return $checkoutSettings;
//    }

//    public function setFromCheckoutToBpostSettings()
//    {
//        $bpost = WCMP_Settings::SETTINGS_BPOST;
//
//        $fromCheckoutToBpost = [
//            "dropoff_days"        => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_DROP_OFF_DAYS,
//            "cutoff_time"         => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_CUTOFF_TIME,
//            "dropoff_delay"       => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_DROP_OFF_DELAY,
//            "deliverydays_window" => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_DELIVERY_DAYS_WINDOW,
//            "signature_enabled"   => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_SIGNATURE_ENABLED,
//            "signature_title"     => "{$bpost}_" . WCMP_Settings::SETTING_SIGNATURE_TITLE,
//            "signature_fee"       => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_SIGNATURE_FEE,
//            "delivery_enabled"    => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
//            "pickup_enabled"      => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_PICKUP_ENABLED,
//            "pickup_title"        => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_PICKUP_TITLE,
//            "pickup_fee"          => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_PICKUP_FEE,
//        ];
//
//        foreach ($fromCheckoutToBpost as $checkoutSetting => $bpostSetting) {
//            $this->newBpostSettings[$bpostSetting] = $this->oldCheckoutSettings[$checkoutSetting];
//            unset($this->oldCheckoutSettings[$checkoutSetting]);
//        }
//
//        return $this->newBpostSettings;
//    }

    private function migrateCheckoutSettings(): void
    {
        $this->newCheckoutSettings = $this->migrateSettings(
            self::getCheckoutMap(),
            $this->newCheckoutSettings
        );

        $this->newBpostSettings = $this->migrateSettings(
            self::getCheckoutBpostMap(),
            $this->newBpostSettings,
            $this->oldCheckoutSettings
        );

        // Remove the settings that were moved to bpost from checkout
        $this->newCheckoutSettings = $this->removeOldSettings(
            self::getCheckoutBpostMap(),
            $this->newCheckoutSettings
        );
    }

    private function migrateExportDefaultsSettings(): void
    {
        $this->newExportDefaultsSettings = $this->migrateSettings(
            self::getExportDefaultsMap(),
            $this->newExportDefaultsSettings,
            $this->oldExportDefaultsSettings
        );

        $this->newBpostSettings = $this->migrateSettings(
            self::getExportDefaultsBpostMap(),
            $this->newBpostSettings,
            $this->oldExportDefaultsSettings
        );

        $this->newExportDefaultsSettings = $this->removeOldSettings(
            self::getExportDefaultsBpostMap(),
            $this->newExportDefaultsSettings
        );

        echo "<table>";
        echo "<tr><td><pre>";
        var_dump("oldExportDefaultsSettings");
        print_r($this->oldExportDefaultsSettings);
        echo "</pre><td>";
        echo "<td><pre>";
        var_dump("newExportDefaultsSettings");
        print_r($this->newExportDefaultsSettings);
        echo "</pre><td>";
        echo "<td><pre>";
        var_dump("newBpostSettings");
        print_r($this->newBpostSettings);
        echo "</pre><td><tr>";
        echo "</table>";
    }

    private function migrateGeneralSettings(): void
    {
        // Rename existing settings
        $this->newGeneralSettings = $this->migrateSettings(
            self::getGeneralMap(),
            $this->newGeneralSettings
        );

        echo "<table>";
        echo "<tr><td><pre>";
        var_dump("oldGeneralSettings");
        print_r($this->oldGeneralSettings);
        echo "</pre><td>";
        echo "<td><pre>";
        var_dump("newGeneralSettings");
        print_r($this->newGeneralSettings);
        echo "</pre><td><tr>";
        echo "</table>";
    }

    /**
     * @return array
     */
    private static function getCheckoutBpostMap(): array
    {
        $bpost = WCMP_Settings::SETTINGS_BPOST;

        return [
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
    }

    /**
     * @return array
     */
    private static function getCheckoutMap(): array
    {
        return [
            "checkout_position"   => WCMP_Settings::SETTING_DELIVERY_OPTIONS_POSITION,
            "custom_css"          => WCMP_Settings::SETTING_DELIVERY_OPTIONS_CUSTOM_CSS,
            "myparcelbe_checkout" => WCMP_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
        ];
    }

    /**
     * @return array
     */
    private static function getGeneralMap(): array
    {
        return [
            "email_tracktrace"     => WCMP_Settings::SETTING_TRACK_TRACE_EMAIL,
            "myaccount_tracktrace" => WCMP_Settings::SETTING_TRACK_TRACE_MY_ACCOUNT,
        ];
    }

    /**
     * Move insured and signature to bpost because these settings are bpost specific and there is no dpd equivalent.
     *
     * @return array
     */
    private static function getExportDefaultsBpostMap(): array
    {
        $bpost = WCMP_Settings::SETTINGS_BPOST;

        return [
            "insured"   => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED,
            "signature" => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_DEFAULT_EXPORT_SIGNATURE,
        ];
    }

    /**
     * @return array
     */
    private static function getExportDefaultsMap(): array
    {
        return [];
    }
}

return new WCMP_Upgrade_Migration_v4_0_0();
