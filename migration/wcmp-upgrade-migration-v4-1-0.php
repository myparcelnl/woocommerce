<?php

use migration\WCMP_Upgrade_Migration;

if (! defined('ABSPATH')) {
    exit;
}

if (class_exists('WCMP_Upgrade_Migration_v4_1_0')) {
    return new WCMP_Upgrade_Migration_v4_1_0();
}

/**
 * Migrates pre v4.1.0 settings
 */
class WCMP_Upgrade_Migration_v4_1_0 extends WCMP_Upgrade_Migration
{
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
    private $newPostNlSettings = [];

    /**
     * @var void
     */
    private $oldCheckoutSettings;

    /**
     * @var void
     */
    private $oldExportDefaultsSettings;

    public function __construct()
    {
        parent::__construct();
    }

    protected function import(): void
    {
        require_once(WCMYPA()->plugin_path() . "/includes/vendor/autoload.php");
        require_once(WCMYPA()->plugin_path() . '/includes/admin/settings/class-wcmypa-settings.php');
    }

    protected function migrate(): void
    {
        $this->oldCheckoutSettings       = get_option("woocommerce_myparcel_checkout_settings");
        $this->oldExportDefaultsSettings = get_option("woocommerce_myparcel_export_defaults_settings");

        $this->newCheckoutSettings       = $this->oldCheckoutSettings;
        $this->newExportDefaultsSettings = $this->oldExportDefaultsSettings;

        $this->migrateCheckoutSettings();
        $this->migrateExportDefaultsSettings();
    }

    protected function setOptionSettingsMap(): void
    {
        $this->optionSettingsMap = [
            "woocommerce_myparcel_checkout_settings"        => $this->newCheckoutSettings,
            "woocommerce_myparcel_export_defaults_settings" => $this->newExportDefaultsSettings,
            "woocommerce_myparcel_postnl_settings"          => $this->newPostNlSettings,
        ];
    }

    private function migrateCheckoutSettings(): void
    {
        // Migrate existing checkout settings to new keys
        $this->newCheckoutSettings = $this->migrateSettings(
            self::getCheckoutMap(),
            $this->newCheckoutSettings
        );

        // Migrate old checkout settings to PostNL
        $this->newPostNlSettings = $this->migrateSettings(
            self::getCheckoutPostnlMap(),
            $this->newPostNlSettings,
            $this->oldCheckoutSettings
        );

        // Remove the settings that were moved to PostNL from checkout
        $this->newCheckoutSettings = $this->removeOldSettings(
            self::getCheckoutPostnlMap(),
            $this->newCheckoutSettings
        );
    }

    private function migrateExportDefaultsSettings(): void
    {
        $this->newPostNlSettings = $this->migrateSettings(
            self::getExportDefaultsPostnlMap(),
            $this->newPostNlSettings,
            $this->oldExportDefaultsSettings
        );

        $this->newExportDefaultsSettings = $this->removeOldSettings(
            self::getExportDefaultsPostnlMap(),
            $this->newExportDefaultsSettings
        );
    }

    /**
     * @return array
     */
    private static function getCheckoutPostnlMap(): array
    {
        $postnl = WCMYPA_Settings::SETTINGS_POSTNL;

        return [
            "evening_enabled"         => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DELIVERY_EVENING_ENABLED,
            "evening_fee"             => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DELIVERY_EVENING_FEE,
            "monday_cutoff_time"      => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_SATURDAY_CUTOFF_TIME,
            "morning_enabled"         => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DELIVERY_MORNING_ENABLED,
            "morning_fee"             => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DELIVERY_MORNING_FEE,
            "myparcel_checkout"       => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
            "only_recipient_enabled"  => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_ONLY_RECIPIENT_ENABLED,
            "only_recipient_fee"      => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_ONLY_RECIPIENT_FEE,
            "saturday_cutoff_enabled" => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_SATURDAY_DELIVERY_ENABLED,
        ];
    }

    /**
     * @return array
     */
    private static function getCheckoutMap(): array
    {
        return [
            "at_home_delivery" => WCMYPA_Settings::SETTING_DELIVERY_TITLE,
        ];
    }

    /**
     * @return array
     */
    private static function getExportDefaultsPostnlMap(): array
    {
        $postnl = WCMYPA_Settings::SETTINGS_POSTNL;

        return [
            "age_check"           => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_SIGNATURE,
            "insured_amount"      => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED_AMOUNT,
            "large_format"        => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_LARGE_FORMAT,
            "only_recipient"      => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_ONLY_RECIPIENT,
            "return"              => "{$postnl}_" . WCMYPA_Settings::SETTING_CARRIER_DEFAULT_EXPORT_RETURN,
        ];
    }
}

return new WCMP_Upgrade_Migration_v4_1_0();
