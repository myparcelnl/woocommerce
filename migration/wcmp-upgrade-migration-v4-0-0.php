<?php

use migration\WCMP_Upgrade_Migration;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;

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
        parent::__construct();
    }

    protected function import(): void
    {
        require_once(WCMP()->plugin_path() . "/includes/vendor/autoload.php");
        require_once(WCMP()->plugin_path() . '/includes/admin/settings/class-wcmp-settings.php');
    }

    protected function migrate(): void
    {
        $this->oldCheckoutSettings       = get_option("woocommerce_myparcel_checkout_settings");
        $this->oldExportDefaultsSettings = get_option("woocommerce_myparcel_export_defaults_settings");
        $this->oldGeneralSettings        = get_option("woocommerce_myparcel_general_settings");

        $this->newCheckoutSettings       = $this->oldCheckoutSettings;
        $this->newExportDefaultsSettings = $this->oldExportDefaultsSettings;
        $this->newGeneralSettings        = $this->oldGeneralSettings;

        $this->migrateCheckoutSettings();
        $this->migrateExportDefaultsSettings();
        $this->migrateGeneralSettings();
    }

    protected function setOptionSettingsMap(): void
    {
        $this->optionSettingsMap = [
            "woocommerce_myparcel_checkout_settings"        => $this->newCheckoutSettings,
            "woocommerce_myparcel_export_defaults_settings" => $this->newExportDefaultsSettings,
            "woocommerce_myparcel_general_settings"         => $this->newGeneralSettings,
            "woocommerce_myparcel_bpost_settings"           => $this->newBpostSettings,
        ];
    }

    private function migrateCheckoutSettings(): void
    {
        // Migrate existing checkout settings to new keys
        $this->newCheckoutSettings = $this->migrateSettings(
            self::getCheckoutMap(),
            $this->newCheckoutSettings
        );

        // Migrate old checkout settings to bpost
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
        // Migrate array value of shipping_methods_package_types
        $this->newExportDefaultsSettings[WCMP_Settings::SETTING_SHIPPING_METHODS_PACKAGE_TYPES] =
            $this->migrateSettings(
                self::getPackageTypesMap(),
                $this->newExportDefaultsSettings[WCMP_Settings::SETTING_SHIPPING_METHODS_PACKAGE_TYPES]
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
    }

    private function migrateGeneralSettings(): void
    {
        // Rename existing settings
        $this->newGeneralSettings = $this->migrateSettings(
            self::getGeneralMap(),
            $this->newGeneralSettings
        );
    }

    /**
     * @return array
     */
    private static function getCheckoutBpostMap(): array
    {
        $bpost = WCMP_Settings::SETTINGS_POSTNL;

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
            "myparcel_checkout" => WCMP_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
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
        $bpost = WCMP_Settings::SETTINGS_POSTNL;

        return [
            "insured"   => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED,
            "signature" => "{$bpost}_" . WCMP_Settings::SETTING_CARRIER_DEFAULT_EXPORT_SIGNATURE,
        ];
    }

    private static function getPackageTypesMap()
    {
        return [
            1 => AbstractConsignment::PACKAGE_TYPE_PACKAGE_NAME,
        ];
    }
}

return new WCMP_Upgrade_Migration_v4_0_0();
